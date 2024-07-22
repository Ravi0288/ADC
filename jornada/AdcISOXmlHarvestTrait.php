<?php

/**
 * Additional functions for ADC XML harvests.
 */
require_once (__DIR__.'/../utils.php');
trait AdcISOXmlHarvestTrait {
  protected function iso_mapping($xmlfile,$config_json){      
    $xml = simplexml_load_file($xmlfile);    
    $namespaces = $xml->getNamespaces(true);

    // Register the namespace
    $xml->registerXPathNamespace('gmd',  "http://www.isotc211.org/2005/gmd");
    $xml->registerXPathNamespace('gmi', "http://www.isotc211.org/2005/gmi");
    $xml->registerXPathNamespace('gco', "http://www.isotc211.org/2005/gco");
    $xml->registerXPathNamespace('gml', "http://www.opengis.net/gml");
    
    $figshare = new stdClass();
    $titleNodeList = $xml->xpath('gmd:identificationInfo/gmd:MD_DataIdentification/gmd:citation/gmd:CI_Citation/gmd:title/gco:CharacterString/text()');
    // Check if the XPath query returned any nodes
    if (!empty($titleNodeList)) {
        // Get the last element from the list using array_pop
        $title = (string) array_pop($titleNodeList);
        // Assign the title to the $figshare object
        $figshare->title = $title;
    }
    

    $figshare->description = $this->prepareBody($xml);
    

    // Preferred Dataset Citation.
    // $figshare->preferredtemporalCoverage = $this->preparePreferedCitation($xml);
    // $uuid=$xml->xpath('gmd:fileIdentifier/gco:CharacterString/text()');
    // $metadata_source_title = 'ISO-19115 Metadata for ' . $uuid;
    // $figshare->metadata_source = $this->createMetadataSource($xml, $metadata_source_title, 'ISO 19139');

    // Handle created property.
    $figshare->timeline=(object)array();
    $figshare->timeline->firstOnline = $this->prepareCreated($xml);
    $figshare->timeline->postedDate = $this->prepareCreated($xml);
    // Handle modified property.
    $rev=$this->prepareModified($xml);
    if(!empty($rev)){
      $figshare->timeline->revision = $rev;
    }

    // Field license.
    $figshare->license = $this->prepareRowLicense($xml);

    $figshare->defined_type = 'Dataset';
    $figshare->custom_fields_list=array();
    // spatial
    $spatial = $this->prepareSpatial($xml);
    if(!empty($spatial)){
       $figshare->custom_fields_list[] = [
        'name'=>'Geographic Coverage',
        'value'=> $spatial
      ];
    }
    if(isset($config_json->ADCgroup)){
      $group = [$config_json->ADCgroup];
      $figshare->custom_fields_list[] = [
        'name'=>'Ag Data Commons Group',
        'value'=> $group
      ];
    }
    $isoTopic = $this->prepareTheme($xml);
    if(!empty($isoTopic)){
      $figshare->custom_fields_list[] = [
       'name'=>'ISO Topic Category',
       'value'=> $isoTopic
     ];
    }

    $spatial_geographical_cover = $this->prepareSpatialGeographicalCover($xml);
    if(!empty($spatial_geographical_cover)){
      $figshare->custom_fields_list[] = [
        'name'=>'Geographic location - description',
        'value'=> $spatial_geographical_cover
      ];
    }
    if(!empty($this->stringIfExists($xml, '//gmd:purpose/gco:CharacterString/text()'))){
      // Intended use.
      $figshare->custom_fields_list[] = [
        'name'=>'Intended use',
        'value'=> $this->stringIfExists($xml, '//gmd:purpose/gco:CharacterString/text()')
      ];
    }
    $temporalCoverage = $this->prepareTemporalCoverage($xml);
    if(!empty($temporalCoverage['begin'])){
      $figshare->custom_fields_list[] = [
        'name'=>'Temporal Extent Start Date',
        'value'=> $temporalCoverage['begin']
      ];
    }
    if(!empty($temporalCoverage['end'])){
      $figshare->custom_fields_list[] = [
        'name'=>'Temporal Extent End Date',
        'value'=> $temporalCoverage['end']
      ];
    }
   
    $frequency = $this->prepareFrequency($xml);
    if(!empty($frequency)){
      $figshare->custom_fields_list[] = [
        'name'=>'Frequency',
        'value'=> [$frequency]
      ];
    }
    // Use limitations.
    if(!empty($this->stringIfExists($xml, '//gmd:resourceConstraints//gmd:otherConstraints/gco:CharacterString/text()'))){
      $figshare->custom_fields_list[] = [
        'name'=>'Use limitations',
        'value' => $this->stringIfExists($xml, '//gmd:resourceConstraints//gmd:otherConstraints/gco:CharacterString/text()'),
      ];
    }
    

    // field_adc_hierarchy.
    $FoRTerm = $this->prepareAdcHierarchy($xml);
    $figshare->categories=$FoRTerm;
    $nalTerm = $this->prepareAdcNALT($xml);
    if(!empty($nalTerm)){
      $figshare->custom_fields_list[]= [
        'name'=>'National Agricultural Library Thesaurus terms',
        'value'=> implode(";",$nalTerm)
        ];
    }
    // field_adc_project.
    // $figshare->adcProject = $this->prepareAdcProject($xml);

    // field_tags
    // Return a list of tags name. the taxonomy will be built by the migration.
    $figshare->tags = $this->prepareRowTags($xml);

    $nptags = $this->prepareRowNPtags($xml);
    if(!empty($nptags)){
      $figshare->custom_fields_list[]= [
        'name'=>'ARS National Program Number',
        'value'=> $nptags
        ];
    }

    $publisher = $this->preparePublisher($xml);
    if(!empty($publisher)){
      $figshare->custom_fields_list[]= [
        'name'=>'Publisher',
        'value'=> $publisher
        ];
    }
    // Authors.
    // $figshare->author_is_organization = FALSE;
    $figshare->authors = $this->prepareRowAuthors($xml, $figshare->author_is_organization);


    // Checkbox field needs 1 or 0 not TRUE or FALSE as value.
    // $figshare->author_is_organization ? $figshare->author_is_organization = 1
    //     : $figshare->author_is_organization = 0;

    // Contact.
    $contact = $this->prepareContact($xml);
    if (!empty($contact)) {
        $contact_name = !empty($contact['individualName']) ? $contact['individualName']: $contact['organisationName'];
        $contact_email = $contact['email'];
        $figshare->custom_fields_list[]= [
          'name'=>'Data contact name',
          'value'=> $contact_name              
          ];
        $figshare->custom_fields_list[]= [
          'name'=>'Data contact email',
          'value'=> $contact_email             
          ];
    }

    // We want to validate the DOI here.
    // Make blank for now.https://github.com/NuCivic/usda-nal/issues/1059#issuecomment-256962977
    $doi = $this->prepareRowDOI($xml);

    if ($doi != FALSE) {
      $figshare->doi = $doi;
    }


    // This ones are hardcoded here, not present in the XML.
    $figshare->custom_fields_list[]= [
      'name'=>'Public Access Level',
      'value'=> ["Public"]
      ];

    $figshare->custom_fields_list[]= [
      'name'=>'OMB Bureau Code',
      'value'=>$this->prepareBureauCode($xml)
      ];
      $figshare->custom_fields_list[]= [
        'name'=>'OMB Program Code',
        'value'=>$this->prepareProgramCode($xml)
        ];

    $related_content = $this->prepareRelatedContent($xml);
    if(!empty($related_content)){
      $figshare->related_materials= $related_content;
    }

    // $figshare->equipment_or_software_used = $this->prepareEquipmentOrSoftware($xml);

    // funding_list.
    $figshare->funding_list = $this->prepareFundrefProject($xml);

    // $figshare->adcProject = array_merge($figshare->adcProject,
    //     $this->prepareGeodataProjectName($xml));

    $figshare->group_id = $config_json->group_id;
    $figshare->custom_fields_list[]= [
      'name'=>'Theme',
      'value'=>[$config_json->Geospatial]
      ];
    return $figshare;
}
protected function prepareBody($xml) {
   $desc="";
   $descNodeList = $xml->xpath('gmd:identificationInfo/gmd:MD_DataIdentification/gmd:abstract/gco:CharacterString/text()');
    // Check if the XPath query returned any nodes
    if (!empty($descNodeList)) {
        // Get the last element from the list using array_pop
        $desc = (string) array_pop($descNodeList);
        // Assign the title to the $figshare object
       
    }
    return $desc;
  }

