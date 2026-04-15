document.addEventListener('DOMContentLoaded', () => {
    loadDashboardData();
    setupVoiceRecognition();
    setupTabs();
    setupThemeToggle(); // New
    
    // Mobile menu toggle
    const btn = document.getElementById('mobile-menu-btn');
    const closeBtn = document.getElementById('mobile-menu-close');
    const backdrop = document.getElementById('mobile-menu-backdrop');
    const sidebar = document.querySelector('aside');

    const openMobileMenu = () => {
        if (!sidebar) return;
        sidebar.classList.remove('-translate-x-full');
        if (backdrop) backdrop.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeMobileMenu = () => {
        if (!sidebar) return;
        sidebar.classList.add('-translate-x-full');
        if (backdrop) backdrop.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    if (btn) btn.addEventListener('click', openMobileMenu);
    if (closeBtn) closeBtn.addEventListener('click', closeMobileMenu);
    if (backdrop) backdrop.addEventListener('click', closeMobileMenu);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeMobileMenu();
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            if (backdrop) backdrop.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        } else if (sidebar && sidebar.classList.contains('-translate-x-full')) {
            if (backdrop) backdrop.classList.add('hidden');
        }
    });
});

let expenseCategories = [];
let incomeCategories = [];
let voiceRecognition;

// --- Theme Management ---
function setupThemeToggle() {
    const btn = document.getElementById('theme-toggle');
    if (!btn) return;

    btn.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.theme = isDark ? 'dark' : 'light';
        
        // Update Chart if it exists (for text colors)
        if (mainChart) {
            updateChartTheme(isDark);
        }
    });
}

function updateChartTheme(isDark) {
    if (!mainChart) return;
    const textColor = isDark ? '#a1a1aa' : '#52525b'; // gray-400 : gray-600
    mainChart.options.plugins.legend.labels.color = textColor;
    mainChart.update();
}


// --- Navigation ---
function setupTabs() {
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const tab = item.getAttribute('data-tab');
            switchTab(tab);
        });
    });
}

