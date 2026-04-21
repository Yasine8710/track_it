<?php
/**
 * ============================================================================
 * INTEGRATION TEST REPORT: Finance Integration
 * ============================================================================
 * Description:
 * This suite validates the boundaries between the core Transactions module and
 * the Goals & Wishes module. It ensures that funding a wish accurately logs
 * an outflow transaction and reduces the user's available balance across the DB.
 * 
 * How to Run:
 * 1. Ensure you are in the project root directory (`track_it`).
 * 2. Ensure MySQL is running on `localhost` with the `trackit_db` database active.
 * 3. Execute: `vendor/bin/phpunit tests/integration/FinanceIntegrationTest.php`
 * ============================================================================
 */

use PHPUnit\Framework\TestCase;

class FinanceIntegrationTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        // Define DB params manually to ensure connection in test environment
        $host = 'localhost';
        $db   = 'trackit_db';
        $user = 'root';
        $pass = '';
        $this->pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $GLOBALS['pdo'] = $this->pdo;
        $GLOBALS['pdo_mock'] = $this->pdo; // Need to supply to db.php

        // Ensure we are in a clean state for the test user (ID 99)
        $this->pdo->exec("DELETE FROM transactions WHERE user_id = 99");
        $this->pdo->exec("DELETE FROM wishes WHERE user_id = 99");
        $this->pdo->exec("DELETE FROM users WHERE id = 99");
        $this->pdo->exec("INSERT INTO users (id, username, password, balance) VALUES (99, 'test_integration', 'hash', 1000)");
        
        // Ensure at least one category exists
        $this->pdo->exec("INSERT IGNORE INTO categories (id, name, type) VALUES (1, 'Test Category', 'inflow')");

        $_SESSION['user_id'] = 99;
        if (!defined('TEST_MODE')) define('TEST_MODE', true);
    }

    /**
     * Test the Integration between Transactions and Wishes
     * Scenario: User adds income, then uses that income to fund a wish.
     */
    public function testIncomeToWishIntegration()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        // 1. Add Income (Transaction -> DB)
        $incomeData = [
            'type' => 'inflow',
            'category_id' => 1,
            'amount' => 500,
            'description' => 'Integration Salary'
        ];
        $GLOBALS['INPUT_DATA'] = json_encode($incomeData);
        
        ob_start();
        require __DIR__ . '/../../api/transaction.php';
        $content = ob_get_clean();
        $transactionOutput = json_decode($content, true);
        
        if ($transactionOutput === null) {
            $this->fail("API response was not valid JSON: " . $content);
        }
        $this->assertTrue($transactionOutput['success'], "Transaction failed: " . print_r($transactionOutput, true));

        // 2. Integration Check: Verify Transaction in DB
        $stmt = $this->pdo->prepare("SELECT SUM(amount) as total_in FROM transactions WHERE type='inflow' AND user_id = 99");
        $stmt->execute();
        $inflow = $stmt->fetch()['total_in'];
        $this->assertEquals(500, $inflow);

        // 3. Add a Wish
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $wishData = [
            'action' => 'add_wish',
            'title' => 'New Laptop',
            'target_amount' => 2000
        ];
        $GLOBALS['mock_input'] = $wishData;
        $GLOBALS['mock_method'] = 'POST';
        
        ob_start();
        $wishScript = file_get_contents(__DIR__ . '/../../api/wishes.php');
        eval('?>' . $wishScript);
        ob_get_clean();

        $stmt = $this->pdo->prepare("SELECT id FROM wishes WHERE title = 'New Laptop' AND user_id = 99");
        $stmt->execute();
        $wishId = $stmt->fetch()['id'];

        // 4. Fund the Wish (Wishes Logic -> User Balance Logic)
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $fundData = [
            'id' => $wishId,
            'amount' => 1200
        ];
        $GLOBALS['mock_input'] = $fundData;
        $GLOBALS['mock_method'] = 'PUT';

        ob_start();
        $wishScript = file_get_contents(__DIR__ . '/../../api/wishes.php');
        $fundOutput = eval('?>' . $wishScript);
        ob_end_clean();

        $this->assertTrue($fundOutput['success'], "Funding failed: " . print_r($fundOutput, true));

        // 5. Final Integration Check: Wish current_amount vs User balance
        $stmt = $this->pdo->prepare("SELECT current_amount FROM wishes WHERE id = ?");
        $stmt->execute([$wishId]);
        $wish = $stmt->fetch();
        $this->assertEquals(1200, $wish['current_amount']);

        $stmt = $this->pdo->prepare("SELECT SUM(amount) as total_out FROM transactions WHERE type='outflow' AND user_id = 99");
        $stmt->execute();
        $outflow = $stmt->fetch()['total_out'];
        $this->assertEquals(1200, $outflow); // 1200 out for funding
    }

    protected function tearDown(): void
    {
        $this->pdo->exec("DELETE FROM transactions WHERE user_id = 99");
        $this->pdo->exec("DELETE FROM wishes WHERE user_id = 99");
        $this->pdo->exec("DELETE FROM users WHERE id = 99");
    }
}
