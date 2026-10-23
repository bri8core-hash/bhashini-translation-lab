<?php
/**
 * JSON endpoint used by the PHP playground.
 * Server-side calls the same /v2/translate-text endpoint as the v3 widget.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/bhashini_api.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!is_post()) {
    respond(['ok' => false, 'error' => 'Use POST for this endpoint.'], 405);
}

$sentToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!csrf_verify(is_string($sentToken) ? $sentToken : '')) {
    respond(['ok' => false, 'error' => 'Invalid or missing CSRF token. Reload the page and try again.'], 403);
}

$raw  = file_get_contents('php://input');
$body = json_decode((string) $raw, true);
if (!is_array($body)) {
    $body = $_POST;
}

$text        = isset($body['text']) ? trim((string) $body['text']) : '';
$source      = isset($body['source']) ? (string) $body['source'] : 'en';
$target      = isset($body['target']) ? (string) $body['target'] : 'hi';
$useGlossary = !empty($body['use_glossary']);

$languages = bhashini_languages();
if ($text === '') {
    respond(['ok' => false, 'error' => 'Please enter some text to translate.'], 422);
}
if (mb_strlen($text) > 5000) {
    respond(['ok' => false, 'error' => 'Text is too long for this trial (5,000 characters maximum).'], 422);
}
if (!isset($languages[$source]) || !isset($languages[$target])) {
    respond(['ok' => false, 'error' => 'Unsupported source or target language code.'], 422);
}

// Basic per-session throttle so a public trial deployment is not an open proxy.
$now   = time();
$calls = isset($_SESSION['translate_calls']) && is_array($_SESSION['translate_calls'])
    ? $_SESSION['translate_calls']
    : [];
$calls = array_values(array_filter($calls, static function ($timestamp) use ($now) {
    return is_int($timestamp) && $timestamp > ($now - 600);
}));
if (count($calls) >= 40) {
    respond(['ok' => false, 'error' => 'Rate limit reached (40 translations per 10 minutes). Wait a few minutes and try again.'], 429);
}
$calls[] = $now;
$_SESSION['translate_calls'] = $calls;

$glossary = $useGlossary ? bhashini_load_glossary() : [];
$result   = bhashini_api_translate($text, $source, $target, $glossary);

$rawResponse = (string) ($result['raw_response'] ?? '');
if (strlen($rawResponse) > 4000) {
    $rawResponse = substr($rawResponse, 0, 4000) . '…';
}

respond([
    'ok'                 => !empty($result['ok']),
    'translated'         => $result['translated'],
    'raw_translation'    => $result['raw_translation'],
    'source'             => $result['source'],
    'target'             => $result['target'],
    'rtl'                => bhashini_is_rtl($target),
    'http_status'        => $result['http_status'],
    'origin'             => $result['origin'],
    'elapsed_ms'         => $result['elapsed_ms'],
    'glossary_requested' => $useGlossary ? count($glossary) : 0,
    'glossary_used'      => $result['glossary_used'],
    'request_text'       => $result['request_text'],
    'raw_response'       => $rawResponse,
    'error'              => $result['error'],
]);
