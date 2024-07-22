import requests
import os
import json
from datetime import datetime
from rest_framework.decorators import api_view
from django.http import JsonResponse
from django.conf import settings
from rest_framework.response import Response

# map entity id with file name on record create
def read_and_update_entity_id(entity_id, source_file):
    file_path = os.path.join(settings.DATA_GOV_ARTICLE, 'article.json')
    obj = {
        "file_name" : source_file,
        "entity_id" : entity_id
    }

    with open(file_path,'r+') as f:
        # First we load existing data into a dict.
        file_data = json.load(f)
        # Join new_data with file_data inside emp_details
        file_data["data"].append(obj)
        # Sets file's current position at offset.
        f.seek(0)
        # convert back to json.
        json.dump(file_data, f, indent = 4)
        f.close()
            

# function to find entity_id from the article.json based on provided source_file path
def get_entity_id(source_file):
    file_path = os.path.join(settings.DATA_GOV_ARTICLE, 'article.json')

    with open(file_path, "r") as f:
        json_content = json.load(f)['data']
        try:
            json_content[0]
        except:
            return None
        
        for rec in json_content:
            if rec['file_name'] == source_file:
                print(rec['entity_id'])
                return rec['entity_id']
    return None

def prepareHandle(figshare_id):
    return '10113/AF'+str(figshare_id)
# 3. Push to Figshare using API for Newly Created Records

# Function to create articles on Figshare
def create_figshare_articles(data, config):
    token=config['token']
    base_url=config['base_url']
    # for record in data:
        # figshare_json = map_to_figshare_json(record, False)
        # Assuming you have Figshare API credentials
    headers = {
        "Content-Type": "application/json",
        "Authorization": token
    }
    try:
        response = requests.post(f'{base_url}', json=data, headers=headers)
        print(json.loads(response.content))
        return json.loads(response.content.decode("utf-8"))
        # read_and_update_entity_id(response.json()['entity_id'], source_file)
    except Exception as e:
        print(e)
        return response
    


# Function to update an existing article on Figshare
def update_figshare_article(article_id, data,config):
    # figshare_json = map_to_figshare_json(data,True)
    token=config['token']
    base_url=config['base_url']
    headers = {
        "Content-Type": "application/json",
        "Authorization": token
    }
    print(json.dumps(data))
    response = requests.put(f'{base_url}/{article_id}', json=data, headers=headers)
    print(response.content)
    return json.loads(response.content.decode("utf-8"))
    
def figshare_api_add_remote_file(article_id,data,config):
    token=config['token']
    base_url=config['base_url']
    headers = {
        "Content-Type": "application/json",
        "Authorization": token
    }
    print(json.dumps(data))
    response = requests.post(f'{base_url}/{article_id}/files', json=data, headers=headers)
    return json.loads(response.content.decode("utf-8"))

def figshare_api_update_remote_file(article_id,data,config):
    token=config['token']
    base_url=config['base_url']
    headers = {
        "Content-Type": "application/json",
        "Authorization": token
    }
    # print(json.dumps(data))
    response = requests.get(f'{base_url}/{article_id}/files', headers=headers)
    files_list=json.loads(response.content.decode("utf-8"))
    file = files_list[0]
    if file['download_url']!=data['link']:
        response = requests.delete(f'{base_url}/{article_id}/files/{file["id"]}',headers=headers)
        response = requests.post(f'{base_url}/{article_id}/files', json=data, headers=headers)
    return json.loads(response.content.decode("utf-8"))

# Function to publish article on Figshare
def publish_figshare_article(article_id, config):
    # figshare_json = map_to_figshare_json(data,True)
    token=config['token']
    base_url=config['base_url']
    headers = {
        "Content-Type": "application/json",
        "Authorization": token
    }
    response = requests.post(f'{base_url}/{article_id}/publish', headers=headers)
    print(response.text)
    return response.content



