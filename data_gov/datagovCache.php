<?php
class dataGovCache {
    // date_default_timezone_set("America/New_York");
    protected $config_json;
    function __construct($config_json) {
        $this->config_json = $config_json;
    }
  
    function usda_harvest_data_gov_cache() {
        $source=$this->config_json->source;
        $outputDirectory = $this->config_json->cachedir;
        $identifiers=[];
        // Read and parse the JSON file
        $jsonData = file_get_contents($source);
        echo $outputDirectory."<br/>";
        if ($jsonData) {
            $identifiers=[];
            // Check if the directory exists, if not, create it
            if (!file_exists($outputDirectory)) {
                mkdir($outputDirectory, 0777, true);
            }
            $dataArray = json_decode($jsonData);
            //echo "<pre>"; print_r($dataArray['dataset']); echo "</pre>";

            // Iterate through each item in the JSON array
            if ($dataArray) {
                $v = $this->dkan_harvest_datajson_cache_pod_v1_1_json($dataArray, $this->config_json);
                foreach($v as $index => $dataset){

                    $id=$dataset->identifier;
                    echo $id;
                    echo "<br/>";
                    if(str_starts_with($id,"https://")){
                        $queryString = parse_url($id, PHP_URL_QUERY);
                        parse_str($queryString, $params);
                        $id=$params['id'];
                    }
                    $identifiers[]=$id;
                    // Generate a unique filename for each item (you can adjust the filename as per your requirement)
                    $filename = $outputDirectory .'/' . $id . '_new';

                    // Encode the item back to JSON format
                    $itemJson = json_encode($dataset, JSON_PRETTY_PRINT);

                    // Save the item as a separate file
                    file_put_contents($filename, $itemJson);
                }
                foreach ($identifiers as $identifier){
                    $oldF = $outputDirectory . '/' . $identifier;
                    $newF = $outputDirectory . '/' . $identifier."_new";
                    echo $oldF;
                    echo "<br/>";
                    if (file_exists($oldF)) {
                        if (file_get_contents($oldF) === file_get_contents($newF)) {
                            unlink($newF);
                        } else {
                            rename($newF, $oldF);
                        }
                    } else {
                        rename($newF, $oldF);
                    }
                }
            }
        }
    }
    
