# News.local

A PHP news site powered by [Back4App](https://www.back4app.com/) (Parse Platform).

Features:

- News feed with sorting (hot / popular / new / old) and infinite scroll
- Voting, comments, favorites
- Post moderation by admins (approve / reject with a reason)
- Private messenger with end-to-end encrypted messages (AES-256; each chat key
  is encrypted server-side with a master key, decryption happens in the browser
  via WebCrypto)
- Dark/light theme and font-size settings
- Multilingual UI (Russian / English)

## Requirements

- PHP 8.x (the project is configured for Open Server Panel 6, PHP-8.4)
- Composer
- A Back4App account with an app (Parse Server + LiveQuery)

## Installation

1. Clone the repository into your domains folder (e.g. `OSPanel/home/news.local`).
2. Install dependencies:
   ```bash
   composer install
   ```
3. Copy `.env.example` to `.env` and fill in the keys from your Back4App
   dashboard (App ID, REST API Key, Master Key, JavaScript Key).
4. Generate the chat encryption master key and put it in `.env`:
   ```bash
   openssl rand -hex 32
   ```
5. Make sure the web server document root points to the `public/` directory.
   With Open Server Panel 6 this is already configured in `.osp/project.ini`
   (`web_root = {base_dir}\public`) — restart OSPanel after changing this file.
6. In production over HTTPS set `COOKIE_SECURE=true` in `.env`.

## Project structure

```
├── public/              # web server document root
│   ├── index.php        # news feed
│   ├── post.php         # post page + comments
│   ├── profile.php      # sign in / registration, post creation
│   ├── favorites.php    # favorite posts
│   ├── admin.php        # post moderation
│   ├── messenger.php    # private messenger
│   ├── settings.php     # appearance & language settings
│   ├── api/             # AJAX endpoints
│   │   ├── action.php           # all actions (votes, chats, posts, moderation)
│   │   ├── load_posts.php       # post card loading (feed pagination)
│   │   └── get-parse-session.php  # session handoff for the Parse JS SDK
│   └── assets/          # css / js
├── src/
│   ├── bootstrap.php    # init: .env, session, Parse SDK
│   ├── helpers.php      # parseRetry(), formatDate()
│   └── i18n.php         # language detection + t()/e() helpers
├── lang/                # UI translations (ru.php, en.php)
├── templates/           # templates: header, post cards
├── .osp/                # Open Server Panel project config
├── .env                 # secrets (not committed!)
└── .env.example         # config template
```

## Environment variables (.env)

| Variable              | Purpose                                             |
|-----------------------|-----------------------------------------------------|
| `PARSE_APP_ID`        | Back4App Application ID                             |
| `PARSE_REST_KEY`      | REST API Key                                        |
| `PARSE_MASTER_KEY`    | Master Key (server-side only!)                      |
| `PARSE_SERVER_URL`    | Parse API URL (`https://parseapi.back4app.com/`)    |
| `PARSE_JS_KEY`        | JavaScript Key (Parse JS SDK / LiveQuery)           |
| `PARSE_LIVEQUERY_URL` | LiveQuery server URL (`https://<app>.b4a.io/`)      |
| `CHAT_MASTER_KEY_HEX` | 64 hex chars — encrypts per-chat keys               |
| `COOKIE_SECURE`       | `true` — send cookies over HTTPS only (production)  |

## Localization

The UI language is selected on the **Settings** page (Russian or English) and
stored in the `lang` cookie. Translations live in `lang/ru.php` and
`lang/en.php`; use `t('key')` / `e('key')` in PHP templates and
`window.i18n` + `tr('key')` in JavaScript. To add a language, create
`lang/<code>.php` with the same keys and add the code to `LANGS` in
`src/i18n.php`.

## Security note

The API keys used to be hardcoded in the source (`config.php`,
`get-parse-session.php`). If this repository was ever published anywhere,
**rotate the keys in your Back4App dashboard** and generate a new
`CHAT_MASTER_KEY_HEX` (note: previously encrypted chat keys will become
undecryptable after that).
