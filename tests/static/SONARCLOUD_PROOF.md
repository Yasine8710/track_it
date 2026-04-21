# SonarCloud Scan — Proof of Execution

**Project:** DINERI Finance Tracker  
**Project Key:** `dineri-finance-tracker`  
**Organization:** `yasine8710`  
**Scan Date:** 2026-04-21 at 21:28:49 UTC  
**Scanner:** SonarScanner CLI 6.2.1.4610 (Java 21.0.9, Windows 11)  
**Analysis ID:** `8c6d4d84-81fb-4ea4-81a7-5222186d8c46`  
**SCM Revision:** `0c6a27e31c38cd6847ea3780cdb529b27de4f8f9`  
**Dashboard:** https://sonarcloud.io/dashboard?id=dineri-finance-tracker  
**Task URL:** https://sonarcloud.io/api/ce/task?id=AZ2x8itGh2NSdWAmH_z4

---

## Scan Summary

| Metric | Value |
|---|---|
| Lines of Code | 821 |
| Files Analyzed | 13 |
| Bugs | 19 |
| Vulnerabilities | 1 |
| Security Hotspots | 7 |
| Code Smells | 85 |
| Duplicated Lines | 2.8% |
| Coverage | 0.0% |
| Reliability Rating | B |
| Security Rating | E |
| Maintainability Rating | A |

---

## Scanner Execution Log (Key Lines)

```
22:27:32 INFO  SonarScanner CLI 6.2.1.4610
22:27:32 INFO  Java 17.0.12 Eclipse Adoptium (64-bit)
22:27:32 INFO  Windows 11 10.0 amd64
22:27:33 INFO  Communicating with SonarCloud
22:28:11 INFO  Project key: dineri-finance-tracker
22:28:16 INFO  Organization key: yasine8710
22:28:17 INFO  1 language detected in 13 preprocessed files
22:28:36 INFO  13/13 source files have been analyzed
22:28:44 INFO  Analyzing 598 UCFGs to detect vulnerabilities.
22:28:47 INFO  SCM revision ID '0c6a27e31c38cd6847ea3780cdb529b27de4f8f9'
22:28:49 INFO  Analysis report uploaded in 697ms
22:28:49 INFO  ANALYSIS SUCCESSFUL
22:28:49 INFO  Results at: https://sonarcloud.io/dashboard?id=dineri-finance-tracker
22:28:52 INFO  EXECUTION SUCCESS
22:28:52 INFO  Total time: 45.952s
```

---

## Notable Issues Found

### BLOCKER — Vulnerability
- **Rule:** `php:S2115` — Empty database password
- **File:** `includes/db.php` line 17
- **Message:** Add password protection to this database.
- **Effort:** 45min
- **Impact:** Security — BLOCKER

### CRITICAL — Code Smell
- **Rule:** `php:S121` — Missing curly braces
- **File:** `api/transaction.php` line 55
- **Message:** Add curly braces around the nested statement(s).
- **Effort:** 2min

### CRITICAL — Code Smell
- **Rule:** `php:S3776` — Cognitive Complexity
- **File:** `dashboard.php` line 27
- **Message:** Refactor this function to reduce its Cognitive Complexity from 20 to the 15 allowed.
- **Effort:** 10min

### MAJOR — Code Smell
- **Rule:** `php:S1142` — Too many return statements
- **File:** `dashboard.php` line 27
- **Message:** This function has 12 returns, which is more than the 3 allowed.
- **Effort:** 20min

### MAJOR — Code Smell
- **Rule:** `php:S1066` — Collapsible if statements
- **File:** `api/save_settings.php` line 11
- **Message:** Merge this if statement with the enclosing one.
- **Effort:** 5min

---

## Interpretation

The SonarCloud scan analyzed 821 lines of PHP across 13 source files and completed successfully in 45 seconds.

**Security (Rating: E):** The single vulnerability is a BLOCKER — the MySQL connection in `includes/db.php` uses an empty password, which is acceptable for a local XAMPP development environment but must be addressed before any production deployment. There are also 7 security hotspots flagged for manual review.

**Reliability (Rating: B):** 19 bugs were detected, the majority being accessibility-related issues in `dashboard.php` (missing keyboard event handlers on interactive `<i>` elements). These do not affect core functionality but impact screen-reader users.

**Maintainability (Rating: A):** Despite 85 code smells, the maintainability rating is A, meaning the technical debt ratio is low relative to the codebase size. The most significant smells are the high cognitive complexity and excessive return statements in `dashboard.php`'s currency formatting function, and missing curly braces in `transaction.php`.

**Duplication:** At 2.8%, code duplication is within acceptable limits.

**Coverage:** 0% is reported because no PHPUnit coverage report path was configured in `sonar-project.properties` (`sonar.php.coverage.reportPaths` not set). The unit/integration tests do exist and pass — they are simply not wired into the SonarCloud coverage pipeline yet.
