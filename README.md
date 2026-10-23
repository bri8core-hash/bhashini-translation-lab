# Bhashini Translation Plugin — Trial Lab

A small PHP site for trying the **Bhashini Translation Plugin v3** features described in the
*Bhashini Translation Plugin v3 — Usage Documentation* (v3.0, 14-05-2025). The reference PDF is
not included in this repository.

It embeds the official widget, and adds a PHP-side playground so you can test the same
translation endpoint, inspect responses, and experiment with glossary overrides.

---

## Quick start

1. Start **Apache** in the XAMPP control panel.
2. Open: **<http://localhost/bhashini/>**
3. Click the round language button at the **bottom-right** and choose Hindi (or any enabled language).
4. Walk through the pages in the top navigation.

No database, Composer install or build step is required.

**Requirements:** PHP 7.4+ with `curl`, `json`, `mbstring` and `openssl` (all enabled in a normal
XAMPP install). Write access to `storage/` is needed to save settings and glossary pairs.

The bundled `certs/cacert.pem` (the Mozilla CA bundle distributed by curl.se) lets PHP's cURL
verify HTTPS on a stock XAMPP install, where `curl.cainfo` is often empty. Update it periodically,
or point `BHASHINI_CA_BUNDLE` at your own bundle.

---

## What you can try

