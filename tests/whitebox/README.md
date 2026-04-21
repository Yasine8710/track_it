# White-Box Testing Suite - DINERI

This folder contains internal logic tests that verify the code structure, branching, and algorithmic correctness of the DINERI backend. These tests use a **Mock PDO** to isolate logic from the database.

## 🎯 Coverage Goals

The suite is designed to achieve 100% coverage across the following criteria:
1. **Statement Coverage**: Every executable line is hit.
2. **Decision (Branch) Coverage**: Every `if/else` path is tested.
3. **Condition Coverage**: Multiple conditions in a single `if` (e.g., `A && B`) are tested for all truth combinations.
4. **Path Coverage**: Unique end-to-end logical paths through functions.
5. **Loop Coverage**: Loops like category matching or default creation are tested for 0, 1, and many iterations.

## 🧪 Coverage Modules

- **auth_logic_test.php**: Tests password hashing and the default category creation loop.
- **voice_logic_test.php**: Validates the regex for amount extraction and the keyword matching logic.
- **wishes_logic_test.php**: Tests transactional integrity when funding a wish.
- **categories_logic_test.php**: Tests category management and color collision logic.
- **data_logic_test.php**: Verifies the mathematical accuracy of balance calculations.
- **transaction_logic_test.php**: Validates amount constraints and CRUD operations.
- **save_settings_logic_test.php**: Tests profile update paths and avatar security filters.
- **history_logic_test.php**: Tests retrieval logic and exception handling.

## 📝 Example Test Snippet (Voice Logic)

```php
// Testing path where a user says an amount without a category
$transcript = "I spent 50 dollars";
$amount = extract_amount($transcript); // White-box test for regex
assertEquals(50.00, $amount, "Logic: Matches exact amount from string");
```

## 📊 Automated Reporting

Run the master coverage report to see the full audit of all 43 logical paths:

```powershell
php tests/whitebox/coverage_report.php
```

![Coverage Screenshot](https://raw.githubusercontent.com/Yasine8710/track_it/main/tests/whitebox/coverage_sample.png)
*(Note: Replace with actual local screenshot path if hosted)*