function switchTab(tabName) {
    // Update Nav
    document.querySelectorAll('.nav-item').forEach(item => {
        const isSelected = item.getAttribute('data-tab') === tabName;
        
        if (isSelected) {
            // New Active Classes
            item.classList.add('bg-zinc-100/50', 'dark:bg-zinc-800/50', 'text-zinc-900', 'dark:text-white', 'border', 'border-zinc-200/50', 'dark:border-zinc-800/50/50', 'active');
            item.classList.remove('text-zinc-500', 'hover:text-zinc-900', 'dark:text-zinc-500', 'dark:hover:text-zinc-100');
        } else {
            // Inactive Classes
            item.classList.remove('bg-zinc-100/50', 'dark:bg-zinc-800/50', 'text-zinc-900', 'dark:text-white', 'border', 'border-zinc-200/50', 'dark:border-zinc-800/50/50', 'active');
            item.classList.add('text-zinc-500', 'hover:text-zinc-900', 'dark:text-zinc-500', 'dark:hover:text-zinc-100');
        }
    });

    // Update View
    const views = ['dashboard', 'income', 'expenses'];
    views.forEach(v => {
        const el = document.getElementById(`view-${v}`);
        if (!el) return;
        if (v === tabName) {
            el.classList.remove('hidden');
            el.classList.add('animate-fade-in');
        } else {
            el.classList.add('hidden');
            el.classList.remove('animate-fade-in');
        }
    });

    // Auto-close sidebar after selecting a tab on mobile.
    if (window.innerWidth < 768) {
        const sidebar = document.querySelector('aside');
        const backdrop = document.getElementById('mobile-menu-backdrop');
        if (sidebar) sidebar.classList.add('-translate-x-full');
        if (backdrop) backdrop.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

// --- Data Loading ---
async function loadDashboardData() {
    try {
        const res = await fetch('api/data.php');
        const data = await res.json();
        
        if (data.success) {
            expenseCategories = data.data.expense_categories;
            incomeCategories = data.data.income_categories;

            updateSummaryCards(data.data);
            
            // Dashboard Widgets
            renderExpenseMiniList(expenseCategories, 'expense-list-mini');
            renderIncomeMiniList(incomeCategories, 'income-list-mini');
            renderTransactions(data.data.transactions);
            renderChart(expenseCategories);

            // Management Views
            renderManageIncomeList(incomeCategories);
            renderManageExpenseList(expenseCategories);
            
        } else {
            if (data.message === 'Unauthorized') window.location.href = 'index.php';
        }
    } catch (e) {
        console.error("Failed to load data", e);
    }
}

function updateSummaryCards(data) {
    const format = (n) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(n);

    // Update Text
    const elInc = document.getElementById('total-income');
    const elExp = document.getElementById('total-expenses');
    const elBal = document.getElementById('remaining-balance');
    
    if(elInc) elInc.textContent = format(data.total_income);
    if(elExp) elExp.textContent = format(data.total_expenses);
    if(elBal) elBal.textContent = format(data.remaining_balance);
    
    // Update Bars
    const total = data.total_income > 0 ? data.total_income : 1; 
    
    const expPerc = Math.min(100, (data.total_expenses / total) * 100);
    const expBar = document.getElementById('expenses-bar');
    if(expBar) expBar.style.width = `${expPerc}%`;

    const balPerc = Math.max(0, Math.min(100, (data.remaining_balance / total) * 100));
    const balBar = document.getElementById('balance-bar');
    if(balBar) balBar.style.width = `${balPerc}%`;
}


// --- Rendering Lists ---

// Mini Lists for Dashboard
function renderExpenseMiniList(categories, elementId) {
    const list = document.getElementById(elementId);
    if(!list) return;
    list.innerHTML = '';

    categories.forEach(cat => {
        const p = Math.min(100, Math.max(0, (cat.spent / cat.allocated) * 100)) || 0;
        const el = document.createElement('div');
        el.className = 'group';
        el.innerHTML = `
            <div class="flex justify-between text-xs mb-1">
                <span class="text-zinc-600 dark:text-zinc-300 font-medium">${cat.name}</span>
                <span class="text-zinc-500 dark:text-zinc-400 font-mono">${formatCurrency(cat.spent)} / ${formatCurrency(cat.allocated)}</span>
            </div>
            <div class="w-full bg-zinc-100 dark:bg-zinc-700/50 rounded-full h-1.5 overflow-hidden">
                <div class="h-1.5 rounded-full transition-all duration-500" style="width: ${p}%; background-color: ${cat.color}"></div>
            </div>
        `;
        list.appendChild(el);
    });
}

function renderIncomeMiniList(categories, elementId) {
    const list = document.getElementById(elementId);
    if(!list) return;
    list.innerHTML = '';

    if (categories.length === 0) {
        list.innerHTML = '<div class="text-zinc-400 text-xs text-center py-2">No income sources yet.</div>';
        return;
    }

    categories.forEach(cat => {
        if(cat.id === null && cat.earned === 0) return; 
        
        const el = document.createElement('div');
        el.className = 'flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg border border-zinc-200/50 dark:border-zinc-800/50/50 hover:bg-white dark:hover:bg-zinc-700/50 transition-colors shadow-sm';
        el.innerHTML = `
            <div class="flex items-center space-x-3">
                <div class="w-1 h-8 rounded-full" style="background-color: ${cat.color}"></div>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">${cat.name}</span>
            </div>
            <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400 font-mono">+${formatCurrency(cat.earned)}</span>
        `;
        list.appendChild(el);
    });
}

function renderTransactions(transactions) {
    const list = document.getElementById('transaction-list');
    list.innerHTML = '';

    if (transactions.length === 0) {
        list.innerHTML = '<div class="text-center text-zinc-400 dark:text-zinc-500 text-sm py-8">No recent transactions</div>';
        return;
    }

    transactions.forEach(t => {
        const isIncome = t.type === 'income';
        // Adapting colors for light/dark
        const amountClass = isIncome ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-900 dark:text-white';
        const icon = isIncome ? 'fa-arrow-down' : 'fa-arrow-up';
        const iconBg = isIncome ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-500' : 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-500';

        const el = document.createElement('div');
        el.className = 'flex items-center justify-between p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors cursor-default';
        el.innerHTML = `
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 rounded-full ${iconBg} flex items-center justify-center shrink-0">
                    <i class="fas ${icon} text-sm"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100 truncate">${t.description || 'No description'}</p>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        <span class="font-mono">${t.transaction_date}</span>
                        <span>â€¢</span>
                        <span class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-600">${t.category_name || 'Uncategorized'}</span>
                    </div>
                </div>
            </div>
            <span class="font-bold font-mono ${amountClass} whitespace-nowrap ml-4">${isIncome ? '+' : '-'}${formatCurrency(t.amount)}</span>
        `;
        list.appendChild(el);
    });
}

// Management Views Renderers
function renderManageIncomeList(categories) {
    const list = document.getElementById('manage-income-list');
    if(!list) return;
    list.innerHTML = '';
    
    const realCats = categories.filter(c => c.id !== null);

    if (realCats.length === 0) {
        document.getElementById('income-empty-state').classList.remove('hidden');
        return;
    }
    document.getElementById('income-empty-state').classList.add('hidden');

    realCats.forEach(cat => {
        const row = document.createElement('tr');
        row.className = "hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group border-b border-zinc-200/50 dark:border-zinc-800/50 last:border-0";
        row.innerHTML = `
             <td class="p-5 font-medium text-zinc-900 dark:text-white">
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 rounded-full shadow-sm" style="background-color: ${cat.color}"></div>
                    <span>${cat.name}</span>
                </div>
             </td>
             <td class="p-5">
                <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-md shadow-sm border border-zinc-200 dark:border-zinc-600" style="background-color: ${cat.color}"></span>
                    <span class="text-zinc-500 dark:text-zinc-400 text-xs uppercase font-mono">${cat.color}</span>
                </div>
             </td>
             <td class="p-5 text-right">
                <button onclick="editCategory(${cat.id}, 'income')" class="text-zinc-400 hover:text-brand-600 dark:hover:text-brand-400 p-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors mr-1">
                    <i class="fas fa-pen"></i>
                </button>
                <button onclick="deleteCategory(${cat.id}, 'income')" class="text-zinc-400 hover:text-red-600 dark:hover:text-red-400 p-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <i class="fas fa-trash"></i>
                </button>
             </td>
        `;
        list.appendChild(row);
    });
}

function renderManageExpenseList(categories) {
    const list = document.getElementById('manage-expenses-list');
    if(!list) return;
    list.innerHTML = '';
    let totalP = 0;

    categories.filter(c => c.id !== null).forEach(cat => {
        totalP += parseFloat(cat.percentage);
        const row = document.createElement('tr');
        row.className = "hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group border-b border-zinc-200/50 dark:border-zinc-800/50 last:border-0";
        row.innerHTML = `
             <td class="p-5 font-medium text-zinc-900 dark:text-white">
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 rounded-full shadow-sm" style="background-color: ${cat.color}"></div>
                    <span>${cat.name}</span>
                </div>
             </td>
             <td class="p-5">
                <div class="w-full max-w-[150px] bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5 mb-2">
                    <div class="bg-brand-500 h-1.5 rounded-full" style="width: ${cat.percentage}%"></div>
                </div>
                <span class="text-sm font-mono text-zinc-600 dark:text-zinc-300 font-semibold">${cat.percentage}%</span>
             </td>
             <td class="p-5">
                 <div class="w-6 h-6 rounded-md shadow-sm border border-zinc-200 dark:border-zinc-600" style="background-color: ${cat.color}"></div>
             </td>
             <td class="p-5 text-right">
                <button onclick="editCategory(${cat.id}, 'expense')" class="text-zinc-400 hover:text-brand-600 dark:hover:text-brand-400 p-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors mr-1">
                    <i class="fas fa-pen"></i>
                </button>
                <button onclick="deleteCategory(${cat.id}, 'expense')" class="text-zinc-400 hover:text-red-600 dark:hover:text-red-400 p-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <i class="fas fa-trash"></i>
                </button>
             </td>
        `;
        list.appendChild(row);
    });

    // Update display
     const pDisplay = document.getElementById('total-percentage');
    if(pDisplay) {
        pDisplay.textContent = totalP + "%";
        pDisplay.className = `text-3xl font-bold ${totalP === 100 ? "text-emerald-600 dark:text-emerald-400" : "text-amber-500 dark:text-yellow-400"}`;
    }
}


// --- Charts ---
let mainChart = null;
function renderChart(categories) {
    const ctx = document.getElementById('expense-chart');
    if(!ctx) return;
    
    // Sort by spent amount desc
    const sorted = [...categories].sort((a,b) => b.spent - a.spent);
    
    const labels = sorted.map(c => c.name);
    const data = sorted.map(c => c.spent);
    const colors = sorted.map(c => c.color);

    if (mainChart) {
        mainChart.destroy();
    }
    
    // Determine initial text color
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#a1a1aa' : '#52525b';

    mainChart = new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { color: textColor, boxWidth: 10, padding: 15, font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 } }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    padding: 12,
                    cornerRadius: 8,
                    titleColor: '#fff',
                    bodyColor: '#e5e7eb',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1
                }
            },
            cutout: '75%',
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    });
}

