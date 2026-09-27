# Sending leads to your Telegram bot

The landing page (`landing.html`) collects a **name** and **email**, then posts
them to a small backend function (`api/lead.js`). That function forwards the
lead to your Telegram bot.

**Why a backend?** Your bot token must stay secret. If you put it in the
page's JavaScript, anyone who opens the page can read it and hijack your bot.
The backend keeps the token on the server, out of the visitor's reach.

## 1. Create the bot and get your IDs

1. In Telegram, open **@BotFather**, send `/newbot`, and follow the prompts.
   BotFather gives you a **token** like `123456789:AA...`.
2. Get your **chat id** (where leads should arrive):
   - Send any message to your new bot first.
   - Open `https://api.telegram.org/bot<YOUR_TOKEN>/getUpdates` in a browser.
   - Find `"chat":{"id":...}` — that number is your `TELEGRAM_CHAT_ID`.
   - For a group: add the bot to the group, send a message, and read the
     group id from the same URL (group ids are negative, e.g. `-100...`).

## 2. Deploy (free options)

### Vercel
1. Push this folder to a Git repo and import it at vercel.com.
2. In **Settings → Environment Variables**, add:
   - `TELEGRAM_BOT_TOKEN` = your token
   - `TELEGRAM_CHAT_ID` = your chat id
3. Deploy. `api/lead.js` is served at `/api/lead` automatically.

### Netlify
1. Move `api/lead.js` to `netlify/functions/lead.js`.
2. Add the same two environment variables under **Site settings → Environment**.
3. In `ressources/js/landing.js`, set `ENDPOINT` to `/.netlify/functions/lead`.

## 3. Test

Open the page, submit a name and email, and the lead should appear in your
Telegram chat within a second or two.

## Notes
- The function validates the name and email server-side too, so junk from
  scripted requests is rejected.
- To also store leads (spreadsheet, database, email), add that inside
  `api/lead.js` alongside the Telegram call.
- Consider adding a spam guard (hCaptcha / Cloudflare Turnstile, or a simple
  rate limit) before going live, since the endpoint is public.
