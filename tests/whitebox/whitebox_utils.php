<?php
/**
 * Simple Mock PDO for White-box testing without a real database.
 * This helps us test internal logic and branching without side effects.
 */

class MockPDO extends PDO {
    public $queries = [];
    public $results = [];
    public $lastInsertId = 1;
    public $inTransaction = false;
    public $lastStmt;

    public function __construct() {}

    /**
     * @return PDOStatement|MockPDOStatement
     */
    public function prepare($query, $options = []): PDOStatement|false {
        $stmt = new MockPDOStatement($query, $this);
        $this->lastStmt = $stmt;
        return $stmt;
    }

    public function lastInsertId($name = null): string {
        return (string)$this->lastInsertId;
    }

    public function beginTransaction(): bool {
        $this->inTransaction = true;
        return true;
    }

    public function commit(): bool {
        $this->inTransaction = false;
        return true;
    }

    public function rollBack(): bool {
        $this->inTransaction = false;
        return true;
    }
}

class MockPDOStatement extends PDOStatement {
    private $query;
    private $pdo;
    private $boundParams = [];

    public function __construct($query, $pdo) {
        $this->query = $query;
        $this->pdo = $pdo;
    }

    public function execute($params = null): bool {
        $this->pdo->queries[] = [
            'query' => $this->query,
            'params' => $params
        ];
        return true;
    }

    public function fetch($mode = PDO::FETCH_DEFAULT, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0): mixed {
        // Return the first available result or null
        return array_shift($this->pdo->results) ?: null;
    }

    public function fetchColumn($column = 0): mixed {
        $res = array_shift($this->pdo->results);
        if (is_array($res)) return array_shift($res);
        return $res ?: null;
    }

    public function fetchAll($mode = PDO::FETCH_DEFAULT, ...$args): array {
        $res = array_shift($this->pdo->results);
        if ($res === null) return [];
        return is_array($res) ? $res : [];
    }
}

function assertEquals($expected, $actual, $message) {
    if ($expected == $actual) {
        echo "[PASS] $message\n";
    } else {
        echo "[FAIL] $message (Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true) . ")\n";
    }
}

function assertTrue($condition, $message) {
    if ($condition) {
        echo "[PASS] $message\n";
    } else {
        echo "[FAIL] $message\n";
    }
}

// Silence warnings in tests and set default server/session state
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}
if (!isset($_SERVER['REQUEST_METHOD'])) $_SERVER['REQUEST_METHOD'] = 'POST';
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}