// --- Utility ---
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
}

// --- Modals & Forms ---
// Re-implemented to be compatible with new HTML structure

function openModal(modalId, mode = null) {
    const modal = document.getElementById(modalId);
    if(modal) {
        modal.classList.remove('hidden');
        
        const content = modal.querySelector('.modal-content');
        if(content) {
            requestAnimationFrame(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            });
        }
    }

    if (modalId === 'transaction-modal' && mode) {
        document.getElementById('t-modal-title').textContent = mode === 'income' ? 'Add Income' : 'Add Expense';
        document.getElementById('t-type').value = mode; 
        
        const select = document.getElementById('t-category');
        select.innerHTML = '<option value="">Uncategorized</option>';
        
        const cats = mode === 'income' ? incomeCategories : expenseCategories;
        cats.forEach(c => {
            if(c.id === null) return;
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            select.appendChild(opt);
        });
    }

    if (modalId === 'manage-category-modal') {
        const form = document.getElementById('category-form');
        form.reset();
        document.getElementById('mc-id').value = ''; 
        document.getElementById('mc-type').value = mode; 
        document.getElementById('mc-modal-title').textContent = mode === 'income' ? 'New Income Source' : 'New Expense Category';
        
        const pGroup = document.getElementById('mc-percentage-group');
        if (mode === 'expense') {
            pGroup.classList.remove('hidden');
            document.getElementById('mc-percentage').required = true;
        } else {
            pGroup.classList.add('hidden');
            document.getElementById('mc-percentage').required = false;
            document.getElementById('mc-percentage').value = 0;
        }
    }
    
    if (modalId === 'history-modal') {
        loadHistory();
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        const content = modal.querySelector('.modal-content');
        if(content) {
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        } else {
            modal.classList.add('hidden');
        }
    }
}

