import requests
import json
import os
from datetime import datetime,timedelta
import time
from rest_framework.decorators import api_view
from rest_framework.response import Response
from django.conf import settings
from pathlib import Path
from .util import *
from .figshare_api import *
import re


# Map Source File into Figshare JSON Format
# Function to map data to Figshare JSON format
def map_to_figshare_json(record, config,env):
    figshare_json = {"title": record["title"]}
    figshare_json['description']=prepare_description(record)
    figshare_json['authors'] = prepare_author(record,config)
    figshare_json['timeline']={}
    # figshare_json['timeline']['firstOnline'] = prepare_created(record)
    figshare_json['timeline']['posted'] = prepare_created(record)
    rev=prepare_modified(record)
    if rev:
        figshare_json['timeline']['revision'] = rev
    
    figshare_json['tags'] = record['keyword']
    figshare_json['categories']=prepare_categories(record,config)
    figshare_json['custom_fields_list']=[]
    figshare_json['group_id']=config['group_id']
    figshare_json['funding_list'] =prepare_fundref(record,config)
    figshare_json['custom_fields_list'].append({
        'name':'ISO Topic Category',
        'value': config['defaults']['isoTopic']
    })
    figshare_json['custom_fields_list'].append({
        'name':'Data contact name',
        'value': record['contactPoint']['fn']
    })
    figshare_json['custom_fields_list'].append({
        'name':'Data contact email',
        'value':record['contactPoint']['hasEmail'].replace("mailto:","")
    })
    figshare_json['custom_fields_list'].append({
        'name':'OMB Bureau Code',
        'value':prepare_bureauCode(record['bureauCode'])
    })
    
    figshare_json['custom_fields_list'].append({
        'name':'Public Access Level',
        'value': [record['accessLevel'].capitalize()]
    })
    figshare_json['custom_fields_list'].append({
        'name':'OMB Program Code',
        'value':prepare_programCode(record['programCode'])
    })
    theme=config['Geospatial']
    if 'distribution' in record:
        for dist in record['distribution']:
            if 'conformsTo' in dist:
                theme="Geospatial"
    figshare_json['custom_fields_list'].append({
        'name':'Theme',
        'value':[theme]
    })
            
    
    frequency=prepare_frequency(record)
    if frequency:
        figshare_json['custom_fields_list'].append({
            'name':'Frequency',
            'value': [frequency]
        })
    spatial=prepare_spatial(record)
    # print(spatial)
    if spatial['desc']:
        figshare_json['custom_fields_list'].append({
        'name':'Geographic location - description',
        'value': spatial['desc']
        })
    elif spatial['geojson']:
        figshare_json['custom_fields_list'].append({
        'name':'Geographic Coverage',
        'value': spatial['geojson']
        })
    temporalCoverage = prepare_temporal_coverage(record)
    if len(temporalCoverage)>0:
        figshare_json['custom_fields_list'].append({
        'name':'Temporal Extent Start Date',
        'value': date("Y-m-d", strtotime(temporalCoverage[0]))
        })
    
    if len(temporalCoverage)>1:
        figshare_json['custom_fields_list'].append({
        'name':'Temporal Extent End Date',
        'value': date("Y-m-d", strtotime(temporalCoverage[1]))
        })
    
    figshare_json['defined_type'] = 'Dataset'
    figshare_json['license']=prepare_license(record,env)
        
    relatedMaterials=prepare_related_materials(record)
    if relatedMaterials:
        figshare_json['related_materials']=relatedMaterials
    
    figshare_json['custom_fields_list'].append({
        'name':'National Agricultural Library Thesaurus terms',
        'value': config['overrides']['nalt'][0]
    })


    figshare_json['custom_fields_list'].append(
        {
            'name':'Publisher',
            'value': record['publisher']['name']
        }
    )
    #     figshare_json['remoteFileLink=this->prepareRemoteFileLink(record);

    return figshare_json


def prepare_description(source):
    desc=source['description'] +"<div><br>This record was taken from the USDA Enterprise Data Inventory that feeds into the  <a href='https://data.gov'>https://data.gov</a> catalog. Data for this record includes the following resources:</div><ul>"

    for distribution in source['distribution']:
      
        if 'downloadURL' in distribution:
            desc=desc+ '<li><a href="'+distribution['downloadURL']+' "> '+ distribution['title'] if 'title' in distribution else distribution['downloadURL'] +'</a></li>'
        else:
            desc=desc+ '<li> <a href="'+distribution['accessURL']+' "> '+distribution['title'] if 'title' in distribution else distribution['accessURL']+'</a></li>'
        
    
    desc=desc+'</ul><div>For complete information, please visit <a href="https://data.gov">https://data.gov</a>.</div>'
    return desc

