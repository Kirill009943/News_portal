let cryptoKey = null;
async function initCrypto(chatKeyBase64) {
    const raw = Uint8Array.from(atob(chatKeyBase64), c => c.charCodeAt(0));
    cryptoKey = await
        crypto.subtle.importKey('raw', raw, { name: 'AES-GCM' }, false, ['encrypt', 'decrypt']);
}

async function encryptText(text) {
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const encoded = new TextEncoder().encode(text);
    const cipher = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, cryptoKey, encoded);
    const result = new Uint8Array(iv.length + cipher.byteLength); result.set(iv, 0);
    result.set(new Uint8Array(cipher), iv.length);
    return 'ENC:v1:' + btoa(String.fromCharCode(...result));
}

async function decryptText(base64) {
    if (!isEncrypted(base64)) return base64;
    try {
        const data = Uint8Array.from(atob(base64.slice(7)), c => c.charCodeAt(0));
        const iv = data.slice(0, 12);
        const cipher = data.slice(12);
        const plain = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, cryptoKey, cipher);
        return new TextDecoder().decode(plain);
    } catch (e) {
        return '[Ошибка расшифровки сообщения]';
    }

}

function isEncrypted(text) {
    return typeof text === 'string' && text.startsWith('ENC:v1:');
}