  /**
   * Prepares Created (timestamp).
   */
protected function prepareCreated($xml) {
  $r = '';
  $created = $xml->xpath("//gmd:MD_DataIdentification/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date/gmd:date[../gmd:dateType/gmd:CI_DateTypeCode[@codeListValue='publication']]/*/text()");
  if (is_array($created) && count($created)) {
    $r = $created[0]->__toString();
    $r = date("Y-m-d", strtotime($r. ' +1 day'));
  }
  return $r;
}


  /**
   * Prepares Modified (timestamp).
   */
protected function prepareModified($xml) {
  $r = '';
  $modified = $xml->xpath("//gmd:MD_DataIdentification/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date/gmd:date[../gmd:dateType/gmd:CI_DateTypeCode[@codeListValue='revision']]/*/text()");

  if (is_array($modified) && count($modified)) {
    $r = $modified[0]->__toString();
    $r = date("Y-m-d", strtotime($r. ' +1 day'));
  }
  return $r;
}


  /**
   * Prepares Frequency.
   */
protected function prepareFrequency($xml) {
  $r = '';
  $chunks = $xml->xpath('//gmd:resourceMaintenance//gmd:MD_MaintenanceInformation//gmd:MD_MaintenanceFrequencyCode');
  if (is_array($chunks) && count($chunks)) {
    $chunk = $chunks[0];
    $chunk = $chunk->attributes()->codeListValue->__toString();
    $chunk = strtolower($chunk);
    $sources=getFrequencyMapping();
    if (in_array($chunk, $sources)) {
      $r = $chunk;
    }elseif (array_key_exists($chunk, $sources)) {
      $r = $sources[$chunk];
    }
  }
  return $r;
}

  /**
   * Prepares User Supplied Tags for the field_tags field.
   *
   * XPATH: gmd:descriptiveKeywords/gmd:MD_Keywords/gmd:keyword WHEN
   * gmd:thesaurusName/gmd:CI_Citation/gmd:title = "NASA Global Change Master
   * Directory (GCMD) Earth Science Keywords"  OR
   * gmd:thesaurusName/gmd:CI_Citation/gmd:title = "Keyword Thesaurus Title"
   *
   * @param $xml
   *   XML object.
   *
   * @return Tags Array.
   */
protected function prepareRowTags($xml) {

  $keywords = $this->retreiveKeywords($xml, array('theme', ''), array(
    'NASA Global Change Master Directory (GCMD) Earth Science Keywords',
    'Keyword Thesaurus Title',
  ));
  $keywords = array_merge($keywords,
      $this->retreiveTopicCategories($xml));
  $npTags = $this->prepareDataSourceAffiliation($xml);
  $npTags = array_merge($npTags, $this->prepareNationalProjectCode($xml));
  $keywords = array_unique(array_merge($keywords, $npTags));
  
  
  return array_values($keywords);
}
protected function prepareRowNPtags($xml) {

  $npTags = $this->prepareDataSourceAffiliation($xml);
  $npTags = array_merge($npTags, $this->prepareNationalProjectCode($xml));
  return $npTags;
}
  /**
   * Prepares Temporal Coverage.
   */
protected function prepareTemporalCoverage($xml) {
  $temporalCoverage = array();
  // First try begin/end pair.
  // $descNodeList = $xml->xpath('gmd:identificationInfo/gmd:MD_DataIdentification/gmd:abstract/gco:CharacterString/text()');
  $chunks = $xml->xpath('//gmd:temporalElement//gml:TimePeriod');
  if (is_array($chunks) && count($chunks)) {
    $chunk = array_pop($chunks);    
    // There are two possible expressions of begin/end pairs.
    if ($this->stringIfExists($chunk, 'gml:beginPosition')) {
      $temporalCoverage = array(
        'begin' => $this->stringIfExists($chunk, 'gml:beginPosition'),
        'end' => $this->stringIfExists($chunk, 'gml:endPosition'),
      );
    }
    elseif ($this->stringIfExists($chunk, 'gml:begin/gml:TimeInstant/gml:timePosition')) {
      $temporalCoverage = array(
        'begin' => $this->stringIfExists($chunk, 'gml:begin/gml:TimeInstant/gml:timePosition'),
        'end' => $this->stringIfExists($chunk, 'gml:end/gml:TimeInstant/gml:timePosition'),
      );
    }
  }
  // Next check for single/ongoing value.
  else {
    $chunks = $xml->xpath('//gmd:extent/gmd:EX_Extent/gmd:temporalElement//gml:TimeInstant');
    if (is_array($chunks) && count($chunks)) {
      $chunk = array_pop($chunks);
      $temporalCoverage['begin'] = $this->stringIfExists($chunk, '//gml:timePosition');
    }
  }

  return $temporalCoverage;
}

