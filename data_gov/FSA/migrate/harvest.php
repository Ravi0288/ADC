<?php
$env = isset($argv[1]) ? $argv[1] : "stage";
require_once(__DIR__.'/../../datagovHarvester.php');
$host_name=gethostname();
$config=file_get_contents("../fsa_config_".$env.".json");
$config_json = json_decode($config);
$fsa_harvester = new DataGovHarvester($config_json,$env);
$fsa_harvester->harvest();