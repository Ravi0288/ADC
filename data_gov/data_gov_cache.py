'''
########################################## BRIEF OF THE PAGE ############################################
This page will read source_json file, filter out the dataset by provided bureauCode and 
each data set will be saved in separate file.

url =>                                  localhost:8000/api/download-and-read-source-json/
url to provide bureauCode =>            localhost:8000/api/download-and-read-source-json/?bureauCode=500:13

'''



import requests
import json
import os
from datetime import datetime
from rest_framework.decorators import api_view
from rest_framework.response import Response
from django.conf import settings
from pathlib import Path
from urllib.parse import urlparse, parse_qs

# function to fetch source.json file from the source url
def fetch_data(url):
    response = requests.get(url)
    # get the data from the response and return
    if response.status_code == 200:
        data = json.loads(response._content.decode('utf-8'))
        return data["dataset"]
    else:
        # function to write log in case error occures. File name will be date time .txt
        file_name = os.path.join(settings.LOGS , datetime.now().strftime("%Y%m%d-%H%M%S") + '.txt')
        with open(file_name, 'wb') as f:
            f.write(response._content)
        return None


# function to filter dataset baseed on provided bureauCode
def filter_data(data, bureauCode):
    result = []

    # iterate through the data
    for item in data:
        # check the access level of the data to public
        if(item['accessLevel'] == 'public'):
            # iterate through the bureauCode and if bureauCode found append in result
            if bureauCode in item['bureauCode']:
                result.append(item)
    return result
    


#  function to write filtered data to file
def save_to_file(data, bureauCode):
    # create folder if not availabe already
    path = os.path.join(settings.DATAGOV_STAKEHOLDERS_ROOT, settings.DATA_GOV_MAPPING[bureauCode])
    count=0
    if not os.path.isdir(path):
        os.makedirs(path)
    text=""
    # iterate through the data
    for item in data:
        file_name=item['identifier']
        
        
        if "10.15482/USDA.ADC/" in file_name:
            continue

        count=count+1
        
        if "http" in file_name:
            # The   URL you want to parse
            url = file_name

            # Parse the URL
            parsed_url = urlparse(url)

            # Extract the query part of the URL
            query = parsed_url.query

            # Parse the query parameters into a dictionary
            params = parse_qs(query)

            # Get the 'id' parameter
            file_name = params.get('id', [None])[0]
            # print(file_name)
        else:
            file_name=file_name.replace("/","")

        # write each data to file and name the file as identifier.json
        file_name = os.path.join(path,  file_name)
        # if file exists read the content
        if os.path.exists(file_name):
            with open(file_name,'r') as f:
                data = json.load(f)
                
            
            # compare the file data with incoming data
            if (data == item):
                # if data are same continue to next iteration
                continue
            else:
                # if data are found mismatched remove the file and go to next if condition
                os.remove(file_name)
        
        # create new file and save the content
        if not os.path.exists(file_name):
            with open(file_name,'w') as f:
                json.dump(item,f)


    return count


# main function to be accessed from the url
# @logger
@api_view(['GET'])
def read_json_and_write_in_file(request):

    # get bureauCode from url 
    bureauCode = request.GET.get('bureauCode')

    # if bureauCode is not provided than assign the default bureauCode
    if not bureauCode:
        bureauCode = '005:13'
        
    if bureauCode not in settings.DATA_GOV_MAPPING:
        return Response("Harvester for this agent is not implemented yet!")
    agency=settings.DATA_GOV_MAPPING[bureauCode]
    
    env = request.GET.get('env')
    if not env:
        env = 'stage'
    BASE_DIR = Path(__file__).resolve().parent

    conf_file=agency+'\\'+agency+"_settings_"+env+".json"
    config_file_full_path=os.path.join(BASE_DIR, conf_file)
    with open(config_file_full_path, 'rb') as f:
        config=json.load(f)
    # fetch_data will download the source.json file
    data = fetch_data(config['source'])

    # if data is received filter out the dataset by bureauCode
    if data:
        count=save_to_file(filter_data(data, bureauCode), bureauCode)
        return Response(str(count)+" records cached!")

    return Response("Cache failed, please try again!")