  /**
   * Prepares Resources.
   */
protected function prepareRowResources($xml) {
  $resources = array();

  // First Resources XPath.
  $distributors = $xml->xpath('//gmd:distributionInfo//gmd:distributor/gmd:MD_Distributor');

  foreach ($distributors as $key => $distributor) {

    // Format.
    $format_xml = $distributor->xpath('gmd:distributorFormat//gmd:MD_Format//gmd:name//gco:CharacterString/text()');
    $format = NULL;
    if (is_array($format_xml) && count($format_xml)) {
      $format = (String) array_pop($format_xml);
      switch ($format) {
        case 'Webpage':
          $format = 'HTML';
          break;

        case 'undefined':
          $format = NULL;
          break;
      }
    }

    foreach ($distributor->xpath('gmd:distributorTransferOptions//gmd:CI_OnlineResource') as $resource) {
      // URL.
      $url_xml = $resource->xpath('gmd:linkage/gmd:URL/text()');
      $url = '';

      if (isset($url_xml[0])) {
        $url = (String) array_pop($url_xml);
      }

      // Description.
      $description = $resource->xpath('gmd:description//gco:CharacterString/text()');
      $description = count($description) ? (String) $description[0] : '';

      // Title.
      $title = $resource->xpath('gmd:name//gco:CharacterString/text()');
      $title = count($title) ? (String) $title[0] : 'Website Pointer to ' . (isset($format) ? $format . ' file' : $url);

      list($status, $return) = self::prepareResourceHelper(
            $url,
            $format,
            $title,
            time(),
            $description
        );
      if ($status == TRUE) {
        $resources[] = $return;
      }
      else {
        $this->reportMessage($return);
      }
    }
  }

  // Second Resources XPath.
  $resources_xpath = $xml->xpath(
      '//gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource'
    );

  foreach ($resources_xpath as $key => $resource) {
    // URL.
    $url_xml = $resource->xpath('gmd:linkage/gmd:URL/text()');
    $url = '';
    if (isset($url_xml[0])) {
      $url = (String) array_pop($url_xml);
    }

    // Format undefined for this XPath
    // Try to guess the format by the extentions at the end of the url.
    // We are dealing with links so default to html.
    // Do not add any new format.
    $format = 'html';
    preg_match('/\.[^\.]+$/i', $url, $ext);
    $ext = ltrim($ext[0], '.');
    $ext_term = taxonomy_get_term_by_name($ext, 'format');
    if ($ext && $ext_term) {
      $format = $ext;
    }

    // Description.
    $description = $resource->xpath('gmd:description//gco:CharacterString/text()');
    $description = count($description) ? (String) $description[0] : '';

    // Title.
    $title = $resource->xpath('gmd:name//gco:CharacterString/text()');
    $title = count($title) ? (String) $title[0] : 'Website Pointer to ' . (isset($format) ? $format . ' file' : $url);

    list ($status, $return) = self::prepareResourceHelper(
          $url,
          $format,
          $title,
          time(),
          $description
      );
    if ($status == TRUE) {
      $resources[] = $return;
    }
    else {
      $this->reportMessage($return);
    }
  }
  return $resources;
}

  /**
   * Get contact name from xml object.
   *
   * @param: XML file object
   *
   * @return: Array with keyed Contact name and email (string).
   */
protected function prepareContact($xml, $allowFallback = TRUE) {
  $contacts = array();

  // Look for pointOfContacts and fallback to the identificationInfo after.
  $xpaths = array(
    'main' => '//gmd:pointOfContact/gmd:CI_ResponsibleParty',
    'fallback' => '//gmd:identificationInfo/gmd:MD_DataIdentification/gmd:citation/gmd:CI_Citation/gmd:citedResponsibleParty/gmd:CI_ResponsibleParty',
  );

  foreach ($xpaths as $xpath_key => $xpath) {
    $responsibleParties = $xml->xpath($xpath);

    foreach ($responsibleParties as $index => $responsibleParty) {
      $item = $this->parseCI_ResponsibleParty($responsibleParty);
      if (empty($item['individualName']) && empty($item['organisationName'])) {
        // No name. Skip.
        continue;
      }

      if (empty($item['email'])) {
        // No email. Skip.
        continue;
      }

      $item['_xpath_key'] = $xpath_key;

      $contacts[$item['role']][] = $item;
    }
  }
  
  if (empty($contacts)) {
    // Couldn't find any suitable CI_ResponsibleParty.
    return FALSE;
  }
  // Check for suitable CI_ResponsibleParty on the main xpath.
  foreach (array(
    'author',
    'originator',
    'principalInvestigator',
    'pointOfContact'
  ) as $role) {
    if (isset($contacts[$role])) {
      foreach ($contacts[$role] as $contact) {
        if ($contact['_xpath_key'] == 'main') {
          return $contact;
        }
      }
    }
  }


  // We should not be here. Actually.
  return FALSE;
}