def prepare_created(source):
    if 'issued' in source:
        date_string=source['issued']
    else:
        date_string=source['modified']
    date_object = datetime.strptime(date_string, "%Y-%m-%d")

    # Add one day to the date
    new_date_object = date_object + timedelta(days=1)

    # Convert the new date back to a string in the same format
    return new_date_object.strftime("%Y-%m-%d")
def prepare_categories(source,config):
    categoryNames=[]
    if config['overrides']['categories']:
        categoryNames=config['overrides']['categories']
    categories = []
    for category in categoryNames:
        categories.append(int(category))

    return categories

def prepare_author(source,config):
    authorNames=[]
    if config['overrides']['author']:
        authorNames=config['overrides']['author']
    authors = []
    for author in authorNames:
        authors.append({'last_name': author})    

    return authors

def prepare_modified(source):
    if 'modified' in source:
        date_object = datetime.strptime(source['modified'], "%Y-%m-%d")
        new_date_object = date_object + timedelta(days=1)
        return new_date_object.strftime("%Y-%m-%d")
    else:
        return None
def prepare_bureauCode(bcodes):
    bureau_codes = get_all_bureauCodes()
    res=[]
    for bc in bcodes:
        if bc in bureau_codes:
          res.append(bureau_codes[bc])
    return res
def prepare_programCode(pcodes):
    program_codes = get_all_programCodes()
    res=[]
    for pc in pcodes:
        if pc in program_codes:
          res.append(program_codes[pc])
    return res
def prepare_fundref(source,config):
    fundref=[];        
    if config['overrides']['fundref']:
        for fund in config['overrides']['fundref']:
            fundref.append({"title":fund})
    return fundref

def prepare_spatial(source):
    res={"geojson":"","desc":""}
    if 'spatial' in source and source['spatial'] != None:
        spatial=source['spatial']
        try:
            # Try to parse the variable as JSON
            spatial = json.loads(spatial)
            res['geojson']=json.dumps({
                "type":"FeatureCollection",
                "features":{"geometry":spatial,"type":"Feature","properties":{}}
            })
            return res
        except ValueError:
            # If parsing fails, print the variable as is        
            pattern = r"[-+]?\d+(\.\d+)?,[-+]?\d+(\.\d+)?,[-+]?\d+(\.\d+)?,[-+]?\d+(\.\d+)?"

            # Search for the pattern in the string
            matches = re.search(pattern, spatial)
            # Check if a match is found
            if matches:
                # Extract the matched string
                spatial = matches.group(0)
                coordinates=spatial.split(",")
                if len(coordinates) == 4:
                    feature ={"type":"Feature","properties":{},"geometry":{"type":"Polygon","coordinates":[[[float(coordinates[0]), float(coordinates[1])],[float(coordinates[2]), float(coordinates[1])],[float(coordinates[2]), float(coordinates[3])],[float(coordinates[0]), float(coordinates[3])],[float(coordinates[0]), float(coordinates[1])]]]}}
                    geojson={"type":"FeatureCollection","features":[feature]}
                    res['geojson']= json.dumps(geojson)
                    return res
                else:
                    res['desc']=spatial
                    return res
            else:
                res['desc']=spatial
                return res
    return res

def prepare_license(source,env):
    licenses = get_licenses(env)
    license=source['license']
    if license in licenses:
        return licenses[license]

def prepare_frequency(record):
    if 'accrualPeriodicity' in record:
        accrualPeriodicity=record['accrualPeriodicity']
        frequencyMapping=get_all_frequency_mapping()
        if accrualPeriodicity and frequencyMapping[accrualPeriodicity]:
            return frequencyMapping[accrualPeriodicity]
def prepare_temporal_coverage(record):
    temporalCoverage=[]
    if 'temporal' in record:
        dates = record['temporal'].split("/")
        for da in dates:
            temporalCoverage.append(da)
    return temporalCoverage

def prepare_related_materials(source):
    related_materials=[]
    if 'references' in source:
      references=source['references']
      for ref in references:
        related_material={
            "identifier":ref,
            "title":ref,
            "relation":"IsSupplementTo",
            "identifier_type":"URL",
        }
        if related_material not in related_materials:
          related_materials.append(related_material)
        
    if 'distribution' in source:
      distribution=source['distribution']
      for dis in distribution:

        if "conformsTo" in dis:
            url=""
            if 'accessURL' in dis:
                url=dis['accessURL']
            else:
                url=dis['downloadURL']
            
            related_material={
                "identifier":url,
                "title":dis['title'],
                "relation":"HasMetadata",
                "identifier_type":"URL",
            }
            if related_material not in related_materials:
                related_materials.append(related_material)
          
    return related_materials

def prepare_remote_url(source):
    if 'landingPage' in source:
        return {'link':source['landingPage']}
    elif 'describedBy' in source:
        return {'link':source['describedBy']}
    else:
        distributions=source['distribution']
        if 'downloadURL' in distributions[0]:
            return {'link':distributions[0]['downloadURL']}
        elif 'accessURL' in distributions[0]:
            return {'link':distributions[0]['accessURL']}
        
    return None

