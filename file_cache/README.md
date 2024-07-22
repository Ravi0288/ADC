# Cache file directory explaination

## Data.gov feed harvester – ADC harvest datasets from their feed into data.gov:
1. ers, nrcs, fas, fsa and fs_land_management belong to this category.
2. The source urls for ERS NRCS FAS and FSA harvest are the same: “https://www.usda.gov/sites/default/files/documents/data.json”
3. Source url for FS Land Management harvesters is: https://data-usfs.hub.arcgis.com/api/feed/dcat-us/1.1.json
4. File type for this harvest is json, but not the same format used for Figshare. Need to generate Figshare json to use to push to Figshare API.
5. Mapping tables between this json and Figshare is available in corresponding github ticket. 
ERS: https://github.com/USDA-REE-ARS/nal-adc-support/issues/6
NRCS: https://github.com/USDA-REE-ARS/nal-adc-support/issues/7
FAS: https://github.com/USDA-REE-ARS/nal-adc-support/issues/9
FSA: https://github.com/USDA-REE-ARS/nal-adc-support/issues/8
fs_land_management: https://github.com/USDA-REE-ARS/nal-adc-support/issues/1

## EDI harvest
1. jornada folder contains sample records received from EDI website. The file type for this harvest is xml. XML standard is EML, contains three different EML standards.
2. PHP program is using xslt transforms to transfer EML standards into ISO 19139. XSLT files can be found in jornada/transforms folder
3. Procedure to convert XML to json is using xpath to extract the corresponding values for each key. 
4. Mapping tables between XML and Figshare json is available in github ticket: https://github.com/USDA-REE-ARS/nal-adc-support/issues/5

## NCBI Harvest
1. ncbi folder contains sample records downloaded from NIH website. The file type for this harvest is xml.
2. Procedure to convert XML to json is using xpath to extract the corresponding values for each key. Mapping table can be found in the corresponding github ticket: https://github.com/USDA-REE-ARS/nal-adc-support/issues/3

## FS Research Data Archive:
1. fs_fgdc folder contains sample records from FS Research Data Archive. The file type for this harvest is xml. XML standard is CSDGM.
2. Procedure to convert XML to json is using xpath to extract the corresponding values for each key. Mapping table can be found in the corresponding github ticket: https://github.com/USDA-REE-ARS/nal-adc-support/issues/2

# Figshare API notes:

1. categories, license and group_id should be integer numbers. IDs for staging and production are different, the corresponding ids can be found in categories license and group file under file_cache/figshare folder.
2. token is need to request all these APIs.

## Figshare API lists used

### categoreis:
1. Figshare API to get all categories list for staging site is: curl -X GET "https://api.figsh.com/v2/account/categories"
2. Figshare API to get all categories list for production site is: curl -X GET "https://api.figshare.com/v2/account/categories"

### group:
1. Figshare API to get all group list for staging site is: curl -X GET "https://api.figsh.com/v2/account/institution/groups"
2. Figshare API to get all group list for production site is: curl -X GET "https://api.figshare.com/v2/account/institution/groups"


### license:
1. Figshare API to get all licsne list for staging site  is: curl -X GET "https://api.figsh.com/v2/account/licenses"
2. Figshare API to get all licsne list for production site  is: curl -X GET "https://api.figshare.com/v2/account/licenses"


### Create new article:
1. Figshare API to create a new article for staging site is: curl -X POST "https://api.figsh.com/v2/account/articles" 
2. Figshare API to create a new article for production site is: curl -X POST "https://api.figshare.com/v2/account/articles" 

### update exsiting article:
1. Figshare API to update an article for staging site is: curl -X PUT "https://api.figsh.com/v2/account/articles/{article_id}" 
2. Figshare API to update an article for production site is: curl -X PUT "https://api.figshare.com/v2/account/articles/{article_id}" 


### publish a draft article or republish an article after updating it:
1. Figshare API to publish article for staging site is: curl -X POST "https://api.figsh.com/v2/account/articles/{article_id}/publish"
2. Figshare API to publish article for production site is: curl -X POST "https://api.figshare.com/v2/account/articles/{article_id}/publish" 


### attach a remote url to an article:
1. Figshare API to attach a remote url to an article for staging site is: curl -X POST "https://api.figsh.com/v2/account/articles/{article_id}/files"
2. Figshare API to attach a remote url to an article for production site is: curl -X POST "https://api.figshare.com/v2/account/articles/{article_id}/files" 
3. API request body is an object {'link'=>{url}};
