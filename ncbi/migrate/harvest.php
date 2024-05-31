<?php
$env = isset($argv[1]) ? $argv[1] : "stage";
require_once(__DIR__.'/ncbiHarvester.php');
$host_name=gethostname();
$config=file_get_contents("ncbi_config_".$env.".json");
$config_json = json_decode($config);
$base_url=$config_json->base_url;
$ncbi_harvester = new NCBIHarvester($base_url,$config_json.$env);
$ncbi_harvester->harvest();