window.tr = window.tr || function (key) {
    return (window.i18n && window.i18n[key]) || key;
};

let activeChatId = null;
let liveSubscription = null;
const messagesStore = {};

function currentUserId() {
    return window.currentUserObjectId || '';
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function fetchWithRetry(url, options = {}, attempts = 3) {
    let lastError;

    for (let i = 1; i <= attempts; i++) {
        try {
            const response = await fetch(url, options);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response;
        } catch (error) {
            lastError = error;
            if (i < attempts) {
                await sleep(500 * i);
            }
        }
    }

    throw lastError;
}

async function postAction(payload, attempts = 3) {
    const response = await fetchWithRetry('api/action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }, attempts);

    const data = await response.json();
    if (!data.success) {
        throw new Error(data.error || 'Request failed');
    }
    return data;
}

async function initParseSession() {
    if (typeof Parse === 'undefined') {
        return;
    }

    try {
        const res = await fetchWithRetry('/api/get-parse-session.php', { credentials: 'same-origin' }, 2);
        const data = await res.json();
        if (!data.sessionToken) return;

        Parse.initialize(data.parse_app_id, data.parse_js_key);
        Parse.serverURL = data.parse_url;
        await Parse.User.become(data.sessionToken);
        await initLiveQuery();
    } catch (error) {
        console.warn('Live updates disabled:', error);
    }
}

initParseSession();

async function initLiveQuery() {
    if (liveSubscription || typeof Parse === 'undefined') return;

    try {
        const q = new Parse.Query('Message');
        liveSubscription = await q.subscribe();
        liveSubscription.on('create', async (message) => {
            const chatId = message.get('chat').id;
            const senderId = message.get('sender').id;
            const isCurrentChat = activeChatId === chatId;
            const text = isCurrentChat ? await decryptText(message.get('text')) : message.get('text');

            const msg = {
                id: message.id,
                text,
                senderId,
                date: Math.floor(message.createdAt.getTime() / 1000)
            };

            const added = addMessageToStore(chatId, msg);
            if (!added) return;

            if (isCurrentChat) {
                renderSingleMessage(msg);
                scrollToBottom();
            } else {
                const badge = document.querySelector('.badge-' + chatId);
                if (badge) {
                    badge.innerText = (parseInt(badge.innerText) || 0) + 1;
                }
            }
        });
    } catch (error) {
        console.warn('LiveQuery unavailable:', error);
    }
}

async function createChat() {
    const input = document.getElementById('user-find-input');
    const text = input.value.trim();
    if (!text) return;

    input.value = '';

    try {
        const data = await postAction({ act: 'create_chat', user_id: text });
        window.location.href = 'messenger.php?chat=' + encodeURIComponent(data.chat_id);
    } catch (error) {
        alert(error.message);
    }
}

function addMessageToStore(chatId, msg) {
    if (!messagesStore[chatId]) messagesStore[chatId] = [];
    if (msg.id && messagesStore[chatId].some(existing => existing.id === msg.id)) return false;
    messagesStore[chatId].push(msg);
    messagesStore[chatId].sort((a, b) => a.date - b.date);
    return true;
}

async function selectChat(chatId, username, el) {
    if (activeChatId === chatId) return;

    activeChatId = chatId;

    document.querySelectorAll('.user-item').forEach(i => i.classList.remove('active'));
    if (el) el.classList.add('active');

    const badge = document.querySelector('.badge-' + chatId);
    if (badge) badge.innerText = '';

    document.getElementById('chat-header').innerText = tr('chat_with') + username;
    document.getElementById('input-area').style.display = 'flex';

    const box = document.getElementById('messages-box');
    box.innerHTML = '<div class="messenger-loading">' + tr('loading') + '</div>';

    try {
        if (!messagesStore[chatId] || messagesStore[chatId].length === 0) {
            await loadHistory(chatId);
        } else {
            await getChatKey(chatId);
            await decryptMessages(chatId);
        }

        renderChatFromStore(chatId);
        scrollToBottom();
    } catch (error) {
        box.innerHTML = '<div class="messenger-error">' + tr('chat_load_failed') + '</div>';
        console.error(error);
    }
}

async function getChatKey(chatId) {
    const data = await postAction({ act: 'get_chat_key', chat_id: chatId });
    await initCrypto(data.chatKey);
}

async function loadHistory(chatId) {
    const data = await postAction({ act: 'get_messages', chat_id: chatId });
    await initCrypto(data.chatKey);

    for (const m of data.messages) {
        addMessageToStore(chatId, {
            id: m.id || `${m.date}-${m.text}`,
            text: await decryptText(m.text),
            senderId: m.is_me ? currentUserId() : 'other',
            date: m.date
        });
    }
}

function renderChatFromStore(chatId) {
    const box = document.getElementById('messages-box');
    box.innerHTML = '';

    const messages = messagesStore[chatId] || [];
    if (messages.length === 0) {
        box.innerHTML = '<div class="messenger-empty">' + tr('no_messages') + '</div>';
        return;
    }

    messages.forEach(renderSingleMessage);
}

function renderSingleMessage(msg) {
    const type = msg.senderId === currentUserId() ? 'sent' : 'received';
    appendMessage(msg.text, type, formatDateTime(msg.date));
}

async function decryptMessages(chatId) {
    for (const msg of messagesStore[chatId] || []) {
        if (isEncrypted(msg.text)) {
            msg.text = await decryptText(msg.text);
        }
    }
}

function formatDateTime(ts) {
    const locale = window.langLocale || 'ru-RU';
    const d = new Date(ts * 1000);
    const time = d.toLocaleTimeString(locale, {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    });
    const date = d.toLocaleDateString(locale, {
        day: '2-digit',
        month: '2-digit',
        year: '2-digit'
    });

    return `${time} | ${date}`;
}

async function sendMessage() {
    if (!activeChatId) return;

    const input = document.getElementById('msg-input');
    const plainText = input.value.trim();
    if (!plainText) return;

    input.value = '';

    try {
        await getChatKey(activeChatId);
        const encryptedText = await encryptText(plainText);
        const data = await postAction({
            act: 'send_message',
            chat_id: activeChatId,
            text: encryptedText
        }, 1);

        const msg = {
            id: data.id || `${data.date}-${encryptedText}`,
            text: plainText,
            senderId: currentUserId(),
            date: data.date
        };
        addMessageToStore(activeChatId, msg);
        renderChatFromStore(activeChatId);
        scrollToBottom();
    } catch (error) {
        input.value = plainText;
        alert(tr('msg_not_sent'));
        console.error(error);
    }
}

function appendMessage(text, type, date) {
    const box = document.getElementById('messages-box');
    const div = document.createElement('div');
    div.className = `message ${type}`;
    div.textContent = text;

    const time = document.createElement('div');
    time.className = 'message-time';
    time.textContent = date;
    div.appendChild(time);

    box.appendChild(div);
}

function scrollToBottom() {
    const box = document.getElementById('messages-box');
    box.scrollTop = box.scrollHeight;
}

function handleEnter(e) {
    if (e.key === 'Enter') sendMessage();
}