async function loadHistory() {
    const list = document.getElementById('full-history-list');
    if (!list) return;
    
    list.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-zinc-500 animate-pulse">Loading transaction history...</td></tr>';
    
    try {
        const res = await fetch('api/history.php');
        const data = await res.json();
        
        if (data.success) {
            list.innerHTML = '';
            if (data.data.length === 0) {
                list.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-zinc-500">No transactions found.</td></tr>';
                return;
            }
            
            data.data.forEach(t => {
                const isIncome = t.type === 'income';
                const sign = isIncome ? '+' : '-';
                const color = isIncome ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
                const catStyle = t.category_color ? `style="color: ${t.category_color}; border-color: ${t.category_color}40; background-color: ${t.category_color}10"` : '';
                
                const row = document.createElement('tr');
                row.className = 'hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors border-b border-zinc-200/50 dark:border-zinc-800/50/50 last:border-0';
                row.innerHTML = `
                    <td class="p-4 text-zinc-600 dark:text-zinc-300 font-mono text-xs whitespace-nowrap">${t.transaction_date}</td>
                    <td class="p-4 font-medium text-zinc-900 dark:text-white">${t.description || '-'}</td>
                    <td class="p-4 text-xs text-zinc-500 dark:text-zinc-400">
                        <span class="px-2 py-1 rounded border" ${catStyle}>${t.category_name || 'Uncategorized'}</span>
                    </td>
                    <td class="p-4 text-right font-bold font-mono ${color}">${sign}${formatCurrency(t.amount)}</td>
                `;
                list.appendChild(row);
            });
        } else {
             list.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-red-500">Failed to load history data.</td></tr>';
        }
    } catch (e) {
        console.error(e);
        list.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-red-500">Network error loading history.</td></tr>';
    }
}

