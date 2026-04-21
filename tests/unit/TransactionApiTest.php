<?php
use PHPUnit\Framework\TestCase;

class TransactionApiTest extends TestCase
{
    private $mockPdo;
    private $mockStmt;

    protected function setUp(): void
    {
        if (!defined('TEST_MODE')) {
            define('TEST_MODE', true);
        }

        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStmt = $this->createMock(PDOStatement::class);
    }

    public function testDeleteTransactionRequiresLogin()
    {
        $_SESSION = []; // No user logged in
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $GLOBALS['pdo'] = $this->mockPdo;
        
        $this->mockPdo->method('prepare')->willReturn($this->mockStmt);
        $this->mockStmt->method('execute')->willReturn(true);

        // Capture output
        ob_start();
        include __DIR__ . '/../../api/transaction.php';
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertFalse($response['success'], "Should fail when not logged in");
    }

    public function testAddTransactionSuccess()
    {
        $_SESSION['user_id'] = 1;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Prepare global variable for mock data as handled in transaction.php
        $GLOBALS['INPUT_DATA'] = json_encode([
            'amount' => 100,
            'type' => 'outflow',
            'category_id' => 1,
            'description' => 'Coffee',
            'date' => '2026-04-21'
        ]);

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);

        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        // Inject mock PDO
        $GLOBALS['pdo'] = $this->mockPdo;

        ob_start();
        $result = include __DIR__ . '/../../api/transaction.php';
        ob_get_clean();

        $this->assertTrue($result['success'], "Transaction should be added successfully");
    }

    public function testAddTransactionInvalidAmount()
    {
        $_SESSION['user_id'] = 1;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $GLOBALS['INPUT_DATA'] = json_encode(['amount' => 0]);

        ob_start();
        $result = include __DIR__ . '/../../api/transaction.php';
        ob_get_clean();

        $this->assertFalse($result['success'], "Should fail with zero amount");
        $this->assertEquals('Invalid amount', $result['message']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['pdo']);
        unset($GLOBALS['INPUT_DATA']);
    }
}