| PDF feature | Where |
|---|---|
| Seamless page translation | `index.php` — sample article, table, form, links |
| Skip translation with `bhashini-skip-translation` | `skip-translation.php` — inline, block, form, table, whole-body opt-out |
| Customizable placement | `settings.php` — bottom-right, bottom-left, inline |
| Preferred language storage | `persistence.php` — cross-page test, `target_lang` URL demo |
| Glossary | `playground.php` — local override sandbox + CSV export for the real glossary request |
| React / Next.js mounting | `spa-demo.php` — injects the script after mount, exactly like `useEffect` |
| Icon colour (`language-icon-color`) | `settings.php` |
| Dynamic DOM / retranslation (FAQ #3) | `skip-translation.php` — insert / rewrite nodes |
| Diagnostics | `diagnostics.php` — PHP checks, API test, plugin console, localStorage |

---

## Files

```
.
├── index.php                 Overview + translatable sample article
├── skip-translation.php      Skip class examples, body opt-out, dynamic DOM
├── persistence.php           localStorage.preferredLanguage across pages
├── settings.php              Plugin configuration → script attributes
├── playground.php            PHP translation + local glossary editor
├── spa-demo.php              Mount-after-render (SPA) demo
├── diagnostics.php           Environment, API, console and storage checks
├── api/
│   └── translate.php         JSON endpoint used by the playground
├── assets/
│   ├── css/app.css
│   └── js/app.js             Console capture, copy buttons, storage panel, AJAX
├── includes/
│   ├── config.php            Settings, glossary, CSRF, plugin tag helpers
│   ├── bhashini_api.php      PHP client for /v2/translate-text
│   └── layout.php            Shared header/footer and plugin mount point
├── certs/
│   └── cacert.pem            CA bundle for server-side HTTPS requests
├── storage/                  settings.json, glossary.json (created on save)
├── .gitignore
└── README.md
```

---

## How the integration works

Every page renders:

```html
<div class="bhashini-plugin-container"></div>
<script src="https://translation-plugin.bhashini.co.in/v3/website_translation_utility.js"></script>
```

The container is emitted **before** the script, which is required because the utility mounts into
the first matching container synchronously. The saved settings become script attributes:

| Attribute | Set by the lab | Notes |
|---|---|---|
| `language-icon-color` | Icon colour setting | Documented in the PDF FAQ |
| `translation-language-list` | Language checkboxes | Present in the current v3 build (not in the PDF body) |
| `default-translated-language` | Auto-translate on first visit | Present in the current build |
| `page-source-language` | Source language setting | Defaults to `en` |

> The PDF documents `src`, the container class, `bhashini-skip-translation` and
> `language-icon-color`. The other attributes are read by the utility source of the current build;
> verify them after any plugin upgrade.

The plugin stores the visitor's language in:

```js
localStorage.preferredLanguage
```

It also uses `sessionStorage.translationCache` for in-session translation caching.

---

## Real vs local

**Real Bhashini services**

- The v3 utility loaded from `translation-plugin.bhashini.co.in`.
- The widget's translations, including skip translation and preferred language handling.
- The PHP playground's translations, via the same `/v2/translate-text` endpoint the widget uses.

**Local demo only**

- The glossary editor. The production glossary is uploaded to **Udyat** and mapped to your API key
  by your onboarding manager, then applied by Bhashini's backend. This lab demonstrates the effect
  by protecting matching English terms with `[[G0]]`-style placeholders before sending, then
  substituting your Indic term in the response. It does **not** change the widget's glossary.
- The settings UI, diagnostics and console capture.
- The generated mock page content.

---

## Notes about the live endpoint (observed 23 Oct 2026)

- The widget posts to `https://translation-plugin.bhashini.co.in/v2/translate-text`.
- The endpoint rejects requests without an `Origin` header
  (`400 Domain is not registered with Bhashini Translation Plugin`).
- An `Origin: http://localhost` request returned HTTP 200 with a Hindi translation, so the trial
  works on localhost **today**. Do not rely on this for production: the PDF's approval process
  exists for a reason, and the endpoint can change.
- A non-mapped origin such as `https://example.com` returns
  `401 Host is not mapped to Bhashini Translation Plugin`.

`includes/bhashini_api.php` forwards the lab's own origin by default. Override it if needed:

```bat
set BHASHINI_API_ORIGIN=https://your-approved-host.example
```

The widget itself sends the browser's real origin automatically.

---

## Production checklist

From the PDF:

1. Register / log in at <https://dashboard.bhashini.co.in>.
2. Open **View Parikshan Applications** → **Request Application** → **Bhashini Translation Plugin**.
3. Choose an onboarding manager (or `NA/Others`) and submit.
4. Enter the **hostname only** — e.g. `bhashini.gov.in`, not `https://www.bhashini.gov.in/home`.
5. Choose the environment: Test, UAT or Production.
6. Wait for manager approval, then CEO approval.
7. Copy the API key and use it in your production integration.
8. Send your glossary term list to your onboarding manager for upload.

Also:

- Request mapping for every hostname you actually serve.
- Load the plugin on **every** page where the preferred language should be restored.
- Add the Manipuri font fallback from the PDF if you support `mni`:

```css
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Meetei+Mayek:wght@100..900&display=swap');

body {
    font-family: 'Noto Sans', Arial, sans-serif, 'Noto Sans Meetei Mayek';
}
```

---

## Security

This is a **local trial**, not a production app.

- `api/translate.php` is an unauthenticated proxy. It applies a per-session limit of
  40 requests / 10 minutes and a 5,000-character cap, but that is not enough for the public internet.
- Add authentication, a server-side rate limit, request logging and CSRF/origin checks before
  deploying the playground.
- `storage/` is protected by `.htaccess` (`Require all denied`). If you use Nginx, add an equivalent
  location block. Do not put API keys in this folder in plain text without protection.
- `diagnostics.php` exposes paths and environment details. Remove or protect it in production.
- Settings are validated and HTML-escaped; the playground renders API output with `textContent`.

---

## Troubleshooting

| Problem | Check |
|---|---|
| Widget does not appear | Is the plugin enabled in Settings? Is there exactly one `.bhashini-plugin-container` rendered before the script? Open `diagnostics.php`. |
| Widget appears but nothing translates | Pick a different target language. Some strings may be skip-translated. Check the console panel for API errors. |
| 401 Host is not mapped | The origin is not approved in the dashboard. Map the hostname or test on localhost. |
| Language resets to English | `localStorage.preferredLanguage` was cleared, or you changed between `localhost` and `127.0.0.1` (different origins). |
| Constant retranslation on hover | FAQ #3: a script is replacing DOM text. Avoid re-rendering unchanged text; prefer attribute/style changes. |
| PHP API test fails | Verify cURL/OpenSSL in `diagnostics.php`, then check outbound network access to Bhashini. |
| Settings will not save | Make `storage/` writable by the Apache user. |

---

## Things to try next

- Add your own page and see the saved language apply automatically.
- Add `bhashini-skip-translation` to a navigation label and compare.
- Set the icon colour to white in Settings and watch the lab switch to a dark pill.
- Limit the dropdown to `en,hi,ta` and reload.
- Turn on auto-translate with Hindi as the default, then clear `preferredLanguage`.
- Add a glossary pair, e.g. `Digital India Bhashini Division = डिजिटल इंडिया भाषिणी प्रभाग`,
  and compare the playground output with the checkbox on and off.
- Export the glossary CSV and use it as the starting point for the list you send to your
  onboarding manager.

---

*Unofficial trial harness. The plugin, endpoint, documentation and dashboard are properties of the
Digital India Bhashini Division. For access or glossary support, contact your onboarding manager or
`ceo-dibd@digitalindia.gov.in`.*
