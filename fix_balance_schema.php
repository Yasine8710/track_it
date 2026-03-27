<?php
require_once 'includes/db.php';

try {
    // Add balance column if missing
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS balance DECIMAL(10,2) DEFAULT 0.00");
    
    // Add email column if missing
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL");
    
    // Add profile_picture column if missing
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT NULL");

    // Recalculate all balances for all users to ensure consistency
    $stmtUsers = $pdo->query("SELECT id FROM users");
    while ($user = $stmtUsers->fetch()) {
        $uid = $user['id'];
        
        $stmtIn = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'inflow'");
        $stmtIn->execute([$uid]);
        $inflow = $stmtIn->fetch()['total'] ?? 0;

        $stmtOut = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'outflow'");
        $stmtOut->execute([$uid]);
        $outflow = $stmtOut->fetch()['total'] ?? 0;

        $balance = $inflow - $outflow;
        
        $stmtUpdate = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmtUpdate->execute([$balance, $uid]);
    }

    echo "DATABASE_FIX_SUCCESSFUL";
} catch (PDOException $e) {
    die("DATABASE_FIX_FAILED: " . $e->getMessage());
}
?>