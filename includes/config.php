<?php
/**
 * Bhashini Translation Plugin Lab
 * Shared configuration, settings storage and helpers.
 *
 * PHP 7.4+ / no external dependencies.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'Bhashini Translation Plugin Lab');
define('APP_ROOT', dirname(__DIR__));
define('STORAGE_DIR', APP_ROOT . '/storage');
define('SETTINGS_FILE', STORAGE_DIR . '/settings.json');
define('GLOSSARY_FILE', STORAGE_DIR . '/glossary.json');
define('PLUGIN_SRC', 'https://translation-plugin.bhashini.co.in/v3/website_translation_utility.js');
define('PLUGIN_PAGE', 'https://translation-plugin.bhashini.co.in/v3/website_translation_utility.js');
define('API_URL', 'https://translation-plugin.bhashini.co.in/v2/translate-text');
define('PLUGIN_DOCS', 'https://dashboard.bhashini.co.in/user/register');

/**
 * Languages exposed by the v3 utility (Indian languages list).
 * Native names are shown in the settings UI; the plugin has its own labels.
 */
function bhashini_languages(): array
{
    return [
        'en'  => ['name' => 'English',        'native' => 'English'],
        'as'  => ['name' => 'Assamese',       'native' => 'অসমীয়া'],
        'bn'  => ['name' => 'Bengali',        'native' => 'বাংলা'],
        'brx' => ['name' => 'Bodo',           'native' => 'बड़ो'],
        'doi' => ['name' => 'Dogri',          'native' => 'डोगरी'],
        'gom' => ['name' => 'Goan Konkani',   'native' => 'गोंयची कोंकणी'],
        'gu'  => ['name' => 'Gujarati',       'native' => 'ગુજરાતી'],
        'hi'  => ['name' => 'Hindi',          'native' => 'हिन्दी'],
        'kn'  => ['name' => 'Kannada',        'native' => 'ಕನ್ನಡ'],
        'ks'  => ['name' => 'Kashmiri',       'native' => 'کٲشُر'],
        'mai' => ['name' => 'Maithili',       'native' => 'मैथिली'],
        'ml'  => ['name' => 'Malayalam',      'native' => 'മലയാളം'],
        'mni' => ['name' => 'Manipuri',       'native' => 'মণিপুরী'],
        'mr'  => ['name' => 'Marathi',        'native' => 'मराठी'],
        'ne'  => ['name' => 'Nepali',         'native' => 'नेपाली'],
        'or'  => ['name' => 'Odia',           'native' => 'ଓଡ଼ିଆ'],
        'pa'  => ['name' => 'Punjabi',        'native' => 'ਪੰਜਾਬੀ'],
        'sa'  => ['name' => 'Sanskrit',       'native' => 'संस्कृत'],
        'sat' => ['name' => 'Santali',        'native' => 'संताली'],
        'sd'  => ['name' => 'Sindhi',         'native' => 'سنڌي'],
        'ta'  => ['name' => 'Tamil',          'native' => 'தமிழ்'],
        'te'  => ['name' => 'Telugu',         'native' => 'తెలుగు'],
        'ur'  => ['name' => 'Urdu',           'native' => 'اردو'],
    ];
}

