<?php
require_once(__DIR__.'/../../AbstractHarvester.php');

class NCBIHarvester extends AbstractHarvester {    
    
    public function harvest() {
        try {
            // date_default_timezone_set("America/New_York");
            $dir_handle = opendir("../../file_cache/ncbi");
            $config_json=$this->config_json;
            $env=$this->env;
            $event_log = fopen("logs/".$env."/NCBI_metadata_report.txt", "a");
            $error_log = fopen("logs/".$env."/NCBI_metadata_error.txt", "a");
            $metadata_adc_links_file = file_get_contents("logs/".$env."/NCBI_metadata_links.json");
            $metadata_adc_links=json_decode($metadata_adc_links_file, true);
            // reading the contents of the directory
            $updated=0;
            $new_added=0;
            $failed=0;
            $unchanged=0;
            while(($file_name = readdir($dir_handle)) !== false) 
            {
                if ($file_name != "." && $file_name != "..") {
                    $accession=$file_name;
                    $full_file = '../../file_cache/ncbi/'.$file_name;
                    $update_time = filemtime($full_file);
                    $last_harvest_on = strtotime($config_json->last_updated);
                    var_dump("<pre>");
                    // filtering out datasets if they don't have public data
                    $content=file_get_contents('https://www.ncbi.nlm.nih.gov/bioproject/PRJNA'.$accession);
                    if(str_contains($content,"No public data is linked to this project")){
                      var_dump($accession);
                      unlink($full_file);
                      continue;
                    }

                    // check file update time VS. last updated time, if file has not changed since last harvest, we don't need to update the record
                    if (file_exists($full_file)&&($last_harvest_on==""||$update_time>$last_harvest_on)) {
                      // mapping from ncbi xml to figshare format
                        $jsonData= $this->ncbi_mapping($full_file,$config_json,$file_name);
                        if(!isset($metadata_adc_links[$accession])){
                          // call figshare api to create a draft
                            $res=$this->figshare_api_req($jsonData);
                            
                            var_dump($res);
                            // entity_id will be returned from figshare if draft creates successfully
                            if(isset($res['entity_id'])){
                                $figshare_nodeid=$res['entity_id'];
                                // handle minting will be using micro services, will update this function once the API is ready
                                $jsonData->handle = $this->prepareHandle($figshare_nodeid);
                                // after minting the handle, will be needed to update the draft to be albe to publish the record
                                $res=$this->figshare_api_req_update($figshare_nodeid,$jsonData);
                                
                                // create a remote file link to the record in figshare
                                $remote_file=array('link'=>'https://www.ncbi.nlm.nih.gov/bioproject/PRJNA'.$accession);
                                $res2=$this->figshare_api_remote_file($figshare_nodeid,$remote_file);
                                // $publish = $this->figshare_api_publish($figshare_nodeid);
                                var_dump($res);
                                // document the source id and figshare node to be able to update records in future
                                $new_added++;
                                $metadata_adc_links[$accession]=$figshare_nodeid;
                                $record_harvested_figshare_link = fopen('logs/'.$env.'/NCBI_metadata_links.json', 'w');
                                fwrite($record_harvested_figshare_link, json_encode($metadata_adc_links));
                            }else{
                                $failed++;
                                fwrite($error_log, $accession.' failed on '.date("Y/m/d").' at '.date("h:i:sa").' Error message:'.$res['message'].PHP_EOL);
                            }
                        }else{
                            // update previously harvested record
                            $figshare_nodeid=$metadata_adc_links[$accession];
                            $update=$this->figshare_api_req_update($figshare_nodeid,$jsonData);
                            var_dump($update);
                            if(isset($update['location'])){
                              // publishing record using API on production is disabled.
                              // $publish = $this->figshare_api_publish($figshare_nodeid);
                              $updated++;
                            }else{
                                $failed++;
                                fwrite($error_log, $accession.' failed on '.date("Y/m/d").' at '.date("h:i:sa").' to update. Error message:'.json_encode($update).PHP_EOL);
                            }
                        }
                    }
                }
                
            }
            // closing the directory
            closedir($dir_handle);  
            // log some events
            fwrite($event_log, 'Last run on '.date("Y/m/d").' at '.date("h:i:sa").'; '.$new_added.' added; '.$updated.' updated; '.$failed.' failed; '.$unchanged.' unchanged'.PHP_EOL);
            $record_harvested_figshare_link = fopen('logs/'.$env.'/NCBI_metadata_links.json', 'w');
            fwrite($record_harvested_figshare_link, json_encode($metadata_adc_links));
            // last_updated time is stored in the ncbi_config.json file
            $config_f = file_get_contents('ncbi_config.json');
            $config=json_decode($config_f);
            $config->last_updated=date("Y/m/d");
            $update_config = fopen('ncbi_config.json', 'w');
            fwrite($update_config, json_encode($config));
            fclose($update_config);
            fclose($event_log);
            fclose($record_harvested_figshare_link);        
        } catch (Exception $ex) {
            trigger_error($ex->getMessage());
        }  
    }
    private function ncbi_mapping($xmlfile,$config_json,$id){
        $xml = simplexml_load_file($xmlfile);
        $figshare = new stdClass();        
        $figshare->title= $this->stringIfExists($xml,'/DocumentSummary/Project/ProjectDescr/Title/text()');
        $figshare->description = strip_tags($this->stringIfExists($xml,'/DocumentSummary/Project/ProjectDescr/Description/text()'));
        if(empty($figshare->description)){
          $figshare->description=$figshare->title;
        }
        $figshare->authors = $this->prepareAuthor($xml);
        $figshare->timeline=(object)array();
        $figshare->timeline->firstOnline = $this->prepareCreated($xml);
        $figshare->timeline->postedDate = $this->prepareCreated($xml);
        $rev=$this->prepareModified($xml);
        if(!empty($rev)){
          $figshare->timeline->revision = $rev;
        }
        $figshare->tags = $this->prepareTags($xml);
        $figshare->categories=$config_json->categories;
        $figshare->custom_fields_list=array();
        $figshare->group_id = $config_json->group_id;
        $figshare->funding_list = $this->prepareFundref($xml);
        $figshare->custom_fields_list[] = array(
                'name'=>'ISO Topic Category',
                'value'=> ['biota']
        );
        $figshare->custom_fields_list[] = array(
                'name'=>'Data contact name',
                'value'=> "BioProject Curation Staff"   
        );
        $figshare->custom_fields_list[] = array(
                'name'=>'Data contact email',
                'value'=> "bioprojecthelp@ncbi.nlm.nih.gov"
        );
        $figshare->custom_fields_list[] = array(
                'name'=>'Theme',
                'value'=>[$config_json->Geospatial]
        );
        
        $temporalCoverage = $this->prepareTemporalCoverage($xml);
        if(!empty($temporalCoverage[0])){
          $figshare->custom_fields_list[] = [
            'name'=>'Temporal Extent Start Date',
            'value'=> $temporalCoverage[0]
          ];
        }
        if(!empty($temporalCoverage[1])){
          $figshare->custom_fields_list[] = [
            'name'=>'Temporal Extent End Date',
            'value'=> $temporalCoverage[1]
          ];
        }
        $figshare->defined_type = 'Dataset';
        $figshare->license =$config_json->license;
        $ids = $xml->xpath('/DocumentSummary/Project/ProjectID/ArchiveID');
        $accession= array_pop($ids);
        $figshare->custom_fields_list[] = array(
            'name'=>'Accession Number',
            'value'=> (String)$accession->attributes()->accession
        $figshare->related_materials=$this->prepareRelatedMaterial($xml);
        
        $nalTerm = $this->prepareNALTerm($xml);
        if(!empty($nalTerm)){
            $figshare->custom_fields_list[]= [
                'name'=>'National Agricultural Library Thesaurus terms',
                'value'=> implode("; ",$nalTerm)
            ];
        }
        $adcGroup=$this->prepareADCProject($figshare->title,$figshare->description,$figshare->tags);
        if(!empty($adcGroup)){
            $figshare->custom_fields_list[]= [
                'name'=>'Ag Data Commons Group',
                'value'=> $adcGroup
            ];
        }

        $figshare->custom_fields_list[]= [
          'name'=>'Publisher',
          'value'=> "National Center for Biotechnology Information"
        ];
        return $figshare;
        
    }
    protected function prepareRelatedMaterial($xml){
      $related_materials=[];
      $publications=$xml->xpath('/DocumentSummary/Project/ProjectDescr/Publication');
      foreach($publications as $publication){
        $DbTypes = $publication->xpath('DbType');
        var_dump($DbTypes);
        
        if(!empty($DbTypes)){
          $DbType = (String)$DbTypes[0];
          $id= (String)$publication->attributes()->id;
          if ($DbType=="ePubmed"){
            $titlearray=$publication->xpath('/StructuredCitation/Title');
            if(!empty($titlearray)){
              $title=(String)$titlearray[0];
            }
            if(!empty($title)){
              $related_materials[]=array(
                "identifier"=>"https://pubmed.ncbi.nlm.nih.gov/".$id,
                "title"=>$title,
                "relation"=>"IsDescribedBy",
                "identifier_type"=>"URL",
              );
            }else{
              $related_materials[]=array(
                "identifier"=>"https://pubmed.ncbi.nlm.nih.gov/".$id,
                "title"=>"https://pubmed.ncbi.nlm.nih.gov/".$id,
                "relation"=>"IsDescribedBy",
                "identifier_type"=>"URL",
              );
            }
          }elseif($DbType=="eDOI"){
            $doi=str_replace("https://doi.org/","",$id);
            $doi=str_replace("http://dx.doi.org/","",$id);
            $doi=str_replace("doi:","",$id);
            $doi=str_replace("https://doi.org/","",$id);
            $titlearray=$publication->xpath('/StructuredCitation/Title');
            if(!empty($titlearray)){
              $title=(String)$titlearray[0];
            }
            if(empty($title)){
              $title=$publication->xpath('/Reference');
            }
            if(empty($title)){
              $title=$doi;
            }
            $related_materials[]=array(
              "identifier"=>$doi,
              "title"=>$title,
              "relation"=>"IsDescribedBy",
              "identifier_type"=>"DOI",
            );
          }
        }
      }
      return $related_materials;
      
    }
    protected function prepareHandle($id){
      return '10113/AF'.$id;
    }
    protected function prepareAuthor($xml){
        $authorNames = $xml->xpath('/DocumentSummary/Submission/Description/Organization/Name');

        $authors = [];
        foreach ($authorNames as $author) {
            $authors[] = array(
                'name' => (string) $author,
            );
        }

        return $authors;
    }
    protected function prepareTemporalCoverage($xml) {
      $startDate = $this->stringIfExists($xml, "/DocumentSummary/Project/ProjectDescr/ProjectReleaseDate");	
      $endDate = $this->stringIfExists($xml, "/DocumentSummary/Project/ProjectDescr/ProjectEndDate");
      if(empty($startDate)){
        $startDate=$this->stringIfExists($xml, "/DocumentSummary/Submission/@submitted");	
      }
      $temporalCoverage = [];
  
      if (!empty($startDate) && $startDate != 'unknown') {
        if(substr($startDate, -1) === 'Z'){
          $datetime = new DateTime($startDate);
          $nyTimeZone = new DateTimeZone('America/New_York');
          $datetime->setTimezone($nyTimeZone);
          $startDate = $datetime->format('Y-m-d');
        }
        if (preg_match('#^\d{4}$#', $startDate)) {
          $temporalCoverage[] = "{$startDate}-01-01";
        }
        elseif (preg_match('#^(\d{4})(\d{2})$#', $startDate, $matches)) {
          $temporalCoverage[] = "{$matches[1]}-{$matches[2]}-01";
        }
        elseif (preg_match('#^(\d{4})(\d{2})(\d{2})$#', $startDate, $matches)) {
          $temporalCoverage[] = "{$matches[1]}-{$matches[2]}-{$matches[3]}";
        }else{
          $temporalCoverage[] = $startDate;	
        }
  
        if (!empty($endDate) && $endDate != 'unknown') {
          if(substr($endDate, -1) === 'Z'){
            $datetime = new DateTime($endDate);
            $nyTimeZone = new DateTimeZone('America/New_York');  
            $datetime->setTimezone($nyTimeZone);  
            $endDate = $datetime->format('Y-m-d\TH:i:sP');
          }
          if (preg_match('#^\d{4}$#', $endDate)) {
            $temporalCoverage[] = "{$endDate}-12-31";
          }
          elseif (preg_match('#^(\d{4})(\d{2})$#', $endDate, $matches)) {
            $temporalCoverage[] = date("Y-m-d", strtotime("{$matches[1]}-$matches[2]"));
          }
          elseif (preg_match('#^(\d{4})(\d{2})(\d{2})$#', $endDate,
              $matches)) {
            $temporalCoverage[] = "{$matches[1]}-{$matches[2]}-{$matches[3]}";
          }
        }
        else {
          $temporalCoverage[] = "";
        }
      }
      
      return $temporalCoverage;
    }
    protected function prepareCreated($xml){	
        $r=''; 	
        $r= $this->stringIfExists($xml, "/DocumentSummary/Project/ProjectDescr/ProjectReleaseDate");	
        if(empty($r)){
            $submission_parts=$xml->xpath('/DocumentSummary/Submission');
            $submission= array_pop( $submission_parts);
            $r= (String)$submission->attributes()->submitted;
        }
        if(!substr($r, -1) === 'Z'){
          $usEstTime = new DateTime($r, new DateTimeZone('America/New_York'));
          $usEstTime->setTimezone(new DateTimeZone('UTC')); // Change the timezone to UTC (Greenwich Mean Time)
          $r = $usEstTime->format('Y-m-d');
          return $r;
        }
        
        return date("Y-m-d", strtotime($r));
    }
    protected function prepareModified($xml){	
        $r=''; 	
        $submission_part = $xml->xpath('/DocumentSummary/Submission');
        $submission= array_pop($submission_part);
        $r= (String)$submission->attributes()->last_update;
        if(!substr($r, -1) === 'Z'){
          $usEstTime = new DateTime($r, new DateTimeZone('America/New_York'));
          $usEstTime->setTimezone(new DateTimeZone('UTC')); // Change the timezone to UTC (Greenwich Mean Time)
          $r = $usEstTime->format('Y-m-d');
          return $r;
        }
        return  date("Y-m-d", strtotime($r));
    }
    protected function prepareFundref($xml){
        $fund = $xml->xpath('/DocumentSummary/Project/ProjectDescr/Grant');
        $fundref=[];
        $fundref_projects=[];
        $r='{"USDA/NRI CSREES": "Cooperative State Research, Education, and Extension Service", "USDA-FS": "U.S. Forest Service", "USDA/NIFA AFRI": "National Institute of Food and Agriculture", "USDA": "U.S. Department of Agriculture", "USDA/TSTAR": "Cooperative State Research, Education, and Extension Service", "DOE": "U.S. Department of Energy", "NSF": "National Science Foundation", "NIFA": "National Institute of Food and Agriculture", "USDA/NIFA": "National Institute of Food and Agriculture", "ARS-USDA": "Agricultural Research Service", "USDA-NIFA": "National Institute of Food and Agriculture", "USDA-NIFA-AFRI": "National Institute of Food and Agriculture", "USDA/ARS": "Agricultural Research Service", "NIH": "National Institutes of Health",
          "USDA CSREES": "Cooperative State Research, Education, and Extension Service", 
          "USDA-ARS": "Agricultural Research Service", 
          "HHMI & GBMF":["Howard Hughes Medical Institute","Gordon and Betty Moore Foundation"],
          "NIGMS": "National Institute of General Medical Sciences", 
          "USDA NIFA": "National Institute of Food and Agriculture", 
          "NIFA SCRI": "National Institute of Food and Agriculture", "SCRI": "National Institute of Food and Agriculture",
          "NIGMS INBRE": "National Institute of General Medical Sciences", "USDA NIFA ARFI": "National Institute of Food and Agriculture", "U.S. DOE": "U.S. Department of Energy", "NIFA/USDA": "National Institute of Food and Agriculture", "ARS": "Agricultural Research Service", 
          "USDA and DOE": ["U.S. Department of Energy","U.S. Department of Agriculture"], "DOE plant Feedstock": "U.S. Department of Energy", "NHGRI": "National Human Genome Research Institute", "USDA-NIFA-SCRI": "National Institute of Food and Agriculture", "Hatch - NIFA": "National Institute of Food and Agriculture", "USFS": "U.S. Forest Service", "USDA - NIFA": "National Institute of Food and Agriculture", "NIFA USDA": "National Institute of Food and Agriculture", "USDA ARS": "Agricultural Research Service", "USDA-CRIS": "National Institute of Food and Agriculture", "US DOE": "U.S. Department of Energy", 
          "USDA-NIFA/DOE BRDI": ["National Institute of Food and Agriculture","Biomass Research and Development Initiative"], 
          "1) CPPO; 2) USDA-ARS SAA (GA).": "Agricultural Research Service", "USDA/APHIS": "Animal and Plant Health Inspection Service", 
          "NCDA/SCBGP": ["North Carolina Department of Agriculture and Consumer Services","Agricultural Marketing Service"], "USDA-ARS-ERRC": "Agricultural Research Service", "USDA-FSA": "Food Standards Agency", 
          "USDA-NRC": ["U.S. Nuclear Regulatory Commission","U.S. Department of Agriculture"], "USGS": "U.S. Geological Survey", "BARD": "United States - Israel Binational Agricultural Research and Development Fund", "USDA-OREI": "National Institute of Food and Agriculture", "USDA AFRI": "National Institute of Food and Agriculture", "USDA-CSREES": "Cooperative State Research, Education, and Extension Service", "ISF": "Israel Science Foundation", "USDA FAS": "Foreign Agricultural Service", "USDA-SCRI": "National Institute of Food and Agriculture", 
          "USAID; KSU": "United States Agency for International Development", "USDAe and Food Resource Initiative Foundational Program": "National Institute of Food and Agriculture", "NIFA - USDA": "National Institute of Food and Agriculture", "USDA, NRCS": "Natural Resources Conservation Service", "USDA-APHIS": "Animal and Plant Health Inspection Service", "USEPA": "U.S. Environmental Protection Agency", "UMD": "University of Maryland", "UMN": "University of Minnesota", "NASA": "National Aeronautics and Space Administration", "EPA": "U.S. Environmental Protection Agency", "NIFA RI.W": "National Institute of Food and Agriculture", "NIFA-USDA": "National Institute of Food and Agriculture", "CRB": "Citrus Research Board", "USDA-FCF": "National Institute of Food and Agriculture", "BMGF": "Bill and Melinda Gates Foundation", "NOAA": "National Oceanic and Atmospheric Administration", "USDA/NIFA/CBG": "National Institute of Food and Agriculture", "USDA, NIFA": "National Institute of Food and Agriculture", "USDA - APHIS": "Animal and Plant Health Inspection Service", "NSGCP": "National Oceanic and Atmospheric Administration", "NIGMS NIH HHS": "National Institute of General Medical Sciences", "NIH-NIDDK": "National Institute of Diabetes and Digestive and Kidney Diseases", "NIDDK NIH HHS": "National Institute of Diabetes and Digestive and Kidney Diseases", "USDA-HATCH": "National Institute of Food and Agriculture", "BBSRC": "Biotechnology and Biological Sciences Research Council", "FDACS": "Florida Department of Agriculture and Consumer Services", "NSERC": "Natural Sciences and Engineering Research Council of Canada", "B&MGF": "Bill and Melinda Gates Foundation", "USDA APHIS": "Animal and Plant Health Inspection Service", "USDA NIFA AFRI BRAG": "National Institute of Food and Agriculture", "NIFA, USDA": "National Institute of Food and Agriculture", "USDA-AFRI": "National Institute of Food and Agriculture", "NIH HHS": "National Institutes of Health", "UCD": "University of California, Davis", "ARS, USDA": "Agricultural Research Service", "FFAR": "Foundation for Food and Agriculture Research", "USDA\u2013NIFA\u2013 SCRI": "National Institute of Food and Agriculture", 
          "1) USDA; 2) HMR": "U.S. Department of Agriculture", "USDA-NIFA; DOE":["National Institute of Food and Agriculture","U.S. Department of Energy"],
          "NSF-NIFA": "National Institute of Food and Agriculture", "USDS-NIFA": "National Institute of Food and Agriculture", "NIFA-IWYP, USDA": "National Institute of Food and Agriculture", "Bill & Melinda Gates Foundation": "Bill and Melinda Gates Foundation", "USDA-NIFA Hatch": "National Institute of Food and Agriculture", "USDA NESARE": "National Institute of Food and Agriculture", "NOAA Sea Grant": "National Oceanic and Atmospheric Administration", "USDA NIFA AFRI": "National Institute of Food and Agriculture", "USDA/Hatch": "National Institute of Food and Agriculture", "NIDDK": "National Institute of Diabetes and Digestive and Kidney Diseases", "UC ANR": "Division of Agriculture and Natural Resources, University of California", "USDA ARS CRIS": "National Institute of Food and Agriculture", "UTK": "University of Tennessee, Knoxville", "USDA-AMS": "Agricultural Marketing Service", "Thrasher": "Thrasher Research Fund", "WHO": "World Health Organization", "USDA WSARE": "National Institute of Food and Agriculture", "USDA-NIFA AFRI": "National Institute of Food and Agriculture", "NESARE": "National Institute of Food and Agriculture", "EU": "European Commission", "WSARE": "National Institute of Food and Agriculture", "COBRE-NIH": "National Institutes of Health", "CDC": "Centers for Disease Control and Prevention", "USDA AFRI NIFA": "National Institute of Food and Agriculture", "NASA, NIFA-USDA": "National Institute of Food and Agriculture", "NE-SARE": "National Institute of Food and Agriculture", "NSF-GRFP": "National Science Foundation", "USFWS": "U.S. Fish and Wildlife Service", "SCBGP": "Agricultural Marketing Service", "NOAA, DOC": "National Oceanic and Atmospheric Administration", "IANR UNL": "University of Nebraska-Lincoln"}';
        $funder_mapping=json_decode($r,TRUE);
        foreach ($fund as $fd) {
            $agencies=$fd->xpath('Agency');
            $fund_agency_array=array_pop($agencies);
            $agency_abbr=(string) $fund_agency_array->attributes()->abbr;
            if (array_key_exists($agency_abbr, $funder_mapping)) {
                if(gettype($funder_mapping[$agency_abbr])=='array'){
                $fundref_projects=[];
                foreach($funder_mapping[$agency_abbr] as $fnd){
                    $fund_agency= $fnd;
                    $fundref_projects[]=$fund_agency;
                }
                }else{
                // echo $funder_mapping[$agency_abbr];
                $fund_agency= $funder_mapping[$agency_abbr];
                $fundref_projects=array($fund_agency);
                }
            }else{
                $fund_agency=$this->stringIfExists($fd,'Agency');
                $fundref_projects=array($fund_agency);
            }
            
            if(!empty($fundref_projects)){
                foreach($fundref_projects as $fun_par){
                    $grant=(string) $fd->attributes()->GrantId;
                    if(!empty($grant)){
                        $fundref[]['title']  =$fun_par.', '.$grant;
                    }else{
                        $fundref[]['title']  =$fun_par;
                    }
                }
            }
        }
        return $fundref;
    }
    protected function prepareADCProject($title,$description,$tags){
        $projectName='';
        $target="NRRL";
        if(strpos($title,$target) !== false ||strpos($description,$target) !== false){
          $projectName=['ARS Culture Collection'];
          return $projectName;
        }else{
          foreach($tags as $tag){
            if(strpos($title,$target) !== false){
              $projectName=['ARS Culture Collection'];
              return $projectName;
              
            }
          }
          return [];
        }
      }
    protected function prepareTags($xml) {
        $keywordList1 = $xml->xpath('/DocumentSummary/Project/ProjectType/ProjectTypeSubmission/Target/Organism/OrganismName');
        $keywordList2 = $xml->xpath('/DocumentSummary/Project/ProjectType/ProjectTypeSubmission/Target/Organism/Strain');
        $keywordList3 = $xml->xpath('/DocumentSummary/Project/ProjectType/ProjectTypeSubmission/Target/Organism/Supergroup');
        $keywordList4 = $xml->xpath('/DocumentSummary/Project/ProjectType/ProjectTypeSubmission/IntendedDataTypeSet/DataType');
        
    
        $keywords = array();
        foreach ($keywordList1 as $keyword) {
          $keywords[] = (string) $keyword;
        }
        foreach ($keywordList2 as $keyword) {
          $keywords[] = (string) $keyword;
        }
        foreach ($keywordList3 as $keyword) {
          $keywords[] = (string) $keyword;
        }
        foreach ($keywordList4 as $keyword) {
          $keywords[] = (string) $keyword;
        }
        if(!empty($keywords)){
        return $keywords;
        }else{
          return ['Biological Sciences','biotechnology','genetics'];
        }
    }
    protected function prepareNALTerm($xml)
  {
    $mapping_table='{"assembly": "genome; genomics; genome assembly; sequence analysis","clone ends": "genomics; sequence analysis; genome","epigenomics": "epigenetics; genome; genomics","exome": "sequence analysis; genomics; genome","genome sequencing": "genomics; sequence analysis; genome","genome sequencing and assembly": "genomics; sequence analysis; genome assembly","map": "genetics; chromosome mapping","metagenome": "metagenomics; sequence analysis","metagenome assembly": "genome assembly; metagenomics","other": "genetics","phenotype or genotype": "genotype-phenotype correlation","proteome": "proteome; proteomics","random survey": "sequence analysis","raw sequence reads": "sequence analysis","targeted loci": "sequence analysis","targeted loci environmental": "sequence analysis","targeted locus": "sequence analysis","transcriptome": "transcriptome; gene expression","gene expression": "transcriptome; gene expression","transcriptome or gene expression": "transcriptome; gene expression","variation": "genetic variation","targeted locus (loci)":"sequence analysis","refseq genome":"genomics; sequence analysis; genome assembly","refseq genome sequencing and assembly":"genomics; sequence analysis; genome assembly","metagenomic assembly":"genome assembly; metagenomics"}';
    $mapping_table=json_decode($mapping_table,TRUE);
    $intendedType= $xml->xpath('/DocumentSummary/Project/ProjectType/ProjectTypeSubmission/IntendedDataTypeSet/DataType');
    if(empty($intendedType)){
     $ProjectDataTypeSet= $xml->xpath('/DocumentSummary/Project/ProjectType/ProjectTypeSubmission/ProjectDataTypeSet/DataType');
     if(!empty($ProjectDataTypeSet)){
      $datatype=(string)$ProjectDataTypeSet[0];
     }
    }else{
      $datatype=(string)$intendedType[0];
    }
    if(!isset($datatype)){
      $nalterm=['Biological Sciences','biotechnology','genetics'];
      return $nalterm;
    }
    $datatype=strtolower($datatype);
    if(isset($mapping_table[$datatype])){
      $term= $mapping_table[$datatype];
      $nalterm=[];
      $term_array=explode(";", $term);
      foreach($term_array as $val){
        $nalterm[]=trim($val);
      }
    }else{
      $nalterm=['Biological Sciences','biotechnology','genetics'];
    }
    
   
    return $nalterm;
  }
    protected function stringIfExists(SimpleXMLElement $element, $xpath, $as_html=FALSE) {
        $result = $element->xpath($xpath);
        $return_string = '';
        if (!empty($result)) {
          foreach ($result as $item) {
            if ($as_html) {
              $return_string .= '<p>' . (string)$item . '</p>';
            } else {
              $return_string .= (string)$item . ' ';
            }
          }
        }
        return trim($return_string);
      }
}
