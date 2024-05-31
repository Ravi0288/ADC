<?php
require_once(__DIR__.'/../AbstractHarvester.php');
require_once (__DIR__.'/../utils.php');

class DataGovHarvester extends AbstractHarvester {    
    
    public function harvest() {
        try {
            // date_default_timezone_set("America/New_York");
            
            $config_json=$this->config_json;
            $env=$this->env;
            $dir_handle = opendir($config_json->cachedir);
            var_dump($config_json);
            $event_log = fopen($config_json->eventlog, "a");
            $error_log = fopen($config_json->errorlog, "a");
            $metadata_adc_links_file = file_get_contents($config_json->metadataLinksFile);
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
                    $full_file =  $config_json->cachedir.'/'.$file_name;
                    echo $full_file;
                    $update_time = filemtime($full_file);
                    $last_harvest_on = strtotime($config_json->last_updated);
                    // check file update time VS. last updated time, if file has not changed since last harvest, we don't need to update the record
                    if (file_exists($full_file)&&($last_harvest_on==""||$update_time>$last_harvest_on)) {
                      // mapping from ncbi xml to figshare format
                        $jsonData= $this->datagov_mapping($full_file,$config_json,$file_name,$env);
                        echo json_encode($jsonData);
                        var_dump("<pre>");
                        // var_dump($metadata_adc_links);
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
                                var_dump($res);
                                // create a remote file link to the record in figshare
                                if($jsonData->remoteFileLink){
                                  $remote_file=$jsonData->remoteFileLink;
                                  $res2=$this->figshare_api_remote_file($figshare_nodeid,$remote_file);
                                }
                                var_dump($res2);
                                $publish = $this->figshare_api_publish($figshare_nodeid);
                                // document the source id and figshare node to be able to update records in future
                                // $json_harvested[$accession]=$lastid;
                                $new_added++;
                                $metadata_adc_links[$accession]=$figshare_nodeid;
                                $record_harvested_figshare_link = fopen($config_json->metadataLinksFile, 'w');
                                fwrite($record_harvested_figshare_link, json_encode($metadata_adc_links));
                            }else{
                                $failed++;
                                fwrite($error_log, $accession.' failed on '.date("Y/m/d").' at '.date("h:i:sa").' Error message:'.$res['message'].PHP_EOL);
                            }
                        }else{
                          continue;
                            // update previously harvested record
                            $figshare_nodeid=$metadata_adc_links[$accession];
                            $update=$this->figshare_api_req_update($figshare_nodeid,$jsonData);
                            if($jsonData->remoteFileLink){
                              $remote_file=$jsonData->remoteFileLink;
                              $res2=$this->figshare_api_remote_file($figshare_nodeid,$remote_file);
                            }
                            
                            if(isset($update['location'])){
                              $publish = $this->figshare_api_publish($figshare_nodeid);
                              $updated++;
                            }else{
                                $failed++;
                                fwrite($error_log, $accession.' failed on '.date("Y/m/d").' at '.date("h:i:sa").' to update. Error message:'.json_encode($update).PHP_EOL);
                            }
                        }
                        break;
                    }

                }
                
            }
            
            // closing the directory
            closedir($dir_handle);  
            // log some events
            fwrite($event_log, 'Last run on '.date("Y/m/d").' at '.date("h:i:sa").'; '.$new_added.' added; '.$updated.' updated; '.$failed.' failed; '.$unchanged.' unchanged'.PHP_EOL);
            $record_harvested_figshare_link = fopen($config_json->metadataLinksFile, 'w');
            fwrite($record_harvested_figshare_link, json_encode($metadata_adc_links));
            // last_updated time is stored in the ncbi_config.json file
            $config_f = file_get_contents($config_json->config_file);
            $config=json_decode($config_f);
            $config->last_updated=date("Y/m/d");
            $update_config = fopen($config_json->config_file, 'w');
            fwrite($update_config, json_encode($config));
            fclose($update_config);
            fclose($event_log);
            fclose($record_harvested_figshare_link);        
        } catch (Exception $ex) {
            trigger_error($ex->getMessage());
        }  
    }
    private function datagov_mapping($source,$config_json,$id,$env){
        $sourceFile = file_get_contents($source);
        $sourceJson = json_decode($sourceFile);
        $figshare = new stdClass();        
        $figshare->title= $sourceJson->title;
        $figshare->description = $this->prepareDescription($sourceJson,$config_json);
        $figshare->authors = $this->prepareAuthor($sourceJson,$config_json);
        $figshare->timeline=(object)array();
        $figshare->timeline->firstOnline = $this->prepareCreated($sourceJson);
        $figshare->timeline->posted = $this->prepareCreated($sourceJson);
        $rev=$this->prepareModified($sourceJson);
        if(!empty($rev)){
          $figshare->timeline->revision = $rev;
        }
        $figshare->tags = $sourceJson->keyword;
        $figshare->categories=$this->prepareCategories($sourceJson->catagories);
        $figshare->custom_fields_list=array();
        $figshare->group_id = $config_json->group_id;
        $figshare->funding_list = $this->prepareFundref($sourceJson->fundref);
        $figshare->custom_fields_list[] = array(
                'name'=>'ISO Topic Category',
                'value'=> [$sourceJson->isoTopic]
        );
        $figshare->custom_fields_list[] = array(
                'name'=>'Data contact name',
                'value'=> $sourceJson->contactPoint->fn   
        );
        $figshare->custom_fields_list[] = array(
                'name'=>'Data contact email',
                'value'=>str_replace("mailto:","",$sourceJson->contactPoint->hasEmail)
        );
        $figshare->custom_fields_list[] = array(
          'name'=>'OMB Bureau Code',
          'value'=>$this->prepareBureauCode($sourceJson->bureauCode)
        );
        $figshare->custom_fields_list[]= [
          'name'=>'Public Access Level',
          'value'=> [ucfirst($sourceJson->accessLevel)]
          ];
        $figshare->custom_fields_list[] = array(
          'name'=>'OMB Program Code',
          'value'=>$this->prepareProgramCode($sourceJson->programCode)
        );
        $figshare->custom_fields_list[] = array(
                'name'=>'Theme',
                'value'=>[$config_json->Geospatial]
        );
        $frequency=$this->prepareFrequency($sourceJson);
        if(!empty($frequency)){
          $figshare->custom_fields_list[] = [
            'name'=>'Frequency',
            'value'=> [$frequency]
          ];
        }
        $spatial=$this->prepareSpatial($sourceJson);
        if(!empty($spatial['desc'])){
          $figshare->custom_fields_list[] = [
            'name'=>'Geographic location - description',
            'value'=> $spatial['desc']
          ];
        }elseif(!empty($spatial['geojson'])){
          $figshare->custom_fields_list[] = [
            'name'=>'Geographic Coverage',
            'value'=> $spatial['geojson']
          ];
        }
        $temporalCoverage = $this->prepareTemporalCoverage($sourceJson);
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
        $figshare->defined_type = 'Dataset';
        $figshare->license =$this->prepareLicense($sourceJson->license,$env);
        
        $relatedMaterials=$this->prepareRelatedMaterials($sourceJson);
        if(!empty($relatedMaterials)){
          $figshare->related_materials=$relatedMaterials;
        }
        // $nalTerm = $this->prepareNALTerm($sourceJson->isoTopic);
        // if(!empty($nalTerm)){
        $figshare->custom_fields_list[]= [
            'name'=>'National Agricultural Library Thesaurus terms',
            'value'=> $sourceJson->nalt
        ];
        // }


        $figshare->custom_fields_list[]= [
          'name'=>'Publisher',
          'value'=> $sourceJson->publisher->name
        ];
        $figshare->remoteFileLink=$this->prepareRemoteFileLink($sourceJson);
        return $figshare;
        
    }
    
    protected function prepareHandle($id){
      return '10113/AF'.$id;
    }
    protected function prepareDescription($source){
      $desc=$source->description."<div><br>This record was taken from the USDA Enterprise Data Inventory that feeds into the  <a href='https://data.gov'>https://data.gov</a> catalog. Data for this record includes the following resources:</div><ul>";
      foreach($source->distribution as $distribution){
        if(isset($distribution->downloadURL)){
            $desc.= "   <li>  <a href='".$distribution->downloadURL."'>".$distribution->title."</a></li>";
          }else{
            $desc.= "   <li> <a href='".$distribution->accessURL."'>".$distribution->title."</a></li>";
          }
      }
      $desc.='</ul><div>For complete information, please visit <a href="https://data.gov">https://data.gov</a>.</div>';
        return $desc;
    }
    protected function prepareSpatial($source){
      $res=array("geojson"=>"","desc"=>"");
      if(isset($source->spatial)){
        $spatial=$source->spatial;
        // $spatial="{\"type\":\"Point\",\"coordinates\":[-121.549172,36.622658]}";
        if(json_decode($spatial)){
          $res['geojson']=json_encode(array("type"=>"FeatureCollection","features"=>array("geometry"=>$spatial,"type"=>"Feature","properties"=>(object)array())));
        }else{
          $pattern = '/^[-+]?\d+(\.\d+)?,[-+]?\d+(\.\d+)?,[-+]?\d+(\.\d+)?,[-+]?\d+(\.\d+)?$/';
          // Check if the input string matches the pattern
          if (preg_match($pattern, $spatial)) {
            $coordinates = explode(',', $spatial);

            // Check if there are exactly four coordinates
            if (count($coordinates) === 4) {
              $geojson = ["type" => "Feature","properties" => (object)[],"geometry" => ["type" => "Polygon","coordinates" => [[[$coordinates[0], $coordinates[1]],[$coordinates[2], $coordinates[1]],[$coordinates[2], $coordinates[3]],[$coordinates[0], $coordinates[3]],[$coordinates[0], $coordinates[1]]]]]];
              // Convert the GeoJSON array to a JSON string
              $res['geojson']= json_encode($geojson);
            } else {
              $res['desc']= $source->spatial;
            }
              
          } else {
            $res['desc']= $source->spatial;
          }
          
        }
      }
      return $res;
    }
    protected function prepareFrequency($source){
      $frequencyMapping=getFrequencyMapping();
      if(isset($source->accrualPeriodicity)){
        if (in_array($source->accrualPeriodicity, array_keys($frequencyMapping))) {
          return $frequencyMapping[$source->accrualPeriodicity];
        }else{
          // var_dump($source->accrualPeriodicity);
        }
      }else{
        return null;
      }
    }
    protected function prepareAuthor($source,$config){
        $authorNames = isset($source->author)?$source->author:$config->overrides->author[0];

        $authors = [];
        $authors[] = array(
            'name' => (string) $authorNames,
        );

        return $authors;
    }
    protected function prepareCategories($cat){
      $categories=explode("|",$cat);
      $res=[];
      foreach($categories as $category){
        $res[]=intval($category);
      }
      return $res;
    }

    protected function prepareRemoteFileLink($source){
      if(isset($source->landingPage)){
        return array('link'=>$source->landingPage);
      }elseif(isset($source->describedBy)){
        return array('link'=>$source->describedBy);
      }else{
        $distributions=$source->distribution;
        if(isset($distributions[0]->downloadURL)){
          return array('link'=>$distributions[0]->downloadURL);
        }elseif(isset($distributions[0]->accessURL)){
          return array('link'=>$distributions[0]->accessURL);
        }
        return null;
      }
    }
    public function validISO8601Date($value) {
      $pass = false;
      // Confirm date range is given and ISO compliant.
      if (substr_count($value, '/') == 1) {
        $patterns = array(
          "^([\\+-]?\\d{4}(?!\\d{2}\\b))((-?)((0[1-9]|1[0-2])(\\3([12]\\d|0[1-9]|3[01]))?|W([0-4]\\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\\d|[12]\\d{2}|3([0-5]\\d|6[1-6])))([T\\s]((([01]\\d|2[0-3])((:?)[0-5]\\d)?|24\\:?00)([\\.,]\\d+(?!:))?)?(\\17[0-5]\\d([\\.,]\\d+)?)?([zZ]|([\\+-])([01]\\d|2[0-3]):?([0-5]\\d)?)?)?)?$",
          "^P(?=\\w*\\d)(?:\\d+Y|Y)?(?:\\d+M|M)?(?:\\d+W|W)?(?:\\d+D|D)?(?:T(?:\\d+H|H)?(?:\\d+M|M)?(?:\\d+(?:\\­.\\d{1,2})?S|S)?)?$",
          "^([\\+-]?\\d{4}(?!\\d{2}\\b))((-?)((0[1-9]|1[0-2])(\\3([12]\\d|0[1-9]|3[01]))?|W([0-4]\\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\\d|[12]\\d{2}|3([0-5]\\d|6[1-6])))([T\\s]((([01]\\d|2[0-3])((:?)[0-5]\\d)?|24\\:?00)([\\.,]\\d+(?!:))?)?(\\17[0-5]\\d([\\.,]\\d+)?)?([zZ]|([\\+-])([01]\\d|2[0-3]):?([0-5]\\d)?)?)?)?(\\/)([\\+-]?\\d{4}(?!\\d{2}\\b))((-?)((0[1-9]|1[0-2])(\\3([12]\\d|0[1-9]|3[01]))?|W([0-4]\\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\\d|[12]\\d{2}|3([0-5]\\d|6[1-6])))([T\\s]((([01]\\d|2[0-3])((:?)[0-5]\\d)?|24\\:?00)([\\.,]\\d+(?!:))?)?(\\17[0-5]\\d([\\.,]\\d+)?)?([zZ]|([\\+-])([01]\\d|2[0-3]):?([0-5]\\d)?)?)?)?$",
          "^([\\+-]?\\d{4}(?!\\d{2}\\b))((-?)((0[1-9]|1[0-2])(\\3([12]\\d|0[1-9]|3[01]))?|W([0-4]\\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\\d|[12]\\d{2}|3([0-5]\\d|6[1-6])))([T\\s]((([01]\\d|2[0-3])((:?)[0-5]\\d)?|24\\:?00)([\\.,]\\d+(?!:))?)?(\\17[0-5]\\d([\\.,]\\d+)?)?([zZ]|([\\+-])([01]\\d|2[0-3]):?([0-5]\\d)?)?)?)?(\\/)P(?=\\w*\\d)(?:\\d+Y|Y)?(?:\\d+M|M)?(?:\\d+W|W)?(?:\\d+D|D)?(?:T(?:\\d+H|H)?(?:\\d+M|M)?(?:\\d+(?:\\­.\\d{1,2})?S|S)?)?$",
          "^P(?=\\w*\\d)(?:\\d+Y|Y)?(?:\\d+M|M)?(?:\\d+W|W)?(?:\\d+D|D)?(?:T(?:\\d+H|H)?(?:\\d+M|M)?(?:\\d+(?:\\­.\\d{1,2})?S|S)?)?\\/([\\+-]?\\d{4}(?!\\d{2}\\b))((-?)((0[1-9]|1[0-2])(\\3([12]\\d|0[1-9]|3[01]))?|W([0-4]\\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\\d|[12]\\d{2}|3([0-5]\\d|6[1-6])))([T\\s]((([01]\\d|2[0-3])((:?)[0-5]\\d)?|24\\:?00)([\\.,]\\d+(?!:))?)?(\\17[0-5]\\d([\\.,]\\d+)?)?([zZ]|([\\+-])([01]\\d|2[0-3]):?([0-5]\\d)?)?)?)?$",
          "^R\\d*\\/([\\+-]?\\d{4}(?!\\d{2}\\b))((-?)((0[1-9]|1[0-2])(\\3([12]\\d|0[1-9]|3[01]))?|W([0-4]\\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\\d|[12]\\d{2}|3([0-5]\\d|6[1-6])))([T\\s]((([01]\\d|2[0-3])((:?)[0-5]\\d)?|24\\:?00)([\\.,]\\d+(?!:))?)?(\\17[0-5]\\d([\\.,]\\d+)?)?([zZ]|([\\+-])([01]\\d|2[0-3]):?([0-5]\\d)?)?)?)?\\/P(?=\\w*\\d)(?:\\d+Y|Y)?(?:\\d+M|M)?(?:\\d+W|W)?(?:\\d+D|D)?(?:T(?:\\d+H|H)?(?:\\d+M|M)?(?:\\d+(?:\\­.\\d{1,2})?S|S)?)?$"
        );
        foreach ($patterns as $pattern) {
          if (preg_match('/'. $pattern . '/', $value)) {
            $pass = true;
          }
        }
      }
      if ($pass) {
        return true;
      }
      return false;
    }
    protected function prepareBureauCode($bcodes) {
      $bureau_codes = getBureauCodes();
      $res=[];
      foreach ($bcodes as $bc){
        if (in_array($bc, array_keys($bureau_codes))) {
          $res[]=$bureau_codes[$bc];
        }
      }
      return $res;
    }
    protected function prepareProgramCode($pcodes){
      $program_codes = getProgramCodes();
      $res=[];
      foreach ($pcodes as $pc){
        if (in_array($pc, array_keys($program_codes))) {
          $res[]=$program_codes[$pc];
        }
      }
      return $res;
    }

    protected function prepareTemporalCoverage($source) {
      $temporalCoverage = [];
      if (isset($source->temporal)) {
        try {
          // Validate ISO 8601 format and confirm a date range was provided.
          if ($this->validISO8601Date($source->temporal)) {
  
            $date = explode("/", $source->temporal);
    
            // The first key is the start date of the 'temporal coverage' and the second key is the
            // end date of the 'temporal coverage'.
            foreach ($date as $key => &$value) {
              // Check if this is a time interval on the second Argument.
              if ($key == 1
                && preg_match("/P(\d*Y)?(\d*M)?(\d*D)?(\d*W)?T?(\d*H)?(\d*M)?(\d*S)?/", $value)) {
                try {
                  $value_diff = new DateInterval($value);
                  // Get the date from the first segment. This should be represented
                  // as a timestamp by now.
                  $value_date = new DateTime();
                  $value_date->setTimestamp($date[0]);
                  $value_date->add($value_diff);
                }
                catch (Exception $e) {
                  var_dump($e);
                }
              }
              // Support 4 digits year time strings.
              elseif (preg_match("@^\d{4}$@", $value)) {
                $value_date = new DateTime();
  
                // If this is the end date then set it to the last day of the year.
                if ($key == 1) {
                  $value_date->setDate($value, 12, 31);
                }
                // If this is the start date then set it to the first day of the year.
                else {
                  $value_date->setDate($value, 1, 1);
                }
  
                $value_date->setTime(0, 0);
              }
              // Fallback to full date/time format.
              else {
                try {
                  $value_date = new DateTime($value);
                }
                catch (Exception $e) {
                  var_dump($e);
                }
              }
  
              if ($value_date) {
                $value = $value_date->getTimestamp();
              }
              else {
                echo 'Cannot determine temporal coverage value. Please review the formatting standards at https://project-open-data.cio.gov/v1.1/schema/#temporal';
              }
            }
  
            if (isset($date[0])) {
              $temporalCoverage[] = $date[0];
            }
            if (isset($date[1])) {
              $temporalCoverage[] = $date[1];
            }
          }
          else {
            throw new Exception();
          }
        }
        catch (Exception $e) {
          var_dump($e);
        }
      }
      return $temporalCoverage;
    }
    protected function prepareCreated($source){	
        $r=''; 	
        $r=isset($source->issued)?$source->issued:$source->modified;
        $newDate = date("Y-m-d", strtotime($r. ' +1 day'));
        return $newDate;
    }
    protected function prepareModified($source){	
        $r=''; 	
        if(isset($source->modified)){
          $r=$source->modified;
          $newDate = date("Y-m-d", strtotime($r. ' +1 day'));
          return $newDate;
        }else{
          return null;
        }
        
    }
    protected function prepareFundref($fun_par){
        $fundref=[];        
        $fundref[]['title']  =$fun_par;
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
    protected function prepareLicense($license,$env){
      $licenses = getLicenses($env);
      if (in_array($license, array_keys($licenses))) {
        return $licenses[$license];
      }else{
        return $license;
      }
    }

  protected function prepareRelatedMaterials($source){
    $related_materials=[];
    if(isset($source->references)){
      $references=$source->references;
      foreach($references as $ref){
        $related_materials=[];
        $related_material=array(
            "identifier"=>$ref,
            "title"=>$ref,
            "relation"=>"IsSupplementTo",
            "identifier_type"=>"URL",
        );
        if(!in_array($related_material,$related_materials)){
          $related_materials[]=$related_material;
        }
      }
    }
    if(isset($source->distribution)){
      $distribution=$source->distribution;
      foreach($distribution as $dist){
        if((isset($dist->downloadURL)&&str_contains($dist->downloadURL,"#"))||(isset($dist->accessURL)&&str_contains($dist->accessURL,"#"))){
          continue;
        }
        $related_materials=[];
        $title="";
        $identifier="";
        if(isset($dist->downloadURL)){
          $identifier=$dist->downloadURL;
          if(isset($dist->title)){
            $title=$dist->title;
          }
        
        }else{
          $identifier=$dist->accessURL;
          if(isset($dist->title)){
            $title=$dist->title;
          }
        }
        $related_material=array(
          "identifier"=>$identifier,
          "title"=>$title,
          "relation"=>"IsSupplementTo",
          "identifier_type"=>"URL",
        );
        if(!in_array($related_material,$related_materials)){
          $related_materials[]=$related_material;
        }
      }
    }
    return $related_materials;
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
