<?php

 function validateDoi($doi) {
    $valid = FALSE;
    $url = 'http://doi.org/api/handles/' . $doi;
    $response = file_get_contents($url);
    $response=json_decode($response);
    if ($response->responseCode == '1') {
    $valid = TRUE;
    }
    return $valid;
  }
  function getProgramCodes(){
    return array(
      "005:000"=>"005:000 - (Primary Program Not Available)",
      "005:001"=>"005:001 - Rural Business Loans",
    "005:002"=>"005:002 - Rural Business Grants",
    "005:003"=>"005:003 - Energy Assistance Loan Guarantees and Payments",
    "005:004"=>"005:004 - Distance Learning Telemedicine and Broadband",
    "005:005"=>"005:005 - Rural Electrification and Telecommunication Loans",
    "005:006"=>"005:006 - Rural Water and Waste Loans and Grants",
    "005:007"=>"005:007 - Single Family Housing",
    "005:008"=>"005:008 - Multi-Family Housing",
    "005:009"=>"005:009 - Farm labor Housing",
    "005:010"=>"005:010 - Rental Assistance",
    "005:011"=>"005:011 - Community Facilities",
    "005:012"=>"005:012 - Farm Loans",
    "005:013"=>"005:013 - Commodity Programs, Commodity Credit Corporation",
    "005:014"=>"005:014 - Conservation Programs, Commodity Credit Corporation",
    "005:015"=>"005:015 - Grassroots Source Water Protection Program",
    "005:016"=>"005:016 - Reforestation Pilot Program",
    "005:017"=>"005:017 - State Mediation Grants",
    "005:018"=>"005:018 - Dairy Indemnity Payment Program (DIPP)",
    "005:019"=>"005:019 - Emergency Conservation Program",
    "005:020"=>"005:020 - Emergency Forest Restoration Program",
    "005:021"=>"005:021 - Noninsured Crop Disaster Assistance Program (NAP)",
    "005:022"=>"005:022 - Federal Crop Insurance Corporation Fund",
    "005:023"=>"005:023 - Public Law 480 Title I Direct Credit and Food for Progress Program Account",
    "005:024"=>"005:024 - Food for Peace Title II Grants",
    "005:025"=>"005:025 - McGovern-Dole International Food for Education and Child Nutrition Program",
    "005:026"=>"005:026 - Market Development and Food Assistance",
    "005:027"=>"005:027 - Conservation Operations",
    "005:028"=>"005:028 - Conservation Easements",
    "005:029"=>"005:029 - Environmental Quality Incentives Program",
    "005:030"=>"005:030 - Capital Improvement and Maintenance",
    "005:031"=>"005:031 - Forest and Rangeland Research",
    "005:032"=>"005:032 - Forest Service Permanent Appropriations and Trust Funds",
    "005:033"=>"005:033 - Land Acquisition",
    "005:034"=>"005:034 - National Forest System",
    "005:035"=>"005:035 - State and Private Forestry",
    "005:036"=>"005:036 - Wildland Fire Management",
    "005:037"=>"005:037 - Research and Education",
    "005:038"=>"005:038 - Extension",
    "005:039"=>"005:039 - Integrated Activities",
    "005:040"=>"005:040 - National Research",
    "005:041"=>"005:041 - Economic Research, Market Outlook, and Policy Analysis",
    "005:042"=>"005:042 - Agricultural Estimates",
    "005:043"=>"005:043 - Census of Agriculture",
    "005:044"=>"005:044 - Grain Regulatory Program",
    "005:045"=>"005:045 - Packers and Stockyards Program",
    "005:046"=>"005:046 - Inspection and Grading of Farm Products",
    "005:047"=>"005:047 - Marketing Services",
    "005:048"=>"005:048 - Payments to States and Possessions",
    "005:049"=>"005:049 - Perishable Agricultural Commodities Act",
    "005:050"=>"005:050 - Commodity Purchases",
    "005:051"=>"005:051 - Safeguarding and Emergency Preparedness/Response",
    "005:052"=>"005:052 - Safe Trade and International Technical Assistance",
    "005:053"=>"005:053 - Animal Welfare",
    "005:054"=>"005:054 - Child Nutrition Programs",
    "005:055"=>"005:055 - Commodity Assistance Programs",
    "005:056"=>"005:056 - Supplemental Nutrition Assistance Program",
    "005:057"=>"005:057 - Center for Nutrition Policy and Promotion",
    "005:058"=>"005:058 - Food Safety and Inspection",
    "005:059"=>"005:059 - Management Activities");
  }
  function getFrequencyMapping(){
    return  array(
      "Annually"=>"annually",
      "Annually or biennally"=>"periodic",
      "Annually or biennially"=>"periodic",
      "As Needed"=>"asNeeded",
      'asneeded'=>"asNeeded",
      "Biannually"=>"biannually",
      "Biennial"=>"biennially",
      "Bimonthly"=>"fortnightly",
      "Biweekly"=>"fortnightly",
      "Complete"=>"notPlanned",
      "Continually"=>"continual",
      "Continuously"=>"continual",
      "Daily"=>"daily",
      "Decennial"=>"periodic",
      "Every two years"=>"biennially",
      "Hourly"=>"continual",
      "irregular"=>"irregular",
      "Irregularly"=>"irregular",
      "Monthly"=>"monthly",
      "None"=>"notPlanned",
      "None needed"=>"notPlanned",
      "None planned"=>"notPlanned",
      "notPlanned"=>"notPlanned",
      'unknown' => 'notPlanned',
      "One to Ten Years"=>"irregular",
      "Quadrennial"=>"periodic",
      "Quarterly"=>"quarterly",
      "R/P0.5W"=>"periodic",
      "R/P1D"=>"daily",
      "R/P1M"=>"monthly",
      "R/P1W"=>"weekly",
      "R/P1Y"=>"annually",
      "R/P2M"=>"fortnightly",
      "R/P2Y"=>"biennially",
      "R/P3.5D"=>"periodic",
      "R/P3M"=>"quarterly",
      "R/P4M"=>"periodic",
      "R/P4Y"=>"periodic",
      "R/P6M"=>"biannually",
      "R/PT1H"=>"continual",
      "R/PT1S"=>"continual",
      "Semiannual"=>"biannually",
      "Semimonthly"=>"fortnightly",
      "Semiweekly"=>"periodic",
      "Three times a month"=>"periodic",
      "Three times a week"=>"periodic",
      "Three times a year"=>"periodic",
      "Triennial"=>"periodic",
      "Weekly"=>"weekly",
    );
  }
  function getBureauCodes(){
    return array("005:00"=>"005:00 - Department of Agriculture",
    "005:03"=>"005:03 - Office of the Secretary",
    "005:04"=>"005:04 - Executive Operations",
    "005:07"=>"005:07 - Office of Civil Rights",
    "005:08"=>"005:08 - Office of Inspector General",
    "005:10"=>"005:10 - Office of the General Counsel",
    "005:12"=>"005:12 - Office of Chief Information Officer",
    "005:13"=>"005:13 - Economic Research Service",
    "005:14"=>"005:14 - Office of Chief Financial Officer",
    "005:15"=>"005:15 - National Agricultural Statistics Service",
    "005:16"=>"005:16 - Hazardous Materials Management",
    "005:18"=>"005:18 - Agricultural Research Service",
    "005:19"=>"005:19 - Buildings and Facilities",
    "005:20"=>"005:20 - National Institute of Food and Agriculture",
    "005:32"=>"005:32 - Animal and Plant Health Inspection Service",
    "005:35"=>"005:35 - Food Safety and Inspection Service",
    "005:37"=>"005:37 - Grain Inspection, Packers and Stockyards Administration",
    "005:45"=>"005:45 - Agricultural Marketing Service",
    "005:47"=>"005:47 - Risk Management Agency",
    "005:49"=>"005:49 - Farm Service Agency",
    "005:53"=>"005:53 - Natural Resources Conservation Service",
    "005:55"=>"005:55 - Rural Development",
    "005:60"=>"005:60 - Rural Utilities Service",
    "005:63"=>"005:63 - Rural Housing Service",
    "005:65"=>"005:65 - Rural Business - Cooperative Service",
    "005:68"=>"005:68 - Foreign Agricultural Service",
    "005:84"=>"005:84 - Food and Nutrition Service",
    "005:96"=>"005:96 - Forest Service");
  }
  function processSpatialData(SimpleXMLElement $xml, array $coordinates) {
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
  function getLicenses($env){
    if($env=="stage"){
      return array(
        "https://creativecommons.org/licenses/by/4.0/"=>50,
        "https://creativecommons.org/licenses/by/4.0"=>50,
        "https://creativecommons.org/publicdomain/zero/1.0/"=>2,
        "http://opendatacommons.org/licenses/pddl/"=>150
      );
    }else{
      return array(
        "https://creativecommons.org/licenses/by/4.0/"=>1,
        "https://creativecommons.org/licenses/by/4.0"=>1,
        "https://creativecommons.org/publicdomain/zero/1.0/"=>2,
        "http://opendatacommons.org/licenses/pddl/"=>192
      );
    }
    
  }
  ?>