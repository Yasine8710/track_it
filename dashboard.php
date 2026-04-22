<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['logout'])) { session_destroy(); }
    header('Location: index.php');
    exit;
}
require_once 'includes/db.php';

function formatMoney($amount, $currency = 'TND') {
    $symbols = [
        'TND' => 'DT',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'CAD' => 'CA$',
        'AUD' => 'A$',
        'JPY' => '¥'
    ];
    $symbol = $symbols[$currency] ?? $currency . ' ';
    return $symbol . number_format((float)$amount, 2);
}

function getCategoryIcon($name) {
    if (!$name) return 'fa-tag';
    $name = strtolower($name);
    if (strpos($name, 'food') !== false || strpos($name, 'dine') !== false || strpos($name, 'groc') !== false) return 'fa-utensils';
    if (strpos($name, 'car') !== false || strpos($name, 'gas') !== false || strpos($name, 'trans') !== false) return 'fa-car';
    if (strpos($name, 'util') !== false || strpos($name, 'electric') !== false) return 'fa-bolt';
    if (strpos($name, 'rent') !== false || strpos($name, 'home') !== false || strpos($name, 'house') !== false) return 'fa-home';
    if (strpos($name, 'health') !== false || strpos($name, 'med') !== false) return 'fa-heartbeat';
    if (strpos($name, 'game') !== false || strpos($name, 'fun') !== false || strpos($name, 'entert') !== false) return 'fa-gamepad';
    if (strpos($name, 'school') !== false || strpos($name, 'edu') !== false) return 'fa-graduation-cap';
    if (strpos($name, 'bill') !== false) return 'fa-file-invoice';
    if (strpos($name, 'shop') !== false || strpos($name, 'cloth') !== false) return 'fa-shopping-bag';
    if (strpos($name, 'work') !== false || strpos($name, 'salary') !== false || strpos($name, 'wage') !== false) return 'fa-briefcase';
    return 'fa-tag';
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmtIn = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'inflow'");
$stmtIn->execute([$user_id]);
$inflowTotal = (float)($stmtIn->fetch()['total'] ?? 0);

$stmtOut = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'outflow'");
$stmtOut->execute([$user_id]);
$outflowTotal = (float)($stmtOut->fetch()['total'] ?? 0);

$calculatedBalance = $inflowTotal - $outflowTotal;

$stmtUpdateBal = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
$stmtUpdateBal->execute([$calculatedBalance, $user_id]);

$stmtHistory = $pdo->prepare("
    SELECT t.id, t.amount, t.type, t.description, t.transaction_date, c.name as cat_name, c.id as cat_id
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ?
    ORDER BY t.transaction_date DESC, t.id DESC
");
$stmtHistory->execute([$user_id]);
$allOps = $stmtHistory->fetchAll();
$recentOps = array_slice($allOps, 0, 5);

$stmtStats = $pdo->prepare("
    SELECT c.name, SUM(t.amount) as total, c.color
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ? 
    AND t.type = 'outflow' 
    AND t.transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY c.name, c.color
    ORDER BY total DESC
");
$stmtStats->execute([$user_id]);
$catStats = $stmtStats->fetchAll();



$stmtCats = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? OR user_id IS NULL");
$stmtCats->execute([$user_id]);
$userCats = $stmtCats->fetchAll();

$userCurrency = $user['currency'] ?? 'TND';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DINERI | Workspace</title>
    <meta name="color-scheme" content="dark light">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/chat.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>const USER_CURRENCY = '<?= $userCurrency ?>';</script>
    <script src="js/app.js" defer></script>
    <style>
        .sidebar { width: 280px; height: 100vh; position: fixed; left: 0; top: 0; background: var(--bg); border-right: 1px solid var(--border); padding: 40px 24px; transition: transform 0.3s; z-index: 1000; }
        .main-content { margin-left: 280px; padding: 40px; min-height: 100vh; padding-bottom: 120px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: 12px; color: var(--text-sub); text-decoration: none; font-weight: 600; margin-bottom: 8px; transition: all 0.3s; cursor: pointer; }
        .nav-item:hover, .nav-item.active { background: var(--card); color: var(--text-main); }
        .nav-item.active { border: 1px solid var(--border); color: var(--accent); }
        .view-section { display: none; }
        .view-section.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 1024px) { .sidebar { display: none; } .main-content { margin-left: 0; padding: 20px; padding-bottom: 110px; } }
        .voice-fab { position: fixed; bottom: 100px; right: 24px; width: 60px; height: 60px; border-radius: 50%; background: var(--accent); color: #000; display: flex; align-items: center; justify-content: center; font-size: 24px; cursor: pointer; box-shadow: 0 10px 30px var(--accent-glow); z-index: 1000; transition: transform 0.3s; }
        .voice-fab:active { transform: scale(0.9); }
        .voice-fab.active { background: var(--danger); animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); } 70% { box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        .momentum-card {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(139,92,246,0.92), rgba(79,70,229,0.95));
            color: #fff;
            padding: 28px;
            min-height: 220px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .momentum-card::before {
            content: '';
            position: absolute;
            inset: -20px -20px 0 50%;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            filter: blur(30px);
            z-index: 0;
        }
        .momentum-card .widget-head {
            position: relative;
            z-index: 1;
        }
        .momentum-card h3 {
            font-size: 1.4rem;
            margin-bottom: 10px;
        }
        .momentum-card p { position: relative; z-index: 1; color: rgba(255,255,255,0.8); margin-bottom: 18px; }
        .momentum-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(120px, 1fr));
            gap: 14px;
            position: relative;
            z-index: 1;
        }
        .momentum-details span {
            background: rgba(255,255,255,0.1);
            padding: 14px 16px;
            border-radius: 18px;
            text-align: center;
            font-size: 0.95rem;
        }
        .momentum-details strong { display: block; font-size: 1.2rem; margin-bottom: 4px; color: #fff; }
        .momentum-graph {
            position: absolute;
            right: -30px;
            bottom: -20px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
            z-index: 0;
        }
        @media (max-width: 640px) {
            .momentum-card { min-height: auto; padding: 20px; }
            .momentum-details { grid-template-columns: 1fr; }
            .momentum-graph { display: none; }
        }
        .pet-card:hover { background: rgba(255,255,255,0.05); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        @media (max-width: 768px) { .pet-card { min-width: 100px; } }
        .chart-container { position: relative; width: 100%; max-width: 350px; margin: 0 auto; }
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
        .cat-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px solid var(--border); margin-bottom: 8px; }
        .premium-input-group { position: relative; margin-bottom: 20px; }
        .premium-input-group label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-sub); margin-bottom: 6px; letter-spacing: 0.5px; }
        .settings-avatar-ring { width: 100px; height: 100px; border-radius: 50%; border: 3px solid var(--accent); padding: 4px; margin: 0 auto 24px; position: relative; overflow: hidden; }
        .settings-avatar-ring img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .quick-entry-tabs { display: flex; gap: 4px; background: rgba(0,0,0,0.2); padding: 4px; border-radius: 10px; margin-bottom: 12px; }
        .qe-tab { flex: 1; text-align: center; padding: 8px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; color: var(--text-sub); }
        .qe-tab.active { background: var(--card); color: var(--text-main); }
        .qe-tab.active.expense { color: var(--danger); }
        .qe-tab.active.income { color: var(--success); }
        .calendar-grid { 
            display: grid; 
            grid-template-columns: repeat(7, 1fr); 
            gap: 4px; 
            background: rgba(255,255,255,0.01);
            border-radius: 12px;
            padding: 4px;
        }
        .calendar-day { 
            background: rgba(255,255,255,0.02); 
            padding: 6px; 
            border-radius: 10px; 
            border: 1px solid var(--border); 
            min-height: 85px; 
            display: flex; 
            flex-direction: column; 
            position: relative;
            transition: all 0.2s ease;
        }
        .calendar-day-header { font-size: 11px; font-weight: 700; color: var(--text-sub); text-align: right; margin-bottom: 2px; }
        .calendar-event { 
            font-size: 9px; 
            padding: 3px 2px; 
            border-radius: 6px; 
            font-weight: 700; 
            text-align: center; 
            margin-top: auto;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media (max-width: 640px) {
            .calendar-day { min-height: 60px; padding: 4px; }
            .calendar-day-header { font-size: 10px; }
            .calendar-event { font-size: 8px; padding: 2px; letter-spacing: -0.5px; }
            .calendar-grid { gap: 2px; padding: 2px; }
        }
        .calendar-event.flow-positive { background: rgba(16,185,129,0.15); color: var(--success); border: 1px solid rgba(16,185,129,0.2); }
        .calendar-event.flow-negative { background: rgba(239,68,68,0.15); color: var(--danger); border: 1px solid rgba(239,68,68,0.2); }
        .calendar-day.clickable { cursor: pointer; }
        .calendar-day.clickable:hover { background: rgba(255,255,255,0.05); border-color: var(--accent); }
        .calendar-day.active { border-color: var(--accent); background: rgba(212,180,245,0.1); }
        .wish-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-top: 24px; }
        .wish-card { background: var(--surface); border: 1px solid var(--border); border-radius: 18px; padding: 20px; position: relative; overflow: hidden; }
        .wish-progress-bg { width: 100%; height: 8px; background: rgba(0,0,0,0.2); border-radius: 8px; margin: 16px 0; overflow: hidden; }
        .wish-progress-fill { height: 100%; background: var(--accent); border-radius: 8px; transition: width 0.5s ease; }
        @keyframes emojiPop {
            0% { opacity: 0; transform: translate(-50%, 50px) scale(0.5); }
            15% { opacity: 1; transform: translate(-50%, -30px) scale(1.3); }
            30% { opacity: 1; transform: translate(-50%, -20px) scale(1.1); }
            80% { opacity: 1; transform: translate(-50%, -40px) scale(1); }
            100% { opacity: 0; transform: translate(-50%, -100px) scale(0.8); }
        }
        .floating-emoji {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 100px;
            z-index: 999999;
            pointer-events: none;
            animation: emojiPop 1.8s ease-out forwards;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
</head>
<body style="color-scheme: dark;">
    <div id="toast"></div>
    <script>
        if (localStorage.getItem('theme') === 'light') {
            document.body.style.colorScheme = 'light';
        } else {
            document.documentElement.style.colorScheme = 'dark';
        }
    </script>
    <aside class="sidebar">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom: 48px;">
            <div style="width:40px; height:40px; background:var(--accent); border-radius:10px; display:flex; align-items:center; justify-content:center; color:#000;"><i class="fas fa-wallet"></i></div>
            <span style="font-family:'Outfit'; font-size:24px; font-weight:700;">DINERI</span>
        </div>
        <nav>
            <div onclick="switchView('home')" class="nav-item active" data-view="home"><i class="fas fa-home"></i> Home</div>
            <div onclick="switchView('stats')" class="nav-item" data-view="stats"><i class="fas fa-chart-pie"></i> Analytics</div>
            <div onclick="switchView('history')" class="nav-item" data-view="history"><i class="fas fa-history"></i> Logbook</div>
            <div onclick="switchView('calendar')" class="nav-item" data-view="calendar"><i class="fas fa-calendar-alt"></i> Calendar</div>
            <div onclick="switchView('wishes')" class="nav-item" data-view="wishes"><i class="fas fa-star"></i> Wishes</div>
            <div onclick="switchView('chat')" class="nav-item" data-view="chat"><i class="fas fa-robot"></i> Assistant</div>
            <div onclick="switchView('settings')" class="nav-item" data-view="settings"><i class="fas fa-cog"></i> Settings</div>
            <a href="dashboard.php?logout=1" class="nav-item" style="margin-top:40px; color:var(--danger);"><i class="fas fa-sign-out-alt"></i> Exit</a>
        </nav>
    </aside>

    <nav class="bottom-nav">
        <div onclick="switchView('home')" class="bottom-nav-item active" data-view="home"><i class="fas fa-home"></i><span>Home</span></div>
        <div onclick="switchView('stats')" class="bottom-nav-item" data-view="stats"><i class="fas fa-chart-pie"></i><span>Stats</span></div>
        <div onclick="switchView('history')" class="bottom-nav-item" data-view="history"><i class="fas fa-history"></i><span>Logs</span></div>
        <div onclick="switchView('calendar')" class="bottom-nav-item" data-view="calendar"><i class="fas fa-calendar-alt"></i><span>Calendar</span></div>
        <div onclick="switchView('wishes')" class="bottom-nav-item" data-view="wishes"><i class="fas fa-star"></i><span>Wishes</span></div>
        <div onclick="switchView('chat')" class="bottom-nav-item" data-view="chat"><i class="fas fa-robot"></i><span>AI</span></div>
        <div onclick="switchView('settings')" class="bottom-nav-item" data-view="settings"><i class="fas fa-cog"></i><span>Settings</span></div>
    </nav>

    <main class="main-content">
        <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 40px;">
            <div style="flex:1;"><h2 id="view-title" style="font-family:'Outfit';">Workspace Active</h2><p id="view-sub" style="color:var(--text-sub); font-size:14px;">Welcome, <?= htmlspecialchars($user['username']) ?></p></div>
            <img src="<?= $user['profile_picture'] ?: 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' ?>" style="width:48px; height:48px; border-radius:50%; border:2px solid var(--accent); cursor:pointer;" onclick="switchView('settings')">
        </header>

        <!-- HOME -->
        <div id="section-home" class="view-section active">
            <section class="stat-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 40px;">
                <div class="premium-card" style="background: linear-gradient(135deg, var(--accent) 0%, #4c1d95 100%); color: #000;">
                    <p style="font-size:12px; font-weight:700; text-transform:uppercase; opacity:0.8;">Total Balance</p>
                    <div style="display:flex; align-items:center; gap:10px;"><h1 id="dynamic-balance" style="font-family:'Outfit'; font-size:42px; margin:10px 0;"><?= formatMoney($calculatedBalance, $userCurrency) ?></h1><i class="fas fa-plus-circle" onclick="openBalanceEdit()" style="cursor:pointer; font-size:16px; opacity:0.6;"></i></div>
                    <div style="font-size:11px; background:rgba(255,255,255,0.2); padding:4px 8px; border-radius:6px; display:inline-block;">Live Sync</div>
                </div>
                <div class="premium-card"><p style="color:var(--text-sub); font-size:12px; font-weight:700; text-transform:uppercase;">Monthly In</p><h2 id="dynamic-inflow" style="color:var(--success); font-family:'Outfit';">+<?= formatMoney($inflowTotal, $userCurrency) ?></h2></div>
                <div class="premium-card" style="position:relative;"><p style="color:var(--text-sub); font-size:12px; font-weight:700; text-transform:uppercase;">Monthly Out</p><h2 id="dynamic-outflow" style="color:var(--danger); font-family:'Outfit';">-<?= formatMoney($outflowTotal, $userCurrency) ?></h2><span style="position:absolute; top:16px; right:16px; background:rgba(255,255,255,0.05); padding:4px 8px; border-radius:6px; font-size:11px; color:var(--text-sub); font-weight:700;" id="dynamic-outflow-perc"><?= $inflowTotal > 0 ? round(($outflowTotal / $inflowTotal) * 100) : 0 ?>% of Income</span></div>
                <div class="momentum-card">
                    <div class="widget-head">
                        <p style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px; opacity:0.85;">Momentum Beacon</p>
                        <h3>Finance flow is strong.</h3>
                        <p>Track your earnings, expenses and progress with a brighter, more creative pulse.</p>
                    </div>
                    <div class="momentum-details">
                        <span><strong><?= count($userCats) ?></strong>Categories</span>
                        <span><strong><?= count($recentOps) ?></strong>Recent logs</span>
                        <?php $totFlow = max(1, $inflowTotal + $outflowTotal); ?>
                        <span><strong><?= round(($inflowTotal / $totFlow) * 100) ?>%</strong>Inflow Ratio</span>
                        <span><strong><?= round(($outflowTotal / $totFlow) * 100) ?>%</strong>Outflow Ratio</span>
                    </div>
                    <div class="momentum-graph"></div>
                </div>
                <div class="premium-card" style="background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(16,185,129,0.02)); border: 1px solid rgba(16,185,129,0.2); position:relative; overflow:hidden;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px;">
                        <div>
                            <p style="color:var(--text-sub); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">Growth Stocks</p>
                            <h3 style="font-family:'Outfit'; font-size:24px; color:var(--text-main);">S&P 500</h3>
                        </div>
                        <div style="background:rgba(16,185,129,0.2); color:var(--success); padding:6px 10px; border-radius:8px; font-weight:700; font-size:14px; display:flex; align-items:center; gap:6px;">
                            <i class="fas fa-arrow-trend-up"></i> +2.4%
                        </div>
                    </div>
                    <p style="font-size:13px; color:var(--text-sub); line-height:1.5;">Your portfolio is tracking positive. Market conditions are favorable for long-term investments today.</p>
                    <div style="position:absolute; bottom:-10px; right:10px; opacity:0.1; font-size:80px; transform:rotate(-15deg); pointer-events:none;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </section>


            
            <section>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="font-family:'Outfit';">Quick Entry</h3>
                </div>
                <div class="premium-card" style="margin-bottom:30px;">
                    <div class="quick-entry-tabs">
                        <div onclick="setQEType('outflow')" id="qe-tab-outflow" class="qe-tab active expense">Expense</div>
                        <div onclick="setQEType('inflow')" id="qe-tab-inflow" class="qe-tab income">Income</div>
                    </div>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:12px;">
                        <input type="number" id="quickAmount" class="modern-input" placeholder="0.00">
                        <select id="quickCat" class="modern-input dark-select-fix">
                            <option value="" style="background:#1a1a1a; color:#fff;">Category...</option>
                            <?php foreach($userCats as $c): ?>
                                <option value="<?= $c['id'] ?>" style="background:#1a1a1a; color:#fff;"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" id="quickDesc" class="modern-input" placeholder="Memo...">
                        <button onclick="saveQuickEntry()" id="qe-btn" class="btn-glass" style="background:var(--accent); color:#000; justify-content:center; border:none; font-weight:700;">Log Expense</button>
                    </div>
                </div>

                <h3 style="margin-bottom:20px; font-family:'Outfit';">Recent Activity</h3>
                <div id="recent-list">
                    <?php if(empty($recentOps)): ?>
                        <p style="text-align:center; padding:20px; color:var(--text-sub);">No transactions found.</p>
                    <?php endif; ?>
                    <?php foreach ($recentOps as $tx): ?>
                        <div class="premium-card" style="padding:16px; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center;">
                            <div style="display:flex; gap:16px; align-items:center;">
                                <div style="width:40px; height:40px; border-radius:12px; background:rgba(255,255,255,0.03); display:flex; align-items:center; justify-content:center; color:<?= $tx['type'] == 'inflow' ? 'var(--success)' : 'var(--danger)' ?>;"><i class="fas <?= getCategoryIcon($tx['cat_name']) ?>"></i></div>
                                <div><p style="font-weight:600;"><?= htmlspecialchars($tx['description'] ?: $tx['cat_name']) ?></p><p style="font-size:12px; color:var(--text-sub);"><?= date('M d', strtotime($tx['transaction_date'])) ?></p></div>
                            </div>
                            <p style="font-weight:700; color:<?= $tx['type'] == 'inflow' ? 'var(--success)' : 'var(--danger)' ?>;"><?= $tx['type'] == 'inflow' ? '+' : '-' ?><?= formatMoney($tx['amount'], $userCurrency) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <!-- STATS -->
        <div id="section-stats" class="view-section">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                <div class="premium-card">
                    <h3 style="margin-bottom:20px; font-family:'Outfit';">Spending Profile</h3>
                    <div class="chart-container" style="max-width: 280px;"><canvas id="spendingChart"></canvas></div>
                    <div style="margin-top: 30px; text-align: center;">
                        <span style="font-size: 11px; color: var(--text-sub); text-transform: uppercase; font-weight: 700;">Top Expense</span>
                        <h2 style="font-family: 'Outfit'; color: var(--accent);"><?= !empty($catStats) ? htmlspecialchars($catStats[0]['name']) : 'N/A' ?></h2>
                    </div>
                </div>

                <div class="premium-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <h3 style="margin-bottom:20px; font-family:'Outfit';">Category Efficiency</h3>
                        <?php foreach($catStats as $stat):?>
                            <div style="margin-bottom:20px;">
                                <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:8px;">
                                    <span style="font-weight:600; display:flex; align-items:center; gap:8px;">
                                        <div style="width:8px; height:8px; border-radius:50%; background:<?= $stat['color'] ?>;"></div>
                                        <i class="fas <?= getCategoryIcon($stat['name']) ?>" style="opacity:0.5; font-size:11px;"></i> <?= htmlspecialchars($stat['name']) ?>
                                    </span>
                                    <span style="color:var(--text-main); font-weight:700;"><?= formatMoney($stat['total'], $userCurrency) ?></span>
                                </div>
                                <div style="width:100%; height:6px; background:rgba(255,255,255,0.05); border-radius:10px; overflow:hidden;">
                                    <div style="width:<?= ($stat['total'] / max(1, $outflowTotal)) * 100 ?>%; height:100%; background:<?= $stat['color'] ?>;"></div>
                                </div>
                                <div style="display:flex; justify-content:flex-end; font-size:10px; color:var(--text-sub); margin-top:4px;">
                                    <?= round(($stat['total'] / max(1, $outflowTotal)) * 100) ?>% of budget
                                </div>
                            </div>
                        <?php endforeach; if(empty($catStats)): ?>
                            <div style="text-align:center; padding:40px; color:var(--text-sub); font-size:14px;">No data recorded yet.</div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 15px; border: 1px dashed var(--border);">
                        <p style="font-size: 12px; color: var(--text-sub);">AI Insight</p>
                        <p style="font-size: 13px; margin-top: 5px;">You've spent <b><?= formatMoney($outflowTotal, $userCurrency) ?></b> recently. High volume detected in <b><?= !empty($catStats) ? htmlspecialchars($catStats[0]['name']) : 'uncategorized' ?></b>.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- HISTORY -->
        <div id="section-history" class="view-section">
            <div style="margin-bottom:24px; display:flex; gap:12px; flex-wrap:wrap;">
                <div style="flex:1; min-width:200px; position:relative;">
                    <i class="fas fa-search" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--text-sub); pointer-events:none;"></i>
                    <input type="text" id="histSearch" class="modern-input" placeholder="Search transactions..." style="padding-left:44px;">
                </div>
                <div style="display:flex; gap:12px; width:100%; max-width:400px;">
                    <select id="histCatFilter" class="modern-input dark-select-fix" style="flex:1;">
                        <option value="all" style="background:#1a1a1a; color:#fff;">Every Category</option>
                        <?php foreach($userCats as $c): ?>
                            <option value="<?= $c['id'] ?>" style="background:#1a1a1a; color:#fff;"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="histFilter" class="modern-input dark-select-fix" style="flex:1;">
                        <option value="all" style="background:#1a1a1a; color:#fff;">All Types</option>
                        <option value="inflow" style="background:#1a1a1a; color:#fff;">Inflow</option>
                        <option value="outflow" style="background:#1a1a1a; color:#fff;">Outflow</option>
                    </select>
                </div>
            </div>
            
            <div id="history-container" style="display: grid; gap: 12px;">
                <?php foreach ($allOps as $tx): ?>
                    <div class="tx-row premium-card" data-cat="<?= $tx['cat_id'] ?>" data-type="<?= $tx['type'] ?>" data-desc="<?= strtolower($tx['description']) ?>" style="padding:16px; display:flex; justify-content:space-between; align-items:center; border: 1px solid var(--border); transition: transform 0.2s ease;">
                        <div style="display:flex; gap:16px; align-items:center;">
                            <div style="width:48px; height:48px; border-radius:14px; background:var(--surface); display:flex; align-items:center; justify-content:center; color:<?= $tx['type'] == 'inflow' ? 'var(--success)' : 'var(--danger)' ?>; font-size:18px;">
                                <i class="fas <?= getCategoryIcon($tx['cat_name']) ?>"></i>
                            </div>
                            <div>
                                <p style="font-weight:600; font-size:16px; color:var(--text-main);"><?= htmlspecialchars($tx['description'] ?: $tx['cat_name']) ?></p>
                                <p style="font-size:12px; color:var(--text-sub); margin-top:2px;">
                                    <i class="far fa-calendar-alt" style="margin-right:4px;"></i><?= date('M d, Y', strtotime($tx['transaction_date'])) ?>
                                    <span style="margin: 0 8px; opacity:0.3;">|</span>
                                    <i class="fas fa-tag" style="margin-right:4px;"></i><?= htmlspecialchars($tx['cat_name'] ?: 'Uncategorized') ?>
                                </p>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <p style="font-weight:700; font-size:18px; font-family:'Outfit'; color:<?= $tx['type'] == 'inflow' ? 'var(--success)' : 'var(--danger)' ?>;">
                                <?= $tx['type'] == 'inflow' ? '+' : '-' ?><span class="tx-amt-display"><?= formatMoney($tx['amount'], $userCurrency) ?></span>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CALENDAR -->
        <div id="section-calendar" class="view-section">
            <div class="stat-grid" style="grid-template-columns: 1fr;">
                <div class="premium-card">
                    <h3 style="font-family:'Outfit'; margin-bottom:20px;">Financial Calendar</h3>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <button class="btn-glass" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                        <h4 id="calendarMonthYear" style="font-size:18px;"></h4>
                        <button class="btn-glass" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <div class="calendar-grid" id="calendarGrid" style="display:grid; grid-template-columns:repeat(7, 1fr); gap:8px;"></div>
                </div>
            </div>
            <div id="calendar-day-details" style="display:none; margin-top:24px;">
                <h3 style="font-family:'Outfit'; margin-bottom:16px;" id="calendar-day-title">Day Details</h3>
                <div id="calendar-day-list" style="display:grid; gap:12px;"></div>
            </div>
        </div>

        <!-- WISHES -->
        <div id="section-wishes" class="view-section">
            <div class="premium-card" style="margin-bottom: 24px;">
                <h3 style="font-family:'Outfit'; margin-bottom:20px;">Save for a Wish</h3>
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <div style="flex:1; min-width:200px;"><input type="text" id="wishTitle" class="modern-input" placeholder="What are you saving for? (e.g. Car, Travel)"></div>
                    <div style="flex:1; min-width:120px;"><input type="number" id="wishTarget" class="modern-input" placeholder="Target Price" step="0.01"></div>
                    <button class="btn-glass" onclick="addWish()" style="min-width:120px; justify-content:center;">Create Wish</button>
                </div>
            </div>
            
            <div id="wishContainer" class="wish-grid">
                <!-- Wishes will be generated here -->
            </div>
        </div>

        <!-- CHAT ASSISTANT -->
        <div id="section-chat" class="view-section">
            <div class="dnchat">

                <div class="dnchat__head">
                    <div class="dnchat__head-icon"><i class="fas fa-robot"></i></div>
                    <div class="dnchat__head-info">
                        <div class="dnchat__head-name">DINARI Assistant</div>
                        <div class="dnchat__head-status">
                            <span class="dnchat__head-dot"></span>Online &middot; Gemini AI
                        </div>
                    </div>
                    <span class="dnchat__head-badge">AI</span>
                </div>

                <div class="dnchat__feed" id="chat-messages"></div>

                <div class="dnchat__chips" id="chat-suggestions"></div>

                <div class="dnchat__bar">
                    <input id="chat-input" class="dnchat__input"
                           type="text" placeholder="Ask about your finances…" autocomplete="off"
                           onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();handleChatSend();}">
                    <button class="dnchat__send" title="Send" onclick="handleChatSend()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>

            </div>
        </div>

        <!-- SETTINGS -->
        <div id="section-settings" class="view-section">
            <div class="settings-grid">
                <div class="premium-card">
                    <div class="settings-avatar-ring"><img id="preview-avatar" src="<?= $user['profile_picture'] ?: 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' ?>"></div>
                    <form id="profileForm" enctype="multipart/form-data">
                        <div class="premium-input-group">
                            <label>Profile Picture</label>
                            <input type="file" name="avatar_file" id="avatar_file" class="modern-input" accept="image/*" style="padding: 10px;">
                        </div>
                        <div class="premium-input-group"><label>Display Name</label><input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="modern-input"></div>
                        <div class="premium-input-group"><label>Email Address</label><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="modern-input"></div>
                        <div class="premium-input-group"><label>Currency Preference</label>
                            <select name="currency" class="modern-input dark-select-fix">
                                <option value="TND" <?= $userCurrency === 'TND' ? 'selected' : '' ?> style="background:#1a1a1a; color:#fff;">Tunisian Dinar (TND)</option>
                                <option value="USD" <?= $userCurrency === 'USD' ? 'selected' : '' ?> style="background:#1a1a1a; color:#fff;">United States Dollar (USD)</option>
                                <option value="EUR" <?= $userCurrency === 'EUR' ? 'selected' : '' ?> style="background:#1a1a1a; color:#fff;">Euro (EUR)</option>
                                <option value="GBP" <?= $userCurrency === 'GBP' ? 'selected' : '' ?> style="background:#1a1a1a; color:#fff;">British Pound (GBP)</option>
                                <option value="CAD" <?= $userCurrency === 'CAD' ? 'selected' : '' ?> style="background:#1a1a1a; color:#fff;">Canadian Dollar (CAD)</option>
                                <option value="AUD" <?= $userCurrency === 'AUD' ? 'selected' : '' ?> style="background:#1a1a1a; color:#fff;">Australian Dollar (AUD)</option>
                                <option value="JPY" <?= $userCurrency === 'JPY' ? 'selected' : '' ?> style="background:#1a1a1a; color:#fff;">Japanese Yen (JPY)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-glass" style="width:100%; justify-content:center; background:var(--accent); color:#000; border:none; font-weight:700; margin-bottom:12px;">Sync Profile</button>
                        <a href="dashboard.php?logout=1" class="btn-glass" style="width:100%; justify-content:center; color:var(--danger); border-color:var(--danger); text-decoration:none; font-weight:700; display:flex; align-items:center; gap:8px;"><i class="fas fa-sign-out-alt"></i> Exit Workspace</a>
                    </form>
                </div>
                <div style="display:flex; flex-direction:column; gap:24px;">
                    <div class="premium-card">
                        <h3 style="margin-bottom:20px; font-family:'Outfit';">Environment</h3>
                        <div class="premium-input-group"><label>Visual Mode</label><select id="themeSelect" class="modern-input dark-select-fix"><option value="dark" style="background:#1a1a1a; color:#fff;">Obsidian Dark</option><option value="light" style="background:#1a1a1a; color:#fff;">Crystal Light</option></select></div>
                    </div>
                    <div class="premium-card" style="background: linear-gradient(135deg, rgba(56,189,248,0.1), rgba(16,185,129,0.12)); border-color: rgba(16,185,129,0.18);">
                        <h3 style="margin-bottom:20px; font-family:'Outfit';">Workspace Pulse</h3>
                        <div style="display:grid; gap:16px;">
                            <div style="padding:18px; border-radius:18px; background: rgba(255,255,255,0.04);">
                                <p style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-sub); margin-bottom:6px; letter-spacing:1px;">Today’s insight</p>
                                <p style="font-size:16px; font-weight:700; color:var(--text-main);">Your balance has been updated with the latest entries.</p>
                            </div>
                            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                <span style="flex:1 1 140px; padding:12px 14px; border-radius:16px; background: rgba(255,255,255,0.06);">Smart edits: <?= count($recentOps) ?></span>
                                <span style="flex:1 1 140px; padding:12px 14px; border-radius:16px; background: rgba(255,255,255,0.06);">Saved categories: <?= count($userCats) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="premium-card">
                        <h3 style="margin-bottom:20px; font-family:'Outfit';">Category Vault</h3>
                        <div style="display:flex; gap:10px; margin-bottom:24px;"><input type="text" id="newCatName" class="modern-input" placeholder="Name..."><button onclick="addCategory()" class="btn-glass" style="width:48px; justify-content:center;"><i class="fas fa-plus"></i></button></div>
                        <div id="cat-list">
                            <?php foreach($userCats as $cat): ?>
                                <div class="cat-item"><span style="display:flex; align-items:center; gap:10px;"><?php if($cat['user_id']): ?><input type="color" value="<?= $cat['color'] ?>" onchange="updateCategoryColor(<?= $cat['id'] ?>, this.value)" style="width:18px;height:18px;border:none;padding:0;cursor:pointer;background:transparent;" title="Change color"><?php else: ?><div style="width:12px; height:12px; border-radius:3px; background:<?= $cat['color'] ?>;"></div><?php endif; ?><i class="fas <?= getCategoryIcon($cat['name']) ?>" style="opacity:0.5; font-size:12px;"></i> <?= htmlspecialchars($cat['name']) ?></span><?php if($cat['user_id']): ?><div style="display:flex; gap:10px;"><i class="fas fa-pen" style="color:var(--accent); cursor:pointer; opacity:0.6;" onclick="editCategoryName(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>')"></i><i class="fas fa-trash-alt" style="color:var(--danger); cursor:pointer; opacity:0.6;" onclick="delCategory(<?= $cat['id'] ?>)"></i></div><?php endif; ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="balModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:9000; align-items:center; justify-content:center;">
        <div class="premium-card" style="width:90%; max-width:380px;"><h3 style="margin-bottom:20px; font-family:'Outfit';">Manual Adjustment</h3><p style="font-size:12px; color:var(--text-sub); margin-bottom:15px;">Set your desired balance entry.</p><input type="number" id="newBal" step="0.01" class="modern-input" placeholder="0.00"><div style="display:flex; gap:12px; margin-top:30px;"><button onclick="closeBalanceModal()" class="btn-glass" style="flex:1;">Cancel</button><button onclick="saveManualBalance()" class="btn-glass" style="flex:1; background:var(--accent); color:#000;">Add Entry</button></div></div>
    </div>

    <div class="voice-fab" id="mic-btn"><i class="fas fa-microphone"></i></div>
    <div id="voice-status" style="position:fixed; bottom:100px; right:24px; background:rgba(0,0,0,0.8); color:white; padding:8px 16px; border-radius:20px; font-size:12px; display:none; z-index:9999;"></div>

    <script>
        let qeType = 'outflow';
        async function syncStats() {
            try {
                const res = await fetch('api/data.php');
                const data = await res.json();
                if(data.success) {
                    document.getElementById('dynamic-balance').innerText = formatCurrency(data.balance);
                    document.getElementById('dynamic-inflow').innerText = '+' + formatCurrency(data.inflow);
                    document.getElementById('dynamic-outflow').innerText = '-' + formatCurrency(data.outflow);
                    if(document.getElementById('dynamic-outflow-perc')) {
                        const iAmount = parseFloat(data.inflow) || 0;
                        const oAmount = parseFloat(data.outflow) || 0;
                        document.getElementById('dynamic-outflow-perc').innerText = iAmount > 0 ? Math.round((oAmount/iAmount)*100) + '% of Income' : '0% of Income';
                    }
                }
            } catch(e) { console.error('Sync failed', e); }
        }

        function setQEType(type) {
            qeType = type;
            document.querySelectorAll('.qe-tab').forEach(t => t.classList.remove('active'));
            document.getElementById('qe-tab-' + type).classList.add('active');
            document.getElementById('qe-btn').innerText = 'Log ' + (type === 'inflow' ? 'Income' : 'Expense');
        }

        async function saveQuickEntry() {
            const amount = document.getElementById('quickAmount').value;
            const catId = document.getElementById('quickCat').value;
            const desc = document.getElementById('quickDesc').value;
            if(!amount) return alert('Enter amount');
            if(qeType === 'outflow' && !catId) return alert('Select category for expense');
            const res = await fetch('api/transaction.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: parseFloat(amount), category_id: catId, description: desc, type: qeType }) 
            });
            const result = await res.json();
            if(result.success) { 
                const emoji = qeType === 'inflow' ? '🤑' : '😢';
                const el = document.createElement('div');
                el.className = 'floating-emoji';
                el.innerText = emoji;
                document.body.appendChild(el);
                
                syncStats().then(() => { 
                    document.getElementById('quickAmount').value = ''; 
                    document.getElementById('quickDesc').value = ''; 
                    setTimeout(() => location.reload(), 1800); 
                }); 
            }
            else alert('Error: ' + (result.message || 'Unknown error'));
        }

        function switchView(view) {
            document.querySelectorAll('.view-section').forEach(s => s.classList.remove('active'));
            document.getElementById('section-' + view).classList.add('active');
            document.querySelectorAll('.nav-item, .bottom-nav-item').forEach(el => el.classList.toggle('active', el.dataset.view === view));
            // Hide voice FAB on chat tab so it doesn't cover the send button
            const micBtn = document.getElementById('mic-btn');
            if (micBtn) micBtn.style.display = view === 'chat' ? 'none' : '';
            if(view === 'stats') initChart();
            if(view === 'calendar') renderCalendar();
            if(view === 'wishes') fetchWishes();
            if(view === 'chat') initChat();
        }

        async function fetchWishes() {
            const res = await fetch('api/wishes.php');
            const data = await res.json();
            if(data.success) renderWishes(data.wishes);
        }

        function renderWishes(wishes) {
            const container = document.getElementById('wishContainer');
            if(!wishes.length) {
                container.innerHTML = '<div style="text-align:center; padding:40px; color:var(--text-sub);">No wishes active right now. Start dreaming!</div>';
                return;
            }
            container.innerHTML = wishes.map(w => {
                const perc = Math.min(100, (w.current_amount / w.target_amount) * 100);
                const isCompleted = parseFloat(w.current_amount) >= parseFloat(w.target_amount);
                const maxAllowed = parseFloat(w.target_amount) - parseFloat(w.current_amount);
                const escapedTitle = w.title.replace(/'/g, "\\'");
                
                return `<div class="wish-card" style="${isCompleted ? 'border-color:var(--success);' : ''}">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h4 style="font-size:18px; font-family:'Outfit';">${w.title} <span style="font-size:12px; background:rgba(255,255,255,0.08); padding:2px 8px; border-radius:10px; margin-left:8px; vertical-align:middle; color:var(--text-sub);">${Math.round(perc)}%</span></h4>
                        <i class="fas fa-trash-alt" style="color:var(--danger); cursor:pointer; opacity:0.6;" onclick="deleteWish(${w.id})"></i>
                    </div>
                    <div class="wish-progress-bg">
                        <div class="wish-progress-fill" style="width: ${perc}%; ${isCompleted ? 'background:var(--success);' : ''}"></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:14px; font-weight:600; margin-bottom:20px;">
                        <span style="color:${isCompleted ? 'var(--success)' : 'var(--success)'};">${formatCurrency(parseFloat(w.current_amount))}</span>
                        <span style="color:var(--text-sub);">Goal: ${formatCurrency(parseFloat(w.target_amount))}</span>
                    </div>
                    ${isCompleted ? 
                        `<button class="btn-glass" style="width:100%; justify-content:center; background:rgba(16,185,129,0.1); color:var(--success); border:1px solid rgba(16,185,129,0.3); pointer-events:none;">
                            <i class="fas fa-check-circle"></i> Completed 🎉
                        </button>` 
                        : 
                        `<button class="btn-glass" style="width:100%; justify-content:center;" onclick="addWishFunds(${w.id}, '${escapedTitle}', ${maxAllowed})">
                            <i class="fas fa-plus"></i> Add Money
                        </button>`
                    }
                </div>`;
            }).join('');
        }

        async function addWish() {
            const title = document.getElementById('wishTitle').value;
            const target_amount = document.getElementById('wishTarget').value;
            if(!title || target_amount <= 0) return alert('Invalid inputs');
            const res = await fetch('api/wishes.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({title: title, target_amount: parseFloat(target_amount)})});
            if((await res.json()).success) { document.getElementById('wishTitle').value = ''; document.getElementById('wishTarget').value = ''; fetchWishes(); }
        }

        async function addWishFunds(id, title, maxAllowed) {
            document.getElementById('wishFundModal').style.display = 'flex';
            document.getElementById('wishFundTitle').innerText = 'Fund: ' + title;
            document.getElementById('wishFundId').value = id;
            document.getElementById('wishFundMax').value = maxAllowed;
            document.getElementById('wishFundAmount').value = '';
            document.getElementById('wishFundAmount').max = maxAllowed;
            document.getElementById('wishFundAmount').focus();

            // Update currency symbol dynamically
            const symbols = { 'TND': 'DT', 'USD': '$', 'EUR': '€', 'GBP': '£', 'CAD': 'CA$', 'AUD': 'A$', 'JPY': '¥' };
            document.getElementById('wishFundCurrency').innerText = symbols[USER_CURRENCY] || USER_CURRENCY;
        }

        async function submitWishFund() {
            const id = document.getElementById('wishFundId').value;
            const amtStr = document.getElementById('wishFundAmount').value;
            const max = parseFloat(document.getElementById('wishFundMax').value);
            const amt = parseFloat(amtStr);
            
            if(amt && !isNaN(amt) && amt > 0) {
                if (amt > max) {
                    alert('You cannot exceed the target goal! Maximum allowed to complete this wish is ' + formatCurrency(max));
                    return;
                }
                const res = await fetch('api/wishes.php', { method: 'PUT', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id, amount: amt})});
                const data = await res.json();
                if(data.success) {
                    closeWishFundModal();
                    if(typeof syncStats === 'function') syncStats();
                    fetchWishes();
                    
                    if (amt === max) {
                        // User completed the wish! Fire celebration
                        if (typeof confetti === 'function') {
                            confetti({
                                particleCount: 150,
                                spread: 70,
                                origin: { y: 0.6 },
                                colors: ['#BB86FC', '#03DAC6', '#FF0266']
                            });
                        }
                    }
                } else {
                    alert('Failed to fund wish. Ensure enough balance if backend checks are configured.');
                }
            } else {
                alert('Please enter a valid amount greater than 0.');
            }
        }
        
        function closeWishFundModal() {
            document.getElementById('wishFundModal').style.display = 'none';
        }

        async function deleteWish(id) {
            if(confirm('Delete this wish completely?')) {
                const res = await fetch('api/wishes.php?id=' + id, {method: 'DELETE'});
                if((await res.json()).success) fetchWishes();
            }
        }

        const allTransactions = <?= json_encode($allOps) ?>;
        let currentDate = new Date();
        
        function changeMonth(dir) {
            currentDate.setMonth(currentDate.getMonth() + dir);
            renderCalendar();
        }

        const getCatIconJS = (name) => {
            if(!name) return 'fa-tag';
            name = name.toLowerCase();
            if(name.includes('food') || name.includes('dine') || name.includes('groc')) return 'fa-utensils';
            if(name.includes('car') || name.includes('gas') || name.includes('trans')) return 'fa-car';
            if(name.includes('util') || name.includes('electric')) return 'fa-bolt';
            if(name.includes('rent') || name.includes('home') || name.includes('house')) return 'fa-home';
            if(name.includes('health') || name.includes('med')) return 'fa-heartbeat';
            if(name.includes('game') || name.includes('fun')) return 'fa-gamepad';
            if(name.includes('school') || name.includes('edu')) return 'fa-graduation-cap';
            if(name.includes('shop') || name.includes('cloth')) return 'fa-shopping-bag';
            if(name.includes('work') || name.includes('salary')) return 'fa-briefcase';
            return 'fa-tag';
        };

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            document.getElementById('calendarMonthYear').innerText = new Date(year, month).toLocaleString('default', { month: 'long', year: 'numeric' });
            
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '';
            
            const days = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
            days.forEach(d => {
                const h = document.createElement('div');
                h.style.cssText = 'text-align:center; font-size:10px; font-weight:800; color:var(--text-sub); margin-bottom:4px; opacity:0.6;';
                h.innerText = d;
                grid.appendChild(h);
            });
            
            const prevMonthDays = new Date(year, month, 0).getDate();
            for(let i=0; i<firstDay; i++) {
                const cell = document.createElement('div');
                cell.className = 'calendar-day';
                cell.style.opacity = '0.3';
                cell.innerHTML = `<div class="calendar-day-header">${prevMonthDays - firstDay + i + 1}</div>`;
                grid.appendChild(cell);
            }
            
            for(let d=1; d<=daysInMonth; d++) {
                const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                
                const dayOps = allTransactions.filter(t => {
                    if (!t.transaction_date) return false;
                    const txDate = t.transaction_date.split(' ')[0];
                    return txDate === dateStr;
                });
                
                const cell = document.createElement('div');
                cell.className = 'calendar-day clickable';
                
                let html = `<div class="calendar-day-header">${d}</div>`;
                
                if(dayOps.length) {
                    let total = 0;
                    dayOps.forEach(op => {
                        const amt = parseFloat(op.amount) || 0;
                        total += (op.type === 'inflow' ? amt : -amt);
                    });
                    const cls = total >= 0 ? 'flow-positive' : 'flow-negative';
                    const sign = total >= 0 ? '+' : '';
                    html += `<div class="calendar-event ${cls}">${sign}${formatCurrency(Math.abs(total))}</div>`;
                }
                
                cell.onclick = () => showDayDetails(dateStr, dayOps, cell);
                cell.innerHTML = html;
                grid.appendChild(cell);
            }
            
            const totalCells = firstDay + daysInMonth;
            const trailingDays = (7 - (totalCells % 7)) % 7;
            for(let i=1; i<=trailingDays; i++) {
                const cell = document.createElement('div');
                cell.className = 'calendar-day';
                cell.style.opacity = '0.3';
                cell.innerHTML = `<div class="calendar-day-header">${i}</div>`;
                grid.appendChild(cell);
            }
        }

        function showDayDetails(dateStr, ops, cellEl) {
            document.querySelectorAll('.calendar-day').forEach(c => c.classList.remove('active'));
            if(cellEl) cellEl.classList.add('active');
            
            document.getElementById('calendar-day-details').style.display = 'block';
            document.getElementById('calendar-day-title').innerText = 'Transactions on ' + dateStr;
            
            const list = document.getElementById('calendar-day-list');
            
            let html = '';
            if(ops.length > 0) {
                html += ops.map(op => {
                    const isIncome = op.type === 'inflow';
                    const color = isIncome ? 'var(--success)' : 'var(--danger)';
                    const sign = isIncome ? '+' : '-';
                    return `<div class="cat-item" style="border:1px solid rgba(255,255,255,0.05); padding:10px; border-radius:12px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; width: 100%;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div style="width:32px; height:32px; border-radius:8px; background:rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center; color:${color};"><i class="fas ${getCatIconJS(op.cat_name)}"></i></div>
                                <div style="text-align: left;">
                                    <div style="font-weight:600; color:var(--text-main)">${op.description || op.cat_name || 'Uncategorized'}</div>
                                    <div style="font-size:11px; color:var(--text-sub);">${op.cat_name || 'General'}</div>
                                </div>
                            </div>
                            <div style="font-weight:700; color:${color}">${sign}${formatCurrency(parseFloat(op.amount))}</div>
                        </div>
                    </div>`;
                }).join('');
            } else {
                html += `<div style="text-align:center; padding:12px; color:var(--text-sub); background:rgba(255,255,255,0.02); border-radius:12px;">No transactions recorded for this day.</div>`;
            }
            
            html += `<div style="margin-top:16px; display:flex; justify-content:center;">
                <button class="btn-glass" onclick="switchView('home');" style="padding:6px 14px; font-size:12px;"><i class="fas fa-plus"></i> Add Entry</button>
            </div>`;
            
            list.innerHTML = html;
            list.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        let chart = null;
        function initChart() {
            const ctx = document.getElementById('spendingChart').getContext('2d');
            if(chart) chart.destroy();
            const data = {
                labels: [<?php foreach($catStats as $s) echo "'".addslashes($s['name'])."',"; ?>],
                datasets: [{
                    data: [<?php foreach($catStats as $s) echo $s['total'].","; ?>],
                    backgroundColor: [<?php foreach($catStats as $s) echo "'".$s['color']."',"; ?>],
                    borderWidth: 0
                }]
            };
            chart = new Chart(ctx, { type: 'doughnut', data: data, options: { cutout: '75%', plugins: { legend: { position: 'bottom', labels: { color: '#fff', padding: 20 } } } } });
        }

        async function addCategory() {
            const name = document.getElementById('newCatName').value;
            if(!name) return;
            const res = await fetch('api/categories.php', { method: 'POST', body: JSON.stringify({ name }) });
            if((await res.json()).success) location.reload();
        }
        async function delCategory(id) {
            if(confirm('Delete?')) fetch('api/categories.php?id='+id, { method: 'DELETE' }).then(() => location.reload());
        }
        async function editCategoryName(id, oldName) {
            const newName = prompt('Enter new name:', oldName);
            if (newName && newName.trim() !== '' && newName !== oldName) {
                const res = await fetch('api/categories.php', { method: 'PUT', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id, name: newName.trim() }) });
                if((await res.json()).success) location.reload();
            }
        }
        async function updateCategoryColor(id, color) {
            const res = await fetch('api/categories.php', { method: 'PUT', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id, color }) });
            if((await res.json()).success) location.reload();
        }

        document.getElementById('histSearch').oninput = document.getElementById('histFilter').onchange = document.getElementById('histCatFilter').onchange = () => {
            const q = document.getElementById('histSearch').value.toLowerCase();
            const f = document.getElementById('histFilter').value;
            const c = document.getElementById('histCatFilter').value;
            document.querySelectorAll('.tx-row').forEach(r => {
                const matchQ = r.dataset.desc.includes(q);
                const matchF = (f === 'all' || r.dataset.type === f);
                const matchC = (c === 'all' || r.dataset.cat === c);
                r.style.display = (matchQ && matchF && matchC) ? 'flex' : 'none';
            });
        };

        function deleteTx(id) { if(confirm('Delete?')) fetch('api/transaction.php?id='+id, { method: 'DELETE' }).then(() => location.reload()); }
        async function editTx(id, oldAmt) {
            const newAmt = prompt("Enter new amount:", oldAmt);
            if (newAmt !== null && !isNaN(newAmt) && newAmt > 0) {
                const res = await fetch('api/transaction.php', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id, amount: parseFloat(newAmt) }) });
                if ((await res.json()).success) location.reload();
            }
        }

        function openBalanceEdit() { document.getElementById('balModal').style.display = 'flex'; }
        function closeBalanceModal() { document.getElementById('balModal').style.display = 'none'; }
        async function saveManualBalance() {
            const val = document.getElementById('newBal').value;
            if(!val) return;
            const res = await fetch('api/save_inflow.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ manual_balance: parseFloat(val) }) });
            if((await res.json()).success) { closeBalanceModal(); syncStats().then(() => setTimeout(() => location.reload(), 1500)); }
        }

        function showToast(msg, isError = false) {
            console.log("Toast Triggered: " + msg);
            // Remove any existing toast
            const oldToast = document.querySelector('.dynamic-toast');
            if (oldToast) oldToast.remove();

            const toast = document.createElement('div');
            toast.className = 'dynamic-toast';
            toast.style.display = 'flex'; // Force display
            toast.style.opacity = '0';
            toast.innerHTML = `
                <div class="toast-content" style="display:flex; align-items:center; gap:12px;">
                    <div class="toast-icon" style="min-width:32px; height:32px; border-radius:10px; background:${isError ? 'var(--danger)' : 'var(--accent)'}; display:flex; align-items:center; justify-content:center; color:#000;">
                        <i class="fas ${isError ? 'fa-times' : 'fa-check'}"></i>
                    </div>
                    <div class="toast-msg" style="color:#fff; font-weight:600; font-size:14px;">${msg}</div>
                </div>
                <div class="toast-loader-bg" style="width:100%; height:3px; background:rgba(255,255,255,0.1); border-radius:10px; overflow:hidden; margin-top:12px;">
                    <div class="toast-loader-fill" style="height:100%; width:100%; background:#10b981; transform-origin:left; animation:toastProgress 2.5s linear forwards;"></div>
                </div>
            `;
            document.body.appendChild(toast);

            // Trigger enter animation with a small delay for DOM painting
            requestAnimationFrame(() => {
                toast.classList.add('active');
            });

            // Exit animation
            setTimeout(() => {
                toast.classList.add('exit');
                setTimeout(() => toast.remove(), 600);
            }, 2600);
        }

        document.getElementById('profileForm').onsubmit = async (e) => {
            e.preventDefault();
            try {
                const response = await fetch('api/save_settings.php', { method: 'POST', body: new FormData(e.target) });
                const json = await response.json();
                if(json.success) { 
                    showToast('Profile Synced!'); 
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast(json.message || 'Failed to sync.', true);
                }
            } catch (err) {
                showToast('Network error.', true);
            }
        };

        const themeSelect = document.getElementById('themeSelect');
        const applyTheme = (theme) => {
            document.documentElement.classList.toggle('light', theme === 'light');
            document.documentElement.classList.toggle('dark', theme === 'dark');
            localStorage.setItem('theme', theme);
        };
        themeSelect.onchange = (e) => applyTheme(e.target.value);
        const savedTheme = localStorage.getItem('theme') || 'dark';
        themeSelect.value = savedTheme;
        applyTheme(savedTheme);

        // Use the function from app.js to handle voice
        window.onload = () => {
            if (typeof setupVoiceRecognition === 'function') {
                setupVoiceRecognition();
            }
        };
    </script>
    <!-- Wish Funding Modal -->
    <div id="wishFundModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(5,5,5,0.92); backdrop-filter:blur(10px); z-index:9999; align-items:center; justify-content:center; padding:20px; animation: fadeIn 0.3s ease;">
        <div class="premium-card" style="width:100%; max-width:420px; padding:40px; position:relative; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); border: 1px solid rgba(255,255,255,0.1);">
            <div style="position:absolute; top:24px; right:24px; width:32px; height:32px; border-radius:10px; background:var(--surface); display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text-sub); transition: 0.3s;" onclick="closeWishFundModal()" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--text-sub)'">
                <i class="fas fa-times"></i>
            </div>
            
            <div style="text-align:center; margin-bottom:30px;">
                <div style="width:64px; height:64px; background:var(--accent-glow); color:var(--accent); border-radius:20px; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:24px;">
                    <i class="fas fa-piggy-bank"></i>
                </div>
                <h3 id="wishFundTitle" style="font-family:'Outfit'; font-size:24px; margin-bottom:8px;">Fund: car</h3>
                <p style="font-size:14px; color:var(--text-sub); line-height:1.6;">Transfer funds to your wish. This will be recorded as an outflow entry to maintain balance accuracy.</p>
            </div>

            <input type="hidden" id="wishFundId">
            <input type="hidden" id="wishFundMax">
            
            <div style="position:relative; margin-bottom:30px;">
                <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-sub); margin-bottom:10px; letter-spacing:1px; text-align:center;">Amount to Transfer</label>
                <div style="display:flex; align-items:center; justify-content:center; position:relative;">
                    <span id="wishFundCurrency" style="position:absolute; left:20px; font-size:24px; font-weight:700; color:var(--accent); opacity:0.8;"><?= $userCurrency ?></span>
                    <input type="number" id="wishFundAmount" class="modern-input" style="font-size:32px; text-align:center; font-family:'Outfit'; font-weight:700; padding:20px; background:rgba(255,255,255,0.03); border-radius:20px; border-color:rgba(139, 92, 246, 0.3);" placeholder="0.00" step="0.01">
                </div>
            </div>

            <button class="btn-glass" style="width:100%; justify-content:center; background:var(--accent); color:#000; border:none; padding:18px; font-weight:700; font-size:16px; border-radius:18px; box-shadow: 0 10px 20px var(--accent-glow);" onclick="submitWishFund()">
                <i class="fas fa-paper-plane" style="margin-right:8px;"></i> Confirm Transfer
            </button>
        </div>
    </div>
    <script src="js/chat.js?v=4"></script>
</body>
</html>
