import os
import time
import webbrowser
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from selenium.common.exceptions import WebDriverException
from webdriver_manager.chrome import ChromeDriverManager
from rest_framework.decorators import api_view
from rest_framework.response import Response
from django.conf import settings
from webdriver_manager.firefox import GeckoDriverManager
import signal
import psutil

import platform

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


# # Extra step to kill any lingering Chrome processes
# def kill_process(process_name):
#     for proc in psutil.process_iter():
#         try:
#             if process_name.lower() in proc.name().lower():
#                 proc.kill()
#         except (psutil.NoSuchProcess, psutil.AccessDenied, psutil.ZombieProcess):
#             pass

# Function to set up Chrome WebDriver
def setup_chrome_driver(download_dir):
    # from selenium.webdriver.chrome.options import Options
    chrome_options = webdriver.ChromeOptions()
    prefs = {
        "download.default_directory": download_dir,
        "download.prompt_for_download": False,
        "download.directory_upgrade": True,
        "safebrowsing.enabled": True
    }
    # Initialize Chrome options
    # chrome_options = Options()
    chrome_options.add_experimental_option("prefs", prefs)
    chrome_options.add_argument("--disable-popup-blocking")

    chrome_options.add_experimental_option("prefs", prefs)
    return webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)


# Function to set up Firefox WebDriver
def setup_firefox_driver(download_dir):
    firefox_options = webdriver.FirefoxOptions()
    firefox_profile = {
        "browser.download.folderList": 2,
        "browser.download.dir": download_dir,
        "browser.helperApps.neverAsk.saveToDisk": "application/xml"
    }
    for key, value in firefox_profile.items():
        firefox_options.set_preference(key, value)
    return webdriver.Firefox(service=Service(GeckoDriverManager().install()), options=firefox_options)




@api_view(['GET'])
def download_xml_file(request):
    # Define the download directory
    download_dir = settings.NCBI_DOCUMENTS
    # Try to set up Chrome WebDriver, fallback to Firefox if Chrome setup fails
    # try:
    #     driver = setup_chrome_driver(download_dir)
    # except WebDriverException:
        # driver = setup_firefox_driver(download_dir)

    driver = setup_chrome_driver(download_dir)
    pid = driver.service.process.pid

    try:
        # Visit the specified URL
        driver.get('https://www.ncbi.nlm.nih.gov/bioproject?term=(USDA*%5BFunding%20Agency%5D%20OR%20NIFAX%5BFunding%20Agency%5D%20OR%20APHIS%5BFundin')

        # Wait for the settings element to be clickable and click it
        wait = WebDriverWait(driver, 10)
        settings_element = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, '.results_settings #sendto > a.tgt_dark')))
        settings_element.click()

        # # Wait for the send to menu to be clickable and click it
        send_to_menu = wait.until(EC.element_to_be_clickable((By.ID, 'send_to_menu')))
        send_to_menu.click()

        # # Click on the File option
        file_option = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, 'fieldset input#dest_File')))
        file_option.click()

        # # Click on the submenu file option
        submenu_file_option = wait.until(EC.element_to_be_clickable((By.ID, 'submenu_File')))
        submenu_file_option.click()

        # # Select 'xml' from the dropdown
        file_format_select = wait.until(EC.element_to_be_clickable((By.ID, 'file_format')))
        file_format_select.send_keys('xml')

        # # Add event listener and reload the document after a delay
        driver.execute_script("""
            document.addEventListener('click', () => {});
            setTimeout(() => { location.reload(); }, 1000);
        """)

        wait = WebDriverWait(driver, 2)
        # # Click the 'Create File' button
        create_file_button = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[@type='submit' and contains(text(), 'Create File')]")))

        time.sleep(2)
        create_file_button.click()

        wait = WebDriverWait(driver, 20)

        # # Add a delay to allow for the download to complete
        # wait = WebDriverWait(driver, 20)
        time.sleep(20)

    
    except Exception as e:
        print("exception occurred", e , "###################")
        p = psutil.Process(pid)
        p.terminate()  #or p.kill()


    finally:
        # finally kill the process
        # kill_process('geckodriver')
        kill_process('chromedriver')


    return Response("done")
