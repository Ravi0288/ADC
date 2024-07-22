<?php
require_once(__DIR__.'/../../AbstractHarvester.php');
require_once(__DIR__.'/../../utils.php');
class fsHarvester extends AbstractHarvester {    
    
    public function harvest() {
        try {
            $dir_handle = opendir("../../file_cache/fs_fgdc");
            $config_json=$this->config_json;
            $env=$this->env;
            $event_log = fopen("logs/".$env."/FS_FGDC_metadata_report.txt", "a");
            $error_log = fopen("logs/".$env."/FS_FGDC_metadata_error.txt", "a");
            $metadata_adc_links_file = file_get_contents("logs/".$env."/FS_FGDC_metadata_links.json");
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
                    $full_file = '../../file_cache/fs_fgdc/'.$file_name;
                    $update_time = filemtime($full_file);
                    $last_harvest_on = strtotime($config_json->last_updated);
                    if (file_exists($full_file)&&($last_harvest_on==""||$update_time>$last_harvest_on)) {
                        // echo "$file_name need update";
                        $jsonData= $this->fs_fgdc_mapping($full_file,$config_json,$file_name);
                        var_dump("<pre>");
                        // var_dump($jsonData);
                        $accession=$jsonData->accession;
                        // list($accession,$version)=$this->getVersion($accession);
                        // echo $version.$accession;
                        if(!isset($metadata_adc_links[$accession])){
                          // echo "create new node";
                            $res=$this->figshare_api_req($jsonData);
                            if(isset($res['entity_id'])){
                                $figshare_nodeid=$res['entity_id'];
                                if(!isset($jsonData->doi)){
                                  $jsonData->handle = $this->prepareHandle($figshare_nodeid);
                                }                                
                                $res=$this->figshare_api_req_update($figshare_nodeid,$jsonData);
                                $resource=$this->prepareResources($accession);
                                $remote_file=array('link'=>$resource);
                                $res2=$this->figshare_api_remote_file($figshare_nodeid,$remote_file);
                                $publish = $this->figshare_api_publish($figshare_nodeid);
                                $new_added++;
                                $metadata_adc_links[$accession]=$figshare_nodeid;
                            }else{
                                $failed++;
                                fwrite($error_log, $accession.' failed on '.date("Y/m/d").' at '.date("h:i:sa").' Error message:'.$res['message'].PHP_EOL);
                            }
                        }else{
                          
                          $figshare_nodeid=$metadata_adc_links[$accession];
                          // update previously harvested record                              
                          $update=$this->figshare_api_req_update($figshare_nodeid,$jsonData);
                          if(isset($update['location'])){
                            $publish = $this->figshare_api_publish($figshare_nodeid);
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
            fwrite($event_log, 'Last run on '.date("Y/m/d").' at '.date("h:i:sa").'; '.$new_added.' added; '.$updated.' updated; '.$failed.' failed; '.$unchanged.' unchanged'.PHP_EOL);
            $record_harvested_figshare_link = fopen('logs/'.$env.'/FS_FGDC_metadata_links.json', 'w');
            fwrite($record_harvested_figshare_link, json_encode($metadata_adc_links));
            fclose($event_log);
            fclose($record_harvested_figshare_link);        
            $config_f = file_get_contents('../fs_fgdc_config_'.$env.'.json');
            $config=json_decode($config_f);
            $config->last_updated=date("Y/m/d");
            $update_config = fopen('../fs_fgdc_config_'.$env.'.json', 'w');
            fwrite($update_config, json_encode($config));
            fclose($update_config);
        } catch (Exception $ex) {
            trigger_error($ex->getMessage());
        }  
    }
    private function fs_fgdc_mapping($xmlfile,$config_json,$id){
        $xml = simplexml_load_file($xmlfile);
        $figshare = new stdClass();
        $xml->registerXpathNamespace('o',
        'http://www.openarchives.org/OAI/2.0/');
        $figshare->title= $this->prepareTitle($xml);
        $figshare->description = $this->prepareDesc($xml);
        $figshare->authors = $this->prepareAuthors($xml);
        $doi=$this->prepareDoi($xml);
        if($doi){
          $figshare->doi=$doi;
        }

        $figshare->timeline=(object)array();

        $figshare->timeline->firstOnline = $this->prepareCreated($xml);
        $figshare->timeline->postedDate = $this->prepareCreated($xml);
        $rev=$this->prepareModified($xml);
        if(!empty($rev)){
          $figshare->timeline->revision = $rev;
        }
        $figshare->accession= $this->stringIfExists($xml, "//o:record/o:header/o:identifier");
        $figshare->tags = $this->prepareTags($xml);
        $figshare->categories=[30202];
        $figshare->custom_fields_list=array();
        $spatial_geographical_cover = $this->prepareSpatialGeographicalCover($xml);
        // echo $spatial_geographical_cover;
        if(!empty($spatial_geographical_cover)){
          $figshare->custom_fields_list[] = [
            'name'=>'Geographic location - description',
            'value'=> $spatial_geographical_cover
          ];
        }
        $figshare->group_id = $config_json->group_id;
        $figshare->funding_list = $this->prepareFundref($xml);
        $isoTopic = $this->prepareISOTopic($xml);
        $figshare->custom_fields_list[] = array(
          'name'=>'ISO Topic Category',
          'value'=> $isoTopic
        );
        $contact=$this->prepareContact($xml);
        // var_dump($contact);
        if(!empty($contact)){
          $figshare->custom_fields_list[] = array(
            'name'=>'Data contact name',
            'value'=> $contact['name']
          );
          $figshare->custom_fields_list[] = array(
            'name'=>'Data contact email',
            'value'=> $contact['email']
          );
        }
        
        $figshare->custom_fields_list[] = array(
          'name'=>'Theme',
          'value'=>[$config_json->Geospatial]
        );
        $figshare->custom_fields_list[]= [
          'name'=>'Public Access Level',
          'value'=> ["Public"]
          ];
        $temporalCoverage = $this->prepareTemporalCoverage($xml);

        if(!empty($temporalCoverage[0])){
          $figshare->custom_fields_list[] = [
            'name'=>'Temporal Extent Start Date',
            'value'=> date("Y-m-d", strtotime($temporalCoverage[0]))
          ];
        }
        if(!empty($temporalCoverage[1])){
          $figshare->custom_fields_list[] = [
            'name'=>'Temporal Extent End Date',
            'value'=> date("Y-m-d", strtotime($temporalCoverage[1]))
          ];
        }
        if(!empty($this->stringIfExists($xml, "//o:idinfo/o:useconst"))){
          $figshare->custom_fields_list[] = [
            'name'=>'Use limitations',
            'value' => $this->stringIfExists($xml, "//o:idinfo/o:useconst"),
          ];
        }
        $figshare->custom_fields_list[]= [
          'name'=>'OMB Bureau Code',
          'value'=>['005:96 - Forest Service']
          ];
        $figshare->custom_fields_list[]= [
          'name'=>'OMB Program Code',
          'value'=>['005:059 - Management Activities']
          ];
        $figshare->defined_type = 'Dataset';
        $figshare->categories=[31922];
        $figshare->license =2;
        $figshare->related_materials=$this->prepareRelatedContent($xml);
        $spatial = $this->prepareSpatialExt($xml);
        if(!empty($spatial)){
          $figshare->custom_fields_list[] = [
            'name'=>'Geographic Coverage',
            'value'=> $spatial
          ];
        }
        $figshare->custom_fields_list[]= [
            'name'=>'National Agricultural Library Thesaurus terms',
            'value'=> "Forestry, Wildland Management"
        ];
        // $adcGroup=$this->prepareADCProject($figshare->title,$figshare->description,$figshare->tags);
        $frequency=$this->prepareFrequency($xml);
        if(!empty($frequency)){
          $figshare->custom_fields_list[] = [
            'name'=>'Frequency',
            'value'=> [$frequency]
          ];
        }
        $publisher = $this->stringIfExists($xml,  "//o:idinfo/o:citation/o:citeinfo/o:pubinfo/o:publish");
        $figshare->custom_fields_list[]= [
          'name'=>'Publisher',
          'value'=> $publisher
        ];

        // var_dump("<pre>");
        // print_r($figshare);
        return $figshare;
        
    }
    protected function prepareTitle($xml) {
      $titlechunks = $xml->xpath("/o:OAI-PMH/o:GetRecord/o:record/o:metadata/o:metadata/o:idinfo/o:citation/o:citeinfo/o:title/text()");
      $title = [];
      foreach ($titlechunks as $chunk) {
        $title[] = (string) $chunk;
      }
      $edition=$xml->xpath("/o:OAI-PMH/o:GetRecord/o:record/o:metadata/o:metadata/o:idinfo/o:citation/o:citeinfo/o:edition/text()");     
      $titletxt=join(" ", $title);
      if(!empty($edition)){
         return $titletxt.': '.$edition[0].' edition';
      }
      return join(" ", $title);
    }
    protected function prepareDesc($xml) {
      $abstract = $xml->xpath("/o:OAI-PMH/o:GetRecord/o:record/o:metadata/o:metadata/o:idinfo/o:descript/o:abstract/text()");
      $desc = [];
      foreach ($abstract as $ab) {
        $desc[] = (string) $ab;
      }
      $desctxt=join(" ", $desc);
      $purpose=$xml->xpath("/o:OAI-PMH/o:GetRecord/o:record/o:metadata/o:metadata/o:idinfo/o:descript/o:purpose/text()");
      if(!empty($purpose)){
        $desctxt= $desctxt.' '.$purpose[0];
      }
      $sup=$xml->xpath("/o:OAI-PMH/o:GetRecord/o:record/o:metadata/o:metadata/o:idinfo/o:descript/o:supplinf/text()");
      if(!empty($sup)){
        return $desctxt.' '.$sup[0];
      }
  
      return $desctxt;
    }
    protected function prepareFrequency($xml) {
      $rawFrequency = $this->stringIfExists($xml,
          '//o:idinfo/o:status/o:update');
      $sources = array(
        'R/P1D' => 'daily',
        'R/P1W' => 'weekly',
        'R/P0.5M' => 'fortnightly',
        'R/P1M' => 'monthly',
        'R/P3M' => 'quarterly',
        'R/P6M' => 'biannually',
        'R/P1Y' => 'annually',
        'irregular' => 'irregular',
        'unknown' => 'unknown',
        'asneeded'=>'irregular',
        "None planned"=>"notPlanned"
      );
      if (in_array($rawFrequency, $sources)) {
        $r = $rawFrequency;
      }elseif (array_key_exists($rawFrequency, $sources)) {
        $r = $sources[$rawFrequency];
      }else{
        return Null;
      }
      return $r;
    }
    protected function prepareHandle($id){
      return '10113/AF'.$id;
    }
    protected function prepareAuthors($xml) {
      $authorNames = $xml->xpath('//o:record/o:metadata/o:metadata/o:idinfo/o:citation/o:citeinfo/o:origin');
  
      $authors = [];
      foreach ($authorNames as $author) {
        $authors[] = array(
          'name' => (string) $author,
        );
      }
  
      return $authors;
    }
    protected function prepareSpatialExt($xml) {
      $coordinates = array(
        'west' => '//o:idinfo/o:spdom/o:bounding/o:westbc/text()',
        'east' => '//o:idinfo/o:spdom/o:bounding/o:eastbc/text()',
        'south' => '//o:idinfo/o:spdom/o:bounding/o:southbc/text()',
        'north' => '//o:idinfo/o:spdom/o:bounding/o:northbc/text()',
      );
  
      return processSpatialData($xml, $coordinates);
    }
    protected function prepareSpatialGeographicalCover($xml){
      return $this->stringIfExists($xml, "//o:idinfo/o:spdom/o:descgeog/text()");
    }
    protected function prepareDoi($xml) {
      $doi = $this->stringIfExists($xml,
          '//o:record/o:metadata/o:metadata/o:idinfo/o:citation/o:citeinfo/o:onlink');
  
      $doiRegex = '#^https?://doi.org/#i';
      if (!empty($doi) && (preg_match($doiRegex, $doi))) {
        $doi = preg_replace($doiRegex, "", $doi);
      }
  
      return empty($doi) ? FALSE : $doi;
    }
    protected function prepareTemporalCoverage($xml) {
      $startDate = $this->stringIfExists($xml, "//o:idinfo/o:timeperd/o:timeinfo/o:rngdates/o:begdate");
      $endDate = $this->stringIfExists($xml, "//o:idinfo/o:timeperd/o:timeinfo/o:rngdates/o:enddate");

      $temporalCoverage = [];

      if (!empty($startDate) && $startDate != 'unknown') {
        if (preg_match('#^\d{4}$#', $startDate)) {
          $temporalCoverage[] = "{$startDate}-01-01 00:00:00";
        }
        elseif (preg_match('#^(\d{4})(\d{2})$#', $startDate, $matches)) {
          $temporalCoverage[] = "{$matches[1]}-{$matches[2]}-01 00:00:00";
        }
        elseif (preg_match('#^(\d{4})(\d{2})(\d{2})$#', $startDate, $matches)) {
          $temporalCoverage[] = "{$matches[1]}-{$matches[2]}-{$matches[3]} 00:00:00";
        }

        if (!empty($endDate) && $endDate != 'unknown') {
          if (preg_match('#^\d{4}$#', $endDate)) {
            $temporalCoverage[] = "{$endDate}-12-31 23:59:59";
          }
          elseif (preg_match('#^(\d{4})(\d{2})$#', $endDate, $matches)) {
            $temporalCoverage[] = date("Y-m-t 23:59:59", strtotime("{$matches[1]}-$matches[2]"));
          }
          elseif (preg_match('#^(\d{4})(\d{2})(\d{2})$#', $endDate,
              $matches)) {
            $temporalCoverage[] = "{$matches[1]}-{$matches[2]}-{$matches[3]} 23:59:59";
          }
        }
        else {
          $temporalCoverage[] = "";
        }
      }

      return $temporalCoverage;
    }
    protected function prepareCreated($xml){	
        $r = '';
        $modified = $xml->xpath("//o:idinfo/o:citation/o:citeinfo/o:pubdate/text()");

        if (is_array($modified) && count($modified)) {
          $r = $modified[0]->__toString();
        }

        if (preg_match("#\d{4}#", $r)) {
          $r = date('Y-m-d', mktime(0, 0, 0, 1, 1, $r));
        }

        return $r;
    }
    protected function prepareModified($xml){	
        $r=''; 	
        $submission_part = $xml->xpath('/DocumentSummary/Submission');
        $submission= array_pop($submission_part);
        if(!empty($submission)){
          $r= (String)$submission->attributes()->last_update;
          $newDate = date("Y-m-d", strtotime($r));
          return $newDate;
        }
        
        return null;
    }
    protected function prepareContact($xml) {
      $contactData = $xml->xpath("//o:idinfo/o:ptcontac/o:cntinfo/o:cntperp|//o:idinfo/o:ptcontac/o:cntinfo/o:cntorgp");
      $contactInfo = array();
      if (!empty($contactData)) {
        $contactRecord = array_pop($contactData);
        $contactRecord->registerXPathNamespace("o",
            "http://www.openarchives.org/OAI/2.0/");
        $individualName=$this->stringIfExists($contactRecord,"./o:cntper/text()");
        if(!empty($individualName)){
          $contactInfo['name'] = $individualName;
        }else{
          $organisationName=$this->stringIfExists($contactRecord,"./o:cntorg/text()");
          if(!empty($organisationName)){
            $contactInfo['name'] = $organisationName;
          }
        }
        $email=$xml->xpath("//o:idinfo/o:ptcontac/o:cntinfo/o:cntemail/text()");
        if(!empty($email)){
          $contactInfo['email']=(string)array_pop($email);
        }else{
          $contactInfo['email']="fsrda@fs.fed.us";
        }
      }else{
         $contactData = $xml->xpath("//o:metainfo/o:metc/o:cntinfo/o:cntperp|//o:metainfo/o:metc/o:cntinfo/o:cntorgp");
          if ($contactData) {
          $contactRecord = array_pop($contactData);
          $contactRecord->registerXPathNamespace("o",
            "http://www.openarchives.org/OAI/2.0/");
          $individualName = $this->stringIfExists($contactRecord, "./o:cntper/text()");
          if(!empty($individualName)){
            $contactInfo['name'] = $individualName;
          }else{
            $organisationName=$this->stringIfExists($contactRecord,"./o:cntorg/text()");
            if(!empty($organisationName)){
              $contactInfo['name'] = $organisationName;
            }
          }
          $email=$xml->xpath("//o:metainfo/o:metc/o:cntinfo/o:cntemail/text()");
          if(!empty($email)){
            $contactInfo['email']=(string)array_pop($email);
          }else{
            $contactInfo['email']="fsrda@fs.fed.us";
          }
  
       }
      }
      return $contactInfo;
    }
    protected function prepareFundref($xml){
        
        $fundref=[];
        $fundref[]=array('title'=>'U.S. Forest Service');            
        return $fundref;
    }
    protected function extractDOI($inputString) {
      // Define a regular expression pattern for matching DOIs
      $pattern = '/10\.\d{4,}\/[-._;()\/:A-Z0-9]+/i';
  
      // Perform a regular expression match
      if (preg_match($pattern, $inputString, $matches)) {
          // $matches[0] contains the matched DOI
          return $matches[0];
      } else {
          // No DOI found in the input string
          return null;
      }
  }
    protected function prepareRelatedContent($xml) {
      $relatedContentData = $xml->xpath("//o:idinfo/o:crossref");
  
      $related = [];
      $linklist = array();
      foreach ($relatedContentData as $relatedContent) {
        $relatedContent->registerXPathNamespace("o",
            "http://www.openarchives.org/OAI/2.0/");
        $title = $this->stringIfExists($relatedContent,
            "./o:citeinfo/o:title/text()");
        $linkData=$relatedContent->xpath('./o:citeinfo/o:onlink');
        foreach($linkData as $lk){
          $link=$this->stringIfExists($lk,'./text()');
          if(str_starts_with($link,"//")){
            $link="https:".$link;
          }
          if (!empty($title) && !empty($link) && array_search($link, $linklist) === FALSE) {
                $title = array($title);
                $edition = $this->stringIfExists($relatedContent,"./o:citeinfo/o:edition/text()");
                if (!empty($edition)) {
                  $title[] = "(" . $edition . " ed.)";
                }
  
                $pubdate = $this->stringIfExists($relatedContent, "./o:citeinfo/o:pubdate/text()");
                if (!empty($pubdate)) {
                  $title[] = "(" . $pubdate . ")";
                }
  
                $title = join(" ", $title);
          }
          $doi=$this->extractDOI($link);
          if($doi){
            $related[] = array(
              'identifier' => $doi,
              'title' => $title,
              'identifier_type'=>"DOI",
              'relation'=>"IsDocumentedBy"
            );
          }else{
            $related[] = array(
              'identifier' => $link,
              'title' => $title,
              'identifier_type'=>"URL",
              'relation'=>"IsDocumentedBy"
            );
          }
          
          $linklist[] = $link;
        //  }
       }
      }
  
      return $related;
    }
    protected function prepareTags($xml) {
      $keywordList = $xml->xpath('//o:idinfo/o:keywords/o:theme[not(o:themekt="ISO 19115 Topic Category")]/o:themekey');
  
      $keywords = array();
      foreach ($keywordList as $keyword) {
        $keywords[] = (string) $keyword;
      }
  
      return $keywords;
    }
    protected function prepareISOTopic($xml){
      $keywordList = $xml->xpath('//o:idinfo/o:keywords/o:theme[o:themekt="ISO 19115 Topic Category"]/o:themekey');
  
      $keywords = array();
      foreach ($keywordList as $keyword) {
        $keywords[] = (string) $keyword;
      }
      if(!empty($keywords)){
        return $keywords;
      }else{
        return ["environment"];
      }
      
    }
    
    protected function prepareNALTerm($xml)
  {
    $mapping_table='{"assembly": "genome; genomics; genome assembly; sequence analysis","clone ends": "genomics; sequence analysis; genome","epigenomics": "epigenetics; genome; genomics","exome": "sequence analysis; genomics; genome","genome sequencing": "genomics; sequence analysis; genome","genome sequencing and assembly": "genomics; sequence analysis; genome assembly","map": "genetics; chromosome mapping","metagenome": "metagenomics; sequence analysis","metagenome assembly": "genome assembly; metagenomics","other": "genetics","phenotype or genotype": "genotype-phenotype correlation","proteome": "proteome; proteomics","random survey": "sequence analysis","raw sequence reads": "sequence analysis","targeted loci": "sequence analysis","targeted loci environmental": "sequence analysis","targeted locus": "sequence analysis","transcriptome": "transcriptome; gene expression","gene expression": "transcriptome; gene expression","transcriptome or gene expression": "transcriptome; gene expression","variation": "genetic variation","targeted locus (loci)":"sequence analysis","refseq genome":"genomics; sequence analysis; genome assembly","refseq genome sequencing and assembly":"genomics; sequence analysis; genome assembly","metagenomic assembly":"genome assembly; metagenomics"}';
    $mapping_table=json_decode($mapping_table,TRUE);
    $intendedType= $xml->xpath('/DocumentSummary/Project/ProjectType/ProjectTypeSubmission/IntendedDataTypeSet/DataType');
    $datatype="";
    if(empty($intendedType)){
     $ProjectDataTypeSet= $xml->xpath('/DocumentSummary/Project/ProjectType/ProjectTypeSubmission/ProjectDataTypeSet/DataType');
     if(!empty($ProjectDataTypeSet)){
      $datatype=(string)$ProjectDataTypeSet[0];
     }
    }else{
      $datatype=(string)$intendedType[0];
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
  protected function prepareResources($identifier) {

    if (!empty($identifier)) {
      $idParts = explode(":", $identifier);
      if (isset($idParts[2]) && !empty($idParts[2])) {
        $url = "https://www.fs.usda.gov/rds/archive/catalog/" . $idParts[2];
        return $url;
      }
    }
    return "https://www.fs.usda.gov/rds/archive/catalog";
   }
   protected function getVersion($identifier) {

    if (!empty($identifier)) {
      $idParts = explode(":", $identifier);
      if (isset($idParts[2]) && !empty($idParts[2])) {
       $versionParts=explode("-", $idParts[2]);
       if(count($versionParts)==3){
        return [$identifier,0];
       }else{
        $vid=$versionParts[count($versionParts)-1];
        array_pop($versionParts);
        return [$idParts[0].":".$idParts[1].":".implode("-",$versionParts),$vid];
       }
      }
    }
    return [$identifier,0];
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
