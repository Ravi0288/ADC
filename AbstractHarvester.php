<?php
require_once 'utils.php';
abstract class AbstractHarvester {
    protected $base_url;
    protected $config_json;
    protected $token;
    protected $env;
    function __construct($config_json,$env) {
        $this->base_url = $config_json->base_url;
        $this->config_json = $config_json;
        $this->token=$config_json->token;
        $this->env=$env;
    }

    abstract protected function harvest();
    protected function transform($xml, $xsl) {
        $sourceDocument = new DOMDocument();
        $sourceDocument->load($xml);

        // Load your XSL stylesheet
        $xslStylesheet = new DOMDocument();
        $xslStylesheet->load($xsl);

        // Create an instance of XSLTProcessor
        $xsltProcessor = new XSLTProcessor();

        // Import the XSL stylesheet
        $xsltProcessor->importStylesheet($xslStylesheet);

        // Transform the XML using the XSLTProcessor
        return $xsltProcessor->transformToXml($sourceDocument);
        // $xslt->transformToXml($xml);
    }


    public function figshare_api_req($json){
      $headers = array();
      $headers[] = 'Accept:application/json';
      $headers[] = 'Authorization: token ' . $this->token;
      $curl = curl_init();
      $options = array(
        CURLOPT_URL => $this->base_url,
        CURLOPT_HEADER => false,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($json),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_VERBOSE => false
      );
      curl_setopt_array($curl, $options);
      $r = curl_exec($curl);
      curl_close($curl);
      // var_dump($r);
      $response = json_decode($r,true);
      return $response;
    }
    public function figshare_api_req_update($nodeid,$json){
      $headers = array();
      $headers[] = 'Accept:application/json';
      $headers[] = 'Authorization: token ' . $this->token;
      $curl = curl_init();
      $options = array(
        CURLOPT_URL => $this->base_url."//".$nodeid,
        CURLOPT_HEADER => false,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($json),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_VERBOSE => false
      );
      curl_setopt_array($curl, $options);
      $r = curl_exec($curl);
      curl_close($curl);
      // var_dump($r);
      $response = json_decode($r,true);
      return $response;
    }
    public function figshare_api_remote_file($nodeid,$remote_file){
      $headers = array();
      $headers[] = 'Accept:application/json';
      $headers[] = 'Authorization: token ' . $this->token;
      $curl = curl_init();
      $options = array(
        CURLOPT_URL => $this->base_url."//".$nodeid."/files",
        CURLOPT_HEADER => false,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($remote_file),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_VERBOSE => false
      );
      curl_setopt_array($curl, $options);
      $r = curl_exec($curl);
      curl_close($curl);
      // var_dump($r);
      $response = json_decode($r,true);
      return $response;
    }
    public function figshare_api_publish($nodeid){
      $headers = array();
      $headers[] = 'Accept:application/json';
      $headers[] = 'Authorization: token ' . $this->token;
      $curl = curl_init();
      $options = array(
        CURLOPT_URL => "https://api.figsh.com/v2/account/articles/".$nodeid."/publish",
        CURLOPT_HEADER => false,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
      //   CURLOPT_POSTFIELDS => json_encode($remote_file),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_VERBOSE => false
      );
      curl_setopt_array($curl, $options);
      $r = curl_exec($curl);
      curl_close($curl);
      // var_dump($r);
      $response = json_decode($r,true);
      return $response;
    }
}


