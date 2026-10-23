<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/bhashini_api.php';
require_once __DIR__ . '/includes/layout.php';

$settings = bhashini_load_settings();
$glossary = bhashini_load_glossary();
$languages = bhashini_languages();

if (is_post()) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash('error', 'The form expired. Reload the page and try again.');
        redirect('playground.php');
    }

    $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
    if ($action === 'save_glossary') {
        $text  = isset($_POST['glossary_text']) ? (string) $_POST['glossary_text'] : '';
        $pairs = bhashini_parse_glossary($text);
        if (!$pairs && trim($text) !== '') {
            flash('error', 'No valid pairs found. Use one pair per line in the form “English term = Indic term”.');
        } elseif (bhashini_save_glossary($pairs)) {
            flash('success', count($pairs) . ' glossary pair(s) saved. They are applied only by this PHP playground.');
        } else {
            flash('error', 'Could not write storage/glossary.json. Check folder permissions.');
        }
        redirect('playground.php');
    }

    if ($action === 'restore_glossary') {
        bhashini_save_glossary(bhashini_default_glossary());
        flash('success', 'Sample glossary restored.');
        redirect('playground.php');
    }

    if ($action === 'clear_glossary') {
        bhashini_save_glossary([]);
        flash('success', 'Local glossary cleared.');
        redirect('playground.php');
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'glossary') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="bhashini-lab-glossary.csv"');
    header('Cache-Control: no-store');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM so Excel opens Indic text correctly.
    $out = fopen('php://output', 'w');
    if ($out !== false) {
        fputcsv($out, ['English term', 'Indic replacement']);
        foreach ($glossary as $pair) {
            fputcsv($out, [$pair['source'], $pair['target']]);
        }
        fclose($out);
    }
    exit;
}

$sampleText = 'Welcome to the Digital India Bhashini Division portal. This Bhashini Translation Plugin demo helps you test language access for every citizen. Keep your API Key private.';
$defaultTarget = in_array('hi', $settings['language_list'], true) ? 'hi' : $settings['language_list'][0];

render_header($settings, ['title' => 'PHP playground · ' . APP_NAME, 'active' => 'playground.php']);
?>