/** Right-to-left target languages. */
function bhashini_is_rtl(string $code): bool
{
    return in_array($code, ['ur', 'sd', 'ks'], true);
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_post(): bool
{
    return isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
}

/* -------------------------------------------------------------------------
 * CSRF + flash messages
 * ---------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/* -------------------------------------------------------------------------
 * JSON storage
 * ---------------------------------------------------------------------- */

function bhashini_read_json(string $file, $fallback)
{
    if (!is_file($file)) {
        return $fallback;
    }
    $raw = @file_get_contents($file);
    if ($raw === false || trim($raw) === '') {
        return $fallback;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $fallback;
}

function bhashini_write_json(string $file, $data): bool
{
    if (!is_dir(STORAGE_DIR) && !@mkdir(STORAGE_DIR, 0775, true) && !is_dir(STORAGE_DIR)) {
        return false;
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }
    return @file_put_contents($file, $json . "\n", LOCK_EX) !== false;
}

/* -------------------------------------------------------------------------
 * Plugin settings
 * ---------------------------------------------------------------------- */

function bhashini_default_settings(): array
{
    return [
        'enabled'          => true,
        'placement'        => 'bottom-right', // bottom-right | bottom-left | inline
        'icon_color'       => '#1D0A69',
        'default_language' => 'en',
        'source_language'  => 'en',
        'auto_translate'   => false,
        'language_list'    => ['en', 'hi', 'kn', 'ta', 'bn', 'mr'],
    ];
}

function bhashini_sanitize_settings(array $input): array
{
    $defaults  = bhashini_default_settings();
    $languages = bhashini_languages();
    $out       = $defaults;

    // Checkboxes are posted only when checked; the settings form adds a "0"
    // hidden field so an intentionally unchecked box is not confused with a
    // missing key from an older or empty settings file.
    if (array_key_exists('enabled', $input)) {
        $out['enabled'] = !empty($input['enabled']);
    }

    $placements = ['bottom-right', 'bottom-left', 'inline'];
    $placement  = isset($input['placement']) ? (string) $input['placement'] : $defaults['placement'];
    $out['placement'] = in_array($placement, $placements, true) ? $placement : $defaults['placement'];

    $color = isset($input['icon_color']) ? trim((string) $input['icon_color']) : '';
    if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
        $out['icon_color'] = strtolower($color);
    }

    $source = isset($input['source_language']) ? (string) $input['source_language'] : $defaults['source_language'];
    $out['source_language'] = isset($languages[$source]) ? $source : $defaults['source_language'];

    $selected = [];
    if (isset($input['languages']) && is_array($input['languages'])) {
        foreach ($languages as $code => $info) {
            if (in_array($code, $input['languages'], true)) {
                $selected[] = $code;
            }
        }
    }
    if (!$selected) {
        $selected = $defaults['language_list'];
    }
    // English must stay available so visitors can switch back.
    if (!in_array('en', $selected, true)) {
        array_unshift($selected, 'en');
    }
    $out['language_list'] = $selected;

    $default = isset($input['default_language']) ? (string) $input['default_language'] : $defaults['default_language'];
    if (!in_array($default, $selected, true)) {
        $default = in_array('en', $selected, true) ? 'en' : $selected[0];
    }
    $out['default_language'] = $default;

    $auto = array_key_exists('auto_translate', $input)
        ? !empty($input['auto_translate'])
        : $defaults['auto_translate'];
    $out['auto_translate'] = $auto && $default !== $out['source_language'];

    return $out;
}

function bhashini_load_settings(): array
{
    $stored = bhashini_read_json(SETTINGS_FILE, []);
    return bhashini_sanitize_settings(is_array($stored) ? $stored : []);
}

function bhashini_save_settings(array $settings): bool
{
    return bhashini_write_json(SETTINGS_FILE, bhashini_sanitize_settings($settings));
}

function bhashini_placement_label(string $placement): string
{
    $labels = [
        'bottom-right' => 'Floating — bottom right',
        'bottom-left'  => 'Floating — bottom left',
        'inline'       => 'Inline in the page',
    ];
    return $labels[$placement] ?? $placement;
}

/**
 * Script attributes for the official plugin, based on saved settings.
 * Only attributes supported by the current v3 utility build are emitted.
 */
function bhashini_plugin_attributes(array $settings): array
{
    if (empty($settings['enabled'])) {
        return [];
    }

    $attrs = [
        'language-icon-color'       => $settings['icon_color'],
        'translation-language-list' => implode(',', $settings['language_list']),
    ];

    if (!empty($settings['auto_translate'])
        && $settings['default_language'] !== $settings['source_language']) {
        $attrs['default-translated-language'] = $settings['default_language'];
    }

    if ($settings['source_language'] !== 'en') {
        $attrs['page-source-language'] = $settings['source_language'];
    }

    return $attrs;
}

/** Full <script> tag for live use. */
function bhashini_plugin_script_tag(array $settings): string
{
    if (empty($settings['enabled'])) {
        return "<!-- Bhashini plugin disabled in Settings -->";
    }

    $html  = '<script src="' . e(PLUGIN_SRC) . '"';
    foreach (bhashini_plugin_attributes($settings) as $name => $value) {
        $html .= ' ' . $name . '="' . e($value) . '"';
    }
    return $html . '></script>';
}

/** Escaped snippet for display inside <code>. */
function bhashini_plugin_snippet(array $settings): string
{
    return bhashini_plugin_script_tag($settings);
}

/** Pick a dark pill behind the widget when the chosen icon colour is light. */
function bhashini_icon_is_light(string $hex): bool
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return false;
    }
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;
    $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    return $luminance > 0.72;
}

/* -------------------------------------------------------------------------
 * Local glossary (demo only)
 *
 * The real Bhashini glossary is maintained per mapped hostname inside the
 * Udyat dashboard and applied by Bhashini's backend. The pairs below are only
 * used by this lab's PHP playground, which protects terms with [[G0]] tokens
 * before calling the public translation endpoint.
 * ---------------------------------------------------------------------- */