  /**
   * Prepares spatial field.
   *
   * @param object $xml
   *   SimpleXMLElement source record.
   *
   * @return array
   *   for field_spactial.
   */
protected function prepareSpatial($xml) {
  $coordinates = array(
    'west' => '//gmd:identificationInfo/gmd:MD_DataIdentification/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:westBoundLongitude/gco:Decimal/text()',
    'east' => '//gmd:identificationInfo/gmd:MD_DataIdentification/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox//gmd:eastBoundLongitude/gco:Decimal/text()',
    'south' => '//gmd:identificationInfo/gmd:MD_DataIdentification/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox//gmd:southBoundLatitude/gco:Decimal/text()',
    'north' => '//gmd:identificationInfo/gmd:MD_DataIdentification/gmd:extent/gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox//gmd:northBoundLatitude/gco:Decimal/text()',
  );
  return $this->processSpatialData($xml, $coordinates);
}
protected function prepareTheme($xml) {
  $vocabulary = 'iso_topic_category_codes';
  $topicCategories = $this->retreiveTopicCategories($xml);
  $themes = array();
  foreach ($topicCategories as $topicCategorie) {
    $themes[] = (String) $topicCategorie;
  }
  return $themes;
}
protected function processSpatialData(SimpleXMLElement $xml, array $coordinates) {
  $geojson=[];
  $geojson["type"]="FeatureCollection";
  $geojson["features"]=[];
  foreach ($coordinates as $key => $value) {
    $coordinates[$key] = $xml->xpath($coordinates[$key]);
    if (!isset($coordinates[$key])) {
    //   $coordinates[$key] = $coordinates[$key]->__toString();
    // }
    // else {
      return $spatial;
    }
  }
  $count=count($coordinates['west'])-1;
  while($count>=0){
    $spatial = [];
    $spatial['type'] = 'Feature';
    $spatial['geometry'] = array("type"=>"", "coordinates"=>array());
    $spatial['properties'] = (object)array();
    if ($coordinates['west'][$count] != $coordinates['east'][$count] && $coordinates['north'][$count] != $coordinates['south'][$count]) {
      $spatial['geometry'] =array("type"=>"Polygon", "coordinates"=>array(array(
          array(floatval($coordinates['west'][$count]), floatval($coordinates['north'][$count])),
          array(floatval($coordinates['west'][$count]), floatval($coordinates['south'][$count])),              
          array(floatval($coordinates['east'][$count]), floatval($coordinates['south'][$count])),
          array(floatval($coordinates['east'][$count]), floatval($coordinates['north'][$count])),
          array(floatval($coordinates['west'][$count]), floatval($coordinates['north'][$count])),
      )));

    }
    else {
        $spatial['geometry'] =array("type"=>"Point", "coordinates"=>array(floatval($coordinates['west'][$count]), floatval($coordinates['north'][$count])));
    }
    $geojson["features"][]=$spatial;
    $count-=1;
  }
  
  return json_encode($geojson);
}
  /**
   * Prepare license.
   *
   * @param SimpleXMLElement $xml
   *   XML ISO documnet.
   *
   * @return appropriate field_license value.
   */
protected function prepareRowLicense($xml) {
  // field_license keys supported in USDA ISO LTAR.
  $license_mapping = array(
    'cc-by' => 50,
    'cc-by-sa' => 58,
    'cc-zero' => 2,
    'gfdl' => 119,
    'notspecified' => 148,
    'other-pd' => 148,
    'us-pd' => 148,
    'other-open' => 149,
  );

  // Use this value if nothing is found.
  $license_default = 50;
  // Use this value if the value found is not supported.
  $license_notsupported = 50;

  // This XPATH is our firsh license condidate:
  // `gmd:resourceConstraints/gmd:MD_LegalConstraints/gmd:useConstraints`.
  $license_found = FALSE;
  $items = $xml->xpath('//gmd:resourceConstraints/gmd:MD_LegalConstraints/gmd:useConstraints/gmd:MD_RestrictionCode');
  // We should have only one result.
  $license_xml = (String) array_pop($items);
  if (!empty($license_xml) && $license_xml !== "otherRestrictions") {
    // @see https://github.com/NuCivic/usda-nal/issues/871#issuecomment-217445593
    // the "otherRestrictions" is the default if no license is set. We ignore
    // any value that contains it.
    $license_found = TRUE;
    if (array_key_exists($license_xml, $license_mapping)) {
      return $license_xml;
    }
    elseif ($license = array_search($license_xml,
          $license_mapping) !== FALSE) {
      return $license;
    }
  }

  // If nothing is found in the first XPATh. Look for
  // `gmd:descriptiveKeywords/gmd:MD_Keywords/gmd:keyword WHEN
  // gmd:thesaurusName/gmd:CI_Citation/gmd:title = "Creative Commons license"`.
  $items = $this->retreiveKeywords($xml, array(), array(
    'Creative Commons license',
    'Ag Data Commons Data License Type',
  ));

  $license_xml = (String) array_pop($items);
  if (!empty($license_xml) && $license_xml !== "otherRestrictions") {
    // @see https://github.com/NuCivic/usda-nal/issues/871#issuecomment-217445593
    // the "otherRestrictions" is the default if no license is set. We ignore
    // any value that contains it.
    $license_found = TRUE;
    if (array_key_exists($license_xml, $license_mapping)) {
      return $license_xml;
    }
    else {
      $license_mapping = array_flip($license_mapping);
      if (array_key_exists($license_xml, $license_mapping)) {
        return $license_xml;
      }
    }
  }

  // No license found. Try to return a default options.
  $license_return = $license_found ? $license_notsupported : $license_default;
  return $license_return;
}

