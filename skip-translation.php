<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/layout.php';

$settings = bhashini_load_settings();
$optout   = isset($_GET['optout']) && (string) $_GET['optout'] === '1';

render_header($settings, [
    'title'      => 'Skip rules · ' . APP_NAME,
    'active'     => 'skip-translation.php',
    'body_class' => $optout ? 'bhashini-skip-translation body-optout' : '',
]);
?>

<section class="section" style="margin-top:0">
    <div class="section-head">
        <div>
            <span class="eyebrow">PDF feature 3</span>
            <h1 style="font-size:clamp(1.7rem,3vw,2.4rem);margin-bottom:8px">Skip translation with a class</h1>
            <p>
                Add <code class="inline">bhashini-skip-translation</code> to any element and the plugin
                leaves it — and everything inside it — in the original language. This page mixes
                protected and unprotected content so you can compare the result side by side.
            </p>
        </div>
        <?php if ($optout): ?>
            <a class="btn btn--accent btn--sm" href="skip-translation.php">Disable page-wide opt-out</a>
        <?php else: ?>
            <a class="btn btn--ghost btn--sm" href="skip-translation.php?optout=1">Try page-wide opt-out</a>
        <?php endif; ?>
    </div>

    <?php if ($optout): ?>
        <div class="callout callout--warn" style="margin-bottom:20px">
            <span class="callout__icon" aria-hidden="true">!</span>
            <div>
                <p><strong>Page-wide opt-out is active.</strong></p>
                <p>
                    The <code class="inline">&lt;body&gt;</code> element now carries the skip class, so
                    the widget should translate nothing on this page. Remove the class with the button
                    above to restore normal behaviour.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid-2" style="align-items:start">
        <article class="card demo-article">
            <span class="eyebrow">Mixed content</span>
            <h2>Aadhaar, PAN and other terms that should stay put</h2>
            <p>
                This paragraph is fully translatable. It mentions the
                <span class="bhashini-skip-translation"><strong>Aadhaar</strong></span> number and the
                <span class="bhashini-skip-translation"><strong>PAN</strong></span> card, which are
                protected inline. The surrounding sentence will change language; those two words
                should not.
            </p>

            <h3>Protected block</h3>
            <div class="example-box bhashini-skip-translation" style="margin-bottom:1rem">
                <p style="margin:0">
                    <strong>Legal notice:</strong> This text is inside an element with the skip class.
                    It should remain exactly as written, in English, no matter which language is
                    selected. Copyright © 2026 Digital India Bhashini Division.
                </p>
            </div>
            <p>
                The paragraph you are reading now is not protected, so it should translate normally.
            </p>

            <h3>Code and identifiers</h3>
            <div class="snippet bhashini-skip-translation">
                <div class="snippet-head">
                    <span>Markup</span>
                    <button class="btn btn--sm btn--ghost" type="button" data-copy="#skip-snippet">Copy</button>
                </div>
                <pre id="skip-snippet">&lt;p class="bhashini-skip-translation"&gt;
  Skip Translating This
&lt;/p&gt;

&lt;p&gt;
  Translate this, but keep &lt;span class="bhashini-skip-translation"&gt;Bhashini&lt;/span&gt; as-is.
&lt;/p&gt;</pre>
            </div>

            <h3>Form fields</h3>
            <div class="input-demo">
                <div class="field">
                    <label for="skip-app-number">Application number</label>
                    <input id="skip-app-number" type="text" placeholder="Enter application number">
                </div>
                <div class="field bhashini-skip-translation">
                    <label for="skip-pan">PAN (protected field)</label>
                    <input id="skip-pan" type="text" placeholder="ABCDE1234F — keep this placeholder">
                </div>
                <p class="small muted" style="margin:0">
                    The second field is wrapped in a protected container, so neither its label nor its
                    placeholder should be translated.
                </p>
            </div>

            <h3>Protected technical table</h3>
            <div class="table-wrap bhashini-skip-translation">
                <table>
                    <thead><tr><th>Header</th><th>Value</th></tr></thead>
                    <tbody>
                        <tr><td>Content-Type</td><td><code>application/json</code></td></tr>
                        <tr><td>X-Utility-Version</td><td><code>v3</code></td></tr>
                        <tr><td>localStorage key</td><td><code>preferredLanguage</code></td></tr>
                    </tbody>
                </table>
            </div>
        </article>

        <div class="form-grid" style="align-content:start">
            <div class="card bhashini-skip-translation">
                <span class="eyebrow">Rules &amp; scope</span>
                <h3>How the skip class behaves</h3>
                <ul class="check-list">
                    <li>The class applies to the element and all of its descendants.</li>
                    <li>It works on blocks, inline spans, table wrappers and form fields.</li>
                    <li>Adding it to <code class="inline">&lt;body&gt;</code> opts the entire page out.</li>
                    <li>Text nodes inside the protected element are not sent to the API.</li>
                    <li>It does not remove the widget; visitors can still change language.</li>
                </ul>
                <div class="callout callout--info" style="margin-top:14px">
                    <span class="callout__icon" aria-hidden="true">i</span>
                    <div>
                        <p>
                            The PDF names this class <code class="inline">bhashini-skip-translation</code>.
                            The current utility source also treats <code class="inline">dont-translate</code>
                            as an internal marker, but use the documented class in your site.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card">
                <span class="eyebrow">FAQ #3 simulation</span>
                <h3>DOM replacement triggers retranslation</h3>
                <p class="small muted">
                    The PDF warns that replacing text nodes can make the plugin treat unchanged content
                    as new. This button rewrites the same string with <code class="inline">textContent</code>.
                    Select a language first, then watch the console and the content.
                </p>
                <p id="rerender-target" data-text="The weather forecast for tomorrow is sunny with a light breeze." style="padding:12px 14px;border:1px dashed var(--border-strong);border-radius:10px;background:#fbfaff">
                    The weather forecast for tomorrow is sunny with a light breeze.
                </p>
                <div class="btn-row">
                    <button class="btn btn--sm" type="button" id="simulate-rerender">Rewrite the text node</button>
                    <span class="badge badge--muted">Rewrites: <span id="rerender-count">0</span></span>
                </div>
            </div>

            <div class="card">
                <span class="eyebrow">Dynamic content</span>
                <h3>Content added after load</h3>
                <p class="small muted">
                    The plugin observes the DOM, so new cards are usually translated as well. Insert a
                    card after choosing a language to test your own mutation patterns.
                </p>
                <button class="btn btn--sm" type="button" id="add-dynamic-card">Insert a new card</button>
                <div id="dynamic-zone" class="form-grid" style="margin-top:14px"></div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <div>
            <span class="eyebrow">Practical guidance</span>
            <h2>Where to use the skip class</h2>
        </div>
    </div>
    <div class="example-grid">
        <div class="card">
            <h3>Good candidates</h3>
            <ul class="check-list">
                <li>Brand and product names that must stay in Latin script.</li>
                <li>Codes, IDs, API keys and file paths.</li>
                <li>Legal text approved in one language only.</li>
                <li>Measurement units and technical standards.</li>
                <li>Third-party disclaimers that must match a contract.</li>
            </ul>
        </div>
        <div class="card">
            <h3>Be careful with</h3>
            <ul class="check-list">
                <li>Protecting an entire page and then wondering why the widget does nothing.</li>
                <li>Using generated class names that do not match the documented one.</li>
                <li>Hiding the widget but leaving the preferred language set.</li>
                <li>Protecting navigation labels that visitors need in their own language.</li>
            </ul>
        </div>
    </div>
</section>

<?php render_footer($settings); ?>
