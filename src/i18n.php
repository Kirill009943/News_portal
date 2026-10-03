<?php

const LANGS = ['ru', 'en'];
const LANG_DEFAULT = 'ru';

$langCode = $_COOKIE['lang'] ?? LANG_DEFAULT;
if (!in_array($langCode, LANGS, true)) {
    $langCode = LANG_DEFAULT;
}

define('LANG_CODE', $langCode);

$LANG = require dirname(__DIR__) . '/lang/' . LANG_CODE . '.php';

function t(string $key): string
{
    global $LANG;
    return $LANG[$key] ?? $key;
}

function e(string $key): string
{
    return htmlspecialchars(t($key), ENT_QUOTES);
}

function langJson(): string
{
    global $LANG;
    return json_encode($LANG, JSON_UNESCAPED_UNICODE);
}

function langLocale(): string
{
    return LANG_CODE === 'ru' ? 'ru-RU' : 'en-US';
}