  /**
   * Prepares publisher.
   */
protected function preparePublisher($xml) {
  $xmlCI_ResponsibleParties = $xml->xpath(
    '//gmd:identificationInfo/gmd:MD_DataIdentification/gmd:citation//gmd:citedResponsibleParty/gmd:CI_ResponsibleParty'
  );
  
  $r = "";

  foreach ($xmlCI_ResponsibleParties as $responsibleParty) {
    $publisher = $this->parseCI_ResponsibleParty($responsibleParty);
    if ($publisher['role'] == 'publisher') {
      if (!empty($publisher['individualName'])) {
        $r = $publisher['individualName'];
        break;
      } else if (!empty($publisher['organisationName'])) {
        $r = $publisher['organisationName'];
        break;
      }
    }
  }
  if (empty($r)) {
    $r = "Publisher Not Specified";
  }
  return $r;
}
protected function prepareSpatialGeographicalCover($xml) {
  $r = array();
  // Get any place type.
  $locations = $this->retreiveKeywords($xml, array('place'));
  foreach ($locations as $key => $location) {
    $location = explode('>', $location);
    $location = end($location);
    if (strpos($location, '000000000000') === FALSE) {
      $location = ucwords(strtolower(trim($location)));
      $r[] = $location;
    }
  }
  return implode(', ', $r);
}

  /**
   * Prepare authors.
   *
   * @param \SimpleXMLElement $xml
   * @param bool $author_is_organization
   * @return array|\CI_ResponsibleParty
   */
protected function prepareRowAuthors(SimpleXMLElement $xml, &$author_is_organization = FALSE) {
  $xmlCI_ResponsibleParties = $xml->xpath(
    '//gmd:identificationInfo/gmd:MD_DataIdentification/gmd:citation//gmd:citedResponsibleParty/gmd:CI_ResponsibleParty'
  );

  $authors = array();

  $authors = $this->processResponsiblePartiesToAuthors($xmlCI_ResponsibleParties,
      $authors, $author_is_organization);

  $r = array();

  $precedences = array(
    'author',
    'originator',
    'principalInvestigator',
    'organization',
  );
  foreach ($precedences as $role) {
    if (isset($authors[$role])) {
      $r = array_merge($r, array_values($authors[$role]));
    }
  }

  $authors = $r;

  if (!$authors || empty($authors)) {

    $pointOfContact = $this->prepareContact($xml, FALSE);

    if ($pointOfContact) {
      $authors[] = [
        'organisationName' => $pointOfContact['organisationName'],
        'individualName' => $pointOfContact['individualName'],
        'email' => $pointOfContact['email'],
        'role' => $pointOfContact['role'],
      ];

      if ($pointOfContact['individualName'] == "") {
        $author_is_organization = TRUE;
      }
      else {
        $author_is_organization = FALSE;
      }
    }
    else {

      $dataSourceAffiliation = $this->prepareGeodataProjectName($xml);

      if (!empty($dataSourceAffiliation)) {

        foreach ($dataSourceAffiliation as $dsa) {
          $authors[] = [
            'organisationName' => $dsa,
            'individualName' => '',
            'email' => ''
          ];
        }
        $author_is_organization = TRUE;
      }
    }

  }
  $res=[];
  foreach($authors as $author){
    if($author["individualName"]!==""){
      $pattern = '/(\d{4}-\d{4}-\d{4}-\d{3}[\dXx])/';
      if(isset($author['individualIdType'])&&$author['individualIdType']=='orcid'&&preg_match($pattern, $author['individualIdType'], $matches)){
        
          $orcid = $matches[1];
          $res[]=[
            "name"=>$author["individualName"],
            "orcid_id"=>$orcid,
          ];
        
      }else{
        $res[]=[
          "name"=>$author["individualName"]
        ];
      }
      
    }else{
      $res[]=[
        "name"=>$author["organisationName"]
      ];
    }
  }
  return $res;

}

  /**
   * Scans for any keywords using the NPXXX format from any thesaurus
   * and adds them to a list.
   *
   * @param SimpleXMLElement $xml
   *   Row XML object.
   *
   * @return array
   *   An array of keywords.
   */
protected function prepareNationalProjectCode(SimpleXMLElement $xml) {
  $keywords = $this->retreiveKeywords($xml, array(), array());
  $keywords = array_filter($keywords, function ($item) {
      return preg_match("/NP\d+/", $item);
  });

  return $keywords;
}

  /**
   *
   */
protected function prepareProgramCode($xml) {
  $program_codes = getProgramCodes();

  $keywords = $this->retreiveKeywords($xml, ['theme'], ['Federal Program Inventory']);

  if (!empty($keywords)) {
    $program_name_raw = array_pop($keywords);
    $program_parts = explode(' > ', $program_name_raw);
    $program_name_raw = array_pop($program_parts);
    if (preg_match("#(\d{3}\:\d{1,3})$#i", $program_name_raw,
        $matched_codes)) {
      $matched_code_parts = explode(":", $matched_codes[1]);
      $matched_code = $matched_code_parts[0] . ":" . substr($matched_code_parts[1], -3);
      if (in_array($matched_code, array_keys($program_codes))) {
        return [$program_codes[$matched_code]];
      }
    }

  }
}

  /**
   * @param $xml
   * @return int|string
   */
protected function prepareBureauCode($xml) {
  $bureau_codes = getBureauCodes();

  $keywords = $this->retreiveKeywords($xml, ['theme'], ['OMB Bureau Codes']);

  if (!empty($keywords)) {
    $bureau_code_name_raw = array_pop($keywords);

    $bureau_parts = explode(" > ", $bureau_code_name_raw);
    $bureau_code_name_raw = array_pop($bureau_parts);
    if (preg_match("#(\d{3}\:\d{1,3})$#i", $bureau_code_name_raw, $matched_codes)) {
      $matched_code_parts = explode(":", $matched_codes[1]);
      $matched_code = $matched_code_parts[0] . ":" . substr($matched_code_parts[1], -2);
      if (in_array($matched_code, array_keys($bureau_codes))) {
        return [$bureau_codes[$matched_code]];
      }
    }
  }
}

