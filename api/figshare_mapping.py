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
    file_path = os.path.join(settings.ARTICLES, 'article.json')
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
    file_path = os.path.join(settings.ARTICLES, 'article.json')

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

# Map Source File into Figshare JSON Format
# Function to map data to Figshare JSON format
def map_to_figshare_json(record, is_update):
    
    figshare_json = {

        "title": record["title"],
        "description": record["description"],
        "authors": [{"name":"USDA Economic Research Service"}],
        "categories": record.get("categories",  [31314]),
        # "timeline": {
        # "firstOnline": "2019-09-19",
        # "posted": "2019-09-19",
        # "revision": "2020-07-18"
        # },
        # "tags" : record.get('tags', None),
        "item_Type":"Dataset",
        "keyword": record.get("Keyword", ""),
        "description": record.get("description", ""),
        "posted_date": record.get(datetime.now(), None),
        "funding":"Economic Research Service",
        "related_material_identifier": record.get("references", ""),
        "relation_type": "IsSupplementTo",
        # "license": record.get("license", None),
        "contactPoint": {
            "fn": record.get("contactPoint", None).get("fn", None),
            "hasEmail": record.get("contactPoint", None).get("hasEmail", None)
        },
        "publisher": {
            "name": record.get("publisher", "")
        },
        "Frequency": record.get("accrualPeriodicity", ""),
        "Theme": "Not specifiled",
        "Geographic_Coverage": record.get("Geographic Coverage", ""),
        "ISO_Topic_Category":"economy",
        "NALT_terms": ["economics", "agricultural economics"],
        "OMB_Bureau_Code": record.get("bureauCode", ""),
        "OMB_Program_Code": record.get("ProgramCode", ""),
        "Public_Access_Level":record.get("accessLevel", ""),
    }

    if is_update:
        figshare_json["posted_date"] = record["issued"]
        figshare_json["Temporal_Start Date"] =  record["issued"]

    return figshare_json



# 3. Push to Figshare using API for Newly Created Records

# Function to create articles on Figshare
def create_figshare_articles(data, source_file):
    for record in data:
        figshare_json = map_to_figshare_json(record, False)
        # Assuming you have Figshare API credentials
        headers = {
            "Content-Type": "application/json",
            "Authorization": settings.TOKEN
        }
        try:
            response = requests.post('https://api.figsh.com/v2/account/articles', json=figshare_json, headers=headers)
            print(response._content)
            read_and_update_entity_id(response.json()['entity_id'], source_file)
        except Exception as e:
            print(e)
        if response.status_code == 201:
            print(f"Article '{record['title']}' created successfully on Figshare.")
            return True
        else:
            print(f"Failed to create article '{record['title']}' on Figshare.", response.status_code, response.content)
            return False




# 4. Update Figshare Corresponding Record if Record Already Exists

# Function to check if an article exists on Figshare
def check_if_article_exists(source_file):
    headers = {
        "Content-Type": "application/json",
        "Authorization": settings.TOKEN
    }

    entity_id = get_entity_id(source_file) 

    if entity_id:
        # response = requests.get('https://api.figsh.com/v2/private_articles/' + entity_id, headers=headers)
        return entity_id
    else:
        return False



# Function to update an existing article on Figshare
def update_figshare_article(article_id, data):
    figshare_json = map_to_figshare_json(data,True)
    headers = {
        "Content-Type": "application/json",
        "Authorization": settings.TOKEN
    }
    response = requests.put(f'https://api.figsh.com/v2/account/articles/{article_id}', json=figshare_json, headers=headers)
    if response.status_code == 200 or response.status_code == 205:
        print(f"Article '{data['title']}' updated successfully on Figshare.")
    else:
        print(f"Failed to update article '{data['title']}' on Figshare. Error: {response.text}")



# Function to handle the entire process
def process_data_and_push_to_figshare(record, source_file):
    # Create or update articles on Figshare
    existing_article_id = check_if_article_exists(source_file)
    if existing_article_id:
        update_figshare_article(existing_article_id, record)
    else:
        create_figshare_articles([record], source_file)





# Endpoint to trigger the process of pushing data to Figshare
@api_view(['GET'])
def push_to_figshare(request):
    # Specify the directory containing the source files
    root_directory = settings.STAKEHOLDERS_ROOT

    
    for root, dirs, files in os.walk(root_directory):
        for file_name in files:
            source_file = os.path.join(root, file_name)
            with open(source_file, 'rb') as f:
                file_content = json.load(f)
            try:
                # Process the data and push it to Figshare
                process_data_and_push_to_figshare(file_content, source_file)
            except Exception as e:
                print("error occure while processing", file_name, e)
                continue

    return Response('Process executed successfully')

