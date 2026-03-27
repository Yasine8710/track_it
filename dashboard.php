<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['logout'])) { session_destroy(); }
    header('Location: index.php');
    exit;
}
require_once 'includes/db.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DINERI | Workspace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    </style>
</head>
<body>
    <aside class="sidebar">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom: 48px;">
            <div style="width:40px; height:40px; background:var(--accent); border-radius:10px; display:flex; align-items:center; justify-content:center; color:#000;"><i class="fas fa-wallet"></i></div>
            <span style="font-family:'Outfit'; font-size:24px; font-weight:700;">DINERI</span>
        </div>
        <nav>
            <div onclick="switchView('home')" class="nav-item active" data-view="home"><i class="fas fa-home"></i> Home</div>
            <div onclick="switchView('stats')" class="nav-item" data-view="stats"><i class="fas fa-chart-pie"></i> Analytics</div>
            <div onclick="switchView('history')" class="nav-item" data-view="history"><i class="fas fa-history"></i> Logbook</div>
            <div onclick="switchView('settings')" class="nav-item" data-view="settings"><i class="fas fa-cog"></i> Settings</div>
            <a href="dashboard.php?logout=1" class="nav-item" style="margin-top:40px; color:var(--danger);"><i class="fas fa-sign-out-alt"></i> Exit</a>
        </nav>
    </aside>

    <nav class="bottom-nav">
        <div onclick="switchView('home')" class="bottom-nav-item active" data-view="home"><i class="fas fa-home"></i><span>Home</span></div>
        <div onclick="switchView('stats')" class="bottom-nav-item" data-view="stats"><i class="fas fa-chart-pie"></i><span>Stats</span></div>
        <div onclick="switchView('history')" class="bottom-nav-item" data-view="history"><i class="fas fa-history"></i><span>Logs</span></div>
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
                    <div style="display:flex; align-items:center; gap:10px;"><h1 id="dynamic-balance" style="font-family:'Outfit'; font-size:42px; margin:10px 0;">$<?= number_format($calculatedBalance, 2) ?></h1><i class="fas fa-plus-circle" onclick="openBalanceEdit()" style="cursor:pointer; font-size:16px; opacity:0.6;"></i></div>
                    <div style="font-size:11px; background:rgba(255,255,255,0.2); padding:4px 8px; border-radius:6px; display:inline-block;">Live Sync</div>
                </div>
                <div class="premium-card"><p style="color:var(--text-sub); font-size:12px; font-weight:700; text-transform:uppercase;">Monthly In</p><h2 id="dynamic-inflow" style="color:var(--success); font-family:'Outfit';">+$<?= number_format($inflowTotal, 2) ?></h2></div>
                <div class="premium-card"><p style="color:var(--text-sub); font-size:12px; font-weight:700; text-transform:uppercase;">Monthly Out</p><h2 id="dynamic-outflow" style="color:var(--danger); font-family:'Outfit';">-$<?= number_format($outflowTotal, 2) ?></h2></div>
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
                        <select id="quickCat" class="modern-input">
                            <option value="">Category...</option>
                            <?php foreach($userCats as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
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
                                <div style="width:40px; height:40px; border-radius:12px; background:rgba(255,255,255,0.03); display:flex; align-items:center; justify-content:center; color:<?= $tx['type'] == 'inflow' ? 'var(--success)' : 'var(--danger)' ?>;"><i class="fas fa-<?= $tx['type'] == 'inflow' ? 'arrow-down' : 'arrow-up' ?>"></i></div>
                                <div><p style="font-weight:600;"><?= htmlspecialchars($tx['description'] ?: $tx['cat_name']) ?></p><p style="font-size:12px; color:var(--text-sub);"><?= date('M d', strtotime($tx['transaction_date'])) ?></p></div>
                            </div>
                            <p style="font-weight:700; color:<?= $tx['type'] == 'inflow' ? 'var(--success)' : 'var(--danger)' ?>;"><?= $tx['type'] == 'inflow' ? '+' : '-' ?>$<?= number_format($tx['amount'], 2) ?></p>
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
                                        <?= htmlspecialchars($stat['name']) ?>
                                    </span>
                                    <span style="color:var(--text-main); font-weight:700;">$<?= number_format($stat['total'], 2) ?></span>
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
                        <p style="font-size: 13px; margin-top: 5px;">You've spent <b>$<?= number_format($outflowTotal, 2) ?></b> recently. High volume detected in <b><?= !empty($catStats) ? htmlspecialchars($catStats[0]['name']) : 'uncategorized' ?></b>.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- HISTORY -->
        <div id="section-history" class="view-section">
            <div style="margin-bottom:20px; display:flex; gap:12px;">
                <input type="text" id="histSearch" class="modern-input" placeholder="Search..." style="flex:1;">
                <select id="histCatFilter" class="modern-input" style="width:140px;">
                    <option value="all">Categories</option>
                    <?php foreach($userCats as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="histFilter" class="modern-input" style="width:110px;"><option value="all">Type</option><option value="inflow">Inflow</option><option value="outflow">Outflow</option></select>
            </div>
            <div id="history-container">
                <?php foreach ($allOps as $tx): ?>
                    <div class="tx-row premium-card" data-cat="<?= $tx['cat_id'] ?>" data-type="<?= $tx['type'] ?>" data-desc="<?= strtolower($tx['description']) ?>" style="padding:16px; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; gap:166px; align-items:center;">
                            <div style="width:40px; height:40px; border-radius:12px; background:rgba(255,255,255,0.03); display:flex; align-items:center; justify-content:center; color:<?= $tx['type'] == 'inflow' ? 'var(--success)' : 'var(--danger)' ?>;"><i class="fas fa-<?= $tx['type'] == 'inflow' ? 'arrow-down' : 'arrow-up' ?>"></i></div>
                            <div><p style="font-weight:600;"><?= htmlspecialchars($tx['description'] ?: $tx['cat_name']) ?></p><p style="font-size:12px; color:var(--text-sub);"><?= date('M d, Y', strtotime($tx['transaction_date'])) ?></p></div>
                        </div>
                        <div style="text-align:right;"><p style="font-weight:700; color:<?= $tx['type'] == 'inflow' ? 'var(--success)' : 'var(--danger)' ?>;"><?= $tx['type'] == 'inflow' ? '+' : '-' ?>$<span class="tx-amt-display"><?= number_format($tx['amount'], 2) ?></span></p><div style="display:flex; gap:8px; justify-content:flex-end;"><span onclick="editTx(<?= $tx['id'] ?>, <?= $tx['amount'] ?>)" style="font-size:11px; color:var(--accent); cursor:pointer;">Edit</span><span onclick="deleteTx(<?= $tx['id'] ?>)" style="font-size:11px; color:var(--danger); cursor:pointer;">Delete</span></div></div>
                    </div>
                <?php endforeach; ?>
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
                        <button type="submit" class="btn-glass" style="width:100%; justify-content:center; background:var(--accent); color:#000; border:none; font-weight:700; margin-bottom:12px;">Sync Profile</button>
                        <a href="dashboard.php?logout=1" class="btn-glass" style="width:100%; justify-content:center; color:var(--danger); border-color:var(--danger); text-decoration:none; font-weight:700; display:flex; align-items:center; gap:8px;"><i class="fas fa-sign-out-alt"></i> Exit Workspace</a>
                    </form>
                </div>
                <div style="display:flex; flex-direction:column; gap:24px;">
                    <div class="premium-card">
                        <h3 style="margin-bottom:20px; font-family:'Outfit';">Environment</h3>
                        <div class="premium-input-group"><label>Visual Mode</label><select id="themeSelect" class="modern-input"><option value="dark">Obsidian Dark</option><option value="light">Crystal Light</option></select></div>
                    </div>
                    <div class="premium-card">
                        <h3 style="margin-bottom:20px; font-family:'Outfit';">Category Vault</h3>
                        <div style="display:flex; gap:10px; margin-bottom:24px;"><input type="text" id="newCatName" class="modern-input" placeholder="Name..."><button onclick="addCategory()" class="btn-glass" style="width:48px; justify-content:center;"><i class="fas fa-plus"></i></button></div>
                        <div id="cat-list">
                            <?php foreach($userCats as $cat): ?>
                                <div class="cat-item"><span style="display:flex; align-items:center; gap:10px;"><div style="width:12px; height:12px; border-radius:3px; background:<?= $cat['color'] ?>;"></div><?= htmlspecialchars($cat['name']) ?></span><?php if($cat['user_id']): ?><i class="fas fa-trash-alt" style="color:var(--danger); cursor:pointer; opacity:0.6;" onclick="delCategory(<?= $cat['id'] ?>)"></i><?php endif; ?></div>
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

    <div class="voice-fab" id="voice-btn"><i class="fas fa-microphone"></i></div>

    <script>
        let qeType = 'outflow';
        async function syncStats() {
            try {
                const res = await fetch('api/data.php');
                const data = await res.json();
                if(data.success) {
                    document.getElementById('dynamic-balance').innerText = '$' + data.balance;
                    document.getElementById('dynamic-inflow').innerText = '+$' + data.inflow;
                    document.getElementById('dynamic-outflow').innerText = '-$' + data.outflow;
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
            if(result.success) { syncStats().then(() => { document.getElementById('quickAmount').value = ''; document.getElementById('quickDesc').value = ''; setTimeout(() => location.reload(), 1500); }); }
            else alert('Error: ' + (result.message || 'Unknown error'));
        }

        function switchView(view) {
            document.querySelectorAll('.view-section').forEach(s => s.classList.remove('active'));
            document.getElementById('section-' + view).classList.add('active');
            document.querySelectorAll('.nav-item, .bottom-nav-item').forEach(el => el.classList.toggle('active', el.dataset.view === view));
            if(view === 'stats') initChart();
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

        document.getElementById('profileForm').onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch('api/save_settings.php', { method: 'POST', body: new FormData(e.target) });
            if((await res.json()).success) { alert('Synced.'); location.reload(); }
        };

        document.getElementById('themeSelect').onchange = (e) => { document.documentElement.className = e.target.value; localStorage.setItem('theme', e.target.value); };
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.className = savedTheme;
        document.getElementById('themeSelect').value = savedTheme;

        const voiceBtn = document.getElementById('voice-btn');
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (SpeechRecognition) {
            const recognition = new SpeechRecognition();
            recognition.onstart = () => { voiceBtn.classList.add('active'); };
            recognition.onend = () => { voiceBtn.classList.remove('active'); };
            recognition.onresult = async (e) => {
                const transcript = e.results[0][0].transcript;
                const res = await fetch('api/process_voice.php', { method: 'POST', body: JSON.stringify({ transcript }) });
                if((await res.json()).success) location.reload();
            };
            voiceBtn.onclick = () => recognition.start();
        }
    </script>
</body>
</html>