  /**
   * Prepares Prefered Citation.
   */
protected function preparePreferedCitation($xml) {
  return "";
}
protected function prepareDataSourceAffiliation($xml) {
  // Third level values USDA > ARS > {value} mapped to NP Code.
  $programMap = array(
    '101 Food Animal Production' => '101',
    '103 Animal Health' => '103',
    '104 Veterinary, Medical, and Urban Entomology' => '104',
    '106 Aquaculture' => '106',
    '301 Plant Genetic Resources, Genomics and Genetic Improvement' => '301',
    '303 Plant Diseases' => '303',
    '304 Crop Protection and Quarantine' => '304',
    '305 Crop Production' => '305',
    '211 Water Availability and Watershed Management' => '211',
    '212 Soil and Air' => '212',
    '213 Biorefining' => '213',
    '214 Agricultural and Industrial Byproducts' => '214',
    '215 Pasture, Forage and Rangeland Systems' => '215',
    '216 Agricultural System Competitiveness and Sustainability' => '216',
    '107 Human Nutrition' => '107',
    '108 Food Safety (animal and plant products)' => '108',
  );
  $separator = ' > ';
  $keywords = $this->retreiveKeywords($xml, array(),
      array('Data Source Affiliation'));

  if (!empty($keywords)) {
    $npCodes = array();
    foreach ($keywords as $keyword) {
      if (strpos($keyword, $separator) !== FALSE) {
        $keywordParts = explode($separator, $keyword);
        $keyword = end($keywordParts);
      }
      if (array_key_exists($keyword, $programMap)) {
        $npCodes[] = $programMap[$keyword];
      }
    }
    return $npCodes;
  }

  return array();
}

  /**
   * Find the DOI.
   *
   * @param SimpleXMLElement $xml
   *   Source XML documnet.
   *
   * @return string|bool
   *   DOI found. Or FALSE in none found.
   */
protected function prepareRowDOI($xml) {
  // Base XPath.
  $xpath = "//gmd:identifier/gmd:MD_Identifier";
  $identifiers = $xml->xpath($xpath);
  foreach ($identifiers as $identifier) {
    $identifierTitle = $this->stringIfExists($identifier,
        'gmd:authority/gmd:CI_Citation/gmd:title/gco:CharacterString/text()');
    if ($identifierTitle != "Dataset DOI") {
      // Not what we want.
      continue;
    }
    
    // Get thegmd:identifier / gmd:MD_Identifier / gmd:authority / gmd:code.
    $doi = $this->stringIfExists($identifier,
        'gmd:code/gco:CharacterString/text()');
    // Sometimes the doi will be preceded with "doi:". Strip that out.
    $doi = str_replace("doi:", "", $doi);
    // If the DOI is valid then return it.
    if (validateDoi($doi)) {
      return $doi;
    }
  }

  // No matching DOI found :(.
  return FALSE;
}

  /**
   * Prepares Project names from Geodata Data Source Affiliation keywords.
   *
   * Returns a list of project names suitable for use by Ag Data Commons.
   *
   * ISO Path: gmd:descriptiveKeywords/gmd:MD_Keywords/gmd:keyword WHEN
   * gmd:thesaurusName/gmd:CI_Citation/gmd:title = "Data Source Affiliation"
   *
   * @param $xml
   *   Xml document to parse
   *
   * @return array
   */
protected function prepareGeodataProjectName($xml) {
  // Map keyword substrings to project names.
  $ltarProjectMap = array(
    "Archbold Biological Station" => "Archbold Biological Station",
    "Archbold Biological Station > Buck Island Ranch" => "Archbold Biological Station",
    "Archbold Biological Station > Range Cattle Research > Education Center, UF" => "Archbold Biological Station",
    "Central Mississippi River Basin" => "Central Mississippi River Basin",
    "Central Plains Experimental Range" => "Central Plains",
    "R . J . Cook Agronomy Farm" => "Cook Agronomy Farm",
    "R.J. Cook Agronomy Farm" => "Cook Agronomy Farm",
    "Eastern Corn Belt" => "Eastern Corn Belt",
    "Great Basin" => "Great Basin",
    "Gulf Atlantic Coastal Plain" => "Gulf Atlantic Coastal Plain",
    "Jornada Experimental Range" => "Jornada Experimental Range",
    "Kellogg Biological Station" => "Kellogg Biological Station",
    "Lower Chesapeake Bay" => "Lower Chesapeake Bay",
    "Lower Chesapeake Bay > Lower Chesapeake Bay Choptank Section" => "Lower Chesapeake Bay",
    "Lower Mississippi River Basin" => "Lower Mississippi River Basin",
    "Northern Plains" => "Northern Plains",
    "Platte River - High Plains Aquifer" => "Platte River-High Plains Aquifer",
    "Southern Plains" => "Southern Plains",
    "Texas Gulf" => "Texas Gulf",
    "Upper Mississippi River Basin" => "Upper Mississippi River Basin",
    "Upper Mississippi River Basin > Saint Paul Office" => "Upper Mississippi River Basin",
    "Upper Mississippi River Basin > Swan Lake Research Farm" => "Upper Mississippi River Basin",
    "Upper Chesapeake Bay" => "Upper Chesapeake Bay",
    "Walnut Gulch Experimental Watershed" => "Walnut Gulch Experimental Watershed",
    "Walnut Gulch Experimental Watershed > Santa Rita Experimental Range" => "Walnut Gulch Experimental Watershed",
  );

  $keywords = $this->retreiveKeywords($xml, array(),
      array('Data Source Affiliation'));

  $vocab = taxonomy_vocabulary_machine_name_load('adc_project');

  if (!empty($keywords)) {
    $geoDataProjects = [];
    foreach ($keywords as $keyword) {
      // Strip out prefix;.
      $keyword = str_replace("United States Department of Agriculture > Agricultural Research Service > Long-Term Agroecosystem Research > ",
          "", $keyword);

      $keyword = trim($keyword);

      if (array_key_exists($keyword, $ltarProjectMap)) {
        $geoDataProjects[] = "Long-Term Agroecosystem Research - LTAR";
        $geoDataProjects[] = $ltarProjectMap[$keyword];
      }
    }
    return $geoDataProjects;
  }

  return array();
}

