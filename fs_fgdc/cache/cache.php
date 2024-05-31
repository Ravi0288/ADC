<?php
// date_default_timezone_set("America/New_York");
$env = isset($argv[1]) ? $argv[1] : "stage";
$config=file_get_contents("../fs_fgdc_config_".$env.".json");
$config_json = json_decode($config);
usda_harvest_fs_fgdc_cache(date("Y-m-d"),$config_json);

function usda_harvest_fs_fgdc_cache( $harvestUpdateTime,$config_json) {
    $source = $config_json->source;
    $headers = array();
    $rToken = FALSE;
    do {
      // Get the number of records available.
      $headers = array_merge($headers, fetchIdentifierList($source, $rToken));
    } while ($rToken != FALSE);
    $cache_dir = "../../file_cache/fs_fgdc";
    $identifiers=[];
    foreach ($headers as $header) {
      $recordId = (string) $header->identifier;
      $safeId = preg_replace("#[^a-zA-Z0-9_]#", "_", $recordId);
  
      $queryParams = array(
            'verb' => 'GetRecord',
            'metadataPrefix' => 'fgdc',
            'identifier' => $recordId,
          );
      $query = http_build_query($queryParams);
      $rawXML = http_req($source . '?' . $query);
  
      $recordXml = simplexml_load_string($rawXML);
  
      if ($recordXml) {
        file_put_contents($cache_dir . DIRECTORY_SEPARATOR . $safeId."_new",
          $recordXml->asXML());
        $identifiers[]=$safeId;
        $recordXml->registerXPathNamespace("o",
          "http://www.openarchives.org/OAI/2.0/");
        $title = (string) $recordXml->xpath("/o:OAI-PMH/o:GetRecord/o:record/o:metadata/o:metadata/o:idinfo/o:citation/o:citeinfo/o:title/text()")[0];
      }
    }
    foreach ($identifiers as $identifier){
        $oldF = $cache_dir . DIRECTORY_SEPARATOR . $identifier;
        $newF = $cache_dir . DIRECTORY_SEPARATOR . $identifier."_new";

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
  
  /**
   * @param $source
   * @return array
   */
  function fetchIdentifierList($source, &$resumptionToken = FALSE) {
    $queryParams = array(
      'verb' => 'ListIdentifiers',
    );
  
    if ($resumptionToken) {
      $queryParams['resumptionToken'] = $resumptionToken;
    }
    else {
      $queryParams['metadataPrefix'] = 'fgdc';
    }
  
    $query=http_build_query($queryParams);
    $request = http_req($source . '?' . $query);
    // var_dump($request);
    $xml = simplexml_load_string($request);
    var_dump($xml);
    $xml->registerXPathNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');
    $headers = $xml->xpath("/oai:OAI-PMH/oai:ListIdentifiers/oai:header");
    if(!empty($xml->xpath("/oai:OAI-PMH/oai:ListIdentifiers/oai:resumptionToken/text()"))){
        $resumptionToken = (string) $xml->xpath("/oai:OAI-PMH/oai:ListIdentifiers/oai:resumptionToken/text()")[0];
    }else{
        $resumptionToken = False;
    }
    
  
    return $headers;
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
  
?>  