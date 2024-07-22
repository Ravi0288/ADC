<?php
$env = isset($argv[1]) ? $argv[1] : "stage";
require_once(__DIR__.'/fsHarvester.php');
$host_name=gethostname();
$config=file_get_contents("../fs_fgdc_config_".$env.".json");
$config_json = json_decode($config);
$base_url=$config_json->base_url;
$fs_harvester = new fsHarvester($base_url,$config_json,$env);
$fs_harvester->harvest();