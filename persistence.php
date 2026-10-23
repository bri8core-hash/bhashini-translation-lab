<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/layout.php';

$settings = bhashini_load_settings();
render_header($settings, ['title' => 'Preferred language · ' . APP_NAME, 'active' => 'persistence.php']);
?>

<section class="section" style="margin-top:0">
    <div class="section-head">
        <div>
            <span class="eyebrow">PDF feature 5</span>
            <h1 style="font-size:clamp(1.7rem,3vw,2.4rem);margin-bottom:8px">Preferred language storage</h1>
            <p>
                The plugin remembers the last language a visitor selected by writing
                <code class="inline">preferredLanguage</code> to the browser's localStorage. On the next
                page of the same website, the widget restores that language and translates automatically.
            </p>
        </div>
        <span class="badge badge--indigo">Per origin, per browser</span>
    </div>

    <div class="grid-2" style="align-items:start">
        <div class="card">
            <span class="eyebrow">Test it in 60 seconds</span>
            <h2>Follow the visitor's journey</h2>
            <ol class="steps">
                <li>
                    <strong>Pick an Indic language</strong>
                    <p>Use the widget on this page and choose Hindi, Tamil or Bengali.</p>
                </li>
                <li>
                    <strong>Check the stored value</strong>
                    <p>The live panel below should show <code class="inline">preferredLanguage</code> set to that language code.</p>
                </li>
                <li>
                    <strong>Open another page</strong>
                    <p>Navigate with the links in the panel. The next page loads in the saved language without asking again.</p>
                </li>
                <li>
                    <strong>Reset the preference</strong>
                    <p>Use the reset button and pages return to the original English content.</p>
                </li>
            </ol>

            <div class="callout callout--info" style="margin-top:18px">
                <span class="callout__icon" aria-hidden="true">i</span>
                <div>
                    <p><strong>Why this works on every page</strong></p>
                    <p>
                        Every page in this lab loads the same v3 utility from the same origin. The
                        widget reads the key during initialisation, so no server-side session or
                        database is involved.
                    </p>
                </div>
            </div>
        </div>

        <div class="card bhashini-skip-translation">
            <span class="eyebrow">Live browser state</span>
            <h3>localStorage on this origin</h3>
            <p class="small muted">This table is read by JavaScript every two seconds. It cannot be rendered by PHP.</p>

            <div class="table-wrap" style="margin-top:12px">
                <table>
                    <thead><tr><th>Key</th><th>Value</th></tr></thead>
                    <tbody id="storage-rows">
                        <tr><td colspan="2" class="muted">Reading localStorage…</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="btn-row" style="margin-top:14px">
                <button class="btn btn--sm" type="button" data-set-language="hi">Prefill Hindi</button>
                <button class="btn btn--sm" type="button" data-set-language="ta">Prefill Tamil</button>
                <button class="btn btn--sm btn--ghost" type="button" data-set-language="bn">Prefill Bengali</button>
                <button class="btn btn--sm btn--danger" type="button" data-reset-language>Reset to English</button>
            </div>
            <p class="help" style="margin-top:10px">
                “Prefill” writes the code and reloads. On reload the plugin applies it exactly as if
                the visitor had picked it from the dropdown.
            </p>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <div>
            <span class="eyebrow">Cross-page links</span>
            <h2>Navigate without losing the choice</h2>
            <p>Open each link in the same tab. Watch the content arrive already translated.</p>
        </div>
    </div>

    <div class="grid-3">
        <a class="card card--link feature-card" href="index.php">
            <span class="feature-icon" aria-hidden="true">1</span>
            <h3>Overview page</h3>
            <p>A long article with headings, lists, a table and a form. The saved language should be applied to all of it.</p>
            <span class="link-more">Open overview →</span>
        </a>
        <a class="card card--link feature-card" href="skip-translation.php">
            <span class="feature-icon" aria-hidden="true">2</span>
            <h3>Skip rules</h3>
            <p>Mixed content where some strings are protected. The saved language applies to the rest.</p>
            <span class="link-more">Open skip rules →</span>
        </a>
        <a class="card card--link feature-card" href="playground.php">
            <span class="feature-icon" aria-hidden="true">3</span>
            <h3>PHP playground</h3>
            <p>Admin UI stays in English because it is marked skip-translation, while explanatory copy can translate.</p>
            <span class="link-more">Open playground →</span>
        </a>
    </div>

    <div class="card bhashini-skip-translation" style="margin-top:20px">
        <span class="eyebrow">Advanced · current build</span>
        <h3>Force a language through the URL</h3>
        <p class="small muted">
            The current v3 utility also reads a <code class="inline">target_lang</code> query parameter
            and saves it as the preferred language. These links simulate arriving from a language-specific
            domain or a campaign URL. They are not described in the PDF, so treat them as
            version-dependent.
        </p>
        <div class="btn-row">
            <a class="btn btn--sm btn--ghost" href="persistence.php?target_lang=hi">?target_lang=hi</a>
            <a class="btn btn--sm btn--ghost" href="persistence.php?target_lang=ta">?target_lang=ta</a>
            <a class="btn btn--sm btn--ghost" href="persistence.php?target_lang=mr">?target_lang=mr</a>
            <a class="btn btn--sm btn--ghost" href="persistence.php?target_lang=en">?target_lang=en</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="grid-2">
        <div class="card">
            <span class="eyebrow">Expected behaviour</span>
            <h3>What persists and what does not</h3>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Item</th><th>Stored where?</th><th>Survives?</th></tr></thead>
                    <tbody>
                        <tr><td>Selected language</td><td><code>localStorage.preferredLanguage</code></td><td>Yes, same origin and browser</td></tr>
                        <tr><td>Translated DOM</td><td>Memory only</td><td>No, recreated per page</td></tr>
                        <tr><td>Translation cache</td><td><code>sessionStorage.translationCache</code></td><td>Same tab/session</td></tr>
                        <tr><td>Server settings</td><td><code>storage/settings.json</code></td><td>Yes, read by PHP</td></tr>
                    </tbody>
                </table>
            </div>
            <p class="small muted" style="margin-top:12px">
                The utility source uses the key <code class="inline">preferredLanguage</code> with no
                reverse-domain prefix. Keep that in mind if your own application also uses generic
                localStorage keys.
            </p>
        </div>

        <div class="card">
            <span class="eyebrow">Debugging tips</span>
            <h3>When the language does not come back</h3>
            <ul class="check-list">
                <li>Confirm the page actually loads the plugin. A page without the script cannot restore anything.</li>
                <li>Check that the URL is the same origin. <code class="inline">localhost</code> and <code class="inline">127.0.0.1</code> are different origins.</li>
                <li>Look for <code class="inline">preferredLanguage</code> in DevTools → Application → Local Storage.</li>
                <li>Private windows and cleared site data will start in English again.</li>
                <li>The <a href="diagnostics.php">diagnostics page</a> lists all localStorage keys and captures the plugin console.</li>
            </ul>
        </div>
    </div>
</section>

<?php render_footer($settings); ?>
