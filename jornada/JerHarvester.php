<?php
require_once(__DIR__.'/../AbstractHarvester.php');
include_once(__DIR__.'/simple_html_dom.php');
include_once(__DIR__.'/AdcISOXmlHarvestTrait.php');
class JerHarvester extends AbstractHarvester {    
    use AdcISOXmlHarvestTrait;
    
    public function harvest() {
        try {
            // date_default_timezone_set("America/New_York");
            $base_url=$this->base_url;
            $config_json=$this->config_json;
            $file_path="../file_cache/jornada/";
            $root_url='https://portal.edirepository.org/nis/identifierbrowse?scope=knb-lter-jrn';
            $html = file_get_html($root_url);
            $id_arrays=[];
            $eml_mapping = array('eml-2.1.0'=>'transforms/eml2.1.0_2iso19139.xsl','eml-2.1.1'=>'transforms/eml2.1.1_2iso19139.xsl','eml-2.2.0'=>'transforms/eml2.2.0_2iso19139.xsl');
            foreach($html->find('a') as $element){
                if(array_key_exists('class', $element->attr)){
                    if($element->attr['class']=='searchsubcat'){
                        $id_arrays[]=$element->text();
                    }
                }
            }

            $JER_metadata_file = file_get_contents("logs/Jer_records.json");
            $json_a = json_decode($JER_metadata_file, true);
            $JER_harvested_metadata_file = file_get_contents("logs/Jer_harvested_records.json");
            $json_harvested = json_decode($JER_harvested_metadata_file, true);
            $event_log = fopen("logs/Jer_metadata_report.txt", "a");
            $error_log = fopen("logs/Jer_metadata_error.txt", "a");
            $metadata_adc_links_file = file_get_contents("logs/Jer_metadata_links.json");
            $metadata_adc_links=json_decode($metadata_adc_links_file, true);
            $updated=0;
            $new_added=0;
            $failed=0;
            $unchanged=0;
            foreach($id_arrays as $ediid){
                $split_ar=explode('.',$ediid);
                $root_url='https://portal.edirepository.org/nis/revisionbrowse?scope=knb-lter-jrn&identifier='.trim($split_ar[1]).'&contentType=application/xml';
                $html = file_get_html($root_url);
                $lastid='';
                foreach($html->find('a') as $element){
                    if(array_key_exists('class', $element->attr)){
                        if($element->attr['class']=='searchsubcat'){
                            $lastid=$element->text();
                        }
                    }
                }
                $link='https://portal.edirepository.org/nis/metadataviewer?packageid='.$lastid.'&contentType=application/xml';
                
                //check if a record needs to be updated
                if(isset($json_harvested[$ediid])&&$json_harvested[$ediid]==$lastid){
                    $unchanged++;
                    continue;
                // check if a record is in the exclusion lists
                }elseif(!isset($json_harvested[$ediid])&&isset($json_a[$ediid])&&$json_a[$ediid]==$lastid){
                    continue;
                }
                $json_a[$ediid]=$lastid;
                

                $xml = simplexml_load_file($link);
                $namespaces = $xml->getNamespaces(true);
                $eml=$namespaces['eml'];
                $eml_explode = explode('/',$eml);
                $eml_version=end($eml_explode);
                $uuid=$xml->attributes();
                $keyword_list=[];
                $keywordSets=$xml->xpath('/eml:eml/dataset/keywordSet');
                foreach($keywordSets as $kwrdSet){
                    $keywords=$kwrdSet->xpath('keyword');
                    foreach($keywords as $kwrd){
                        $keyword_list[]=$kwrd[0];
                    }
                }
                
                if(in_array('LTAR',$keyword_list)||in_array('LTAR_LTER',$keyword_list)){
                    $fund="";
                    $xml->asXML($file_path.$uuid.'_EML.xml');
                    if(array_key_exists($eml_version,$eml_mapping)){
                        $rsl=$this->transform($file_path.$uuid.'_EML.xml',$eml_mapping[$eml_version]);
                        file_put_contents ($file_path.$uuid.'_ISO19139.xml',$rsl);
                        $snippets = new DOMDocument;
                        $snippets->load('funder_snippet.xml');
                        $xpath = new DOMXPath($snippets);
                        // update
                        // getElementsByTagNameNS
                        $xpath->registerNamespace('gmd', 'http://www.isotc211.org/2005/gmd');
                        $xpath->registerNamespace('gco','http://www.isotc211.org/2005/gco');
                        $val=$xpath->query("//gmd:MD_Keywords//gmd:keyword//gco:CharacterString");
                        $fund = $xml->xpath('/eml:eml/dataset/project/funding/section/para');
                        if(empty($fund)){
                            $fund = $xml->xpath('/eml:eml/dataset/project/funding/para');
                            if(empty($fund)){
                                $fund= $xml->xpath('/eml:eml/dataset/project/funding');
                            }
                        }
                        if(empty($fund)){
                            $fund=["Agricultural Research Service"];
                        }
                        $fundref=array();
                        // $groupname='';
                        if(in_array('LTAR',$keyword_list)){
                            if(preg_match('/\d+-\d+-\d+-\d+\w+/', $fund[0], $matches)){
                                $grant=$matches[0];
                                $fund_val = 'Agricultural Research Service, '.trim($grant);
                            }elseif(preg_match('/\d+-\d+-\d+-\w+/', $fund[0], $matches)){
                                $grant=$matches[0];
                                $fund_val='Agricultural Research Service, '.trim($grant);
                            }elseif(preg_match('/\d+-\d+-\d+/', $fund[0], $matches)){
                                $grant=$matches[0];
                                $fund_val='Agricultural Research Service, '.trim($grant);
                            }else{
                                $fund_val = 'Agricultural Research Service';
                            }
                            
                            $fundref[]=$fund_val;
                            // $groupname='Jornada Experimental Range LTAR';
                        }else{
                            // $groupname="Jornada Experimental Range LTAR and Jornada Basin LTER";
                            foreach($fund as $fd){
                                $fundref[]='National Science Foundation';
                                $fundref[]='Agricultural Research Service';
                                if(strpos($fd,'grant')!==false){
                                    $grant=explode('grant ',$fd)[1];
                                    $fundref[0]='National Science Foundation, '.trim($grant);
                                }elseif(strpos($fd,':')!==false){
                                    $grant=explode(':',$fd)[1];
                                    $fundref[0]='National Science Foundation, '.trim($grant);
                                }elseif(preg_match('/\d+-\d+-\d+-\d+\w+/', $fd, $matches)){
                                    $grant=$matches[0];
                                    $fundref[1]='Agricultural Research Service, '.trim($grant);
                                }elseif(preg_match('/\d+-\d+-\d+-\w+/', $fd, $matches)){
                                    $grant=$matches[0];
                                    $fundref[1]='Agricultural Research Service, '.trim($grant);
                                }elseif(preg_match('/\d+-\d+-\d+/', $fd, $matches)){
                                    $grant=$matches[0];
                                    $fundref[1]='Agricultural Research Service, '.trim($grant);
                                }
                            }
                            
                        }
                        foreach($fundref as $frf){
                            $val->item(0)->nodeValue =$frf;
                            file_put_contents('test.xml',$snippets->saveXML()); 
                            $xml=simplexml_load_file($file_path.$uuid.'_ISO19139.xml');
                            $funding_snippets=simplexml_load_file('test.xml');
                            $xml->registerXPathNamespace('gmd', 'http://www.isotc211.org/2005/gmd');
                            $xml->registerXPathNamespace('gco','http://www.isotc211.org/2005/gco');                            
                            $descriptivekeywords = $xml->xpath('//gmd:identificationInfo//gmd:MD_DataIdentification//gmd:descriptiveKeywords'); // xpath returns an array
                            if (!empty($descriptivekeywords)) {                            
                                $this->insertAfter($funding_snippets, $descriptivekeywords[0]);
                            }                            
                        }

                        file_put_contents($file_path.$uuid.'_ISO19139.xml',$xml->saveXML());   
                        $file_name_with_full_path=$file_path.$uuid.'_ISO19139.xml';
                        $jsonData= $this->iso_mapping($file_name_with_full_path,$config_json);
                        $jsonData=$this->related_material($jsonData,$uuid);
                        // newly added
                        if(!isset($json_harvested[$ediid])){
                            $res=$this->figshare_api_req($jsonData);
                            if(isset($res['entity_id'])){
                                $figshare_nodeid=$res['entity_id'];
                                $json_harvested[$ediid]=$lastid;
                                $res=$this->figshare_api_req_update($figshare_nodeid,$jsonData);
                                $remote_file=array('link'=>'https://portal.edirepository.org/nis/mapbrowse?scope=knb-lter-jrn&identifier='.trim($split_ar[1]));
                                $res2=$this->figshare_api_remote_file($figshare_nodeid,$remote_file);
                                $publish = $this->figshare_api_publish($figshare_nodeid);
                                $json_harvested[$ediid]=$lastid;
                                $new_added++;
                                $metadata_adc_links[$ediid]=$figshare_nodeid;
                            }else{
                                $failed++;
                                fwrite($error_log, $lastid.' failed on '.date("Y/m/d").' at '.date("h:i:sa").' Error message:'.$res['message'].PHP_EOL);
                            }
                        }else{
                            // update previously harvested record
                            $figshare_nodeid=$metadata_adc_links[$ediid];
                            $res=$this->figshare_api_req_update($figshare_nodeid,$jsonData);
                            $publish = $this->figshare_api_publish($figshare_nodeid);
                            if(isset($res['location'])){
                                $updated++;
                                $json_harvested[$ediid]=$lastid;
                            }else{
                                $failed++;
                                fwrite($error_log, $lastid.' failed on '.date("Y/m/d").' at '.date("h:i:sa").' to update. Error message:'.json_encode($res).PHP_EOL);
                            }
                        }
                        
                        
                    }else{
                        $failed++;
                                fwrite($error_log, $lastid.' failed on '.date("Y/m/d").' at '.date("h:i:sa").' because eml version does not support! '.$eml_version.PHP_EOL);
                    }
                }
            }
            fwrite($event_log, 'Last run on '.date("Y/m/d").' at '.date("h:i:sa").'; '.$new_added.' added; '.$updated.' updated; '.$failed.' failed; '.$unchanged.' unchanged'.PHP_EOL);
            $record_version_mapping = fopen('logs/Jer_records.json', 'w');
            fwrite($record_version_mapping, json_encode($json_a));
            fclose($record_version_mapping);
            $record_harvested = fopen('logs/Jer_harvested_records.json', 'w');
            fwrite($record_harvested, json_encode($json_harvested));
            $record_harvested_figshare_link = fopen('logs/Jer_metadata_links.json', 'w');
            fwrite($record_harvested_figshare_link, json_encode($metadata_adc_links));
            fclose($record_harvested);
            fclose($event_log);
            fclose($record_harvested_figshare_link);
        } catch (Exception $ex) {
            trigger_error($ex->getMessage());
        }  
    }
    private function related_material($json,$uuid){

        $related_html='https://portal.edirepository.org//nis//mapbrowse?packageid='.$uuid;
        $html = file_get_html($related_html);
        $related_material=[];
        if ($html !== false) {
            // Find all div elements with class "table-row"
            $tableRows = $html->find('div.table-row');
        
            // Iterate through each table row
            foreach ($tableRows as $row) {
                // Find the label inside the current row
                $label = $row->find('label.labelBold', 0);
                
                // Check if the label's text matches "Journal Citations:"
                if ($label && $label->plaintext === 'Journal Citations:') {
                    $olElement = $row->find('div.table-cell ul.no-list-style li ol', 0);
                    if ($olElement) {
                        $liElements = $olElement->find('li');
                        foreach ($liElements as $li) {
                            $a = $li->find('a.searchsubcat')[0];
                            $href = $a->href;
                            $textContent = $li->plaintext;
                            $pattern = '/\((10\.\d{4,}\/\S+)\)/';
                            if(preg_match($pattern, $textContent, $matches)) {
                                $doi = $matches[1]; // The matched DOI value
                                $related_material[]=array(
                                    "identifier"=>$doi,
                                    "title"=>$textContent,
                                    "relation"=>"IsSupplementTo",
                                    "identifier_type"=>"DOI",
                                );
                            }else{
                                $related_material[]=array(
                                    "identifier"=>$href,
                                    "title"=>$textContent,
                                    "relation"=>"IsSupplementTo",
                                    "identifier_type"=>"URL",
                                );
                            }
                        }
                    }
                }
            }
        
            // Clean up memory
            $html->clear();
            if(!empty($related_material)){
                $json->related_materials = $related_material;
            }
            
        }

        return $json;
    }
    
}
