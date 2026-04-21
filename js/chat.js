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

function initChat() {
    // Only set up non-toggle listeners here, toggle is handled in dashboard.php
    
    // Floating Window Inputs
    const floatInput  = document.getElementById('chat-input');
    const floatSend   = document.getElementById('chat-send-btn');
    if (floatInput && floatSend) {
        floatSend.onclick = () => handleInput(floatInput);
        floatInput.onkeydown = (e) => { if (e.key === 'Enter') handleInput(floatInput); };
    }

    // Full Section Inputs
    const sectionInput = document.getElementById('section-chat-input');
    const sectionSend  = document.getElementById('section-chat-send-btn');
    if (sectionInput && sectionSend) {
        sectionSend.onclick = () => handleInput(sectionInput);
        sectionInput.onkeydown = (e) => { if (e.key === 'Enter') handleInput(sectionInput); };
        
        // If we switch to assistant section, ensure welcome shows if empty
        if (chatState.messages.length === 0) showWelcome();
    }

    // Swipe-to-close for Mobile
    const chatWindow = document.getElementById('chat-window');
    let touchStartY = 0;
    if (chatWindow) {
        chatWindow.addEventListener('touchstart', (e) => {
            touchStartY = e.touches[0].clientY;
        }, { passive: true });

        chatWindow.addEventListener('touchend', (e) => {
            const touchEndY = e.changedTouches[0].clientY;
            if (touchEndY - touchStartY > 100) { // Swipe down 100px
                if (typeof toggleChatWindow === 'function') toggleChatWindow();
            }
        }, { passive: true });
    }
}

function handleInput(inputEl) {
    const text = inputEl.value.trim();
    if (!text || chatState.isWaiting) return;
    inputEl.value = '';
    sendChatMessage(text);
}

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

function appendMessage(text, sender) {
    const targets = ['chat-messages', 'section-messages'];
    targets.forEach(id => {
        const feed = document.getElementById(id);
        if (!feed) return;
        const bubble = document.createElement('div');
        bubble.className = `chat-bubble chat-bubble-${sender}`;
        bubble.textContent = text;
        feed.appendChild(bubble);
        feed.scrollTop = feed.scrollHeight;
    });
}

function showTypingIndicator() {
    const targets = ['chat-messages', 'section-messages'];
    targets.forEach(id => {
        const feed = document.getElementById(id);
        if (!feed) return;
        const indicator = document.createElement('div');
        indicator.className = 'typing-indicator';
        indicator.id = id + '-typing';
        indicator.innerHTML = '<span></span><span></span><span></span>';
        feed.appendChild(indicator);
        feed.scrollTop = feed.scrollHeight;
    });
}

function hideTypingIndicator() {
    const indicators = [document.getElementById('chat-messages-typing'), document.getElementById('section-messages-typing')];
    indicators.forEach(el => el?.remove());
}

function setInputDisabled(disabled) {
    const inputs = ['chat-input', 'chat-send-btn', 'section-chat-input', 'section-chat-send-btn'];
    inputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = disabled;
        if (el && el.tagName === 'BUTTON') el.style.opacity = disabled ? '0.5' : '1';
    });
}

function renderSuggestions(suggestions) {
    const targets = ['chat-suggestions', 'section-suggestions'];
    targets.forEach(id => {
        const container = document.getElementById(id);
        if (!container) return;
        container.innerHTML = '';
        suggestions.forEach(text => {
            const chip = document.createElement('div');
            chip.className = 'chat-suggestion-chip';
            chip.textContent = text;
            chip.onclick = () => { if (!chatState.isWaiting) sendChatMessage(text); };
            container.appendChild(chip);
        });
    });
}

function clearSuggestions() {
    ['chat-suggestions', 'section-suggestions'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '';
    });
}

function showWelcome() {
    const targets = ['chat-messages', 'section-messages'];
    targets.forEach(id => {
        const feed = document.getElementById(id);
        if (!feed) return;
        feed.innerHTML = `
            <div class="chat-welcome" id="${id}-welcome">
                <div class="chat-welcome-icon">✨</div>
                <h3>Hi, I'm DINARI</h3>
                <p>Your AI financial assistant. Ask me anything about your spending or balance!</p>
            </div>
        `;
    });
    renderSuggestions(chatState.messages.length === 0 ? DEFAULT_SUGGESTIONS : []);
}

function clearWelcome() {
    [document.getElementById('chat-messages-welcome'), document.getElementById('section-messages-welcome')].forEach(el => el?.remove());
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', initChat);