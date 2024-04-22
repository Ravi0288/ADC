import requests
import os
import json
from datetime import datetime
from rest_framework.decorators import api_view
from django.http import JsonResponse
from django.conf import settings
from rest_framework.response import Response

# 1. Read from Local Directory/Database

# Function to read data from a local directory
# def read_data_from_local_directory(directory_path):
#     data = []
#     # Iterate over files in the directory
#     for filename in os.listdir(directory_path):
#         # Assuming each file contains JSON data
#         with open(os.path.join(directory_path, filename), 'r') as file:
#             file_data = json.load(file)
#             data.extend(file_data)
#     return data


# 2. Map Source File into Figshare JSON Format
# Function to map data to Figshare JSON format
def map_to_figshare_json(record):
    
    figshare_json = {

        "title": "USDA Economic Research Service",
        "authors": ["ECONOMICS > Applied Economics > Agricultural economics"],
        "categories": ["Dataset"],
        "keyword": record.get("Keyword", ""),
        "description": record.get("description", ""),
        "posted_date": record.get("Temporal Extent Start Date", ""),
        "related_material_identifier": record.get("IsSupplementTo", ""),
        "relation_type": "IsSupplementTo",
        "license": "license_choice",
        "contactPoint": {
            "fn": record.get("Data Contact Name", ""),
            "hasEmail": record.get("Data Contact Email", "")
        },
        "publisher": {
            "name": record.get("Publisher", "")
        },
        "temporal": record.get("temporal", ""),
        "accrualPeriodicity": record.get("accrualPeriodicity", "Not specified"),
        "Theme": record.get("spatial", "economy"),
        "Temporal Extent End Date": record.get("Temporal Extent End Date", ""),
        "Frequency": record.get("Frequency", ""),
        "Geographic Coverage": record.get("Geographic Coverage", ""),
        "ISO Topic Category": ["economics", "agricultural economics"],
        "NALT terms": record.get("NALT terms", ""),
        "bureauCode": "005:13",
        "programCode": "005:13",
        
    }
    return figshare_json

# 3. Push to Figshare using API for Newly Created Records

# Function to create articles on Figshare
def create_figshare_articles(data):
    for record in data:
        figshare_json = map_to_figshare_json(record)
        # Assuming you have Figshare API credentials
        headers = {
            "Content-Type": "application/json",
            "Authorization": "Bearer YOUR_FIGSHARE_ACCESS_TOKEN"
        }
        response = requests.post('https://api.figshare.com/v2/private_articles', json=figshare_json, headers=headers)
        if response.status_code == 201:
            print(f"Article '{record['title']}' created successfully on Figshare.")
        else:
            print(f"Failed to create article '{record['title']}' on Figshare. Error: {response.text}")

# 4. Update Figshare Corresponding Record if Record Already Exists

# Function to check if an article exists on Figshare
def check_if_article_exists(title):
    headers = {
        "Authorization": "Bearer YOUR_FIGSHARE_ACCESS_TOKEN"
    }
    params = {
        "title": title
    }
    response = requests.get('https://api.figshare.com/v2/private_articles', params=params, headers=headers)
    if response.status_code == 200:
        articles = response.json()
        if articles:
            return articles[0]['id']  # Assuming we return the ID of the first matching article
    return False

# Function to update an existing article on Figshare
def update_figshare_article(article_id, data):
    figshare_json = map_to_figshare_json(data)
    headers = {
        "Content-Type": "application/json",
        "Authorization": "Bearer YOUR_FIGSHARE_ACCESS_TOKEN"
    }
    response = requests.put(f'https://api.figshare.com/v2/private_articles/{article_id}', json=figshare_json, headers=headers)
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
                return JsonResponse({'status': 'success', 'message': 'Data pushed to Figshare successfully'})
            except Exception as e:
                return JsonResponse({'status': 'error', 'message': str(e)})
