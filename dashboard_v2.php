<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/db.php';

// Fetch current user data for profile picture
$stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();
$avatarPath = $currentUser['profile_picture'] ?? null;
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dineri</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        black: '#000000',
                        white: '#ffffff',
                        neutral: {
                            50: '#fafafa',
                            100: '#f5f5f5',
                            200: '#e5e5e5',
                            300: '#d4d4d4',
                            400: '#a3a3a3',
                            500: '#737373',
                            600: '#525252',
                            700: '#404040',
                            800: '#262626',
                            900: '#171717',
                            950: '#0a0a0a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #fafafa; color: #171717; transition: background-color 0.2s, color 0.2s; -webkit-tap-highlight-color: transparent; }
        html.dark body { background-color: #000000; color: #f5f5f5; }
        
        .dineri-card { 
            background: #ffffff; 
            border: 1px solid #e5e5e5; 
            border-radius: 1rem; 
            transition: all 0.2s ease;
        }
        html.dark .dineri-card { 
            background: #0a0a0a; 
            border-color: #262626; 
        }

        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }

        .view-section { display: none; animation: fadeUp 0.3s ease-out forwards; }
        .view-section.active { display: block; }
        
        @keyframes fadeUp { 
            from { opacity: 0; transform: translateY(8px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        /* Bottom Nav Active State */
        .mobile-nav-item.active { color: #171717; font-weight: 600; }
        html.dark .mobile-nav-item.active { color: #ffffff; }
        .mobile-nav-item.active .nav-icon { transform: translateY(-2px); }
        
        /* Desktop Sidebar Active */
        .desktop-nav-item.active { background-color: #f5f5f5; color: #171717; font-weight: 600; }
        html.dark .desktop-nav-item.active { background-color: #171717; color: #ffffff; }

        /* Input Styles */
        .dineri-input {
            width: 100%; padding: 0.75rem 1rem; border-radius: 0.75rem; font-weight: 500; outline: none; transition: border-color 0.2s;
            background-color: #fafafa; border: 1px solid #e5e5e5; color: #171717;
        }
        .dineri-input:focus { border-color: #a3a3a3; }
        html.dark .dineri-input {
            background-color: #0a0a0a; border-color: #262626; color: #f5f5f5;
        }
        html.dark .dineri-input:focus { border-color: #525252; }

        .dineri-btn {
            width: 100%; padding: 0.75rem; border-radius: 0.75rem; font-weight: 600; text-align: center; transition: all 0.2s;
            background-color: #171717; color: #ffffff; border: 1px solid #171717;
        }
        .dineri-btn:hover { background-color: #404040; border-color: #404040; }
        html.dark .dineri-btn { background-color: #ffffff; color: #000000; border-color: #ffffff; }
        html.dark .dineri-btn:hover { background-color: #d4d4d4; border-color: #d4d4d4; }
    </style>
</head>
<body class="h-screen overflow-hidden flex flex-col md:flex-row pb-[72px] md:pb-0 relative">

    <!-- Desktop Sidebar (Hidden on Mobile) -->
    <aside class="hidden md:flex w-72 flex-col border-r border-neutral-200 dark:border-neutral-800 bg-white dark:bg-black h-full">
        <div class="h-20 flex items-center px-8">
            <span class="text-2xl font-bold tracking-tight">dineri.</span>
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto hide-scroll">
            <button class="desktop-nav-item active w-full flex items-center px-4 py-3 rounded-xl text-neutral-500 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition-colors" data-tab="dashboard">
                <i class="fas fa-layer-group w-6 text-lg"></i> Overview
            </button>
            <button class="desktop-nav-item w-full flex items-center px-4 py-3 rounded-xl text-neutral-500 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition-colors" data-tab="income">
                <i class="fas fa-arrow-turn-down w-6 text-lg"></i> Inflow
            </button>
            <button class="desktop-nav-item w-full flex items-center px-4 py-3 rounded-xl text-neutral-500 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition-colors" data-tab="expenses">
                <i class="fas fa-arrow-turn-up w-6 text-lg"></i> Outflow
            </button>
        </nav>
        
        <div class="p-4 border-t border-neutral-200 dark:border-neutral-800">
            <div class="dineri-card p-3 flex flex-col gap-3">
                <a href="profile.php" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                    <?php if ($avatarPath && file_exists($avatarPath)): ?>
                        <img src="<?php echo htmlspecialchars($avatarPath); ?>" class="w-10 h-10 rounded-full object-cover border border-neutral-200 dark:border-neutral-800">
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center font-bold text-neutral-800 dark:text-neutral-200">
                            <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0 text-left">
                        <p class="text-sm font-semibold truncate"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></p>
                        <p class="text-xs text-neutral-500">Settings</p>
                    </div>
                </a>
                <button onclick="window.location.href='index.php?logout=1'" class="w-full py-2 bg-neutral-50 dark:bg-neutral-900 rounded-lg text-sm font-medium text-red-600 dark:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors">
                    Log out
                </button>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col h-full bg-neutral-50 dark:bg-[#000000] relative">
        
        <!-- Top Header Mobile & Desktop -->
        <header class="h-16 md:h-20 px-6 md:px-10 flex items-center justify-between z-30 sticky top-0 bg-neutral-50/80 dark:bg-[#000000]/80 backdrop-blur-md border-b border-neutral-200 dark:border-neutral-800">
            <h1 id="page-title" class="text-xl md:text-2xl font-bold tracking-tight">Overview</h1>
            
            <div class="flex items-center gap-3">
                <button id="voice-btn" class="w-10 h-10 rounded-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 flex items-center justify-center text-neutral-600 dark:text-neutral-300 hover:scale-105 transition-transform shadow-sm">
                    <i class="fas fa-microphone"></i>
                </button>
                <button id="theme-toggle" class="w-10 h-10 rounded-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 flex items-center justify-center text-neutral-600 dark:text-neutral-300 hover:scale-105 transition-transform shadow-sm">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:block"></i>
                </button>
                <!-- Mobile Profile Link -->
                <a href="profile.php" class="md:hidden w-10 h-10 rounded-full border border-neutral-200 dark:border-neutral-800 overflow-hidden shrink-0 block">
                    <?php if ($avatarPath && file_exists($avatarPath)): ?>
                        <img src="<?php echo htmlspecialchars($avatarPath); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center font-bold text-sm">
                            <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </a>
            </div>
        </header>

        <div id="voice-status" class="absolute top-20 left-1/2 -translate-x-1/2 bg-neutral-900 dark:bg-white text-white dark:text-black text-xs px-4 py-2 rounded-full z-50 transform -translate-y-full opacity-0 transition-all duration-300 pointer-events-none font-medium shadow-lg">Listening...</div>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-4 md:p-10 hide-scroll">
            
            <!-- ====== OVERVIEW (DASHBOARD) ====== -->
            <div id="view-dashboard" class="view-section active space-y-6 max-w-5xl mx-auto">
                
                <!-- Top Main Card -->
                <div class="dineri-card p-6 md:p-8 bg-neutral-950 dark:bg-white text-white dark:text-black border-none relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 opacity-10 dark:opacity-5 transform rotate-12 pointer-events-none">
                        <i class="fas fa-asterisk text-9xl"></i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-sm font-medium text-neutral-300 dark:text-neutral-500 mb-1 tracking-wide uppercase">Net Capital</p>
                        <h2 class="text-4xl md:text-6xl font-bold tracking-tighter" id="remaining-balance">$0.00</h2>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <!-- Inflow Card -->
                    <div class="dineri-card p-6 flex flex-col justify-between">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <p class="text-sm font-medium text-neutral-500 mb-1">Inflow</p>
                                <h3 class="text-2xl font-bold" id="total-income">$0.00</h3>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center">
                                <i class="fas fa-arrow-turn-down text-neutral-700 dark:text-neutral-300"></i>
                            </div>
                        </div>
                        <button onclick="openModal('inflow-modal')" class="w-full py-2.5 bg-neutral-100 dark:bg-neutral-900 hover:bg-neutral-200 dark:hover:bg-neutral-800 rounded-lg text-sm font-medium transition-colors">
                            Update Inflow
                        </button>
                    </div>

                    <!-- Outflow Card -->
                    <div class="dineri-card p-6 flex flex-col justify-between">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <p class="text-sm font-medium text-neutral-500 mb-1">Outflow</p>
                                <h3 class="text-2xl font-bold" id="total-expenses">$0.00</h3>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center">
                                <i class="fas fa-arrow-turn-up text-neutral-700 dark:text-neutral-300"></i>
                            </div>
                        </div>
                        <button onclick="openModal('transaction-modal', 'expense')" class="w-full py-2.5 bg-neutral-100 dark:bg-neutral-900 hover:bg-neutral-200 dark:hover:bg-neutral-800 rounded-lg text-sm font-medium transition-colors">
                            Log Outflow
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Chart -->
                    <div class="lg:col-span-1 dineri-card p-6 h-[350px] flex flex-col">
                        <h3 class="font-semibold mb-4 text-neutral-700 dark:text-neutral-300">Distribution</h3>
                        <div class="flex-1 relative w-full flex items-center justify-center min-h-0">
                            <canvas id="expense-chart"></canvas>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="lg:col-span-2 dineri-card p-6 h-[350px] flex flex-col">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-neutral-700 dark:text-neutral-300">Recent Activity</h3>
                        </div>
                        <div class="flex-1 overflow-y-auto hide-scroll">
                            <div id="transactions-list" class="space-y-1">
                                <!-- JS Gen -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ====== INFLOW VIEW ====== -->
            <div id="view-income" class="view-section max-w-5xl mx-auto space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg md:text-2xl font-bold">Inflow Categories</h2>
                        <p class="text-sm text-neutral-500">Manage sources of income.</p>
                    </div>
                    <button onclick="openModal('manage-category-modal', 'income')" class="bg-neutral-900 dark:bg-white text-white dark:text-black px-4 py-2 rounded-lg font-medium text-sm hover:opacity-90 transition-opacity">
                        <i class="fas fa-plus mr-1"></i> Add
                    </button>
                </div>
                <div id="manage-income-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- JS Gen -->
                </div>
            </div>

            <!-- ====== OUTFLOW VIEW ====== -->
            <div id="view-expenses" class="view-section max-w-5xl mx-auto space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg md:text-2xl font-bold">Outflow Rulesets</h2>
                        <p class="text-sm text-neutral-500">Divvy up logic percentage.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="px-3 py-1.5 rounded-lg border border-neutral-200 dark:border-neutral-800 text-sm font-medium">
                            Allocated: <span id="total-percentage" class="ml-1 font-bold">0%</span>
                        </div>
                        <button onclick="openModal('manage-category-modal', 'expense')" class="bg-neutral-900 dark:bg-white text-white dark:text-black px-4 py-2 rounded-lg font-medium text-sm hover:opacity-90 transition-opacity">
                            <i class="fas fa-plus mr-1"></i> Add
                        </button>
                    </div>
                </div>
                <div id="manage-expenses-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- JS Gen -->
                </div>
            </div>

        </div>
    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="md:hidden fixed bottom-0 w-full h-[72px] bg-white/90 dark:bg-[#0a0a0a]/90 backdrop-blur-lg border-t border-neutral-200 dark:border-neutral-800 flex items-center justify-around px-2 pb-safe z-40">
        <button class="mobile-nav-item active flex flex-col items-center justify-center w-20 text-neutral-400 gap-1 transition-all h-full" data-tab="dashboard">
            <i class="fas fa-layer-group text-xl nav-icon transition-transform"></i>
            <span class="text-[10px] font-medium">Overview</span>
        </button>
        <button class="mobile-nav-item flex flex-col items-center justify-center w-20 text-neutral-400 gap-1 transition-all h-full" data-tab="income">
            <i class="fas fa-arrow-turn-down text-xl nav-icon transition-transform"></i>
            <span class="text-[10px] font-medium">Inflow</span>
        </button>
        <button class="mobile-nav-item flex flex-col items-center justify-center w-20 text-neutral-400 gap-1 transition-all h-full" data-tab="expenses">
            <i class="fas fa-arrow-turn-up text-xl nav-icon transition-transform"></i>
            <span class="text-[10px] font-medium">Outflow</span>
        </button>
    </nav>

    <!-- ==== MODALS ==== -->
    <div id="modal-backdrop" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] hidden transition-opacity opacity-0" onclick="closeAllModals()"></div>

    <!-- Inflow Modal -->
    <div id="inflow-modal" class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-[110] hidden w-[90%] max-w-sm">
        <div class="dineri-card p-6 scale-95 opacity-0 transition-all duration-200" id="inflow-modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-lg">Update Inflow</h3>
                <button onclick="closeModal('inflow-modal')" class="text-neutral-400 hover:text-neutral-900 dark:hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <form id="inflow-form" onsubmit="handleInflowSubmit(event)" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-neutral-600 dark:text-neutral-400">Total Capital</label>
                    <input type="number" step="0.01" id="inflow-amount" required class="dineri-input" placeholder="0.00">
                </div>
                <div class="flex items-center gap-2 p-3 bg-neutral-50 dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800">
                    <input type="checkbox" id="inflow-fixed" class="w-4 h-4 rounded accent-black dark:accent-white border-neutral-300">
                    <label for="inflow-fixed" class="text-xs font-medium text-neutral-600 dark:text-neutral-400 lead-tight mt-0.5">Lock format for future logging (persistent base)</label>
                </div>
                <button type="submit" class="dineri-btn mt-2">Apply</button>
            </form>
        </div>
    </div>

    <!-- Transaction Modal -->
    <div id="transaction-modal" class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-[110] hidden w-[90%] max-w-sm">
        <div class="dineri-card p-6 scale-95 opacity-0 transition-all duration-200" id="tx-modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-lg" id="tx-title">Log Outflow</h3>
                <button onclick="closeModal('transaction-modal')" class="text-neutral-400 hover:text-neutral-900 dark:hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <form id="transaction-form" onsubmit="handleTransactionSubmit(event)" class="space-y-4">
                <input type="hidden" id="tx-action" name="action" value="add">
                <input type="hidden" id="tx-id" name="id" value="">
                <input type="hidden" id="tx-type" name="type" value="expense">
                
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-neutral-600 dark:text-neutral-400">Category</label>
                    <select id="tx-category" name="category_id" class="dineri-input cursor-pointer">
                        <!-- JS gen -->
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-neutral-600 dark:text-neutral-400">Amount</label>
                    <input type="number" step="0.01" id="tx-amount" name="amount" required class="dineri-input" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-neutral-600 dark:text-neutral-400">Date</label>
                    <input type="date" id="tx-date" name="transaction_date" required class="dineri-input text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-neutral-600 dark:text-neutral-400">Note</label>
                    <input type="text" id="tx-desc" name="description" class="dineri-input" placeholder="Optional descriptor">
                </div>
                <button type="submit" class="dineri-btn mt-2">Execute Action</button>
            </form>
        </div>
    </div>

    <!-- Edit/Add Category Modal -->
    <div id="manage-category-modal" class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-[110] hidden w-[90%] max-w-sm">
        <div class="dineri-card p-6 scale-95 opacity-0 transition-all duration-200" id="mc-modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-lg" id="mc-modal-title">Category Info</h3>
                <button onclick="closeModal('manage-category-modal')" class="text-neutral-400 hover:text-neutral-900 dark:hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <form id="category-form" onsubmit="handleCategorySubmit(event)" class="space-y-4">
                <input type="hidden" id="mc-id" value="">
                <input type="hidden" id="mc-type" value="">
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-neutral-600 dark:text-neutral-400">Title</label>
                    <input type="text" id="mc-name" required class="dineri-input">
                </div>
                <div id="mc-percentage-container">
                    <label class="block text-sm font-medium mb-1.5 text-neutral-600 dark:text-neutral-400">Allocation Logic (%)</label>
                    <input type="number" id="mc-percentage" step="0.1" min="0" max="100" class="dineri-input">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-neutral-600 dark:text-neutral-400">Color Tag</label>
                    <div class="flex items-center gap-3">
                        <input type="color" id="mc-color" class="w-12 h-12 p-1 rounded-xl bg-neutral-100 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 cursor-pointer">
                        <span class="text-xs text-neutral-500">Select a hex value</span>
                    </div>
                </div>
                <button type="submit" class="dineri-btn mt-2">Save Parameters</button>
            </form>
        </div>
    </div>

    <!-- JS Logic -->
    <script>
        const formatMoney = (n) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(n);
        let categories = [], incomeCategories = [], expenseCategories = [];
        let mainChart = null;

        // Dark/Light Mode Logic
        const root = document.documentElement;
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }

        document.getElementById('theme-toggle').addEventListener('click', () => {
            root.classList.toggle('dark');
            localStorage.setItem('theme', root.classList.contains('dark') ? 'dark' : 'light');
            if (mainChart) renderChart(expenseCategories); // Re-render text colors
        });

        // Navigation Interactivity
        const setupTabs = (selector, activeClass) => {
            document.querySelectorAll(selector).forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const tabIds = ['dashboard', 'income', 'expenses'];
                    const target = e.currentTarget.dataset.tab;
                    
                    document.querySelectorAll(selector).forEach(b => b.classList.remove(activeClass));
                    let syncBtns = document.querySelectorAll(`[data-tab="${target}"]`);
                    syncBtns.forEach(sb => sb.classList.add(activeClass));

                    tabIds.forEach(id => document.getElementById(`view-${id}`).classList.remove('active'));
                    document.getElementById(`view-${target}`).classList.add('active');

                    let titles = { 'dashboard': 'Overview', 'income': 'Inflow', 'expenses': 'Outflow' };
                    document.getElementById('page-title').innerText = titles[target];
                });
            });
        };
        setupTabs('.mobile-nav-item', 'active');
        setupTabs('.desktop-nav-item', 'active');

        // Modal Handlers
        window.openModal = (id, type = null) => {
            document.getElementById('modal-backdrop').classList.remove('hidden');
            document.getElementById(id).classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('modal-backdrop').classList.replace('opacity-0', 'opacity-100');
                const content = document.getElementById(id).querySelector('.dineri-card');
                content.classList.replace('scale-95', 'scale-100');
                content.classList.replace('opacity-0', 'opacity-100');
            }, 10);

            if (id === 'transaction-modal') {
                document.getElementById('transaction-form').reset();
                document.getElementById('tx-action').value = 'add';
                document.getElementById('tx-id').value = '';
                document.getElementById('tx-date').valueAsDate = new Date();
                
                const catSelect = document.getElementById('tx-category');
                catSelect.innerHTML = (type === 'income' ? incomeCategories : expenseCategories)
                    .map(c => `<option value="${c.id}">${c.name}</option>`).join('');
                document.getElementById('tx-type').value = type;
                document.getElementById('tx-title').innerText = 'Log ' + (type === 'income' ? 'Inflow' : 'Outflow');
            }
            if (id === 'manage-category-modal') {
                document.getElementById('category-form').reset();
                document.getElementById('mc-id').value = '';
                document.getElementById('mc-type').value = type;
                document.getElementById('mc-percentage-container').style.display = type === 'income' ? 'none' : 'block';
                if(type==='income') document.getElementById('mc-percentage').value = '0';
                document.getElementById('mc-modal-title').innerText = type === 'income' ? 'New Source' : 'New Ruleset';
            }
        };

        window.closeModal = (id) => {
            document.getElementById('modal-backdrop').classList.replace('opacity-100', 'opacity-0');
            const content = document.getElementById(id).querySelector('.dineri-card');
            content.classList.replace('scale-100', 'scale-95');
            content.classList.replace('opacity-100', 'opacity-0');
            setTimeout(() => {
                document.getElementById('modal-backdrop').classList.add('hidden');
                document.getElementById(id).classList.add('hidden');
            }, 200);
        };
        window.closeAllModals = () => { ['inflow-modal', 'transaction-modal', 'manage-category-modal'].forEach(closeModal); };

        async function loadDashboardData() {
            try {
                const res = await fetch('api/data.php');
                const d = await res.json();

                document.getElementById('inflow-amount').value = d.financials.income_amount;
                document.getElementById('inflow-fixed').checked = d.financials.is_fixed == 1;
                
                document.getElementById('total-income').innerText = formatMoney(d.financials.income_amount);
                const exTotal = d.financials.expense_categories.reduce((sum, c) => sum + parseFloat(c.spent), 0);
                document.getElementById('total-expenses').innerText = formatMoney(exTotal);
                document.getElementById('remaining-balance').innerText = formatMoney(d.financials.remaining_balance);

                incomeCategories = d.financials.income_categories;
                expenseCategories = d.financials.expense_categories;
                
                renderTransactions(d.recent_transactions);
                renderChart(expenseCategories);
                renderManageIncomeList(incomeCategories);
                renderManageExpenseList(expenseCategories);

            } catch(e) { console.error('Data fetch error:', e); }
        }

        function renderTransactions(txs) {
            const list = document.getElementById('transactions-list');
            list.innerHTML = '';
            if(!txs.length) {
                list.innerHTML = '<p class="text-neutral-500 text-sm py-4">No recent logs.</p>';
                return;
            }

            txs.forEach(t => {
                const isIncome = t.type === 'income';
                const sColor = isIncome ? 'text-neutral-400' : 'text-neutral-900 dark:text-neutral-100';
                
                list.innerHTML += `
                    <div class="group flex items-center justify-between p-3.5 hover:bg-neutral-100 dark:hover:bg-neutral-900 rounded-xl transition-colors mb-2">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-8 rounded-full" style="background-color: ${t.cat_color}"></div>
                            <div>
                                <p class="font-medium text-sm text-neutral-900 dark:text-white flex items-center gap-2">
                                    ${t.cat_name} 
                                    ${isIncome ? '' : `<i class="fas fa-arrow-turn-up text-[10px] text-neutral-400"></i>`}
                                </p>
                                <p class="text-xs text-neutral-500">${t.log_date} · ${t.description || 'System Log'}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="font-bold text-sm ${sColor}">${isIncome ? '+' : '-'}${formatMoney(t.amount)}</span>
                            <div class="hidden group-hover:flex items-center gap-2">
                                <button onclick="editTransaction(${t.id})" class="text-neutral-400 hover:text-black dark:hover:text-white transition-colors" title="Edit">
                                    <i class="fas fa-pen text-xs"></i>
                                </button>
                                <button onclick="deleteTransaction(${t.id})" class="text-neutral-400 hover:text-red-500 transition-colors" title="Delete">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        function renderChart(cats) {
            const ctx = document.getElementById('expense-chart');
            const realCats = cats.filter(c => c.id !== null && parseFloat(c.spent) > 0);
            
            if (mainChart) mainChart.destroy();
            
            if (realCats.length === 0) {
                ctx.outerHTML = '<canvas id="expense-chart"></canvas>'; 
                return;
            }

            mainChart = new Chart(document.getElementById('expense-chart'), {
                type: 'doughnut',
                data: {
                    labels: realCats.map(c => c.name),
                    datasets: [{
                        data: realCats.map(c => c.spent),
                        backgroundColor: realCats.map(c => c.color),
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '80%',
                    plugins: {
                        legend: { position: 'right', labels: { color: document.documentElement.classList.contains('dark') ? '#a3a3a3' : '#525252', font: { family: 'Inter', size: 11, weight: '500' }, usePointStyle: true, pointStyle: 'circle' } }
                    }
                }
            });
        }

        function renderManageIncomeList(cats) {
            const list = document.getElementById('manage-income-list');
            if(!list) return;
            list.innerHTML = '';
            
            cats.filter(c => c.id !== null).forEach(c => {
                list.innerHTML += `
                    <div class="dineri-card p-4 hover:-translate-y-0.5 shadow-sm">
                        <div class="flex justify-between items-start mb-3">
                            <div class="w-8 h-8 rounded-lg" style="background-color: ${c.color}"></div>
                            <div class="flex gap-2">
                                <button onclick="editCategory(${c.id}, 'income')" class="text-neutral-400 hover:text-black dark:hover:text-white transition-colors"><i class="fas fa-pen text-sm"></i></button>
                                <button onclick="deleteCategory(${c.id})" class="text-neutral-400 hover:text-red-500 transition-colors"><i class="fas fa-trash text-sm"></i></button>
                            </div>
                        </div>
                        <h3 class="font-semibold text-neutral-900 dark:text-white text-sm">${c.name}</h3>
                        <p class="text-xs text-neutral-500 uppercase tracking-widest mt-1">Source</p>
                    </div>
                `;
            });
        }

        function renderManageExpenseList(cats) {
            const list = document.getElementById('manage-expenses-list');
            if(!list) return;
            list.innerHTML = '';
            let total = 0;

            cats.filter(c => c.id !== null).forEach(c => {
                total += parseFloat(c.percentage);
                list.innerHTML += `
                    <div class="dineri-card p-4 hover:-translate-y-0.5 shadow-sm">
                        <div class="flex justify-between items-start mb-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white" style="background-color: ${c.color}">
                            </div>
                            <div class="flex gap-2">
                                <button onclick="editCategory(${c.id}, 'expense')" class="text-neutral-400 hover:text-black dark:hover:text-white transition-colors"><i class="fas fa-pen text-sm"></i></button>
                                <button onclick="deleteCategory(${c.id})" class="text-neutral-400 hover:text-red-500 transition-colors"><i class="fas fa-trash text-sm"></i></button>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1 text-sm">
                                <h3 class="font-semibold text-neutral-900 dark:text-white">${c.name}</h3>
                                <span class="font-mono bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 rounded text-xs text-neutral-700 dark:text-neutral-300 font-bold border border-neutral-200 dark:border-neutral-700">${c.percentage}%</span>
                            </div>
                            <div class="w-full bg-neutral-100 dark:bg-neutral-900 rounded-full h-1 mt-2">
                                <div class="h-1 rounded-full" style="width: ${c.percentage}%; background-color: ${c.color}"></div>
                            </div>
                        </div>
                    </div>
                `;
            });

            const tp = document.getElementById('total-percentage');
            if(tp) {
                tp.textContent = total + "%";
                tp.className = `ml-1 font-bold ${total === 100 ? 'text-neutral-900 dark:text-white' : 'text-red-500'}`;
            }
        }

        // Logic Actions
        window.handleInflowSubmit = async (e) => {
            e.preventDefault();
            const amt = document.getElementById('inflow-amount').value;
            const fix = document.getElementById('inflow-fixed').checked;

            try {
                const res = await fetch('api/save_inflow.php', {
                    method: 'POST', body: JSON.stringify({ amount: amt, is_fixed: fix })
                });
                const d = await res.json();
                if(d.success) {
                    closeModal('inflow-modal');
                    loadDashboardData();
                } else alert(d.message);
            } catch(e) { console.error(e); }
        }

        window.handleTransactionSubmit = async (e) => {
            e.preventDefault();
            const form = e.target;
            const payload = Object.fromEntries(new FormData(form).entries());

            try {
                const res = await fetch('api/transaction.php', {
                    method: 'POST', body: JSON.stringify(payload)
                });
                const d = await res.json();
                if (d.success) {
                    closeModal('transaction-modal');
                    loadDashboardData();
                } else alert(d.message);
            } catch(e) { console.error(e); }
        }

        window.editTransaction = async (id) => {
            try {
                const res = await fetch('api/data.php');
                const d = await res.json();
                const tx = d.recent_transactions.find(t => t.id == id);
                if (tx) {
                    openModal('transaction-modal', tx.type);
                    document.getElementById('tx-action').value = 'update';
                    document.getElementById('tx-id').value = tx.id;
                    document.getElementById('tx-amount').value = tx.amount;
                    document.getElementById('tx-date').value = tx.log_date;
                    document.getElementById('tx-desc').value = tx.description;
                    document.getElementById('tx-category').value = tx.category_id;
                    document.getElementById('tx-title').innerText = 'Modify Record';
                }
            } catch(e) {}
        }

        window.deleteTransaction = async (id) => {
            if(!confirm("Erase this record?")) return;
            try {
                const res = await fetch('api/transaction.php', { method: 'POST', body: JSON.stringify({ action: 'delete', id: id }) });
                const d = await res.json();
                if(d.success) loadDashboardData();
                else alert(d.message);
            } catch(e){}
        }

        window.handleCategorySubmit = async (e) => {
            e.preventDefault();
            const id = document.getElementById('mc-id').value;
            const t = document.getElementById('mc-type').value;
            const obj = {
                action: id ? 'update' : 'add', id: id, type: t,
                name: document.getElementById('mc-name').value,
                color: document.getElementById('mc-color').value,
                percentage: t === 'expense' ? document.getElementById('mc-percentage').value : 0
            };
            try {
                const res = await fetch('api/save_settings.php', { method: 'POST', body: JSON.stringify(obj) });
                const d = await res.json();
                if(d.success) { closeModal('manage-category-modal'); loadDashboardData(); }
                else alert(d.message);
            } catch(e) { console.error(e); }
        }

        window.editCategory = (id, type) => {
            const arr = type === 'income' ? incomeCategories : expenseCategories;
            const cat = arr.find(c => c.id == id);
            if(!cat) return;
            
            openModal('manage-category-modal', type);
            document.getElementById('mc-id').value = cat.id;
            document.getElementById('mc-name').value = cat.name;
            document.getElementById('mc-color').value = cat.color;
            if (type === 'expense') document.getElementById('mc-percentage').value = cat.percentage;
            document.getElementById('mc-modal-title').innerText = 'Edit ' + (type==='income'?'Source':'Ruleset');
        }

        window.deleteCategory = async (id) => {
            if(!confirm("Remove this protocol permanently?")) return;
            try {
                const res = await fetch('api/save_settings.php', { method: 'POST', body: JSON.stringify({ action: 'delete', id: id }) });
                const d = await res.json();
                if (d.success) loadDashboardData();
                else alert(d.message);
            } catch(e) {}
        }

        // Voice
        function setupVoiceRecognition() {
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SR) { document.getElementById('voice-btn').style.display = 'none'; return; }
            
            const recog = new SR();
            const btn = document.getElementById('voice-btn');
            const stat = document.getElementById('voice-status');

            btn.addEventListener('click', () => {
                recog.start();
                btn.classList.add('animate-pulse');
                stat.innerHTML = 'Listening...';
                stat.style.opacity = '1'; stat.style.transform = 'translate(-50%, 0)';
            });

            recog.onresult = async (e) => {
                btn.classList.remove('animate-pulse');
                const t = e.results[0][0].transcript;
                stat.innerHTML = `Interpreting: "${t}"...`;

                try {
                    const res = await fetch('api/process_voice.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ text: t }) });
                    const d = await res.json();
                    
                    if (d.success) {
                        stat.innerHTML = `Applied.`;
                        loadDashboardData();
                    } else {
                        stat.innerHTML = `Not understood.`;
                    }
                    setTimeout(() => { stat.style.opacity = '0'; stat.style.transform = 'translate(-50%, -100%)'; }, 3000);
                } catch (err) { }
            };

            recog.onerror = (e) => {
                btn.classList.remove('animate-pulse');
                let empty = 'Speech Error';
                if(e.error === 'network') empty = 'Requires HTTPS for Voice';
                stat.innerHTML = empty;
                setTimeout(() => { stat.style.opacity = '0'; stat.style.transform = 'translate(-50%, -100%)'; }, 3000);
            }
        }

        // First Load
        loadDashboardData();
        setupVoiceRecognition();
    </script>
</body>
</html>