// --- Voice ---
function setupVoiceRecognition() {
    const micBtn = document.getElementById('mic-btn');
    const statusText = document.getElementById('voice-status');

    if (!('webkitSpeechRecognition' in window)) {
        if (statusText) statusText.textContent = "Browser not supported.";
        // Disable button visually
        if (micBtn) micBtn.classList.add('opacity-50', 'grayscale');
        return;
    }

    voiceRecognition = new webkitSpeechRecognition();
    voiceRecognition.continuous = false;
    voiceRecognition.lang = 'en-US';

    if (micBtn) {
        micBtn.addEventListener('click', () => {
            voiceRecognition.start();
            micBtn.classList.add('recording-active');
            if (statusText) {
                statusText.style.display = 'block';
                statusText.innerHTML = '<span class="text-white font-bold animate-pulse">Listening...</span>';
            }
        });

        voiceRecognition.onresult = async (event) => {
            micBtn.classList.remove('recording-active');
            const transcript = event.results[0][0].transcript;
            if (statusText) statusText.textContent = `Processing: "${transcript}"...`;
            
            try {
                const res = await fetch('api/process_voice.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transcript: transcript })
                });
                const result = await res.json();
                
                if (result.success) {
                    if (statusText) statusText.innerHTML = `<span class="text-emerald-400"><i class="fas fa-check mr-1"></i> ${result.message}</span>`;
                    setTimeout(() => {
                        if (statusText) {
                            statusText.style.display = 'none';
                            statusText.innerHTML = '';
                        }
                    }, 3000);
                    
                    if (typeof loadDashboardData === 'function') loadDashboardData();
                    if (typeof syncStats === 'function') syncStats();
                    
                    // If we are on dashboard.php, a reload might be simpler for certain UI elements
                    if (window.location.pathname.includes('dashboard.php')) {
                        setTimeout(() => location.reload(), 1500);
                    }
                } else {
                    if (statusText) statusText.innerHTML = `<span class="text-red-400"><i class="fas fa-times mr-1"></i> ${result.message}</span>`;
                }
            } catch (e) {
                if (statusText) statusText.textContent = "Error processing voice command.";
            }
        };
        
        voiceRecognition.onerror = (e) => {
            micBtn.classList.remove('recording-active');
            console.error("Speech Recognition Error:", e.error);
            if (statusText) statusText.textContent = `Voice error: ${e.error}. Try again.`;
        };

        voiceRecognition.onend = () => {
             micBtn.classList.remove('recording-active');
        };
    }
}

// --- Form Submissions (Same as before, just kept for completeness) ---

window.handleTransactionSubmit = async (e) => {
    e.preventDefault();
    const form = e.target;
    // ... logic same ...
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());

    try {
        const res = await fetch('api/transaction.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            closeModal('transaction-modal');
            form.reset();
            loadDashboardData();
        } else {
            alert(data.message);
        }
    } catch (e) { console.error(e); }
}

window.handleCategorySubmit = async (e) => {
    e.preventDefault();
    const form = e.target;
    const id = document.getElementById('mc-id').value;
    const name = document.getElementById('mc-name').value;
    const color = document.getElementById('mc-color').value;
    const type = document.getElementById('mc-type').value;
    const percentage = document.getElementById('mc-percentage').value;

    const payload = {
        categories: [{
            id: id ? id : null,
            name: name,
            color: color,
            type: type,
            percentage: percentage
        }]
    };

    try {
        const res = await fetch('api/save_settings.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if(data.success) {
            closeModal('manage-category-modal');
            loadDashboardData();
        } else {
            alert(data.message);
        }
    } catch(e) { console.error(e); }
}

