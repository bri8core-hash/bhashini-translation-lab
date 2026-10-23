<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/layout.php';

$settings  = bhashini_load_settings();
$languages = bhashini_languages();
$langCount = count($settings['language_list']);

render_header($settings, ['title' => 'Overview · ' . APP_NAME, 'active' => 'index.php']);
?>

<section class="hero">
    <div class="hero-grid">
        <div>
            <span class="hero-badge">
                <span class="status-dot" aria-hidden="true"></span>
                Live trial · Bhashini Translation Plugin v3
            </span>
            <h1>Translate a real PHP site, without guessing how the plugin behaves.</h1>
            <p>
                This is a working harness for the drop-in Bhashini translation widget documented in
                <em>Bhashini Translation Plugin v3 — Usage Documentation</em>. Pick a language from
                the floating widget, inspect what gets translated, test the skip class, and follow a
                visitor's language choice across pages.
            </p>
            <div class="hero-actions">
                <a class="btn btn--accent" href="#try-it">Try the widget on this page</a>
                <a class="btn btn--ghost" href="playground.php">Open the PHP playground</a>
            </div>
        </div>
        <aside class="hero-card" aria-label="Current plugin configuration">
            <h2>Current configuration</h2>
            <dl>
                <div>
                    <dt>Widget</dt>
                    <dd><?= !empty($settings['enabled']) ? 'Enabled' : 'Disabled' ?></dd>
                </div>
                <div>
                    <dt>Placement</dt>
                    <dd><?= e(bhashini_placement_label($settings['placement'])) ?></dd>
                </div>
                <div>
                    <dt>Languages offered</dt>
                    <dd><?= (int) $langCount ?> of <?= count($languages) ?></dd>
                </div>
                <div>
                    <dt>Icon colour</dt>
                    <dd><span class="mono"><?= e($settings['icon_color']) ?></span></dd>
                </div>
                <div>
                    <dt>Auto-translate</dt>
                    <dd><?= !empty($settings['auto_translate']) ? 'On load (' . e($settings['default_language']) . ')' : 'Off' ?></dd>
                </div>
            </dl>
        </aside>
    </div>
</section>

<div class="stat-grid" role="list">
    <div class="stat" role="listitem"><span>Interface</span><strong>PHP 7.4+ · no framework</strong></div>
    <div class="stat" role="listitem"><span>Plugin</span><strong>Official v3 utility</strong></div>
    <div class="stat" role="listitem"><span>Translation API</span><strong><?= (!empty($settings['enabled'])) ? 'Live endpoint' : 'Widget off' ?></strong></div>
    <div class="stat" role="listitem"><span>Glossary demo</span><strong>Local override sandbox</strong></div>
</div>

<section class="section" id="features">
    <div class="section-head">
        <div>
            <span class="eyebrow">Guided tour</span>
            <h2>Every documented feature has a test page</h2>
            <p>Use these pages in order if you are validating the plugin for a site, or jump straight to the part you need.</p>
        </div>
    </div>

    <div class="grid-3">
        <a class="card card--link feature-card" href="#try-it">
            <span class="feature-icon" aria-hidden="true">अ</span>
            <h3>Page translation</h3>
            <p>Headings, body copy, lists, tables, quotes, link text and form placeholders are translated in place by the widget.</p>
            <span class="link-more">Translate this page →</span>
        </a>
        <a class="card card--link feature-card" href="skip-translation.php">
            <span class="feature-icon" aria-hidden="true">⊘</span>
            <h3>Skip translation</h3>
            <p>Protect brand names, code, legal strings or whole sections with <code class="inline">.bhashini-skip-translation</code>.</p>
            <span class="link-more">Test the skip rules →</span>
        </a>
        <a class="card card--link feature-card" href="persistence.php">
            <span class="feature-icon" aria-hidden="true">↺</span>
            <h3>Preferred language storage</h3>
            <p>The plugin remembers the last Indic language in localStorage and re-applies it on the next page.</p>
            <span class="link-more">Follow a visitor across pages →</span>
        </a>
        <a class="card card--link feature-card" href="settings.php">
            <span class="feature-icon" aria-hidden="true">⚙</span>
            <h3>Placement &amp; icon colour</h3>
            <p>Move the widget between bottom-right, bottom-left and inline, limit the language list and restyle the icon.</p>
            <span class="link-more">Change the configuration →</span>
        </a>
        <a class="card card--link feature-card" href="playground.php">
            <span class="feature-icon" aria-hidden="true">&lt;/&gt;</span>
            <h3>PHP translation playground</h3>
            <p>Call the same translation endpoint server-side, see raw responses, and try a glossary override sandbox.</p>
            <span class="link-more">Translate from PHP →</span>
        </a>
        <a class="card card--link feature-card" href="spa-demo.php">
            <span class="feature-icon" aria-hidden="true">◫</span>
            <h3>SPA / dynamic mounting</h3>
            <p>Reproduce the React or Next.js <code class="inline">useEffect</code> pattern by injecting the script after mount.</p>
            <span class="link-more">Open the SPA demo →</span>
        </a>
    </div>
