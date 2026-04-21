# System Testing Report

**Date:** 2025-02-14
**Framework:** PHPUnit
**Target Component:** Full API Pipeline

## Overview
System Testing was conducted using the `FullSystemTest.php` suite, designed to ensure end-to-end functionality across multiple internal API interactions and verify aggregate ledger mathematical logic. The suite mimics a full application lifecycle via raw PHP programmatic endpoint inclusions.

## Test Strategy & Scenarios
1. **User Setup:** Dynamically creating a full mocked sandbox user context and capturing their implicit currency and user ID identifiers.
2. **Category Bootstrapping:** Simulating a user accessing `/api/categories.php` via a `POST` request to configure custom `outflow` buckets.
3. **Transaction Batching:** Simulating `/api/transaction.php` requests with `$GLOBALS['mock_input']` carrying high volumes of Income (`inflow`) and subsequent diverse Expenses (`outflow`).
4. **Data Rollup Assertions:** Executing `/api/data.php` via an independent simulated `GET` stream to verify that:
   - Overall mathematical `balance` calculation is fully reconciled against batch operations.
   - Separate aggregate variables, such as total monthly `inflow` vs `outflow`, compute perfectly in logic bounds.
   - The returned `JSON` response schema perfectly reflects system variables.

## Results
- **Status:** PASS ✅.
- The `data.php` endpoint calculates an aggregated `$calculatedBalance = $inflowTotal - $outflowTotal;` ensuring system fidelity regardless of transaction sequence logging.

## Remediation Applied During Testing
- Due to the nature of PHP output buffering (`ob_start`) during CLI test runs, API logic that outputs `JSON` had to be decoupled from standard procedural `exit()` commands. Tests injected mocked parameters allowing multi-stage tests to clear buffers properly and prevent "Session cannot be started after headers" warnings.