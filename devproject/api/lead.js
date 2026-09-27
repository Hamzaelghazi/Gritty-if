// Serverless function: receives a lead from the landing page and forwards it
// to your Telegram bot. Works on Vercel and Netlify (default export handler).
//
// SET THESE AS ENVIRONMENT VARIABLES on your host — never hard-code them here:
//   TELEGRAM_BOT_TOKEN   the token from @BotFather, e.g. 123456:AA...
//   TELEGRAM_CHAT_ID     where to deliver leads (your user id, or a group/channel id)
//
// The token stays on the server, so it is never exposed to visitors.

export default async function handler(req, res) {
    if (req.method !== 'POST') {
        return res.status(405).json({ error: 'Method not allowed' });
    }

    const token = process.env.TELEGRAM_BOT_TOKEN;
    const chatId = process.env.TELEGRAM_CHAT_ID;
    if (!token || !chatId) {
        return res.status(500).json({ error: 'Server not configured' });
    }

    // Body may arrive parsed (Vercel) or as a raw string.
    let body = req.body;
    if (typeof body === 'string') {
        try { body = JSON.parse(body); } catch (_) { body = {}; }
    }
    body = body || {};

    const name = String(body.name || '').trim().slice(0, 100);
    const email = String(body.email || '').trim().slice(0, 200);
    const page = String(body.page || '').trim().slice(0, 300);

    const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email);
    if (name.length < 2 || !emailOk) {
        return res.status(400).json({ error: 'Invalid name or email' });
    }

    // Escape for Telegram HTML parse mode.
    const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    const text =
        '🚀 <b>New Gritty-ify lead</b>\n\n' +
        '👤 <b>Name:</b> ' + esc(name) + '\n' +
        '✉️ <b>Email:</b> ' + esc(email) + '\n' +
        (page ? '🔗 <b>From:</b> ' + esc(page) + '\n' : '') +
        '🕒 ' + new Date().toISOString();

    try {
        const tgRes = await fetch('https://api.telegram.org/bot' + token + '/sendMessage', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ chat_id: chatId, text, parse_mode: 'HTML', disable_web_page_preview: true })
        });
        const data = await tgRes.json();
        if (!data.ok) {
            console.error('Telegram error:', data);
            return res.status(502).json({ error: 'Telegram rejected the message' });
        }
        return res.status(200).json({ ok: true });
    } catch (err) {
        console.error(err);
        return res.status(502).json({ error: 'Could not reach Telegram' });
    }
}