async function deleteCategory(id, type) {
    if(!confirm("Are you sure? This will delete the category but keep transactions as 'Uncategorized'.")) return;
    
    try {
        const res = await fetch('api/save_settings.php', {
            method: 'POST',
            body: JSON.stringify({ categories: [], deletedIds: [id] })
        });
        const data = await res.json();
        if(data.success) loadDashboardData();
    } catch(e) { console.error(e); }
}

function editCategory(id, type) {
    const cats = type === 'income' ? incomeCategories : expenseCategories;
    const cat = cats.find(c => c.id == id);
    if (!cat) return;

    openModal('manage-category-modal', type);
    
    document.getElementById('mc-modal-title').textContent = 'Edit ' + (type === 'income' ? 'Source' : 'Category');
    document.getElementById('mc-id').value = cat.id;
    document.getElementById('mc-name').value = cat.name;
    document.getElementById('mc-color').value = cat.color;
    document.getElementById('mc-type').value = type;

    if (type === 'expense') {
        document.getElementById('mc-percentage').value = cat.percentage;
    }
}

// Quick Entry Functions
let quickEntryType = 'outflow';

function setQEType(type) {
    quickEntryType = type;
    document.getElementById('qe-tab-outflow').classList.toggle('active', type === 'outflow');
    document.getElementById('qe-tab-inflow').classList.toggle('active', type === 'inflow');
    const btn = document.getElementById('qe-btn');
    if (btn) {
        btn.textContent = type === 'inflow' ? 'Log Income' : 'Log Expense';
    }
}

async function saveQuickEntry() {
    const amount = parseFloat(document.getElementById('quickAmount').value);
    const categoryId = document.getElementById('quickCat').value;
    const description = document.getElementById('quickDesc').value;
    
    if (!amount || amount <= 0) {
        alert('Please enter a valid amount');
        return;
    }
    
    try {
        const res = await fetch('api/transaction.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                amount: amount,
                type: quickEntryType,
                category_id: categoryId || null,
                description: description || '',
                date: new Date().toISOString().split('T')[0]
            })
        });
        
        const data = await res.json();
        if (data.success) {
            // Clear form
            document.getElementById('quickAmount').value = '';
            document.getElementById('quickCat').value = '';
            document.getElementById('quickDesc').value = '';
            
            alert('Transaction logged successfully!');
            // Reload page to show updated data
            setTimeout(() => location.reload(), 500);
        } else {
            alert('Error: ' + (data.message || 'Failed to save transaction'));
        }
    } catch (e) {
        console.error('Error saving transaction:', e);
        alert('Error: ' + e.message);
    }
}

// View Navigation
function switchView(viewName) {
    // Update nav items
    document.querySelectorAll('.nav-item').forEach(item => {
        const isActive = item.getAttribute('data-view') === viewName;
        item.classList.toggle('active', isActive);
    });
    document.querySelectorAll('.bottom-nav-item').forEach(item => {
        const isActive = item.getAttribute('data-view') === viewName;
        item.classList.toggle('active', isActive);
    });
    
    // Update view title
    const titles = {
        'home': 'Workspace Active',
        'stats': 'Analytics Dashboard',
        'history': 'Transaction Logbook',
        'settings': 'Settings & Preferences'
    };
    document.getElementById('view-title').textContent = titles[viewName] || 'Dashboard';
    
    // Show/hide sections
    document.querySelectorAll('.view-section').forEach(section => {
        section.classList.remove('active');
    });
    const activeSection = document.getElementById(`section-${viewName}`);
    if (activeSection) {
        activeSection.classList.add('active');
    }

    if (viewName === 'stats' && typeof initChart === 'function') {
        initChart();
    }
    if (viewName === 'calendar' && typeof renderCalendar === 'function') {
        renderCalendar();
    }
    if (viewName === 'wishes' && typeof fetchWishes === 'function') {
        fetchWishes();
    }
}

