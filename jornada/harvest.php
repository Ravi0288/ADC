<?php

require_once(__DIR__.'/JerHarvester.php');
$host_name=gethostname();
$config=file_get_contents("jornada_config.json");
$config_json = json_decode($config);
$base_url='https://api.figsh.com/v2/account/articles';
$jer_harvester = new JerHarvester($base_url,$config_json);
$jer_harvester->harvest();