function bhashini_default_glossary(): array
{
    return [
        ['source' => 'Digital India Bhashini Division', 'target' => 'डिजिटल इंडिया भाषिणी प्रभाग'],
        ['source' => 'Bhashini Translation Plugin',     'target' => 'भाषिणी अनुवाद प्लगइन'],
        ['source' => 'API Key',                         'target' => 'एपीआई कुंजी'],
    ];
}

function bhashini_load_glossary(): array
{
    $stored = bhashini_read_json(GLOSSARY_FILE, null);
    if (!is_array($stored)) {
        return bhashini_default_glossary();
    }
    return bhashini_normalize_glossary($stored);
}

function bhashini_normalize_glossary(array $pairs): array
{
    $clean = [];
    $seen  = [];
    foreach ($pairs as $pair) {
        if (!is_array($pair)) {
            continue;
        }
        $source = isset($pair['source']) ? trim((string) $pair['source']) : '';
        $target = isset($pair['target']) ? trim((string) $pair['target']) : '';
        if ($source === '' || $target === '') {
            continue;
        }
        if (mb_strlen($source) > 200 || mb_strlen($target) > 300) {
            continue;
        }
        $key = mb_strtolower($source);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $clean[] = ['source' => $source, 'target' => $target];
        if (count($clean) >= 100) {
            break;
        }
    }
    return $clean;
}

/** Parse "English = Indic" lines (also supports "=>", "|" and tabs). */
function bhashini_parse_glossary(string $text): array
{
    $pairs = [];
    $lines = preg_split('/\r\n|\r|\n/', $text);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        $parts = null;
        foreach (['=>', '=', '|', "\t"] as $separator) {
            if (strpos($line, $separator) !== false) {
                $parts = explode($separator, $line, 2);
                break;
            }
        }
        if ($parts === null) {
            continue;
        }
        $source = trim($parts[0]);
        $target = trim($parts[1]);
        if ($source !== '' && $target !== '') {
            $pairs[] = ['source' => $source, 'target' => $target];
        }
    }
    return bhashini_normalize_glossary($pairs);
}

function bhashini_glossary_to_text(array $pairs): string
{
    $lines = [];
    foreach ($pairs as $pair) {
        $lines[] = $pair['source'] . ' = ' . $pair['target'];
    }
    return implode("\n", $lines);
}

function bhashini_save_glossary(array $pairs): bool
{
    return bhashini_write_json(GLOSSARY_FILE, bhashini_normalize_glossary($pairs));
}

/* -------------------------------------------------------------------------
 * API origin
 *
 * The public translate endpoint rejects requests without an Origin header.
 * By default we forward the origin of this lab (localhost works today), but
 * you can override it with the BHASHINI_API_ORIGIN environment variable.
 * A production deployment must use a hostname approved in the Bhashini
 * dashboard.
 * ---------------------------------------------------------------------- */

function bhashini_api_origin(): string
{
    $override = getenv('BHASHINI_API_ORIGIN');
    if (is_string($override) && trim($override) !== '') {
        return rtrim(trim($override), '/');
    }
    $scheme = 'http';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_PROTO'])[0] === 'https' ? 'https' : 'http';
    }
    $host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : 'localhost';
    return $scheme . '://' . $host;
}

/**
 * Locate a CA bundle for server-side HTTPS requests.
 *
 * A stock XAMPP PHP often has cURL enabled but no curl.cainfo configured, which
 * makes every HTTPS call fail with "unable to get local issuer certificate".
 * This lab ships certs/cacert.pem and prefers it, then falls back to php.ini.
 */
function bhashini_ca_bundle(): ?string
{
    $candidates = [];
    $env = getenv('BHASHINI_CA_BUNDLE');
    if (is_string($env) && trim($env) !== '') {
        $candidates[] = trim($env);
    }
    $candidates[] = APP_ROOT . '/certs/cacert.pem';
    $curlCa = ini_get('curl.cainfo');
    if (is_string($curlCa) && $curlCa !== '') {
        $candidates[] = $curlCa;
    }
    $opensslCa = ini_get('openssl.cafile');
    if (is_string($opensslCa) && $opensslCa !== '') {
        $candidates[] = $opensslCa;
    }
    foreach ($candidates as $candidate) {
        if (is_file($candidate) && is_readable($candidate)) {
            return $candidate;
        }
    }
    return null;
}

/** Best-effort app URL (used in docs/status panels). */
function bhashini_app_url(): string
{
    return bhashini_api_origin() . rtrim(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')), '/\\') . '/';
}