    /**
     * @param $source
     * @return array
     */
    function dkan_harvest_datajson_cache_pod_v1_1_json($data, $source) {
        $datasets = $data->dataset;

        // Filter datasets, only allow datasets that have the filters value.
        $filters = $source->filters;
        if (!empty($filters)) {
            $datasets = array_filter($datasets, function ($dataset) use ($filters) {
            $dataset_key = $this->dkan_harvest_datajson_prepare_item_id($dataset->identifier);
            // Default action is to DROP the dataset if it does not meet the
            // filtering criteria.
            if($dataset->accessLevel!="public"){
                return FALSE;
            }
            $accept = FALSE;
            foreach ($filters as $key => $filter_values) {
                $filter_values = array_map('trim', $filter_values);
                if (is_array($key)) {
                    $key_values = array_map('trim', $this->dkan_harvest_datajson_get_filter_value($dataset, $key));
                }
                else {
                    $key_values = $this->dkan_harvest_datajson_get_filter_value($dataset, $key);
                }
        
                // if ($key_values == array(t("No keyword provided"))) {
                //   break;
                // }
                // cache_resource_definition_value.
                if (!empty($key_values) && !empty($filter_values) && count(array_intersect((array) $filter_values, (array) $key_values)) > 0) {
                // The value to filter is an array and does intersect with the
                // dataset value. ACCEPT.
                $accept = TRUE;
                }
                if ($accept) {
                // Dataset have at least one filter that match. No need for more
                // proccecing.
                }
            }
        
        
            return $accept;
            });
        }
    
    
        // Exclude datasets, drop datasets that have the excludes value.
        $excludes = $source->excludes;
        if (!empty($excludes)) {
            $datasets = array_filter($datasets, function ($dataset) use ($excludes) {
            $dataset_key = $this->dkan_harvest_datajson_prepare_item_id($dataset->identifier);
            // Default action is to accept dataset that does not meet the
            // excluding criteria.
            
            $accept = TRUE;
            foreach ($excludes as $path => $exclude_value) {
                $value = dkan_harvest_datajson_get_value($dataset, $path);
                if (!empty($value) && count(array_intersect((array) $exclude_value, (array) $value)) > 0) {
                // The value to exclude is an array and does intersect
                // with the dataset value then drop it.
                $accept = FALSE;
                }
        
                if (!$accept) {
                // Dataset have at least one exclude criterion that matches.
                // No need for more proccecing.
                break;
                }
            }

        
            // Drop the dataset if excluded.
            return $accept;
            });
        }
        
        // Override field values.
        $overrides = $source->overrides;
        $datasets = array_map(function ($dataset) use ($overrides) {
            $identifier = $this->dkan_harvest_datajson_prepare_item_id($dataset->identifier);
            $overridden = FALSE;
        
            foreach ($overrides as $path => $override_values) {
            $override_values = array_map('trim', $override_values);
            if (!empty($path) && !empty($override_values)) {
                $overridden = $this->dkan_harvest_datajson_set_value($dataset, $path, $override_values[0], TRUE);
            }
            
            }
        
            return $dataset;
        }, $datasets
        );
        
        // Set default values for empty fields.
        $defaults = $source->defaults;
        $datasets = array_map(function ($dataset) use ($defaults) {
            $identifier = $this->dkan_harvest_datajson_prepare_item_id($dataset->identifier);
            $defaulted = FALSE;
        
            foreach ($defaults as $path => $default_values) {
            $defaults_values = array_map('trim', $default_values);
            $defaulted = $defaulted || $this->dkan_harvest_datajson_set_default_value($dataset, $path, $default_values[0]);
            if ($defaulted) {
                $defaulted = FALSE;
            }
            }
        
            return $dataset;
        }, $datasets);
        return $datasets;
    }
    function http_req($url){
    echo $url;
    $headers = array();
    $curl = curl_init();
    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPGET => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_VERBOSE => false
    );
    curl_setopt_array($curl, $options);
    $r = curl_exec($curl);
    curl_close($curl);
    return $r;
    }
    function dkan_harvest_datajson_prepare_item_id($identifier) {
    if (filter_var($identifier, FILTER_VALIDATE_URL)) {
        $identifier = parse_url($identifier, PHP_URL_PATH);
        $frag = explode('/', $identifier);
    
        // Return the last non empty URL Path element.
        $frag = array_filter($frag);
        $identifier = end($frag);
    }
    
    return $identifier;
    }
    function dkan_harvest_datajson_get_filter_value($obj, $path) {
    $keys = explode('.', $path);
    $value = $obj;
    
    foreach ($keys as $key) {
        if (isset($value->$key)) {
        $value = $value->$key;
        }
        else {
        // drupal_set_message(t('Datasets that do not have a value for @path will not be included in the harvest.', array('@path' => $path, 'title' => $obj['title'])), 'warning', FALSE);
        return;
        }
    }
    return $value;
    }
    function dkan_harvest_datajson_set_value(&$obj, $path, $value, $override = FALSE) {
        $updated = FALSE;
        $keys = explode('.', $path);
        $branch = &$obj;
      
        foreach ($keys as $key) {
          if (isset($branch->$key)) {
            $branch = &$branch->$key;
          }
          else {
            $branch->$key = array();
            $branch = &$branch->$key;
          }
          // drupal_set_message(t('The @path field was set to "@value" on all harvested datasets.',
          //   array('@path' => $path, '@value' => $value, '@title' => $obj['title'])), 'warning', FALSE);
        }
      
        // Update the obj if $override is set or the branch is empty.
        if ($override || empty($branch)) {
          $branch = $value;
          $updated = TRUE;
        }
      
        return $updated;
      }
      function dkan_harvest_datajson_set_default_value(&$obj, $path, $value, $override = FALSE) {
        $updated = FALSE;
        $keys = explode('.', $path);
        $branch = &$obj;
      
        foreach ($keys as $key) {
          if (isset($branch->$key)) {
            $branch = &$branch->$key;
          }
          else {
            $branch->$key = array();
            $branch = &$branch->$key;
          }
        //   drupal_set_message(t('A @path value of "@value" was added to datasets where the @path field was empty.',
        //     array('@path' => $path, '@value' => $value, '@title' => $obj['title'])), 'warning', FALSE);
        }
      
        // Update the obj if $override is set or the branch is empty.
        if ($override || empty($branch)) {
          $branch = $value;
          $updated = TRUE;
        }
      
        return $updated;
      }
}
?>