  /**
   * Helper for "Related To" ADC Field.
   *
   * Type: Title/URL; multiple allowed
   * Explanation: A link to an outside resource that provides additional context
   *  to the dataset, e.g. a project website, manual, or documentation.
   * Machine name: 
   * XPath: gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:linkage/gmd:URL
   * WHEN gmd:description = "project website" or "documentation"
   */
protected function prepareRelatedContent($xml) {
  $r = array();
  $items = $xml->xpath('//gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource');
  if (is_array($items) && count($items)) {
    foreach ($items as $item) {
      $description_xml = $item->xpath('gmd:description/gco:CharacterString');
      
        $url_xml = $item->xpath('gmd:linkage/gmd:URL');
        $title_xml = $item->xpath('gmd:name/gco:CharacterString');

        // No point in having a link with title but no url.
        if (isset($url_xml[0]) && $url_xml[0]->__toString() &&
            (isset($title_xml[0])||isset($description_xml[0]))) {
          $r[] = array(
            'identifier' => $url_xml[0]->__toString(),
            'title' => isset($title_xml[0])?$title_xml[0]->__toString():$description_xml[0]->__toString(),
            'relation' => "IsSupplementTo",
            'identifier_type' => "URL",
          );
        }
    }
  }
  return $r;
}

  /**
   * Get fundref sourcea and progam codes.
   *
   * @param $xml
   *   XML source document.
   *
   * @return Array of fundref projects each project is an keyed array  with
   *   'source' and 'project_number'.
   */
protected function prepareFundrefProject($xml) {
  $fundref_project = [];
  $fundref_keywords = $this->retreiveKeywords($xml, ['theme'], ['FundRef', 'Crossref Funding']);
  // $this->sanitizeTermList($fundref_keywords, 'dkan_sci_fundref');

  foreach ($fundref_keywords as $fundref_keyword) {
    // if(strpos($fundref_keyword,"; grant=")>0){
    //   $ary = explode("; grant=",$fundref_keyword);
    //   $fundref_project[]=array(
    //     "title"=>$ary[0],
    //     "grant_code"=>$ary[1],
    //   );
    // }else{
      $fundref_project[]=array(
        "title"=>$fundref_keyword
      );
    // }
    
  }

  return $fundref_project;
}
protected function stringIfExists(SimpleXMLElement $element, $xpath, $as_html=FALSE) {
  $result = $element->xpath($xpath);
  $return_string = '';
  if (!empty($result)) {
    foreach ($result as $item) {
      if ($as_html) {
        $return_string .= '<p>' . (string)$item . '</p>';
      } else {
        $return_string .= (string)$item . ' ';
      }
    }
  }
  return trim($return_string);
}
protected function insertAfter(SimpleXMLElement $new, SimpleXMLElement $target) {
  $target = dom_import_simplexml($target);
  $new    = $target->ownerDocument->importNode(dom_import_simplexml($new), true);
  if ($target->nextSibling) {
      $target->parentNode->insertBefore($new, $target->nextSibling);
  } else {
      $target->parentNode->appendChild($new);
  }
}
  /**
   * Get instrument for the field_equipment_or_software_used field.
   */
protected function prepareEquipmentOrSoftware($xml) {
  $instruments = [];
  $instrument_keywords = $this->retreiveKeywords($xml, ['instrument']);

  foreach ($instrument_keywords as $instrument_keyword) {
    $instruments[] = [
      'url' => '',
      'title' => ucwords(strtolower(trim($instrument_keyword))),
    ];
  }

  return $instruments;
}

  /**
   * Get Primary or Related citation Article.
   *
   * @param $xml
   *   XML Document to parse.
   * @param $thesaurus_doi
   *   TRUE if we are looking for the primary article.
   *
   * @return $citation_article to use for field_primary_article or
   *   field_article_citation_col with 'doi' and 'citation', or empty if the
   *   $thesaurus_doi is not provided or no value is found.
   */
protected function prepareCitationArticle($xml, $thesaurus_doi = NULL, $thesaurus_pubag = NULL) {
  $citation_article = array();

  $items = $xml->xpath('//gmd:descriptiveKeywords/gmd:MD_Keywords');

  if (is_array($items)) {
    foreach ($items as $item) {
      // We only expect one value here. If the xpath returned is not an
      // array, array_pop will return NULL.
      $item_value = (String) array_pop($item->xpath('gmd:keyword/gco:CharacterString/text()'));
      $item_thesaurus = (String) array_pop($item->xpath('gmd:thesaurusName//gmd:title/gco:CharacterString/text()'));

      // Look for the DOI.
      if (isset($thesaurus_doi)
          && $thesaurus_doi == $item_thesaurus
          && isset($item_value)
          && !empty($item_value)) {
        // Clean and Validate the DOI.
        // Sometimes the DOIs in the XML document will be prefixed with "doi:".
        $item_value = str_replace("doi:", "", $item_value);
        if (validateDoi($item_value)) {
          $citation_article['doi'] = $item_value;
          // $citation_article['citation'] = $this->getCitationFromDOI($item_value);
        }
      }
      // Look for the PubAG.
      elseif (isset($thesaurus_pubag)
          && $thesaurus_pubag == $item_thesaurus
          && isset($item_value)) {

        $matches = array();

        if (preg_match('@/?(\d+)$@', $item_value, $matches)) {
          $citation_article['pubag'] = $matches[1];
        }
      }
    }
  }

  return $citation_article;
}


