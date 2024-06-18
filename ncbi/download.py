from django.http import HttpResponse
from rest_framework.decorators import api_view
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time
import os
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
from rest_framework.response import Response
from django.conf import settings
import platform
import psutil

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