<section class="section" style="margin-top:0">
    <div class="section-head">
        <div>
            <span class="eyebrow">Server-side test bench</span>
            <h1 style="font-size:clamp(1.7rem,3vw,2.4rem);margin-bottom:8px">PHP translation playground</h1>
            <p>
                The widget is client-side, but the plugin's endpoint can also be called from PHP.
                This page translates through <code class="inline">api/translate.php</code>, shows the
                raw response, and demonstrates a <strong>local glossary override</strong> using
                protected placeholders. The admin controls are marked skip-translation.
            </p>
        </div>
        <span class="badge badge--amber">Trial only · rate limited</span>
    </div>

    <div class="playground-grid">
        <div class="panel bhashini-skip-translation">
            <div class="panel__head">
                <h2>Translate text</h2>
                <button class="btn btn--sm btn--ghost" type="button" id="load-sample"
                        data-sample="<?= e($sampleText) ?>">Load sample</button>
            </div>
            <div class="panel__body">
                <form id="translate-form" class="form-grid">
                    <div class="field">
                        <label for="source-text">Source text</label>
                        <textarea id="source-text" name="text" rows="6" maxlength="5000"
                                  placeholder="Type or paste English text…"><?= e($sampleText) ?></textarea>
                        <p class="help">Up to 5,000 characters. The plugin chunks longer pages automatically; this lab does not.</p>
                    </div>

                    <div class="translate-controls">
                        <div class="field">
                            <label for="source-language">From</label>
                            <select id="source-language" name="source">
                                <?php foreach ($languages as $code => $info): ?>
                                    <option value="<?= e($code) ?>" <?= $settings['source_language'] === $code ? 'selected' : '' ?>>
                                        <?= e($info['name']) ?> (<?= e($code) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="target-language">To</label>
                            <select id="target-language" name="target">
                                <?php foreach ($languages as $code => $info): ?>
                                    <option value="<?= e($code) ?>" <?= $defaultTarget === $code ? 'selected' : '' ?>>
                                        <?= e($info['name']) ?> (<?= e($code) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <label class="check-row" for="use-glossary">
                        <input type="checkbox" id="use-glossary" name="use_glossary" value="1" checked>
                        <span>
                            Apply the local glossary (<?= count($glossary) ?> pair<?= count($glossary) === 1 ? '' : 's' ?>).
                            Terms are replaced with <code class="inline">[[Gn]]</code> placeholders before sending.
                        </span>
                    </label>

                    <div class="btn-row">
                        <button class="btn" type="submit" id="translate-submit">Translate with Bhashini</button>
                        <span class="small muted">POST → <code class="inline">api/translate.php</code></span>
                    </div>
                </form>

                <div id="translation-result" hidden>
                    <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.08em;color:var(--muted)">Result</h3>
                    <div class="result-text" id="translation-output" lang="hi"></div>
                    <div class="result-meta">
                        <span id="translation-meta"></span>
                    </div>
                    <div class="glossary-badges" id="glossary-badges"></div>
                    <div class="error-box" id="translation-error" hidden></div>
                    <details style="margin-top:14px">
                        <summary>Raw JSON response</summary>
                        <pre id="translation-raw"></pre>
                    </details>
                </div>
            </div>
            <div class="panel__foot">
                <p class="small muted" style="margin:0">
                    The API needs an <code class="inline">Origin</code> header. PHP sends the origin of
                    this lab; a mapped production hostname should be used instead.
                </p>
            </div>
        </div>

        <div class="form-grid">
            <div class="panel bhashini-skip-translation">
                <div class="panel__head">
                    <h2>Local glossary override</h2>
                    <span class="badge badge--indigo"><?= count($glossary) ?> pairs</span>
                </div>
                <div class="panel__body">
                    <p class="small muted">
                        This is <strong>not</strong> the official Bhashini glossary. It is a local
                        demonstration: matching English terms are swapped for placeholders, translated,
                        then replaced with the Indic term you supply. The draft list can also be exported
                        as CSV and sent to your onboarding manager for the real glossary upload.
                    </p>

                    <div class="table-wrap" style="margin-bottom:14px">
                        <table>
                            <thead><tr><th>English term</th><th>Override</th></tr></thead>
                            <tbody>
                                <?php if (!$glossary): ?>
                                    <tr><td colspan="2" class="muted">No local pairs. Add some below.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($glossary as $pair): ?>
                                        <tr>
                                            <td><?= e($pair['source']) ?></td>
                                            <td lang="hi"><?= e($pair['target']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <form method="post" action="playground.php" class="form-grid">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="save_glossary">
                        <div class="field">
                            <label for="glossary-text">One pair per line: English = Indic</label>
                            <textarea id="glossary-text" name="glossary_text" rows="5" spellcheck="false"><?= e(bhashini_glossary_to_text($glossary)) ?></textarea>
                            <p class="help">Separators accepted: <code class="inline">=</code>, <code class="inline">=&gt;</code>, <code class="inline">|</code> or a tab.</p>
                        </div>
                        <div class="btn-row">
                            <button class="btn btn--sm" type="submit">Save glossary</button>
                            <a class="btn btn--sm btn--ghost" href="playground.php?export=glossary">Export CSV</a>
                        </div>
                    </form>

                    <div class="btn-row" style="margin-top:10px">
                        <form method="post" action="playground.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="restore_glossary">
                            <button class="btn btn--sm btn--ghost" type="submit">Restore samples</button>
                        </form>
                        <form method="post" action="playground.php" onsubmit="return confirm('Remove all local glossary pairs?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="clear_glossary">
                            <button class="btn btn--sm btn--danger" type="submit">Clear all</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card bhashini-skip-translation">
                <span class="eyebrow">Under the hood</span>
                <h3>Call it from your own PHP</h3>
                <div class="snippet">
                    <div class="snippet-head">
                        <span>PHP</span>
                        <button class="btn btn--sm btn--ghost" type="button" data-copy="#php-snippet">Copy</button>
                    </div>
                    <pre id="php-snippet" style="max-height:none">require 'includes/bhashini_api.php';

$result = bhashini_api_translate(
    'Welcome to the portal.',
    'en',
    'hi',
    bhashini_load_glossary()
);

if ($result['ok']) {
    echo $result['translated'];
} else {
    echo $result['error'];
}</pre>
                </div>
                <p class="small muted" style="margin-top:12px">
                    The helper sets the <code class="inline">Origin</code> header, posts to
                    <code class="inline">/v2/translate-text</code>, and returns a structured array with
                    HTTP status, timing, raw output and glossary usage.
                </p>
            </div>

            <div class="callout callout--warn bhashini-skip-translation">
                <span class="callout__icon" aria-hidden="true">!</span>
                <div>
                    <p><strong>Real glossary vs this demo</strong></p>
                    <p>
                        According to the PDF, the production glossary is uploaded to Udyat and mapped to
                        your API key by your onboarding manager, then applied by Bhashini's backend.
                        Local pairs here never leave this lab and do not affect the widget.
                    </p>
                </div>
            </div>

            <div class="callout callout--info bhashini-skip-translation">
                <span class="callout__icon" aria-hidden="true">i</span>
                <div>
                    <p><strong>Want to see the widget's own translation?</strong></p>
                    <p>
                        Remove the <code class="inline">bhashini-skip-translation</code> class from this
                        panel and select a language. The official plugin will translate this page too,
                        including the admin labels.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php render_footer($settings); ?>