</section>

<section class="section" id="try-it">
    <div class="section-head">
        <div>
            <span class="eyebrow">Sample page content</span>
            <h2>Try it on this page</h2>
            <p>
                Click the Bhashini language button
                <?= $settings['placement'] === 'inline' ? 'in the inline card below the article' : ($settings['placement'] === 'bottom-left' ? 'at the bottom left' : 'at the bottom right') ?>
                and choose Hindi, Tamil, Bengali or any enabled language. Everything inside this article is translatable unless it is marked otherwise.
            </p>
        </div>
        <div class="quick-links">
            <a class="chip" href="settings.php">Limit language list</a>
            <a class="chip" href="diagnostics.php">Check widget status</a>
        </div>
    </div>

    <div class="grid-2" style="align-items:start">
        <article class="card demo-article">
            <span class="eyebrow">Digital India · sample article</span>
            <h2>One plugin, twenty-three scheduled languages</h2>
            <p class="lead">
                The Bhashini Translation Plugin lets a website offer its content in Bhartiya languages
                without rebuilding the site. The widget translates the page in place, remembers the
                visitor's choice, and can leave selected strings untouched.
            </p>
            <p>
                This paragraph is ordinary page content. When you pick a language, the plugin walks the
                DOM, sends text to Bhashini, and replaces the original text nodes. It also translates
                input placeholders, button labels and link text so that the experience stays consistent.
            </p>

            <h3>What the plugin translates</h3>
            <ul>
                <li>Paragraphs, headings, list items and table cells.</li>
                <li>Button labels, link text, image alt text and form placeholders.</li>
                <li>Content added to the page after the first translation, such as cards or filters.</li>
            </ul>

            <blockquote>
                “Language is the bridge between a citizen and a service. Translation should be a
                property of the website, not a separate version of it.”
            </blockquote>

            <h3>Services mentioned in this sample</h3>
            <div class="table-wrap">
                <table class="prose-table">
                    <thead>
                        <tr><th>Service</th><th>Purpose</th><th>Typical user</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Content translation</td><td>Translate a whole page in place</td><td>Government portals</td></tr>
                        <tr><td>Preferred language</td><td>Reuse the last selected language</td><td>Returning visitors</td></tr>
                        <tr><td>Glossary</td><td>Override domain-specific terms</td><td>Schemes and departments</td></tr>
                        <tr><td>Selective skip</td><td>Keep brands or code unchanged</td><td>Product teams</td></tr>
                    </tbody>
                </table>
            </div>

            <h3>Inline skip example</h3>
            <p>
                The product name <span class="bhashini-skip-translation"><strong>Bhashini</strong></span>
                stays in Latin script because it has the skip class, while this sentence around it is
                translated. The brand name <span class="bhashini-skip-translation"><strong>DigiLocker</strong></span>
                is protected the same way.
            </p>

            <h3>Form elements are translated too</h3>
            <div class="input-demo">
                <div class="field">
                    <label for="demo-name">Full name</label>
                    <input id="demo-name" type="text" placeholder="Enter your full name">
                </div>
                <div class="field">
                    <label for="demo-state">State or union territory</label>
                    <select id="demo-state">
                        <option>Select a state</option>
                        <option>Karnataka</option>
                        <option>Tamil Nadu</option>
                        <option>West Bengal</option>
                        <option>Maharashtra</option>
                    </select>
                </div>
                <div class="btn-row">
                    <button class="btn btn--sm" type="button">Submit application</button>
                    <button class="btn btn--sm btn--ghost" type="button">Save draft</button>
                </div>
            </div>

            <p class="small muted" style="margin-top:1.4rem">
                Some strings above are deliberately marked with <code class="inline">bhashini-skip-translation</code>.
                Edit <code class="inline">index.php</code> and add or remove the class to compare the behaviour.
            </p>
        </article>

        <div class="form-grid" style="align-content:start">
            <div class="callout callout--info">
                <span class="callout__icon" aria-hidden="true">i</span>
                <div>
                    <p><strong>Where is the widget?</strong></p>
                    <p>
                        <?php if (empty($settings['enabled'])): ?>
                            The plugin is currently disabled in <a href="settings.php">Settings</a>. Enable it to try translation.
                        <?php elseif ($settings['placement'] === 'inline'): ?>
                            It is mounted in the <em>Inline placement</em> card at the bottom of this page, because the current placement is set to inline.
                        <?php else: ?>
                            Look at the <strong><?= $settings['placement'] === 'bottom-left' ? 'bottom-left' : 'bottom-right' ?></strong>
                            corner of the viewport. The round language button is the widget.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="callout callout--warn">
                <span class="callout__icon" aria-hidden="true">!</span>
                <div>
                    <p><strong>Production needs an approved hostname.</strong></p>
                    <p>
                        The PDF describes requesting the Translation Plugin application in the Bhashini
                        dashboard, mapping the hostname, and copying the API key after approval. This lab
                        is a trial; the public endpoint is not a substitute for that process.
                    </p>
                </div>
            </div>

            <div class="card">
                <span class="eyebrow">Implementation</span>
                <h3>Two lines, one container</h3>
                <p class="small muted">The container must exist before the script runs. The plugin mounts into the first matching element.</p>
                <div class="snippet bhashini-skip-translation">
                    <div class="snippet-head">
                        <span>index.php</span>
                        <button class="btn btn--sm btn--ghost" type="button" data-copy="#container-snippet">Copy</button>
                    </div>
                    <pre id="container-snippet" style="max-height:none">&lt;div class="bhashini-plugin-container"&gt;&lt;/div&gt;
