import requests
import json
import os
from datetime import datetime
source_url = "https://www.usda.gov/sites/default/files/documents/data.json"

def fetch_data(url):
    response = requests.get(url)
    if response.status_code == 200:
        return response.json()
    else:
        print("Failed to fetch data from the source Url")
        return None
    
def filter_data(data):
    filter_data = [item for item in data['data'] if item.get('bureauCode') == '005:13' and item.get('accessLevel') == 'public']
    return filter_data

def save_to_file(data):
    file_name = 'filtered_data.json'
    with open(file_name,'w') as f:
        json.dump(data,f)
    print(f"Filtered data saved to {file_name}")

def need_update(data):
    if not os.path.exists('filtered_data.json'):
        return True
    else:
        with open('filtered_data.json', 'r') as f:
            existing_data = json.load(f)
            return data != existing_data
        
def main():
    data = fetch_data(source_url)
    if data:
        filter_data = filter_data(data)

        if need_update(filter_data):
            save_to_file(filter_data)
        else:
            print("Filterd data is already up to data")
    else:
        print("failed to fetch data. Exiting")


if __name__ == "__main__":
    main()