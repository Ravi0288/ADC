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

def get_all_bureauCodes():
    return {"005:00":"005:00 - Department of Agriculture",
    "005:03":"005:03 - Office of the Secretary",
    "005:04":"005:04 - Executive Operations",
    "005:07":"005:07 - Office of Civil Rights",
    "005:08":"005:08 - Office of Inspector General",
    "005:10":"005:10 - Office of the General Counsel",
    "005:12":"005:12 - Office of Chief Information Officer",
    "005:13":"005:13 - Economic Research Service",
    "005:14":"005:14 - Office of Chief Financial Officer",
    "005:15":"005:15 - National Agricultural Statistics Service",
    "005:16":"005:16 - Hazardous Materials Management",
    "005:18":"005:18 - Agricultural Research Service",
    "005:19":"005:19 - Buildings and Facilities",
    "005:20":"005:20 - National Institute of Food and Agriculture",
    "005:32":"005:32 - Animal and Plant Health Inspection Service",
    "005:35":"005:35 - Food Safety and Inspection Service",
    "005:37":"005:37 - Grain Inspection, Packers and Stockyards Administration",
    "005:45":"005:45 - Agricultural Marketing Service",
    "005:47":"005:47 - Risk Management Agency",
    "005:49":"005:49 - Farm Service Agency",
    "005:53":"005:53 - Natural Resources Conservation Service",
    "005:55":"005:55 - Rural Development",
    "005:60":"005:60 - Rural Utilities Service",
    "005:63":"005:63 - Rural Housing Service",
    "005:65":"005:65 - Rural Business - Cooperative Service",
    "005:68":"005:68 - Foreign Agricultural Service",
    "005:84":"005:84 - Food and Nutrition Service",
    "005:96":"005:96 - Forest Service"}

def get_all_programCodes():
    return {
        "005:000":"005:000 - (Primary Program Not Available)",
        "005:001":"005:001 - Rural Business Loans",
        "005:002":"005:002 - Rural Business Grants",
        "005:003":"005:003 - Energy Assistance Loan Guarantees and Payments",
        "005:004":"005:004 - Distance Learning Telemedicine and Broadband",
        "005:005":"005:005 - Rural Electrification and Telecommunication Loans",
        "005:006":"005:006 - Rural Water and Waste Loans and Grants",
        "005:007":"005:007 - Single Family Housing",
        "005:008":"005:008 - Multi-Family Housing",
        "005:009":"005:009 - Farm labor Housing",
        "005:010":"005:010 - Rental Assistance",
        "005:011":"005:011 - Community Facilities",
        "005:012":"005:012 - Farm Loans",
        "005:013":"005:013 - Commodity Programs, Commodity Credit Corporation",
        "005:014":"005:014 - Conservation Programs, Commodity Credit Corporation",
        "005:015":"005:015 - Grassroots Source Water Protection Program",
        "005:016":"005:016 - Reforestation Pilot Program",
        "005:017":"005:017 - State Mediation Grants",
        "005:018":"005:018 - Dairy Indemnity Payment Program (DIPP)",
        "005:019":"005:019 - Emergency Conservation Program",
        "005:020":"005:020 - Emergency Forest Restoration Program",
        "005:021":"005:021 - Noninsured Crop Disaster Assistance Program (NAP)",
        "005:022":"005:022 - Federal Crop Insurance Corporation Fund",
        "005:023":"005:023 - Public Law 480 Title I Direct Credit and Food for Progress Program Account",
        "005:024":"005:024 - Food for Peace Title II Grants",
        "005:025":"005:025 - McGovern-Dole International Food for Education and Child Nutrition Program",
        "005:026":"005:026 - Market Development and Food Assistance",
        "005:027":"005:027 - Conservation Operations",
        "005:028":"005:028 - Conservation Easements",
        "005:029":"005:029 - Environmental Quality Incentives Program",
        "005:030":"005:030 - Capital Improvement and Maintenance",
        "005:031":"005:031 - Forest and Rangeland Research",
        "005:032":"005:032 - Forest Service Permanent Appropriations and Trust Funds",
        "005:033":"005:033 - Land Acquisition",
        "005:034":"005:034 - National Forest System",
        "005:035":"005:035 - State and Private Forestry",
        "005:036":"005:036 - Wildland Fire Management",
        "005:037":"005:037 - Research and Education",
        "005:038":"005:038 - Extension",
        "005:039":"005:039 - Integrated Activities",
        "005:040":"005:040 - National Research",
        "005:041":"005:041 - Economic Research, Market Outlook, and Policy Analysis",
        "005:042":"005:042 - Agricultural Estimates",
        "005:043":"005:043 - Census of Agriculture",
        "005:044":"005:044 - Grain Regulatory Program",
        "005:045":"005:045 - Packers and Stockyards Program",
        "005:046":"005:046 - Inspection and Grading of Farm Products",
        "005:047":"005:047 - Marketing Services",
        "005:048":"005:048 - Payments to States and Possessions",
        "005:049":"005:049 - Perishable Agricultural Commodities Act",
        "005:050":"005:050 - Commodity Purchases",
        "005:051":"005:051 - Safeguarding and Emergency Preparedness/Response",
        "005:052":"005:052 - Safe Trade and International Technical Assistance",
        "005:053":"005:053 - Animal Welfare",
        "005:054":"005:054 - Child Nutrition Programs",
        "005:055":"005:055 - Commodity Assistance Programs",
        "005:056":"005:056 - Supplemental Nutrition Assistance Program",
        "005:057":"005:057 - Center for Nutrition Policy and Promotion",
        "005:058":"005:058 - Food Safety and Inspection",
        "005:059":"005:059 - Management Activities"
    }
