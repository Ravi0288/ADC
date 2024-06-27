from django.http import HttpResponse
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time
import os
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
from django.conf import settings
import platform
import os
import xml.etree.ElementTree as ET
from rest_framework.decorators import api_view
from xml.dom import minidom
import glob

def kill_process(process_name):
    """
    Kills all instances of a given process by name.

    :param process_name: The name of the process to kill (without the extension)
    """
    system = platform.system()
    if system == 'Windows':
        os.system(f"taskkill /f /im {process_name}.exe /T")
    elif system in ('Linux', 'Darwin'):  # Darwin is macOS
        os.system(f"pkill -f {process_name}")
    else:
        print(f"Unsupported OS: {system}")



def download_xml_file(request):
    # Define download directory
    download_dir = settings.NCBI_DOCUMENTS
    
    # Set up Chrome WebDriver with preferences
    chrome_options = webdriver.ChromeOptions()
    prefs = {
        "download.default_directory": download_dir,
        "download.prompt_for_download": False,
        "download.directory_upgrade": True,
        "safebrowsing.enabled": True
    }
    chrome_options.add_experimental_option("prefs", prefs)
    chrome_options.add_argument("--disable-popup-blocking")
    
    # Initialize Chrome WebDriver
    driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)
    # pid = driver.service.process.pid
    
    try:
        # Navigate to the URL
        driver.get('https://www.ncbi.nlm.nih.gov/bioproject?term=(USDA*%5BFunding%20Agency%5D%20OR%20NIFAX%5BFunding%20Agency%5D%20OR%20APHIS%5BFundin')

        # Wait for and click the settings element
        wait = WebDriverWait(driver, 10)
        settings_element = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, '.results_settings #sendto > a.tgt_dark')))
        settings_element.click()

        # Wait for and click the send to menu
        send_to_menu = wait.until(EC.element_to_be_clickable((By.ID, 'send_to_menu')))
        send_to_menu.click()

        # Click on the File option
        file_option = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, 'fieldset input#dest_File')))
        file_option.click()

        # Click on the submenu File option
        submenu_file_option = wait.until(EC.element_to_be_clickable((By.ID, 'submenu_File')))
        submenu_file_option.click()

        # Select 'xml' from the dropdown
        file_format_select = wait.until(EC.element_to_be_clickable((By.ID, 'file_format')))
        file_format_select.send_keys('xml')

        # Click the 'Create File' button
        create_file_button = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[@type='submit' and contains(text(), 'Create File')]")))
        create_file_button.click()

        # Add a delay to allow for the download to complete
        time.sleep(30)  # Adjust as per your requirement
        # driver.service._terminate_process()
        print("quitted in main")
        # kill_process("chrome")
        driver.close()
        driver.quit()

    except Exception as e:
        print("entered in exception")
        # kill_process("chrome")
        driver.close()
        driver.quit()
        print(f"Exception occurred: {e}")

    return HttpResponse("done")



# function to get latest file
def get_latest_file(download_dir):
    list_of_files = glob.glob(os.path.join(download_dir, '*'))
    if not list_of_files:
        return None
    latest_file = max(list_of_files, key=os.path.getmtime)
    return latest_file

# function to add all the xml records under a single root
def wrap_with_root_element(file_content):
    return f"<root>{file_content}</root>"

# function to read xml file
def read_existing_file_content(file_path):
    try:
        with open(file_path, 'r', encoding='utf-8') as file:
            return file.read()
    except FileNotFoundError:
        return None

# function to normalize the xml content
def normalize_xml_content(xml_string):
    try:
        root = ET.fromstring(xml_string)
        rough_string = ET.tostring(root, encoding='utf-8', xml_declaration=True, method='xml')
        reparsed = minidom.parseString(rough_string)
        return reparsed.toprettyxml(indent="  ", encoding='utf-8').decode('utf-8').strip()
    except ET.ParseError as e:
        print(f"Error parsing XML for normalization: {e}")
        return xml_string.strip()

# function to extract the record and save assession wise
def extract_and_save_records(latest_file, output_dir):
    created, updated = 0, 0
    try:
        with open(latest_file, 'r', encoding='utf-8') as file:
            file_content = file.read()
    except UnicodeDecodeError:
        with open(latest_file, 'r', encoding='latin-1') as file:
            file_content = file.read()
    
    wrapped_content = wrap_with_root_element(file_content)
    
    try:
        root = ET.fromstring(wrapped_content)
    except ET.ParseError as e:
        print(f"Error parsing XML: {e}")
        return
    
    for doc_summary in root.findall('DocumentSummary'):
        archive_id_element = doc_summary.find('.//ArchiveID')
        if archive_id_element is not None:
            accession_number = archive_id_element.get('accession')
            new_tree = ET.ElementTree(doc_summary)
            rough_string = ET.tostring(doc_summary, encoding='utf-8', xml_declaration=True)
            reparsed = minidom.parseString(rough_string)
            new_tree_str = reparsed.toprettyxml(indent="  ", encoding='utf-8').decode('utf-8').strip()
            file_name = os.path.join(output_dir, f"{accession_number}.xml")

            existing_content = read_existing_file_content(file_name)

            if existing_content is None:
                created += 1
                with open(file_name, 'wb') as f:
                    new_tree.write(f, encoding='utf-8', xml_declaration=True)
            else:
                existing_content_normalized = normalize_xml_content(existing_content)
                if existing_content_normalized == new_tree_str:
                    print(f"No changes for {file_name}.")
                    continue
                else:
                    updated += 1
                    with open(file_name, 'wb') as f:
                        new_tree.write(f, encoding='utf-8', xml_declaration=True)

    print(f"{created} file(s) created and {updated} file(s) updated")


# this is the main function
@api_view(['GET'])
def seperate_record_by_assesion_number(request):
    base_dir = settings.NCBI_DOCUMENTS
    file_path = os.path.join(base_dir, 'bioproject_result.xml')

    # if the file exists process it
    if os.path.isfile(file_path):
        extract_and_save_records(file_path, base_dir)
        
        # delete the file once processed
        try:
            os.remove(file_path)
        except:
            pass

        return HttpResponse("All records separated and saved as accession.xml successfully")
    else:
        return HttpResponse("No file found to proceed further. Exiting")

