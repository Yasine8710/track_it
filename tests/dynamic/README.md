# Dynamic Testing Suite - Selenium

This folder contains automated UI (User Interface) tests that simulate a user interacting with the browser. It uses **Selenium WebDriver** to click buttons, fill forms, and verify that the page responds correctly.

## 🚀 Prerequisites

To run these tests, you need Python and Chrome installed.

1. **Python Packages**:
   The following packages must be installed:
   ```bash
   pip install selenium webdriver-manager
   ```

2. **Local Server**:
   Ensure your XAMPP Apache and MySQL are running, and the project is accessible at `http://localhost/track_it`.

## 🧪 Included Tests

- **selenium_test.py**: 
  - **Auth**: Verifies login flow.
  - **Quick Entry**: Tests adding Inflow and handling JS alerts.
  - **Navigation**: Visits History, Calendar, Stats, Wishes, and Settings.
  - **Wishes**: Creates a new wish, finds the specific card, and funds it via modal.
  - **Settings**: Adds categories and updates profile (verifying toast notifications).
  - **Voice Simulation**: Injects a mock speech recognition event to test the `api/process_voice.php` handler.

## 🏃 How to Run

Execute the script using Python:

```powershell
python tests/dynamic/selenium_test.py
```

## 🛠️ Verified Selectors (as of April 2026)

| Element | Selector | Type |
|---------|----------|------|
| Sidebar Nav | `[data-view='SECTION_NAME']` | CSS |
| Quick Amount | `quickAmount` | ID |
| Log Button | `qe-btn` | ID |
| Wish Title | `wishTitle` | ID |
| Wish Target | `wishTarget` | ID |
| Profile Email| `email` | NAME |
| Success Toast| `dynamic-toast` | CLASS |
| Nav Sections | `section-SECTION_NAME` | ID |

## 📝 Test Logic Example

```python
# Finding an element and interacting with it
driver.find_element(By.NAME, "username").send_keys("testuser")
driver.find_element(By.NAME, "password").send_keys("password123")
driver.find_element(By.XPATH, "//button[contains(text(), 'Login')]").click()
```

## 🔍 Why Dynamic Testing?
Unlike White-box logic tests or Black-box API tests, Dynamic UI testing ensures that:
1. **JavaScript** runs correctly in the browser.
2. **CSS/Layout** doesn't block user interactions.
3. **Form Submissions** work as a real user would experience them.
