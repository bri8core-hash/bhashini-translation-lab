<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/layout.php';

$settings = bhashini_load_settings();
render_header($settings, [
    'title'       => 'SPA demo · ' . APP_NAME,
    'active'      => 'spa-demo.php',
    'load_plugin' => false,
]);
?>

<section class="section" style="margin-top:0">
    <div class="section-head">
        <div>
            <span class="eyebrow">SPA / framework integration</span>
            <h1 style="font-size:clamp(1.7rem,3vw,2.4rem);margin-bottom:8px">Mount the plugin after the component exists</h1>
            <p>
                This page deliberately does <strong>not</strong> load the utility on page load. It
                reproduces the React / Next.js pattern from the PDF: create a container, then append
                the script inside <code class="inline">useEffect</code> after the component mounts.
                Use the button to inject the real v3 utility now.
            </p>
        </div>
        <span class="badge badge--amber">Plugin not loaded yet</span>
    </div>

    <div class="grid-2" style="grid-template-columns:minmax(0,1.1fr) minmax(0,.9fr);align-items:start">
        <div class="panel">
            <div class="panel__head">
                <h2>Live mounting demo</h2>
                <span class="spa-state" data-spa-state data-state="idle">Not mounted</span>
            </div>
            <div class="panel__body">
                <p class="small muted">
                    In a single-page app the plugin can run before the target element exists. The PDF
                    solves this by injecting the script after mount. The button below does exactly that.
                </p>

                <div class="spa-stage">
                    <div class="btn-row" style="margin-bottom:16px">
                        <button class="btn" type="button" id="mount-plugin"
                                data-src="<?= e(PLUGIN_SRC) ?>"
                                <?= empty($settings['enabled']) ? 'disabled' : '' ?>>
                            Mount component &amp; inject plugin
                        </button>
                        <button class="btn btn--ghost" type="button" id="spa-update-content">Simulate a route update</button>
                    </div>

                    <?php if (empty($settings['enabled'])): ?>
                        <div class="callout callout--warn" style="margin-bottom:16px">
                            <span class="callout__icon" aria-hidden="true">!</span>
                            <p>The plugin is disabled in <a href="settings.php">Settings</a>, so mounting is disabled here too.</p>
                        </div>
                    <?php endif; ?>

                    <div id="spa-content" data-update-count="0">
                        <span class="eyebrow">Mounted component content</span>
                        <h2>Citizen services, rendered by the app shell</h2>
                        <p>
                            This block represents a React component. It is already in the DOM when you
                            press the mount button, which is the condition the plugin needs. Choose a
                            language from the widget after it appears.
                        </p>
                        <p class="muted small">
                            Inline skip still works here:
                            <span class="bhashini-skip-translation">Bhashini</span> stays untranslated.
                        </p>
                    </div>

                    <div style="margin-top:18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                        <div id="spa-container" class="bhashini-plugin-container" hidden
                             data-icon-color="<?= e($settings['icon_color']) ?>"
                             data-languages="<?= e(implode(',', $settings['language_list'])) ?>"
                             data-default-language="<?= !empty($settings['auto_translate']) ? e($settings['default_language']) : '' ?>"
                             data-source-language="<?= e($settings['source_language']) ?>"></div>
                        <span class="mount-marker" aria-hidden="true">mount<br>point</span>
                        <span class="small muted">The container stays empty until the plugin attaches.</span>
                    </div>
                </div>

                <div class="callout callout--info" style="margin-top:16px">
                    <span class="callout__icon" aria-hidden="true">i</span>
                    <div>
                        <p><strong>Reload to reset.</strong></p>
                        <p>
                            The script is only appended once. A full reload returns this page to the
                            “not loaded” state and clears the container. The saved language preference
                            in localStorage is intentionally kept.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-grid">
            <div class="card bhashini-skip-translation">
                <span class="eyebrow">React / Next.js</span>
                <h3>The pattern from the PDF</h3>
                <div class="snippet">
                    <div class="snippet-head">
                        <span>Footer component</span>
                        <button class="btn btn--sm btn--ghost" type="button" data-copy="#spa-snippet">Copy</button>
                    </div>
                    <pre id="spa-snippet" style="max-height:none">useEffect(() =&gt; {
  const script = document.createElement("script");
  script.src = "https://translation-plugin.bhashini.co.in/v3/website_translation_utility.js";
  script.async = true;
  document.body.appendChild(script);
}, []);</pre>
                </div>
                <p class="small muted" style="margin-top:12px">
                    For Next.js, the PDF recommends this <code class="inline">useEffect</code> or
                    <code class="inline">next/script</code> with
                    <code class="inline">strategy="afterInteractive"</code> to avoid hydration issues.
                </p>
            </div>

            <div class="card bhashini-skip-translation">
                <span class="eyebrow">Checklist</span>
                <h3>Before injecting</h3>
                <ul class="check-list">
                    <li>Render <code class="inline">&lt;div class="bhashini-plugin-container"&gt;&lt;/div&gt;</code> first.</li>
                    <li>Inject the script exactly once, not on every route change.</li>
                    <li>Keep the container mounted; do not unmount it on navigation.</li>
                    <li>Do not re-append the script after every render.</li>
                    <li>Use a stable footer or root layout component.</li>
                    <li>Test with client-side navigation, not just hard reloads.</li>
                </ul>
            </div>

            <div class="callout callout--warn bhashini-skip-translation">
                <span class="callout__icon" aria-hidden="true">!</span>
                <div>
                    <p><strong>The retranslation trap</strong></p>
                    <p>
                        SPA routers often replace DOM subtrees. The plugin treats new text nodes as new
                        content and may retranslate. FAQ #3 recommends reducing unnecessary DOM reloads;
                        prefer updating attributes and styles over re-rendering large text blocks.
                    </p>
                </div>
            </div>

            <div class="card bhashini-skip-translation">
                <span class="eyebrow">Current settings</span>
                <h3>What will be injected</h3>
                <dl class="diag-list">
                    <div class="diag-row"><dt>Language filter</dt><dd><code><?= e(implode(',', $settings['language_list'])) ?></code></dd></div>
                    <div class="diag-row"><dt>Icon colour</dt><dd><code><?= e($settings['icon_color']) ?></code></dd></div>
                    <div class="diag-row"><dt>Auto-translate</dt><dd><?= !empty($settings['auto_translate']) ? e($settings['default_language']) : 'off' ?></dd></div>
                    <div class="diag-row"><dt>Source language</dt><dd><code><?= e($settings['source_language']) ?></code></dd></div>
                </dl>
                <p class="small muted" style="margin-top:12px">Change these on the <a href="settings.php">Settings</a> page.</p>
            </div>
        </div>
    </div>
</section>

<?php render_footer($settings, ['load_plugin' => false]); ?>
