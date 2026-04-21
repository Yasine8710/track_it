<?php
use PHPUnit\Framework\TestCase;

class WishesApiTest extends TestCase
{
    private $pdo;
    private $stmt;

    protected function setUp(): void
    {
        if (!defined('TEST_MODE')) {
            define('TEST_MODE', true);
        }
        $this->pdo = $this->createMock(PDO::class);
        $this->stmt = $this->createMock(PDOStatement::class);
        $GLOBALS['pdo'] = $this->pdo;
    }

    /**
     * Requirement: Input Validation
     * Test creating a wish with invalid data (empty title or zero amount)
     */
    public function testCreateWishValidation()
    {
        $_SESSION['user_id'] = 1;
        $GLOBALS['mock_method'] = 'POST';
        
        // Edge Case: Empty title
        $GLOBALS['mock_input'] = ['title' => '', 'target_amount' => 100];
        $result = include __DIR__ . '/../../api/wishes.php';
        $this->assertFalse($result['success'], "Should fail with empty title");
        $this->assertEquals('Invalid data', $result['message']);

        // Edge Case: Zero/Negative amount
        $GLOBALS['mock_input'] = ['title' => 'Travel', 'target_amount' => 0];
        $result = include __DIR__ . '/../../api/wishes.php';
        $this->assertFalse($result['success'], "Should fail with zero amount");

        $GLOBALS['mock_input'] = ['title' => 'Travel', 'target_amount' => -50];
        $result = include __DIR__ . '/../../api/wishes.php';
        $this->assertFalse($result['success'], "Should fail with negative amount");
    }

    /**
     * Requirement: Logic inside the function & Exception handling
     * Test funding a wish - verifies transaction handling and rollbacks
     */
    public function testFundWishLogicAndRollback()
    {
        $_SESSION['user_id'] = 1;
        $GLOBALS['mock_method'] = 'PUT';
        $GLOBALS['mock_input'] = ['id' => 5, 'amount' => 50];

        // Prepare mock for the 3 sequential queries in PUT
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchColumn')->willReturn('New Car');

        // 1. Test Success Path (Logic Check)
        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('commit');

        $result = include __DIR__ . '/../../api/wishes.php';
        $this->assertTrue($result['success'], "Wish funding should succeed");

        // 2. Test Exception Handling (Edge Case: DB Error during update)
        // Reset expectation for next include
        $this->pdo = $this->createMock(PDO::class);
        $GLOBALS['pdo'] = $this->pdo;
        $this->pdo->method('prepare')->willReturn($this->stmt);
        
        $this->pdo->expects($this->once())->method('rollBack');
        $this->stmt->method('execute')->willThrowException(new Exception("DB connection failed"));

        $result = include __DIR__ . '/../../api/wishes.php';
        $this->assertFalse($result['success'], "Should handle database exceptions gracefully");
        $this->assertEquals('Failed to fund wish', $result['message']);
    }

    /**
     * Requirement: Return values
     * Test unauthorized access
     */
    public function testUnauthorizedAccess()
    {
        unset($_SESSION['user_id']); // Ensure no session exists
        $result = include __DIR__ . '/../../api/wishes.php';
        
        $this->assertFalse($result['success'], "Should return success => false for unauthorized");
        $this->assertEquals('Unauthorized', $result['message'], "Error message must be correct");
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['pdo']);
        unset($GLOBALS['mock_method']);
        unset($GLOBALS['mock_input']);
    }
}