  /**
   * Get gmd:topicCategory keywords.
   */
protected function retreiveTopicCategories($xml) {
  $r = array();

  $items = $xml->xpath('//gmd:topicCategory/gmd:MD_TopicCategoryCode/text()');
  if (is_array($items) && count($items)) {
    foreach ($items as $item) {
      $r[] = (String) $item;
    }
  }

  return $r;
}

  /**
   * Filters keywords by gmd:type.
   */
protected function retreiveKeywords($xml, $type = array('theme'), $thesaurus = array()) {
  $r = array();
  $items = $xml->xpath('//gmd:descriptiveKeywords/gmd:MD_Keywords');
  if (is_array($items) && count($items)) {
    foreach ($items as $key => $item) {
      $item_type = '';
      $itemTypeXml = $item->xpath('gmd:type/gmd:MD_KeywordTypeCode');
      if (!empty($itemTypeXml)) {
        // Only support one type.              
        $itemTypeXml = array_pop($itemTypeXml);
        $item_type = (String) $itemTypeXml->attributes()->codeListValue;
      }

      $in_type = FALSE;
      $in_thesaurus = FALSE;

      // If $type is empty, any type will do.
      if (!count($type)) {
        $in_type = TRUE;
      }
      else {
        $in_type = in_array($item_type, $type);
      }

      // If $thesaurus is empty, any thesaurus will do.
      if (!count($thesaurus)) {
        $in_thesaurus = TRUE;
      }
      else {
        $item_thesaurus = $item->xpath('gmd:thesaurusName//gmd:title/gco:CharacterString/text()');
        if (count($thesaurus) && count($item_thesaurus)) {
          $item_thesaurus = $item_thesaurus[0]->__toString();
          $in_thesaurus = in_array($item_thesaurus, $thesaurus);
        }
      }

      if ($in_type && $in_thesaurus) {
        $keywords = $item->xpath('gmd:keyword/gco:CharacterString/text()');
        foreach ($keywords as $keyword) {
          $r[] = $keyword->__toString();
        }
      }
    }
  }
  return $r;
}

  /**
   * Parse a single CI_ResponsibleParty SimpleXMLElement.
   */
protected function parseCI_ResponsibleParty(SimpleXMLElement $xmlCI_ResponsibleParty) {
  $responsible_party = array();

  $responsible_party['organisationName'] = $this->stringIfExists(
    $xmlCI_ResponsibleParty,
    'gmd:organisationName/gco:CharacterString/text()'
  );
  $individual_name_string = $this->stringIfExists(
    $xmlCI_ResponsibleParty,
    'gmd:individualName/gco:CharacterString/text()'
  );
  if (strpos($individual_name_string, ';') !== false) {
    list($temp_individual_name, $individual_id_string) = explode(';', $individual_name_string);
  } else {
      $temp_individual_name = $individual_name_string;
      $individual_id_string = ''; // Or set a default value
  }
  // list($temp_individual_name, $individual_id_string) = explode(';', $individual_name_string);
  $responsible_party['individualName'] = $temp_individual_name;

  if (!empty($individual_id_string)) {
    list($individual_id_type, $individual_id) = explode('=', $individual_id_string);
    if(!empty($individual_id_type)){
      $individual_id_type = trim(filter_var($individual_id_type, FILTER_SANITIZE_STRING));
      $responsible_party['individualIdType'] = $individual_id_type;
    }
    if(!empty($individual_id)){
      $individual_id = trim(filter_var($individual_id, FILTER_SANITIZE_STRING));
      $responsible_party['individualId'] = $individual_id;
    }
  }

  $responsible_party['email'] = $this->stringIfExists(
    $xmlCI_ResponsibleParty,
    'gmd:contactInfo//gmd:electronicMailAddress/gco:CharacterString/text()'
  );
  $responsible_party['role'] = $this->stringIfExists(
    $xmlCI_ResponsibleParty,
    'gmd:role/gmd:CI_RoleCode/@codeListValue'
  );
  return $responsible_party;
}

  /**
   * @param $xmlCI_ResponsibleParties
   * @param $authors
   * @return array
   */
protected function processResponsiblePartiesToAuthors(
  $xmlCI_ResponsibleParties,
  $authors,
  &$author_is_organization
) {
  foreach ($xmlCI_ResponsibleParties as $responsibleParty) {
    $author = $this->parseCI_ResponsibleParty($responsibleParty);
    // We need a name.
    if (empty($author['individualName']) && empty($author['organisationName'])) {
      continue;
    }

    // Invert the individualName if we have it.
    if (!empty($author['individualName'])) {
      $author['individualName'] = $author['individualName'];
    }

    // Where there is no name or email for Responsible Party can use
    // organization name and ensure that the organization flag is checked.

    if (empty($author['individualName']) && empty($author['email'])) {
      $author_is_organization = TRUE;
    }

    $authors[$author['role']][] = $author;
  }
  return $authors;
}
protected function prepareAdcHierarchy($xml) {
  $separator = ' > ';
  $vocabulary = 'adc_hierarchy';
  $keywords = $this->retreiveKeywords($xml, array('theme'),
      array('Ag Data Commons Keywords'));

  if (!empty($keywords)) {
    $hierarchy = array();
    foreach ($keywords as $keyword) {
      if (strpos($keyword, $separator) != FALSE) {
        $keyword = end(explode($separator, $keyword));
      }
      $hierarchy[] = intval($keyword);
    }
    $hierarchy = array_unique($hierarchy);
    return $hierarchy;
  }

  // Empty if nothing found.
  return array();
}
protected function prepareAdcNALT($xml) {
  $separator = ' > ';
  $vocabulary = 'adc_hierarchy';
  $keywords = $this->retreiveKeywords($xml, array('theme'),
      array('National Agricultural Library Thesaurus (NALT)'));

  if (!empty($keywords)) {
    $hierarchy = array();
    foreach ($keywords as $keyword) {
      if (strpos($keyword, $separator) != FALSE) {
        $keyword = end(explode($separator, $keyword));
      }
      $hierarchy[] = $keyword;
    }
    return $hierarchy;
  }

  // Empty if nothing found.
  return array();
}
}