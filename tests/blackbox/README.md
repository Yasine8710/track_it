# Black-Box Testing Suite - DINERI

This folder contains end-to-end functional tests for the DINERI application. These tests simulate a real user interacting with the system via HTTP requests to the API endpoints and verifying the final state in the database.

## 🧪 Included Tests

- **auth_test.php**: Verifies user registration, login with correct credentials, and rejection of invalid passwords.
- **transaction_test.php**: Validates manual transaction logging (inflow/outflow) and ensures totals are calculated correctly.
- **dashboard_test.php**: Checks if the dashboard summary data matches the transactional state of the user.
- **settings_test.php**: Tests profile personalization, including username, email, and currency updates.
- **wishes_test.php**: Validates the goal tracking system, including wish creation and the funding logic that automatically records an outflow transaction.

## 🛠️ Infrastructure

- **test_utils.php**: A shared utility file that handles session simulation, HTTP request mocking (`simulate_post`, `simulate_get`), and database cleanup.
- **TEST_MODE**: Tests run with `TEST_MODE` enabled to allow the API to return data instead of redirecting or exiting.

## 📝 Example Test Snippet (Auth)

```php
// Simulating a registration request
$res = simulate_post('../../api/auth.php', [
    'action' => 'register',
    'username' => 'testuser',
    'password' => 'password123',
    'full_name' => 'Test User'
]);

if ($res['success']) {
    echo "[PASSED] User Registration: " . $res['message'] . "\n";
}
```

## 🚀 How to Run

Execute the mastering script to run all blackbox modules:

```powershell
php tests/blackbox/auth_test.php
php tests/blackbox/transaction_test.php
php tests/blackbox/dashboard_test.php
php tests/blackbox/settings_test.php
php tests/blackbox/wishes_test.php
```
