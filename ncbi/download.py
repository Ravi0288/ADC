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


# wrap the document in single root.
def wrap_with_root_element(file_content):
    return f"<root>{file_content}</root>"


def extract_and_save_records(latest_file, output_dir, created=0, updated=0):
    try:
        with open(latest_file, 'r', encoding='utf-8') as file:  # Specify the encoding
            file_content = file.read()

    # downloaded file has some format related issues. Try a different encoding if utf-8 fails      
    except UnicodeDecodeError:
        with open(latest_file, 'r', encoding='latin-1') as file:
            file_content = file.read()
    
    # The file has multiple records with no root, to process the file we need to wrap all the records under a single root
    wrapped_content = wrap_with_root_element(file_content)
    
    # parse the xml file
    try:
        root = ET.fromstring(wrapped_content)
    except ET.ParseError as e:
        print(f"Error parsing XML: {e}")
        return
    
    # read the xml file, find the records and save file
    for doc_summary in root.findall('DocumentSummary'):
        uid = doc_summary.get('uid')
        archive_id_element = doc_summary.find('.//ArchiveID')
        if archive_id_element is not None:
            accession_number = archive_id_element.get('accession')
            # Create a new XML element for the record
            new_tree = ET.ElementTree(doc_summary)

            file_name = os.path.join(output_dir, f"{accession_number}.xml")

            # check if file already exist, than update
            if os.path.isfile(file_name):
                updated +=1
                with open(file_name, 'wb') as f:
                    new_tree.write(f, encoding='utf-8', xml_declaration=True)
                    f.close()

            # else Save the new XML element to a file named with the accession number
            else:
                created+=1
                new_tree.write(file_name)
    print(created, " file created and ", updated, "file updated")





from rest_framework.decorators import api_view
@api_view(['GET'])
def seperate_record_by_assesion_number(request):
    # Define directory / file path
    base_dir = settings.NCBI_DOCUMENTS
    file_path = os.path.join(base_dir, 'bioproject_result.xml')

    if os.path.isfile(file_path):
        # segregate the file content and save the content by accession number as new file
        extract_and_save_records(file_path, base_dir)
        # once file is processed remove the file
        os.remove(file_path)
        
        return HttpResponse("all records seperated and saved as accession.xml sucessfully")
    else:
        return HttpResponse("No file found to procceed further. Exiting")



