<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/layout.php';

$settings = bhashini_load_settings();

if (is_post()) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash('error', 'The form expired. Reload the page and try again.');
        redirect('settings.php');
    }

    $action = isset($_POST['action']) ? (string) $_POST['action'] : 'save';
    if ($action === 'reset') {
        bhashini_save_settings(bhashini_default_settings());
        flash('success', 'Settings restored to the defaults.');
        redirect('settings.php');
    }

    $candidate = bhashini_sanitize_settings($_POST);
    if (bhashini_save_settings($candidate)) {
        flash('success', 'Settings saved. Reloading any page will load the plugin with the new attributes.');
    } else {
        flash('error', 'Could not write storage/settings.json. Check that the storage folder is writable by Apache.');
    }
    redirect('settings.php');
}

$languages      = bhashini_languages();
$selectedLangs  = $settings['language_list'];
$placementValue = $settings['placement'];
$savedAt        = is_file(SETTINGS_FILE) ? date('d M Y, H:i', (int) filemtime(SETTINGS_FILE)) : null;

render_header($settings, ['title' => 'Settings · ' . APP_NAME, 'active' => 'settings.php']);
?>

<div class="bhashini-skip-translation">
    <div class="section-head" style="margin-top:0">
        <div>
            <span class="eyebrow">Control panel</span>
            <h1 style="font-size:clamp(1.7rem,3vw,2.3rem);margin-bottom:8px">Plugin settings</h1>
            <p>
                These options are rendered into the official plugin's script tag on every page.
                Save, then open any demo page to see the effect immediately. The page you are on is
                marked skip-translation so the controls stay in English.
            </p>
        </div>
        <div class="quick-links">
            <span class="badge badge--indigo">Saved <?= $savedAt ? e($savedAt) : 'defaults (not saved yet)' ?></span>
            <a class="chip" href="diagnostics.php">Diagnostics</a>
        </div>
    </div>

    <div class="grid-2" style="grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);align-items:start">
        <form id="settings-form" class="card form-grid" method="post" action="settings.php"
              data-plugin-src="<?= e(PLUGIN_SRC) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">

            <div class="field">
                <span class="field-label">Plugin state</span>
                <label class="check-row" for="plugin-enabled">
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" id="plugin-enabled" name="enabled" value="1" <?= !empty($settings['enabled']) ? 'checked' : '' ?>>
                    <span>Load the v3 utility on every page of this lab.</span>
                </label>
                <p class="help">Turn this off to compare the original page with the translated version.</p>
            </div>

            <div class="field">
                <label for="placement">Widget placement</label>
                <select id="placement" name="placement">
                    <?php foreach (['bottom-right' => 'Floating — bottom right', 'bottom-left' => 'Floating — bottom left', 'inline' => 'Inline — inside page content'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $placementValue === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="help">
                    The PDF calls this “customizable placement”. The plugin mounts into the container;
                    this lab moves the container, so the widget follows it.
                </p>
            </div>

            <div class="form-row">
                <div class="field">
                    <label for="icon-color">Language icon colour</label>
                    <input type="color" id="icon-color" name="icon_color" value="<?= e($settings['icon_color']) ?>">
                    <p class="help">Passed as <code class="inline">language-icon-color</code>. Light colours automatically get a dark pill behind the widget.</p>
                </div>
                <div class="field">
                    <label for="source-language">Page source language</label>
                    <select id="source-language" name="source_language">
                        <?php foreach ($languages as $code => $info): ?>
                            <option value="<?= e($code) ?>" <?= $settings['source_language'] === $code ? 'selected' : '' ?>>
                                <?= e($info['name']) ?> (<?= e($code) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="help">Passed as <code class="inline">page-source-language</code> only when it is not English. The PDF assumes English source content.</p>
                </div>
            </div>

            <div class="field">
                <span class="field-label">Languages offered in the dropdown</span>
                <div class="lang-grid">
                    <?php foreach ($languages as $code => $info): ?>
                        <label class="lang-option">
                            <input type="checkbox" name="languages[]" value="<?= e($code) ?>"
                                   data-name="<?= e($info['name']) ?>" data-native="<?= e($info['native']) ?>"
                                   <?= in_array($code, $selectedLangs, true) ? 'checked' : '' ?>
                                   <?= $code === 'en' ? 'disabled' : '' ?>>
                            <span><?= e($info['name']) ?></span>
                            <small lang="<?= e($code) ?>"><?= e($info['native']) ?></small>
                        </label>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="languages[]" value="en">
                <p class="help">
                    English is always included so visitors can switch back. The selection becomes
                    <code class="inline">translation-language-list</code>. The current v3 build exposes <?= count($languages) ?> Indian languages.
                </p>
            </div>

            <div class="form-row">
                <div class="field">
                    <label for="default-language">Default target language</label>
                    <select id="default-language" name="default_language">
                        <?php foreach ($selectedLangs as $code): ?>
                            <?php if (!isset($languages[$code])) { continue; } ?>
                            <option value="<?= e($code) ?>" <?= $settings['default_language'] === $code ? 'selected' : '' ?>>
                                <?= e($languages[$code]['name']) ?> (<?= e($code) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="help">Used only when “auto-translate on first visit” is on.</p>
                </div>
                <div class="field">
                    <span class="field-label">First-visit behaviour</span>
                    <label class="check-row" for="auto-translate">
                        <input type="hidden" name="auto_translate" value="0">
                        <input type="checkbox" id="auto-translate" name="auto_translate" value="1" <?= !empty($settings['auto_translate']) ? 'checked' : '' ?>>
                        <span>Auto-translate on load with the default language.</span>
                    </label>
                    <p class="help">
                        Adds <code class="inline">default-translated-language</code>. If a visitor already
                        chose another language, their saved preference still applies.
                    </p>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn" type="submit">Save settings</button>
                <a class="btn btn--ghost" href="index.php">Open a demo page</a>
            </div>
        </form>

        <div class="form-grid">
            <div class="card">
                <span class="eyebrow">Live preview</span>
                <h3>Generated script tag</h3>
                <p class="small muted">Updates as you change the form, before saving.</p>
                <div class="snippet" style="margin-top:12px">
                    <div class="snippet-head">
                        <span>Plugin script</span>
                        <button class="btn btn--sm btn--ghost" type="button" data-copy="#snippet-preview">Copy</button>
                    </div>
                    <pre id="snippet-preview" data-snippet-preview style="max-height:none;white-space:pre-wrap"></pre>
                </div>
            </div>

            <div class="card">
                <span class="eyebrow">Reference</span>
                <h3>Attributes used by this lab</h3>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Attribute</th><th>Effect</th></tr></thead>
                        <tbody>
                            <tr><td><code>language-icon-color</code></td><td>SVG icon fill colour (documented in the FAQ).</td></tr>
                            <tr><td><code>translation-language-list</code></td><td>Filters the dropdown. Not in the PDF body, but present in the current v3 build.</td></tr>
                            <tr><td><code>default-translated-language</code></td><td>Auto-translate on first visit.</td></tr>
                            <tr><td><code>page-source-language</code></td><td>Source language, default <code>en</code>.</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="small muted" style="margin-top:12px">
                    The PDF documents the script, the container, the skip class and the icon colour.
                    The other attributes are read by the current utility source; treat them as
                    version-dependent.
                </p>
            </div>

            <div class="callout callout--warn">
                <span class="callout__icon" aria-hidden="true">!</span>
                <div>
                    <p><strong>Auto-translate order</strong></p>
                    <p>
                        URL parameter, domain redirect, default language, then saved preference. The
                        plugin source defines this priority; check it after upgrading to a newer utility.
                    </p>
                </div>
            </div>

            <form method="post" action="settings.php" onsubmit="return confirm('Reset all plugin settings to defaults?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset">
                <button class="btn btn--danger btn--block" type="submit">Reset to defaults</button>
            </form>
        </div>
    </div>
</div>

<?php render_footer($settings); ?>
