const chatState = { messages: [], isWaiting: false };

const DEFAULT_SUGGESTIONS = [
    "How's my balance?",
    'Monthly summary',
    'Where does my money go?',
    'Tips to save money',
];

// Called by switchView('chat') — shows welcome on first open only
function initChat() {
    const feed = document.getElementById('chat-messages');
    if (!feed || feed.dataset.chatReady) return;
    feed.dataset.chatReady = '1';
    if (chatState.messages.length === 0) showWelcome();
}

// Called directly from onclick / onkeydown in HTML
function handleChatSend() {
    const input = document.getElementById('chat-input');
    if (!input) return;
    const text = input.value.trim();
    if (!text || chatState.isWaiting) return;
    input.value = '';
    sendChatMessage(text);
}

// ─── Core send ────────────────────────────────────────────────────────────────

async function sendChatMessage(text) {
    clearWelcome();
    clearSuggestions();
    appendMessage(text, 'user');
    chatState.messages.push({ role: 'user', text });

    setInputDisabled(true);
    showTyping();

    try {
        const res  = await fetch('api/chat.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ message: text }),
        });
        const data = await res.json();
        hideTyping();

        if (data.success && data.response) {
            appendMessage(data.response, 'bot');
            chatState.messages.push({ role: 'bot', text: data.response });
            renderSuggestions(data.suggestions ?? DEFAULT_SUGGESTIONS);
        } else {
            appendMessage(data.message || 'Something went wrong. Try again.', 'bot');
            renderSuggestions(DEFAULT_SUGGESTIONS);
        }
    } catch (err) {
        hideTyping();
        appendMessage("Couldn't reach the server. Check your connection.", 'bot');
        renderSuggestions(DEFAULT_SUGGESTIONS);
    }

    setInputDisabled(false);
    document.getElementById('chat-input')?.focus();
}

// ─── DOM helpers ──────────────────────────────────────────────────────────────

function appendMessage(text, sender) {
    const feed = document.getElementById('chat-messages');
    if (!feed) return;

    const row = document.createElement('div');
    row.className = 'dnmsg dnmsg--' + sender;

    if (sender === 'bot') {
        const icon = document.createElement('div');
        icon.className = 'dnmsg__icon';
        icon.innerHTML = '<i class="fas fa-robot"></i>';

        const bubble = document.createElement('div');
        bubble.className = 'dnmsg__bubble dnmsg__bubble--bot';
        bubble.textContent = text;

        row.appendChild(icon);
        row.appendChild(bubble);
    } else {
        const bubble = document.createElement('div');
        bubble.className = 'dnmsg__bubble dnmsg__bubble--user';
        bubble.textContent = text;
        row.appendChild(bubble);
    }

    feed.appendChild(row);
    feed.scrollTop = feed.scrollHeight;
}

function renderSuggestions(list) {
    const box = document.getElementById('chat-suggestions');
    if (!box) return;
    box.innerHTML = '';
    (list || []).forEach(function(label) {
        const btn = document.createElement('button');
        btn.className   = 'dnchip';
        btn.textContent = label;
        btn.onclick = function() {
            if (chatState.isWaiting) return;
            clearSuggestions();
            sendChatMessage(label);
        };
        box.appendChild(btn);
    });
}

function clearSuggestions() {
    const box = document.getElementById('chat-suggestions');
    if (box) box.innerHTML = '';
}

function showTyping() {
    const feed = document.getElementById('chat-messages');
    if (!feed || document.getElementById('dn-typing')) return;

    const row = document.createElement('div');
    row.id = 'dn-typing';
    row.className = 'dntyping';

    const icon = document.createElement('div');
    icon.className = 'dnmsg__icon';
    icon.innerHTML = '<i class="fas fa-robot"></i>';

    const dots = document.createElement('div');
    dots.className = 'dntyping__dots';
    dots.innerHTML = '<span></span><span></span><span></span>';

    row.appendChild(icon);
    row.appendChild(dots);
    feed.appendChild(row);
    feed.scrollTop = feed.scrollHeight;
}

function hideTyping() {
    const el = document.getElementById('dn-typing');
    if (el) el.remove();
}

function showWelcome() {
    const feed = document.getElementById('chat-messages');
    if (!feed) return;

    const el = document.createElement('div');
    el.id = 'dn-welcome';
    el.className = 'dnwelcome';
    el.innerHTML =
        '<div class="dnwelcome__icon"><i class="fas fa-robot"></i></div>' +
        '<h3>Hi, I\'m DINARI!</h3>' +
        '<p>Ask me about your balance, spending habits, or how to save more.</p>';
    feed.appendChild(el);
    renderSuggestions(DEFAULT_SUGGESTIONS);
}

function clearWelcome() {
    const el = document.getElementById('dn-welcome');
    if (el) el.remove();
}

function setInputDisabled(on) {
    chatState.isWaiting = on;
    const input = document.getElementById('chat-input');
    if (input) input.disabled = on;
}
