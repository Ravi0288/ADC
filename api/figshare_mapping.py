import requests
import os
import json
from datetime import datetime
from rest_framework.decorators import api_view
from django.http import JsonResponse
from django.conf import settings
from rest_framework.response import Response

# 1. Read from Local Directory/Database

# 2. Map Source File into Figshare JSON Format
# Function to map data to Figshare JSON format
def map_to_figshare_json(record, is_update):
    
    figshare_json = {

        "title": record['title'],
        "authors": [{"author":"USDA Economic Research Service"}],
        # "categories": ["ECONOMICS > Applied Economics > Agricultural economics"],
        "categories": [1],
        "item_Type":"Dataset",
        "keyword": record.get("Keyword", ""),
        "description": record.get("description", ""),
        "posted_date": record.get(datetime.now(), None),
        "funding":"Economic Research Service",
        "related_material_identifier": record.get("references", ""),
        "relation_type": "IsSupplementTo",
        # "license": record.get("license",""),
        "license": 1000,
        "contactPoint": {
            "fn": record.get("contactPoint", None).get("fn", None),
            "hasEmail": record.get("contactPoint", None).get("hasEmail", None)
        },
        "publisher": {
            "name": record.get("publisher", "")
        },
        # "Temporal Extent Start Date": record.get("temporal", ""),
        # "Temporal_End_Date": datetime.now(),
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
        figshare_json['posted_date'] = record['issued']
        figshare_json["Temporal_Start Date"] =  record['issued']

    return figshare_json



# 3. Push to Figshare using API for Newly Created Records

# Function to create articles on Figshare
def create_figshare_articles(data):
    for record in data:
        figshare_json = map_to_figshare_json(record, False)
        # Assuming you have Figshare API credentials
        headers = {
            "Content-Type": "application/json",
            "Authorization": settings.TOKEN
        }
        try:
            response = requests.post('https://api.figshare.com/v2/account/articles', json=figshare_json, headers=headers)
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
def check_if_article_exists(title):
    headers = {
        "Content-Type": "application/json",
        "Authorization": settings.TOKEN
    }
    params = {
        "title": title
    }
    response = requests.get('https://api.figshare.com/v2/private_articles/', params=params, headers=headers)
    if response.status_code == 200:
        articles = response.json()
        if articles:
            return articles[0]['id']  # Assuming we return the ID of the first matching article
    return False



# Function to update an existing article on Figshare
def update_figshare_article(article_id, data):
    figshare_json = map_to_figshare_json(data,True)
    headers = {
        "Content-Type": "application/json",
        "Authorization": settings.TOKEN
    }
    response = requests.put(f'https://api.figshare.com/v2/account/articles/{article_id}', json=figshare_json, headers=headers)
    if response.status_code == 200:
        print(f"Article '{data['title']}' updated successfully on Figshare.")
    else:
        print(f"Failed to update article '{data['title']}' on Figshare. Error: {response.text}")



# Function to handle the entire process
def process_data_and_push_to_figshare(record, directory_path):
    # Create or update articles on Figshare
    existing_article_id = check_if_article_exists(record['title'])
    if existing_article_id:
        update_figshare_article(existing_article_id, record)
    else:
        create_figshare_articles([record])





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

