import time
import os
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager

def setup_driver():
    chrome_options = Options()
    # chrome_options.add_argument("--headless")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--start-maximized")
    
    service = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service, options=chrome_options)
    return driver

def test_end_to_end_flow():
    driver = setup_driver()
    wait = WebDriverWait(driver, 15)
    base_url = "http://localhost/track_it"
    
    print(f"--- Starting Full End-to-End UI Test ---")
    
    try:
        # 1. Login
        print("\n[STEP 1] Logging in...")
        driver.get(f"{base_url}/index.php")
        
        user_input = wait.until(EC.presence_of_element_located((By.NAME, "username")))
        driver.find_element(By.NAME, "username").send_keys("testuser_selenium")
        driver.find_element(By.NAME, "password").send_keys("password123")
        
        login_btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Login Account')]")
        login_btn.click()
        
        wait.until(EC.url_contains("dashboard.php"))
        print("[PASS] Login successful.")

        # 2. Transaction Management (Quick Entry)
        print("\n[STEP 2] Adding transactions...")
        
        # Add Inflow (Salary)
        print("Switching to Income tab...")
        inflow_tab = wait.until(EC.element_to_be_clickable((By.ID, "qe-tab-inflow")))
        inflow_tab.click()
        
        print("Entering Salary details...")
        driver.find_element(By.ID, "quickAmount").send_keys("1000")
        driver.find_element(By.ID, "quickDesc").send_keys("Salary Bonus")
        
        save_btn = driver.find_element(By.ID, "qe-btn")
        save_btn.click()
        
        # Handle the alert that appears
        alert = wait.until(EC.alert_is_present())
        print(f"Alert detected: {alert.text}")
        alert.accept()

        # Wait for page reload/refresh
        wait.until(EC.staleness_of(save_btn))
        print("[PASS] Logged Inflow.")

        # 3. Calendar & History
        print("\n[STEP 3] Verifying Navigation...")
        
        sections = ['history', 'calendar', 'stats', 'wishes', 'settings']
        for section in sections:
            print(f"Navigating to {section}...")
            nav_item = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, f"[data-view='{section}']")))
            nav_item.click()
            
            section_el = driver.find_element(By.ID, f"section-{section}")
            wait.until(EC.visibility_of(section_el))
            print(f"[PASS] {section} view active.")

        # 4. Wishes/Goals
        print("\n[STEP 4] Managing Wishes...")
        # Already on wishes but let's be sure
        driver.find_element(By.CSS_SELECTOR, "[data-view='wishes']").click()
        
        wish_title = "New Monitor " + str(int(time.time()))
        driver.find_element(By.ID, "wishTitle").send_keys(wish_title)
        driver.find_element(By.ID, "wishTarget").send_keys("300")
        
        add_wish_btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Create Wish')]")
        add_wish_btn.click()
        
        # Wait for wish to appear in container
        wait.until(EC.text_to_be_present_in_element((By.ID, "wishContainer"), wish_title))
        print("[PASS] Wish created.")

        # Find the 'Add Money' button for THIS specific wish
        # The wish cards are generated dynamically.
        wish_cards = driver.find_elements(By.CLASS_NAME, "wish-card")
        target_card = None
        for card in wish_cards:
            if wish_title in card.text:
                target_card = card
                break
        
        if target_card:
            # The button text is "Add Money" but it also has fa-plus icon. 
            # Sometimes Selenium has trouble with exact match if there are icons or extra spaces.
            fund_btn = target_card.find_element(By.CSS_SELECTOR, "button.btn-glass")
            fund_btn.click()
            
            # Modal opens
            wait.until(EC.visibility_of_element_located((By.ID, "wishFundModal")))
            driver.find_element(By.ID, "wishFundAmount").send_keys("50")
            driver.find_element(By.XPATH, "//button[contains(@onclick, 'submitWishFund()')]").click()
            
            # Wait for modal to close
            wait.until(EC.invisibility_of_element_located((By.ID, "wishFundModal")))
            print("[PASS] Wish funded successfully.")
        else:
            print("[FAIL] Could not find 'Add Money' button for the new wish.")

        # 5. Settings & Profile
        print("\n[STEP 5] Updating Profile & Categories...")
        driver.find_element(By.CSS_SELECTOR, "[data-view='settings']").click()
        
        # Category addition via Settings
        cat_name = "Automated " + str(int(time.time()))
        driver.find_element(By.ID, "newCatName").send_keys(cat_name)
        add_cat_btn = driver.find_element(By.XPATH, "//button[contains(@onclick, 'addCategory()')]")
        add_cat_btn.click()
        
        # Handle Category added alert
        alert = wait.until(EC.alert_is_present())
        print(f"Alert detected: {alert.text}")
        alert.accept()

        # Wait for refresh
        wait.until(EC.staleness_of(add_cat_btn))
        print(f"[PASS] Category '{cat_name}' added and page refreshed.")
        
        # We need to go back to settings view if reload reset it (dashboard usually defaults to home)
        driver.find_element(By.CSS_SELECTOR, "[data-view='settings']").click()
        wait.until(EC.text_to_be_present_in_element((By.ID, "cat-list"), cat_name))
        print("[PASS] New category verified in Settings.")

        # Profile Update
        email_field = driver.find_element(By.NAME, "email")
        email_field.clear()
        email_field.send_keys("selenium_active@test.com")
        
        sync_btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Sync Profile')]")
        sync_btn.click()
        
        # Profile sync also reloads and shows toast - wait for toast
        toast = wait.until(EC.visibility_of_element_located((By.CLASS_NAME, "dynamic-toast")))
        print(f"[PASS] Settings sync toast detected: {toast.text}")

        # 6. Voice Simulation (Advanced)
        print("\n[STEP 6] Simulating Voice Command...")
        # We manually trigger the 'onresult' logic using execute_script to simulate a transcript
        driver.execute_script('let res = {results: [[{transcript: "add expense 25 for testing"}]]}; window.voiceRecognition.onresult(res);')
        
        # This triggers a fetch and then a reload on success
        time.sleep(4) # Wait for simulation reload
        print("[PASS] Voice simulation command sent.")

    except Exception as e:
        print(f"[ERROR] Test failed: {str(e)}")
        # Take screenshot on failure
        driver.get_screenshot_as_file("test_failure.png")
        print("Failure screenshot saved as 'test_failure.png'")
    
    finally:
        time.sleep(3)
        driver.quit()
        print("\n--- End-to-End Test Complete ---")

if __name__ == "__main__":
    test_end_to_end_flow()
