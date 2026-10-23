<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/bhashini_api.php';
require_once __DIR__ . '/includes/layout.php';

$settings = bhashini_load_settings();
$glossary = bhashini_load_glossary();
$run      = isset($_GET['run']) && (string) $_GET['run'] === '1';

/**
 * HEAD/GET probe for the utility script.
 */
function probe_plugin_script(string $url): array
{
    $result = ['ok' => false, 'status' => 0, 'bytes' => null, 'error' => null, 'elapsed_ms' => 0];
    if (!function_exists('curl_init')) {
        $result['error'] = 'cURL unavailable';
        return $result;
    }
    $start = microtime(true);
    $ch = curl_init($url);
    $probeOptions = [
        CURLOPT_NOBODY         => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'BhashiniPluginLab/1.0',
    ];
    $caBundle = bhashini_ca_bundle();
    if ($caBundle !== null) {
        $probeOptions[CURLOPT_CAINFO] = $caBundle;
    }
    curl_setopt_array($ch, $probeOptions);
    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $length = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    $error  = curl_error($ch);
    curl_close($ch);
    $result['status']     = $status;
    $result['bytes']      = is_numeric($length) && $length > 0 ? (int) $length : null;
    $result['error']      = $body === false ? ($error !== '' ? $error : 'Request failed') : null;
    $result['ok']         = $body !== false && $status >= 200 && $status < 400;
    $result['elapsed_ms'] = (int) round((microtime(true) - $start) * 1000);
    return $result;
}

$apiTest    = null;
$scriptTest = null;
if ($run) {
    $apiTest    = bhashini_api_translate('Hello, this is a diagnostics test from the PHP lab.', 'en', 'hi', []);
    $scriptTest = probe_plugin_script(PLUGIN_SRC);
}

$checks = [
    'PHP version'              => PHP_VERSION . (version_compare(PHP_VERSION, '7.4.0', '>=') ? ' ✓' : ' — needs 7.4+'),
    'cURL extension'           => extension_loaded('curl') ? 'Loaded' : 'Missing (required for server-side translation)',
    'JSON extension'           => extension_loaded('json') ? 'Loaded' : 'Missing',
    'mbstring extension'       => extension_loaded('mbstring') ? 'Loaded' : 'Missing (recommended for Indic text)',
    'OpenSSL extension'        => extension_loaded('openssl') ? 'Loaded' : 'Missing (HTTPS requests may fail)',
    'allow_url_fopen'          => ini_get('allow_url_fopen') ? 'On' : 'Off (cURL is used instead)',
    'CA bundle (cURL)'         => bhashini_ca_bundle() ?: 'Missing — HTTPS requests will fail certificate verification',
    'storage/ writable'        => is_writable(STORAGE_DIR) ? 'Yes' : 'No — settings and glossary cannot be saved',
    'settings file'            => is_file(SETTINGS_FILE) ? 'Present' : 'Not created yet (defaults in use)',
    'glossary file'            => is_file(GLOSSARY_FILE) ? 'Present' : 'Not created yet (sample pairs in use)',
    'Request origin sent'      => bhashini_api_origin(),
    'PHP SAPI'                 => PHP_SAPI,
];

