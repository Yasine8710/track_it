# DINERI Static Testing Suite

This folder contains the configuration and tools for **Static Testing** of the DINERI application. We use these to ensure the code uses modern, clean, and bug-free practices without actually executing it.

## 🚀 How to Run the Tests

Move into this directory first:
```powershell
cd tests/static
```

### 1. Run Logical Health Check (PHPStan)
Scans all files for logical errors, type mismatches, and undefined variables. I have configured it to reach **Level 5** strictness.
```powershell
php ../../vendor/bin/phpstan analyze -c phpstan.neon
```

### 2. Run Quality & Style Audit (PHPCS)
Identifies "dirty" code, non-standard indentation, or legacy PHP tags against the **PSR-12** expert standard.
```powershell
php ../../vendor/bin/phpcs --standard=PSR12 ../../api ../../includes ../../*.php
```

### 3. Automatically Fix Formatting (PHPCBF)
If PHPCS finds many simple errors (like whitespace or indentation), run this to fix them across the entire app at once:
```powershell
php ../../vendor/bin/phpcbf --standard=PSR12 ../../api ../../includes ../../*.php
```

### 4. Advanced Analysis (SonarQube)
I have installed a local **SonarScanner** in the project root. You can use it to find security vulnerabilities and deep code issues.

**To run a scan:**
1.  **Start your SonarQube Server** (or use SonarCloud).
2.  Open `sonar-project.properties` in the root folder and update your `sonar.host.url` and `sonar.token`.
3.  Run the local scanner:
```powershell
./sonar-scanner/bin/sonar-scanner.bat
```

---

## 📂 Analysis Scope & Files
The tools are configured to analyze the following core components of the app:

| Component | Files Included | Purpose |
| :--- | :--- | :--- |
| **Backend APIs** | `api/*.php` | Authenticate, Transactions, Wishes, AI Voice |
| **Logic Layer** | `includes/db.php` | Database connection health |
| **Main Views** | `index.php`, `dashboard.php` | Front-end controller logic |
| **User Flow** | `register.php` | User creation integrity |

## 📊 Summary of Current Health
- **Logical Accuracy:** Level 5 (Strong). No critical logical failures detected.
- **Coding Standard:** PSR-12. Currently identifying formatting improvements.
- **Critical Alert:** `register.php` contains a UTF-8 BOM character (can cause browser issues).

---

## Configuration Files
- `phpstan.neon`: Custom settings for PHPStan logic analysis.
- `phpstan_bootstrap.php`: Helper script to mock database objects (`$pdo`) for safe testing.

