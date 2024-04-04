'''
########################################## BRIEF OF THE PAGE ############################################
This page will read source_json file, filter out the dataset by provided bureauCode and 
each data set will be saved in separate file.

url =>                                  localhost:8000/apidownload-and-read-source-json/
url to provide bureauCode =>            localhost:8000/apidownload-and-read-source-json/?bureauCode=500:13

'''



import requests
import json
import os
from datetime import datetime
from rest_framework.decorators import api_view
from rest_framework.response import Response
from django.conf import settings
from pathlib import Path

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
            for codes in item['bureauCode']:
                if codes == bureauCode:
                    result.append(item)
    print(len(result))
    return result
    


#  function to write filtered data to file
def save_to_file(data):
    # iterate through the data
    for item in data:
        # write each data to file and name the file as identifier.json
        file_name = os.path.join(settings.STAKEHOLDERS_URL , item['identifier'] + '.json')
        # if file already exist continue else write new file

        if not os.path.exists(file_name):
            with open(file_name,'w') as f:
                json.dump(item,f)

    return True


# main function to be accessed from the url
# @logger
@api_view(['GET'])
def read_json_and_write_in_file(request):

    # get bureauCode from url 
    bureauCode = request.GET.get('bureauCode')

    # if bureauCode is not provided than assign the default bureauCode
    if not bureauCode:
        bureauCode = '005:13'

    # fetch_data will download the source.json file
    data = fetch_data(settings.JSON_SOURCE)

    # if data is received filter out the dataset by bureauCode
    if data:
        save_to_file(filter_data(data, bureauCode))

    return Response("done")