$settingsJson = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$glossaryJson = json_encode($glossary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

render_header($settings, ['title' => 'Diagnostics · ' . APP_NAME, 'active' => 'diagnostics.php']);
?>

<div class="bhashini-skip-translation">
    <div class="section-head" style="margin-top:0">
        <div>
            <span class="eyebrow">Troubleshooting</span>
            <h1 style="font-size:clamp(1.7rem,3vw,2.4rem);margin-bottom:8px">Diagnostics</h1>
            <p>
                Server checks, a live API call, the plugin console and the browser's localStorage in
                one place. This page is marked skip-translation so technical values stay readable.
            </p>
        </div>
        <div class="btn-row">
            <a class="btn btn--sm" href="diagnostics.php?run=1">Run live tests</a>
            <button class="btn btn--sm btn--ghost" type="button" data-reset-language>Reset language</button>
        </div>
    </div>

    <div class="grid-2" style="align-items:start">
        <div class="panel">
            <div class="panel__head">
                <h2>Server environment</h2>
                <span class="badge badge--indigo">PHP <?= e(PHP_VERSION) ?></span>
            </div>
            <div class="panel__body">
                <dl class="diag-list">
                    <?php foreach ($checks as $label => $value): ?>
                        <div class="diag-row">
                            <dt><?= e($label) ?></dt>
                            <dd><?= e($value) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
                <p class="small muted" style="margin-top:12px">
                    Running from <code><?= e(bhashini_app_url()) ?></code>.
                    The translation endpoint currently accepts <code>localhost</code> origins; production
                    requests must come from a hostname mapped in the Bhashini dashboard.
                </p>
            </div>
        </div>

        <div class="form-grid">
            <div class="panel">
                <div class="panel__head">
                    <h2>Browser checks</h2>
                    <span class="widget-chip" data-plugin-status data-state="waiting">
                        <span class="status-dot" aria-hidden="true"></span>
                        <span data-plugin-status-text>Checking widget…</span>
                    </span>
                </div>
                <div class="panel__body">
                    <p class="small muted">Collected with JavaScript; PHP cannot see localStorage.</p>
                    <div class="table-wrap" style="margin-top:12px">
                        <table>
                            <thead><tr><th>localStorage key</th><th>Value</th></tr></thead>
                            <tbody id="storage-rows">
                                <tr><td colspan="2" class="muted">Reading localStorage…</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="small" style="margin-top:12px">
                        Expected key: <code>preferredLanguage</code>. Also check
                        <code>sessionStorage.translationCache</code> in DevTools for cached responses.
                    </p>
                </div>
            </div>

            <div class="callout callout--info">
                <span class="callout__icon" aria-hidden="true">i</span>
                <div>
                    <p><strong>Widget is missing?</strong></p>
                    <p>
                        Confirm the container is emitted before the script, the plugin is enabled in
                        <a href="settings.php">Settings</a>, and the browser can reach
                        <code>translation-plugin.bhashini.co.in</code>. The console panel below records
                        <code>[Bhashini]</code> messages.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($run): ?>
        <div class="section">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Live tests · <?= date('H:i:s') ?></span>
                    <h2>Results</h2>
                    <p>These requests ran from PHP on the server, not from your browser.</p>
                </div>
            </div>

            <div class="grid-2" style="align-items:start">
                <div class="panel">
                    <div class="panel__head">
                        <h2>Translation API</h2>
                        <?php if ($apiTest && $apiTest['ok']): ?>
                            <span class="badge badge--green">OK · <?= (int) $apiTest['http_status'] ?> · <?= (int) $apiTest['elapsed_ms'] ?> ms</span>
                        <?php else: ?>
                            <span class="badge badge--red">Failed</span>
                        <?php endif; ?>
                    </div>
                    <div class="panel__body">
                        <?php if ($apiTest): ?>
                            <p class="small muted">
                                POST <code><?= e(API_URL) ?></code><br>
                                Origin <code><?= e($apiTest['origin']) ?></code> · source <code><?= e($apiTest['source']) ?></code> → target <code><?= e($apiTest['target']) ?></code>
                            </p>
                            <div class="result-text" lang="hi" style="margin-top:12px"><?= e($apiTest['translated'] ?: $apiTest['error']) ?></div>
                            <div class="result-meta">
                                <span>HTTP <?= (int) $apiTest['http_status'] ?></span>
                                <span><?= (int) $apiTest['elapsed_ms'] ?> ms</span>
                                <?php if ($apiTest['error']): ?><span class="badge badge--red"><?= e($apiTest['error']) ?></span><?php endif; ?>
                            </div>
                            <details style="margin-top:12px">
                                <summary>Raw API response</summary>
                                <pre><?= e($apiTest['raw_response'] ?: '(empty)') ?></pre>
                            </details>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel__head">
                        <h2>Plugin script</h2>
                        <?php if ($scriptTest && $scriptTest['ok']): ?>
                            <span class="badge badge--green">Reachable</span>
                        <?php else: ?>
                            <span class="badge badge--red">Not reachable</span>
                        <?php endif; ?>
                    </div>
                    <div class="panel__body">
                        <?php if ($scriptTest): ?>
                            <dl class="diag-list">
                                <div class="diag-row"><dt>URL</dt><dd><code><?= e(PLUGIN_SRC) ?></code></dd></div>
                                <div class="diag-row"><dt>HTTP status</dt><dd><?= (int) $scriptTest['status'] ?></dd></div>
                                <div class="diag-row"><dt>Content-Length</dt><dd><?= $scriptTest['bytes'] ? number_format((int) $scriptTest['bytes']) . ' bytes' : 'Not reported (HEAD request)' ?></dd></div>
                                <div class="diag-row"><dt>Round trip</dt><dd><?= (int) $scriptTest['elapsed_ms'] ?> ms</dd></div>
                                <?php if ($scriptTest['error']): ?>
                                    <div class="diag-row"><dt>Error</dt><dd><?= e($scriptTest['error']) ?></dd></div>
                                <?php endif; ?>
                            </dl>
                            <p class="small muted" style="margin-top:12px">
                                A HEAD request is used so the lab does not download the whole 160 KB utility.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="callout callout--warn" style="margin-top:26px">
            <span class="callout__icon" aria-hidden="true">!</span>
            <div>
                <p><strong>Live tests have not run yet.</strong></p>
                <p>Use <a href="diagnostics.php?run=1">Run live tests</a> to call the translation endpoint and probe the plugin script from PHP.</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="section">
        <div class="panel">
            <div class="panel__head">
                <h2>Browser console</h2>
                <div class="log-toolbar" style="margin:0">
                    <label class="check-row" style="font-size:.8rem;color:var(--muted)">
                        <input type="checkbox" id="only-bhashini" checked>
                        <span>Only [Bhashini] messages</span>
                    </label>
                    <button class="btn btn--sm btn--ghost" type="button" id="clear-console">Clear</button>
                </div>
            </div>
            <div class="panel__body">
                <p class="small muted" style="margin-bottom:10px">
                    Captured before the plugin loads. Includes errors and unhandled promise rejections.
                </p>
                <pre id="console-log" class="log-box" aria-live="polite"></pre>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="grid-2" style="align-items:start">
            <div class="card">
                <span class="eyebrow">Rendered markup</span>
                <h3>Effective script tag</h3>
                <div class="snippet" style="margin-top:12px">
                    <div class="snippet-head">
                        <span>PHP output</span>
                        <button class="btn btn--sm btn--ghost" type="button" data-copy="#diag-snippet">Copy</button>
                    </div>
                    <pre id="diag-snippet" style="max-height:none;white-space:pre-wrap"><?= e(bhashini_plugin_snippet($settings)) ?></pre>
                </div>
                <p class="small muted" style="margin-top:12px">
                    Mount placement: <strong><?= e(bhashini_placement_label($settings['placement'])) ?></strong>.
                    Container selector: <code>.bhashini-plugin-container</code> (the plugin uses the first match).
                </p>

                <details style="margin-top:14px">
                    <summary>Saved settings JSON</summary>
                    <pre><?= e((string) $settingsJson) ?></pre>
                </details>
                <details>
                    <summary>Local glossary JSON</summary>
                    <pre><?= e((string) $glossaryJson) ?></pre>
                </details>
            </div>

            <div class="card">
                <span class="eyebrow">How to read the results</span>
                <h3>Common failure modes</h3>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Symptom</th><th>Likely cause</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>No widget appears</td>
                                <td>Container missing, plugin disabled, script blocked, or container rendered after the script.</td>
                            </tr>
                            <tr>
                                <td>Button appears, nothing translates</td>
                                <td>Hostname not mapped, API error, or all content is skip-translated.</td>
                            </tr>
                            <tr>
                                <td>401 “Host is not mapped”</td>
                                <td>The origin sent to Bhashini is not approved. Map it in the dashboard.</td>
                            </tr>
                            <tr>
                                <td>Translation stuck in English</td>
                                <td>Selected language equals the page source language, or the site URL has no matching domain mapping.</td>
                            </tr>
                            <tr>
                                <td>Constant retranslation</td>
                                <td>A script replaces DOM text on hover or state change. See FAQ #3.</td>
                            </tr>
                            <tr>
                                <td>PHP API test fails</td>
                                <td>cURL/OpenSSL missing, DNS blocked, or the Origin header is rejected.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="callout callout--danger">
            <span class="callout__icon" aria-hidden="true">!</span>
            <div>
                <p><strong>Do not expose this diagnostics page publicly.</strong></p>
                <p>
                    It reveals local paths, environment details and translation responses. Remove it or
                    protect it with authentication before deploying the lab anywhere beyond localhost.
                </p>
            </div>
        </div>
    </div>
</div>

<?php render_footer($settings); ?>
