<?php
$env = isset($argv[1]) ? $argv[1] : "stage";
require_once(__DIR__.'/../../datagovHarvester.php');
$host_name=gethostname();
$config=file_get_contents("../nrcs_config_".$env.".json");
$config_json = json_decode($config);
$nrcs_harvester = new DataGovHarvester($config_json,$env);
$nrcs_harvester->harvest();