&lt;script src="https://translation-plugin.bhashini.co.in/v3/website_translation_utility.js"&gt;&lt;/script&gt;</pre>
                </div>
            </div>

            <div class="card">
                <span class="eyebrow">Coverage</span>
                <h3>Mapped to the PDF</h3>
                <ul class="check-list">
                    <li><strong>Translation</strong> — this page</li>
                    <li><strong>Skip class</strong> — <a href="skip-translation.php">skip rules</a></li>
                    <li><strong>Placement</strong> — <a href="settings.php">settings</a></li>
                    <li><strong>Preferred language</strong> — <a href="persistence.php">persistence</a></li>
                    <li><strong>Glossary</strong> — <a href="playground.php">playground</a> (local demo)</li>
                    <li><strong>SPA integration</strong> — <a href="spa-demo.php">dynamic mount</a></li>
                    <li><strong>Icon colour</strong> — <a href="settings.php">settings</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section" id="how-it-works">
    <div class="section-head">
        <div>
            <span class="eyebrow">How this lab is wired</span>
            <h2>A small PHP site around the official widget</h2>
            <p>
                There is no framework and no build step. PHP renders the pages and the script attributes;
                the widget and the translation endpoint remain Bhashini's.
            </p>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <span class="eyebrow">Integration path</span>
            <h3>What happens on page load</h3>
            <ol class="steps">
                <li>
                    <strong>PHP renders settings</strong>
                    <p>Saved options become script attributes such as icon colour, language filter and default language.</p>
                </li>
                <li>
                    <strong>The container is placed first</strong>
                    <p>The fixed or inline mount point is emitted before the plugin script so the widget can attach.</p>
                </li>
                <li>
                    <strong>The v3 utility runs</strong>
                    <p>It injects its stylesheet, builds the language button and reads <code class="inline">localStorage.preferredLanguage</code>.</p>
                </li>
                <li>
                    <strong>Translation happens in place</strong>
                    <p>Text nodes are batched and sent to Bhashini. English or the saved Indic language is applied to the live DOM.</p>
                </li>
            </ol>
        </div>

        <div class="card">
            <span class="eyebrow">Current server-rendered tag</span>
            <h3>What this lab is loading</h3>
            <div class="snippet bhashini-skip-translation">
                <div class="snippet-head">
                    <span>Generated from storage/settings.json</span>
                    <button class="btn btn--sm btn--ghost" type="button" data-copy="#live-snippet">Copy</button>
                </div>
                <pre id="live-snippet" style="max-height:none"><?= e(bhashini_plugin_snippet($settings)) ?></pre>
            </div>
            <p class="small muted" style="margin-top:14px">
                Change these attributes on the <a href="settings.php">Settings</a> page. The preview there updates live before you save.
            </p>
            <div class="callout callout--success" style="margin-top:14px">
                <span class="callout__icon" aria-hidden="true">✓</span>
                <p>
                    The app chrome (header, footer, admin controls) is marked skip-translation so the
                    controls stay readable while you test. The sample article is intentionally left
                    translatable.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="callout callout--danger">
        <span class="callout__icon" aria-hidden="true">!</span>
        <div>
            <p><strong>Before you expose this on a network</strong></p>
            <p>
                The PHP playground is a translation proxy with no authentication. It is fine on
                <code class="inline">localhost</code> for a trial, but do not publish it as-is. Add
                authentication, tighten the rate limit, and use an approved hostname and API key for
                production. See <code class="inline">README.md</code> for the checklist.
            </p>
        </div>
    </div>
</section>

<?php render_footer($settings); ?>