// Transaction Management
async function editTx(id, amount) {
    const newAmount = prompt('Enter new amount:', amount);
    if (newAmount === null || isNaN(newAmount) || parseFloat(newAmount) <= 0) return;
    
    try {
        const res = await fetch('api/transaction.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: id,
                amount: parseFloat(newAmount)
            })
        });
        const data = await res.json();
        if (data.success) {
            alert('Transaction updated!');
            location.reload();
        } else {
            alert('Error updating transaction');
        }
    } catch (e) {
        console.error(e);
        alert('Error: ' + e.message);
    }
}

async function deleteTx(id) {
    if (!confirm('Delete this transaction?')) return;
    
    try {
        const res = await fetch(`api/transaction.php?id=${id}`, {
            method: 'DELETE'
        });
        const data = await res.json();
        if (data.success) {
            alert('Transaction deleted!');
            location.reload();
        } else {
            alert('Error deleting transaction');
        }
    } catch (e) {
        console.error(e);
        alert('Error: ' + e.message);
    }
}

// Category Management
function addCategory() {
    const name = document.getElementById('newCatName').value.trim();
    if (!name) {
        alert('Please enter a category name');
        return;
    }
    
    addCategoryToAPI(name);
    document.getElementById('newCatName').value = '';
}

function delCategory(id) {
    if (!confirm('Delete this category?')) return;
    deleteCategoryFromAPI(id);
}

async function addCategoryToAPI(name) {
    try {
        const res = await fetch('api/categories.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name })
        });
        const data = await res.json();
        if (data.success) {
            alert('Category added!');
            location.reload();
        } else {
            alert(data.message || 'Error adding category');
        }
    } catch (e) {
        console.error(e);
        alert('Error: ' + e.message);
    }
}

async function deleteCategoryFromAPI(id) {
    try {
        const res = await fetch(`api/categories.php?id=${id}`, {
            method: 'DELETE'
        });
        const data = await res.json();
        if (data.success) {
            alert('Category deleted!');
            location.reload();
        } else {
            alert('Error deleting category');
        }
    } catch (e) {
        console.error(e);
        alert('Error: ' + e.message);
    }
}

async function saveCategoryToAPI(categoryData) {
    try {
        const res = await fetch('api/categories.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(categoryData)
        });
        const data = await res.json();
        if (data.success) {
            alert('Category saved!');
            location.reload();
        } else {
            alert(data.message || 'Error saving category');
        }
    } catch (e) {
        console.error(e);
        alert('Error: ' + e.message);
    }
}

// Balance Management
function openBalanceEdit() {
    const modal = document.getElementById('balModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeBalanceModal() {
    const modal = document.getElementById('balModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function saveManualBalance() {
    const amount = parseFloat(document.getElementById('newBal').value);
    if (!amount || amount < 0) {
        alert('Please enter a valid amount');
        return;
    }
    
    // Save as inflow transaction with description "Manual Balance Adjustment"
    saveQuickEntry_Manual(amount);
    closeBalanceModal();
}

async function saveQuickEntry_Manual(amount) {
    try {
        const res = await fetch('api/transaction.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                amount: amount,
                type: 'inflow',
                category_id: null,
                description: 'Manual Balance Adjustment',
                date: new Date().toISOString().split('T')[0]
            })
        });
        
        const data = await res.json();
        if (data.success) {
            document.getElementById('newBal').value = '';
            alert('Balance adjusted!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to adjust balance'));
        }
    } catch (e) {
        console.error(e);
        alert('Error: ' + e.message);
    }
}

// Global scope assignments
window.editCategory = editCategory;
window.deleteCategory = deleteCategory;
window.switchTab = switchTab;
window.openModal = openModal;
window.closeModal = closeModal;
window.setQEType = setQEType;
window.saveQuickEntry = saveQuickEntry;
window.switchView = switchView;
window.editTx = editTx;
window.deleteTx = deleteTx;
window.addCategory = addCategory;
window.delCategory = delCategory;
window.openBalanceEdit = openBalanceEdit;
window.closeBalanceModal = closeBalanceModal;
window.saveManualBalance = saveManualBalance;
window.logout = function() { window.location.href = 'index.php?logout=1'; }