def get_licenses(env):
    if env=="stage":
      return {
        "https://creativecommons.org/licenses/by/4.0/":50,
        "https://creativecommons.org/licenses/by/4.0":50,
        "https://creativecommons.org/publicdomain/zero/1.0/":2,
        "http://opendatacommons.org/licenses/pddl/":150,
        "http://www.usa.gov/publicdomain/label/1.0/":71
      }       
    
    else:
      return {
        "https://creativecommons.org/licenses/by/4.0/":1,
        "https://creativecommons.org/licenses/by/4.0":1,
        "https://creativecommons.org/publicdomain/zero/1.0/":2,
        "http://opendatacommons.org/licenses/pddl/":192,
        "http://www.usa.gov/publicdomain/label/1.0/":96,
        "https://www.usa.gov/publicdomain/label/1.0/":96
      }
def get_all_frequency_mapping():
    return  {
      "Annually":"annually",
      "Annually or biennally":"periodic",
      "Annually or biennially":"periodic",
      "As Needed":"asNeeded",
      'asneeded':"asNeeded",
      "Biannually":"biannually",
      "Biennial":"biennially",
      "Bimonthly":"fortnightly",
      "Biweekly":"fortnightly",
      "Complete":"notPlanned",
      "Continually":"continual",
      "Continuously":"continual",
      "Daily":"daily",
      "Decennial":"periodic",
      "Every two years":"biennially",
      "Hourly":"continual",
      "irregular":"irregular",
      "Irregularly":"irregular",
      "Monthly":"monthly",
      "None":"notPlanned",
      "None needed":"notPlanned",
      "None planned":"notPlanned",
      "notPlanned":"notPlanned",
      'unknown' : 'notPlanned',
      "One to Ten Years":"irregular",
      "Quadrennial":"periodic",
      "Quarterly":"quarterly",
      "R/P0.5W":"periodic",
      "R/P1D":"daily",
      "R/P1M":"monthly",
      "R/P1W":"weekly",
      "R/P1Y":"annually",
      "R/P2M":"fortnightly",
      "R/P2Y":"biennially",
      "R/P3.5D":"periodic",
      "R/P3M":"quarterly",
      "R/P4M":"periodic",
      "R/P4Y":"periodic",
      "R/P6M":"biannually",
      "R/PT1H":"continual",
      "R/PT1S":"continual",
      "Semiannual":"biannually",
      "Semimonthly":"fortnightly",
      "Semiweekly":"periodic",
      "Three times a month":"periodic",
      "Three times a week":"periodic",
      "Three times a year":"periodic",
      "Triennial":"periodic",
      "Weekly":"weekly",
  }
# Endpoint to trigger the process of pushing data to Figshare
@api_view(['GET'])
def push_to_figshare(request):
    # Specify the directory containing the source files
    root_directory = settings.DATAGOV_STAKEHOLDERS_ROOT

    
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

