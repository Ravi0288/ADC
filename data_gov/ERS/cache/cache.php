<?php
$env = isset($argv[1]) ? $argv[1] : "stage";
require_once(__DIR__.'/../../datagovCache.php');
$config=file_get_contents("../ers_config_".$env.".json");
$config_json = json_decode($config);
$ers_cacher = new datagovCache($config_json);
$ers_cacher->usda_harvest_data_gov_cache();
