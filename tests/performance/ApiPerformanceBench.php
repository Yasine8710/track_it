<?php

namespace Tests\Performance;

use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/**
 * @Revs(100)
 * @Iterations(5)
 * @OutputTimeUnit("milliseconds")
 */
class ApiPerformanceBench extends TestCase
{
    private $pdo;

    public function __construct()
    {
        // Define TEST_MODE to prevent headers/sessions
        if (!defined('TEST_MODE')) {
            define('TEST_MODE', true);
        }
    }

    /**
     * Set up a mock PDO for benchmarking the logic layer
     */
    private function setupMockPdo()
    {
        $this->pdo = $this->createMockPdo();
        $GLOBALS['pdo'] = $this->pdo;
        $_SESSION['user_id'] = 1;
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    private function createMockPdo()
    {
        $pdo = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stmt = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pdo->method('prepare')->willReturn($stmt);
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->method('commit')->willReturn(true);
        $pdo->method('lastInsertId')->willReturn('100');
        
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(['id' => 1, 'balance' => 5000, 'username' => 'testuser']);
        $stmt->method('fetchAll')->willReturn([['id' => 1, 'amount' => 100]]);

        return $pdo;
    }

    /**
     * Benchmark Transaction Logic
     * @Subject
     */
    public function benchTransactionLogic()
    {
        $this->setupMockPdo();
        
        $_POST = [
            'type' => 'outflow',
            'category_id' => 1,
            'amount' => 50,
            'description' => 'Performance Test'
        ];

        // Capture output to prevent flooding console
        ob_start();
        require __DIR__ . '/../../api/transaction.php';
        ob_end_clean();
    }

    /**
     * Benchmark Wish Logic
     * @Subject
     */
    public function benchWishLogic()
    {
        $this->setupMockPdo();
        
        $_POST = [
            'action' => 'add_wish',
            'title' => 'Speed Test',
            'target_amount' => 1000
        ];

        ob_start();
        require __DIR__ . '/../../api/wishes.php';
        ob_end_clean();
    }
}
