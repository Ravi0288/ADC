# NCBI harvest

## Steps to get each record in xml (This process is currently done using Cypress):
1. Visit NIH website using the link https://www.ncbi.nlm.nih.gov/bioproject?term=(USDA%5BFunding%20Agency%5D%20OR%20NIFA%5BFunding%20Agency%5D%20OR%20APHIS%5BFunding%20Agency%5D%20OR%20USFS%5BFunding%20Agency%5D%20OR%20NRCS%5BFunding%20Agency%5D%20OR%20USDA*%5BSubmitter%20Organization%5D%20OR%20U.S.%20Department%20of%20Agriculture%5BSubmitter%20Organization%5D%20OR%20US%20Department%20of%20Agriculture%5BSubmitter%20Organization%5D%20OR%20Agricultural%20Research%20Service%5BSubmitter%20Organization%5D%20OR%20NIFA%5BSubmitter%20Organization%5D%20OR%20APHIS%5BSubmitter%20Organization%5D%20OR%20NRRL%5BTitle%5D%20OR%20NRRL%5BKeyword%5D%20OR%20NRRL%5BDescription%5D%20OR%20United%20States%20Department%20of%20Agriculture%5BFunding%20Agency%5D%20OR%20United%20States%20Department%20of%20Agriculture%5BSubmitter%20Organization%5D)

2. Click 'Send to' > select 'File' > select XML under 'Format' > click 'Create File'
3. Download search result into one XML file
4. Using python to separate each record into a single file



## Loop through the cache directory to map each record into Figshare format, mint handle if needed and push to Figshare. 

- Mapping table is available in https://github.com/USDA-REE-ARS/nal-adc-support/issues/3, hard-coded values are also included in the ticket. Using xpath to extract the corresponding values.

## File type for NCBI harvest is XML format. Files in figshare_json folder are the corresponding json format used to push to Figshare API
