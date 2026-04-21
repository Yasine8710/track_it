<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/db.php';
}

function getUserFinancialSummary($pdo, $userId) {
    $monthStart = date('Y-m-01');
    $monthEnd   = date('Y-m-t');

    $st = $pdo->prepare("SELECT 
        SUM(CASE WHEN type = 'inflow'  THEN amount ELSE 0 END) AS total_inflow,
        SUM(CASE WHEN type = 'outflow' THEN amount ELSE 0 END) AS total_outflow
        FROM transactions WHERE user_id = ?");
    $st->execute([$userId]);
    $totals = $st->fetch();

    $totalInflow  = (float)($totals['total_inflow']  ?? 0);
    $totalOutflow = (float)($totals['total_outflow'] ?? 0);

    $st = $pdo->prepare("SELECT 
        SUM(CASE WHEN type = 'inflow'  THEN amount ELSE 0 END) AS month_inflow,
        SUM(CASE WHEN type = 'outflow' THEN amount ELSE 0 END) AS month_outflow,
        COUNT(*) AS month_transaction_count
        FROM transactions 
        WHERE user_id = ? AND transaction_date BETWEEN ? AND ?");
    $st->execute([$userId, $monthStart, $monthEnd]);
    $monthly = $st->fetch();

    $st = $pdo->prepare("
        SELECT c.name, SUM(t.amount) AS total
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.type = 'outflow'
          AND t.transaction_date BETWEEN ? AND ?
        GROUP BY c.id, c.name
        ORDER BY total DESC
        LIMIT 5");
    $st->execute([$userId, $monthStart, $monthEnd]);
    $topCategories = $st->fetchAll();

    $st = $pdo->prepare("
        SELECT t.transaction_date, t.amount, t.type, t.description, 
               c.name AS category_name
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
        ORDER BY t.transaction_date DESC, t.id DESC
        LIMIT 5");
    $st->execute([$userId]);
    $recentTransactions = $st->fetchAll();

    $st = $pdo->prepare("
        SELECT name, percentage, type
        FROM categories
        WHERE user_id = ? OR user_id IS NULL
        ORDER BY type, name");
    $st->execute([$userId]);
    $categoryBudgets = $st->fetchAll();

    return [
        'total_inflow'            => $totalInflow,
        'total_outflow'           => $totalOutflow,
        'current_balance'         => $totalInflow - $totalOutflow,
        'month_inflow'            => (float)($monthly['month_inflow']  ?? 0),
        'month_outflow'           => (float)($monthly['month_outflow'] ?? 0),
        'month_transaction_count' => (int)($monthly['month_transaction_count'] ?? 0),
        'top_categories'          => $topCategories,
        'recent_transactions'     => $recentTransactions,
        'category_budgets'        => $categoryBudgets,
    ];
}

function formatFinancialContext($summary) {
    $monthOutflow = $summary['month_outflow'];

    $lines = [
        "USER FINANCIAL SNAPSHOT:",
        "Current Balance: " . number_format($summary['current_balance'], 2) . " TND",
        "This Month: Earned " . number_format($summary['month_inflow'], 2) . " TND, Spent " 
            . number_format($monthOutflow, 2) . " TND (" 
            . $summary['month_transaction_count'] . " transactions)",
        "",
        "TOP SPENDING CATEGORIES (This Month):",
    ];

    foreach (formatTopCategories($summary['top_categories'], $monthOutflow) as $line) {
        $lines[] = $line;
    }

    $lines[] = "";
    $lines[] = "RECENT TRANSACTIONS:";

    foreach (formatRecentTransactions($summary['recent_transactions']) as $line) {
        $lines[] = $line;
    }

    return implode("\n", $lines);
}

function formatTopCategories(array $categories, float $monthOutflow) {
    if (empty($categories)) return ["No spending data for this month."];

    $lines = [];
    foreach ($categories as $cat) {
        $name    = $cat['name'];
        $amt     = (float)$cat['total'];
        $percent = $monthOutflow > 0 ? ($amt / $monthOutflow) * 100 : 0;
        $lines[] = "- {$name}: " . number_format($amt, 2) . " TND (" . round($percent, 1) . "%)";
    }
    return $lines;
}

function formatRecentTransactions(array $transactions) {
    if (empty($transactions)) return ["No recent activity found."];

    $lines = [];
    foreach ($transactions as $tx) {
        $date = date('M d', strtotime($tx['transaction_date']));
        $type = $tx['type'] === 'inflow' ? '+' : '-';
        $amt  = number_format($tx['amount'], 2);
        $desc = $tx['description'] ?: 'No description';
        $cat  = $tx['category_name'] ?: 'Uncategorized';
        $lines[] = "- {$date}: {$type}{$amt} TND ({$cat}) — {$desc}";
    }
    return $lines;
}

function buildFallbackResponse($userMessage, $summary) {
    $balance = number_format($summary['current_balance'], 2);
    $spent   = number_format($summary['month_outflow'], 2);

    $responses = [
        "Your balance is {$balance} TND, and you've spent {$spent} TND this month. Stay focused!",
        "You're doing great! You have {$balance} TND available right now.",
        "Keep track of every expense to reach your financial goals faster!",
    ];

    return [
        'response'    => $responses[array_rand($responses)],
        'suggestions' => [
            'How is my balance?',
            'Where does my money go?',
            'What is my biggest expense?',
        ]
    ];
}