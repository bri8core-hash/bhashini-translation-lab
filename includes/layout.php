<?php
/**
 * Shared page chrome. Every page calls render_header() and render_footer().
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function bhashini_nav_items(): array
{
    return [
        'index.php'           => 'Overview',
        'skip-translation.php'=> 'Skip rules',
        'persistence.php'     => 'Persistence',
        'playground.php'      => 'PHP playground',
        'spa-demo.php'        => 'SPA demo',
        'diagnostics.php'     => 'Diagnostics',
        'settings.php'        => 'Settings',
    ];
}

function render_header(array $settings, array $options = []): void
{
    $title     = $options['title'] ?? APP_NAME;
    $active    = $options['active'] ?? '';
    $loadPlugin = array_key_exists('load_plugin', $options) ? (bool) $options['load_plugin'] : true;
    $expectPlugin = $loadPlugin && !empty($settings['enabled']);
    $bodyClass = $options['body_class'] ?? '';
    $nav       = bhashini_nav_items();
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&family=Noto+Sans+Meetei+Mayek:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%231D0A69'/%3E%3Ctext x='32' y='43' font-size='32' text-anchor='middle' fill='%23fff'%3E%E0%A4%AD%3C/text%3E%3C/svg%3E">
</head>
<body class="<?= e($bodyClass) ?>"
      data-page="<?= e($active) ?>"
      data-expect-plugin="<?= $expectPlugin ? '1' : '0' ?>">
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header bhashini-skip-translation" id="top">
    <div class="wrap header-inner">
        <a class="brand" href="index.php" aria-label="Bhashini Translation Plugin Lab home">
            <span class="brand-mark" aria-hidden="true">भ</span>
            <span class="brand-text">
                <strong>Bhashini Plugin Lab</strong>
                <small>v3 translation utility · trial build</small>
            </span>
        </a>
        <nav class="site-nav" aria-label="Main navigation">
            <?php foreach ($nav as $href => $label): ?>
                <a href="<?= e($href) ?>"<?= $active === $href ? ' class="is-active" aria-current="page"' : '' ?>>
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="header-status">
            <span class="status-dot<?= empty($settings['enabled']) ? ' is-off' : '' ?>" aria-hidden="true"></span>
            <span><?= empty($settings['enabled']) ? 'Plugin off' : 'Plugin on' ?></span>
        </div>
    </div>
</header>

<main id="main" class="site-main">
    <div class="wrap">
        <?php foreach (take_flashes() as $flash): ?>
            <div class="flash flash--<?= e($flash['type']) ?>" role="status">
                <span><?= e($flash['message']) ?></span>
                <button type="button" class="flash-close" aria-label="Dismiss">&times;</button>
            </div>
        <?php endforeach; ?>
    <?php
}

function render_footer(array $settings, array $options = []): void
{
    $loadPlugin   = array_key_exists('load_plugin', $options) ? (bool) $options['load_plugin'] : true;
    $expectPlugin = $loadPlugin && !empty($settings['enabled']);
    $placement    = $settings['placement'] ?? 'bottom-right';
    $inline       = $placement === 'inline';
    $lightIcon    = bhashini_icon_is_light((string) ($settings['icon_color'] ?? '#1D0A69'));
    $dockClass    = 'plugin-dock';
    if ($placement === 'bottom-left') {
        $dockClass .= ' plugin-dock--bl';
    } elseif ($inline) {
        $dockClass .= ' plugin-dock--inline';
    } else {
        $dockClass .= ' plugin-dock--br';
    }
    if ($lightIcon) {
        $dockClass .= ' plugin-dock--dark';
    }

    if ($inline && $expectPlugin) {
        ?>
        <section class="plugin-inline-card bhashini-skip-translation" aria-label="Inline plugin mount point">
            <div>
                <span class="eyebrow">Inline placement</span>
                <p class="plugin-inline-title">The widget is mounted inside this card, not fixed to a corner.</p>
            </div>
            <div class="<?= e($dockClass) ?>">
                <div class="bhashini-plugin-container"></div>
            </div>
        </section>
        <?php
    }
    ?>
    </div><!-- /.wrap -->
</main>

<footer class="site-footer bhashini-skip-translation">
    <div class="wrap footer-inner">
        <div class="footer-brand">
            <span class="brand-mark brand-mark--sm" aria-hidden="true">भ</span>
            <div>
                <strong>Bhashini Translation Plugin Lab</strong>
                <p>An unofficial trial harness for the Digital India Bhashini v3 website translation utility.</p>
            </div>
        </div>
        <div class="footer-meta">
            <span class="storage-chip">
                <span class="storage-label">localStorage.preferredLanguage</span>
                <code data-localstorage="preferredLanguage" data-empty="(not set)">—</code>
            </span>
            <?php if ($expectPlugin): ?>
                <span class="widget-chip" data-plugin-status data-state="waiting">
                    <span class="status-dot" aria-hidden="true"></span>
                    <span data-plugin-status-text>Checking widget…</span>
                </span>
            <?php elseif (!$loadPlugin): ?>
                <span class="widget-chip" data-state="off">
                    <span class="status-dot is-off" aria-hidden="true"></span>
                    <span>Plugin not loaded on this page</span>
                </span>
            <?php else: ?>
                <span class="widget-chip" data-state="off">
                    <span class="status-dot is-off" aria-hidden="true"></span>
                    <span>Disabled in Settings</span>
                </span>
            <?php endif; ?>
            <a class="footer-link" href="diagnostics.php">Run diagnostics</a>
        </div>
    </div>
    <div class="wrap footer-legal">
        <p>
            Translation is provided by
            <a href="https://bhashini.gov.in" target="_blank" rel="noopener noreferrer">Bhashini</a>.
            Production use requires hostname mapping and an API key from the
            <a href="https://dashboard.bhashini.co.in/user/login" target="_blank" rel="noopener noreferrer">Bhashini dashboard</a>.
            Do not use this trial app as-is on a public server.
        </p>
    </div>
</footer>

<script src="assets/js/app.js"></script>
<?php if ($expectPlugin): ?>
    <?php if (!$inline): ?>
        <div class="<?= e($dockClass) ?>">
            <div class="bhashini-plugin-container"></div>
        </div>
    <?php endif; ?>
    <?= bhashini_plugin_script_tag($settings) ?>
<?php endif; ?>
</body>
</html>
    <?php
}