@api_view(['GET'])
def push_to_figshare(request):

    # get bureauCode from url 
    bureauCode = request.GET.get('bureauCode')
    env = request.GET.get('env')

    # if bureauCode is not provided than assign the default bureauCode
    if not bureauCode:
        bureauCode = '005:13'
    if not env:
        env = 'stage'
    if bureauCode not in settings.DATA_GOV_MAPPING:
        return Response("Harvester for this agent is not implemented yet!")
    agency=settings.DATA_GOV_MAPPING[bureauCode]
    BASE_DIR = Path(__file__).resolve().parent
    conf_file=agency+'\\'+agency+"_settings_"+env+".json"
    config_file_full_path=os.path.join(BASE_DIR, conf_file)
    with open(config_file_full_path, 'rb') as f:
        config=json.load(f)
    root_directory=settings.DATAGOV_STAKEHOLDERS_ROOT
    folder=os.path.join(root_directory,agency)
    metadata_adc_links_file=os.path.join(BASE_DIR, agency+"\\log\\"+env+"\\metadata_link.json")
    with open(metadata_adc_links_file, 'rb') as f:
        metadata_adc_links=json.load(f)
        f.close()
    metadata_error_file=os.path.join(BASE_DIR, agency+"\\log\\"+env+"\\metadata_error.txt")
    metadata_report_file=os.path.join(BASE_DIR, agency+"\\log\\"+env+"\\metadata_report.txt")
    metadata_error = open(metadata_error_file, "a")
    metadata_report = open(metadata_report_file, "a")
    updated=0
    new_added=0
    failed=0
    unchanged=0
    for root, dirs, files in os.walk(folder):
        for file_name in files:
            source_file = os.path.join(root, file_name)
            update_time = os.path.getmtime(source_file)
            date_obj = datetime.strptime(config['last_updated'], "%Y/%m/%d")

            last_harvest_on = float(date_obj.timestamp())
            # print(update_time>last_harvest_on)
            with open(source_file, 'rb') as f:
                file_content = json.load(f)
            try:
                # Process the data and push it to Figshare
                accession=file_name                

                if last_harvest_on=="" or update_time>last_harvest_on:
                    jsonData=map_to_figshare_json(file_content,config,env)

                    if accession in metadata_adc_links:
                        print("update new article")
                        figshare_nodeid=metadata_adc_links[accession]
                        update=update_figshare_article(figshare_nodeid,jsonData,config)
                        # print(type(update))
                        remote_url= prepare_remote_url(file_content)
                        res2=figshare_api_update_remote_file(figshare_nodeid,remote_url,config)
                        
                        if 'location' in update:
                            updated=updated+1
                            publish = publish_figshare_article(figshare_nodeid,config)
                            # print(publish)
                        else:
                            print("failed")
                            failed=failed+1
                            metadata_error.write(json.dumps(update))
                            metadata_error.write("\n")

                    else:
                        print("create new article")
                        article=create_figshare_articles(jsonData,config)
                        if 'entity_id' in article:
                            new_added+=1
                            figshare_nodeid=article['entity_id']
                            metadata_adc_links[accession]=figshare_nodeid
                            # handle minting will be using micro services, will update this function once the API is ready
                            jsonData['handle'] = prepareHandle(figshare_nodeid)
                            remote_url= prepare_remote_url(file_content)
                            update=update_figshare_article(figshare_nodeid,jsonData,config)
                            res2=figshare_api_add_remote_file(figshare_nodeid,remote_url,config)
                            publish = publish_figshare_article(figshare_nodeid,config)
                        else:
                            print("failed")
                            failed+=1
                            metadata_error.write(json.dumps(article))
                            metadata_error.write("\n")

            except Exception as e:
                print("error occure while processing", file_name, e)
                # break
                continue
            # break

    log_msg='Last run on '+str(datetime.today().strftime('%Y/%m/%d'))+' at '+str(time.strftime("%H:%M:%S", time.localtime()))+'; '+str(new_added)+' added; '+str(updated)+' updated; '+str(failed)+' failed; '+str(unchanged)+' unchanged.'
    metadata_report.write(log_msg+"\n")

    # record_harvested_figshare_link = open(metadata_adc_links_file, 'w')
    with open(metadata_adc_links_file, 'w') as f:
        json.dump(metadata_adc_links, f)
    # record_harvested_figshare_link.write( json.dumps(metadata_adc_links))
    config['last_updated']=datetime.today().strftime('%Y/%m/%d')
    
    # update_config = open(config_file_full_path, 'w')
    # update_config.write(json.dumps(config))
    with open(config_file_full_path, 'w') as f:
        json.dump(config, f)
    
    return Response(log_msg)