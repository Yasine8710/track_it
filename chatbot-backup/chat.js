const chatState = {
    messages: [],
    isWaiting: false,
};

const DEFAULT_SUGGESTIONS = [
    "How's my balance?",
    'Monthly summary',
    'Where does my money go?',
    'Tips to save money',
];

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------

function initChat() {
    const input   = document.getElementById('chat-input');
    const sendBtn = document.getElementById('chat-send-btn');
    if (!input || !sendBtn) return;

    // Attach listeners once — guard against multiple tab switches
    if (input.dataset.chatReady) return;
    input.dataset.chatReady = '1';

    sendBtn.addEventListener('click', () => {
        const text = input.value.trim();
        if (!text || chatState.isWaiting) return;
        input.value = '';
        sendChatMessage(text);
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            const text = input.value.trim();
            if (!text || chatState.isWaiting) return;
            input.value = '';
            sendChatMessage(text);
        }
    });

    if (chatState.messages.length === 0) {
        showWelcome();
    }
}

// ---------------------------------------------------------------------------
// Send flow
// ---------------------------------------------------------------------------

async function sendChatMessage(text) {
    clearWelcome();
    clearSuggestions();
    appendMessage(text, 'user');
    chatState.messages.push({ role: 'user', text });

    setInputDisabled(true);
    showTypingIndicator();

    try {
        const res  = await fetch('api/chat.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ message: text }),
        });

        const data = await res.json();
        hideTypingIndicator();

        if (data.success && data.response) {
            appendMessage(data.response, 'bot');
            chatState.messages.push({ role: 'bot', text: data.response });
            renderSuggestions(data.suggestions ?? DEFAULT_SUGGESTIONS);
        } else {
            const errMsg = data.message || "Something went wrong. Try again in a moment.";
            appendMessage(errMsg, 'bot');
            renderSuggestions(DEFAULT_SUGGESTIONS);
        }
    } catch (_err) {
        hideTypingIndicator();
        appendMessage("I couldn't reach the server. Check your connection and try again.", 'bot');
        renderSuggestions(DEFAULT_SUGGESTIONS);
    } finally {
        setInputDisabled(false);
    }
}

// ---------------------------------------------------------------------------
// DOM helpers
// ---------------------------------------------------------------------------

function appendMessage(text, sender) {
    const feed = document.getElementById('chat-messages');
    if (!feed) return;

    const bubble = document.createElement('div');
    bubble.className = `chat-bubble chat-bubble-${sender}`;
    bubble.textContent = text;

    feed.appendChild(bubble);
    scrollToBottom(feed);
}

function renderSuggestions(suggestions) {
    const container = document.getElementById('chat-suggestions');
    if (!container) return;

    container.innerHTML = '';
    (suggestions || []).forEach((label) => {
        const chip = document.createElement('button');
        chip.className   = 'chat-suggestion-chip';
        chip.textContent = label;
        chip.addEventListener('click', () => {
            if (chatState.isWaiting) return;
            clearSuggestions();
            sendChatMessage(label);
        });
        container.appendChild(chip);
    });
}

function clearSuggestions() {
    const container = document.getElementById('chat-suggestions');
    if (container) container.innerHTML = '';
}

function showTypingIndicator() {
    const feed = document.getElementById('chat-messages');
    if (!feed || document.getElementById('typing-indicator')) return;

    const indicator = document.createElement('div');
    indicator.id        = 'typing-indicator';
    indicator.className = 'typing-indicator';
    indicator.innerHTML = '<span></span><span></span><span></span>';

    feed.appendChild(indicator);
    scrollToBottom(feed);
}

function hideTypingIndicator() {
    const el = document.getElementById('typing-indicator');
    if (el) el.remove();
}

function showWelcome() {
    const feed = document.getElementById('chat-messages');
    if (!feed) return;

    const welcome = document.createElement('div');
    welcome.id        = 'chat-welcome';
    welcome.className = 'chat-welcome';
    welcome.innerHTML = `
        <div class="chat-welcome-icon">👋</div>
        <h3>Hi! I'm your DINARI assistant.</h3>
        <p>I can help you understand your spending and find ways to save.</p>
    `;

    feed.appendChild(welcome);
    renderSuggestions(DEFAULT_SUGGESTIONS);
}

function clearWelcome() {
    const el = document.getElementById('chat-welcome');
    if (el) el.remove();
}

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function setInputDisabled(disabled) {
    chatState.isWaiting = disabled;
    const input   = document.getElementById('chat-input');
    const sendBtn = document.getElementById('chat-send-btn');
    if (input)   input.disabled   = disabled;
    if (sendBtn) sendBtn.disabled = disabled;
}

function scrollToBottom(feed) {
    feed.scrollTop = feed.scrollHeight;
}
