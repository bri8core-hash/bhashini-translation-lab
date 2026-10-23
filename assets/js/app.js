/**
 * Bhashini Plugin Lab — client helpers.
 * Loaded before the official plugin so console diagnostics are captured.
 */
(function () {
    'use strict';

    var logs = window.__bhashiniLogs = window.__bhashiniLogs || [];

    function stringify(value) {
        if (typeof value === 'string') return value;
        if (value instanceof Error) return value.stack || value.message;
        try {
            return JSON.stringify(value);
        } catch (err) {
            return String(value);
        }
    }

    function record(level, args) {
        var parts = [];
        for (var i = 0; i < args.length; i++) {
            parts.push(stringify(args[i]));
        }
        var message = parts.join(' ');
        if (message.length > 2000) message = message.slice(0, 2000) + '…';
        logs.push({
            time: new Date(),
            level: level,
            message: message
        });
        if (logs.length > 300) logs.shift();
    }

    ['log', 'info', 'warn', 'error', 'debug'].forEach(function (level) {
        var original = console[level];
        console[level] = function () {
            record(level, arguments);
            if (original) original.apply(console, arguments);
        };
    });

    window.addEventListener('error', function (event) {
        record('error', ['Uncaught: ' + (event.message || 'unknown') + ' @ ' + (event.filename || '?') + ':' + (event.lineno || 0)]);
    });
    window.addEventListener('unhandledrejection', function (event) {
        record('error', ['Unhandled promise rejection: ' + stringify(event.reason)]);
    });

    function $(selector, root) {
        return (root || document).querySelector(selector);
    }

    function $$(selector, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(selector));
    }

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            var area = document.createElement('textarea');
            area.value = text;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.left = '-9999px';
            document.body.appendChild(area);
            area.select();
            try {
                document.execCommand('copy') ? resolve() : reject(new Error('Copy failed'));
            } catch (err) {
                reject(err);
            } finally {
                document.body.removeChild(area);
            }
        });
    }

    function flashButton(button, text) {
        var original = button.getAttribute('data-original-label') || button.textContent;
        button.setAttribute('data-original-label', original);
        button.textContent = text;
        button.classList.add('is-copied');
        window.setTimeout(function () {
            button.textContent = original;
            button.classList.remove('is-copied');
        }, 1600);
    }

    function preferredLanguage() {
        try {
            return localStorage.getItem('preferredLanguage') || '';
        } catch (err) {
            return '';
        }
    }

    function refreshStorageBindings() {
        $$('[data-localstorage]').forEach(function (el) {
            var key = el.getAttribute('data-localstorage');
            var value = '';
            try {
                value = localStorage.getItem(key) || '';
            } catch (err) {
                value = '';
            }
            el.textContent = value || el.getAttribute('data-empty') || '—';
        });
    }

    function setPluginStatus(state, text) {
        $$('[data-plugin-status]').forEach(function (el) {
            el.setAttribute('data-state', state);
            el.classList.toggle('is-ready', state === 'ready');
            el.classList.toggle('is-warn', state === 'warn');
            el.classList.toggle('is-off', state === 'off');
        });
        $$('[data-plugin-status-text]').forEach(function (el) {
            el.textContent = text;
        });
    }

    // Expose the few helpers other pages/tests may need.
    window.BhashiniLab = {
        logs: logs,
        setPluginStatus: setPluginStatus,
        refreshStorage: refreshStorageBindings,
        preferredLanguage: preferredLanguage
    };

    onReady(function () {
        refreshStorageBindings();

        /* ---------------------------------------------------------------
         * Copy-to-clipboard buttons
         * ------------------------------------------------------------ */
        $$('[data-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                var selector = button.getAttribute('data-copy');
                var target = selector ? $(selector) : null;
                var text = target ? target.textContent : (button.getAttribute('data-copy-text') || '');
                copyText(text.trim()).then(function () {
                    flashButton(button, 'Copied');
                }).catch(function () {
                    flashButton(button, 'Copy failed');
                });
            });
        });

        /* ---------------------------------------------------------------
         * Flash messages
         * ------------------------------------------------------------ */
        $$('.flash-close').forEach(function (button) {
            button.addEventListener('click', function () {
                var flash = button.closest('.flash');
                if (flash) flash.remove();
            });
        });

        /* ---------------------------------------------------------------
         * Preferred-language controls
         * ------------------------------------------------------------ */
        $$('[data-reset-language]').forEach(function (button) {
            button.addEventListener('click', function () {
                try {
                    localStorage.removeItem('preferredLanguage');
                } catch (err) {
                    /* ignore */
                }
                window.location.reload();
            });
        });

        $$('[data-set-language]').forEach(function (button) {
            button.addEventListener('click', function () {
                var code = button.getAttribute('data-set-language');
                try {
                    if (code === 'en') {
                        localStorage.removeItem('preferredLanguage');
                    } else {
                        localStorage.setItem('preferredLanguage', code);
                    }
                } catch (err) {
                    /* ignore */
                }
                window.location.reload();
            });
        });

        /* ---------------------------------------------------------------
         * Is the official widget mounted?
         * ------------------------------------------------------------ */
        if (document.body.getAttribute('data-expect-plugin') === '1') {
            var attempts = 0;
            var timer = window.setInterval(function () {
                if ($('#bhashini-translation')) {
                    setPluginStatus('ready', 'Widget mounted');
                    window.clearInterval(timer);
                } else if (attempts++ >= 20) {
                    setPluginStatus('warn', 'Widget not found');
                    window.clearInterval(timer);
                }
            }, 500);
        }

        /* ---------------------------------------------------------------
         * Settings: live script-tag preview
         * ------------------------------------------------------------ */
        var settingsForm = $('#settings-form');
        if (settingsForm) {
            var preview = $('[data-snippet-preview]');
            var buildSnippet = function () {
                if (!preview) return;
                var enabled = $('#plugin-enabled');
                if (enabled && !enabled.checked) {
                    preview.textContent = '<!-- Bhashini plugin disabled in Settings -->';
                    return;
                }
                var src = settingsForm.getAttribute('data-plugin-src') || '';
                var attrs = [];
                var color = $('#icon-color');
                if (color && color.value) attrs.push(['language-icon-color', color.value]);
                var langs = $$('input[name="languages[]"]:checked').map(function (input) {
                    return input.value;
                });
                if (langs.length) attrs.push(['translation-language-list', langs.join(',')]);
                var source = $('#source-language');
                var defaultLang = $('#default-language');
                var auto = $('#auto-translate');
                if (auto && auto.checked && defaultLang && source && defaultLang.value !== source.value) {
                    attrs.push(['default-translated-language', defaultLang.value]);
                }
                if (source && source.value && source.value !== 'en') {
                    attrs.push(['page-source-language', source.value]);
                }
                var html = '<script src="' + src + '"';
                attrs.forEach(function (pair) {
                    html += ' ' + pair[0] + '="' + String(pair[1]).replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '"';
                });
                html += '></' + 'script>';
                preview.textContent = html;
            };
            var defaultSelect = $('#default-language');
            var sourceSelect = $('#source-language');
            var autoToggle = $('#auto-translate');
            var syncDefaultOptions = function () {
                if (!defaultSelect) return;
                var current = defaultSelect.value;
                var checked = $$('input[name="languages[]"]:checked').map(function (input) {
                    return input.value;
                });
                if (checked.indexOf('en') === -1) checked.unshift('en');
                defaultSelect.innerHTML = '';
                checked.forEach(function (code) {
                    var input = document.querySelector('input[name="languages[]"][value="' + code + '"]');
                    if (!input) return;
                    var option = document.createElement('option');
                    option.value = code;
                    var name = input.getAttribute('data-name') || code;
                    var native = input.getAttribute('data-native') || '';
                    option.textContent = name + (native && native !== name ? ' (' + native + ')' : '');
                    defaultSelect.appendChild(option);
                });
                defaultSelect.value = checked.indexOf(current) !== -1 ? current : (checked[0] || 'en');
                if (autoToggle && sourceSelect) {
                    autoToggle.disabled = defaultSelect.value === sourceSelect.value;
                }
            };

            $$('input, select', settingsForm).forEach(function (input) {
                input.addEventListener('change', function () {
                    if (input.name === 'languages[]' || input.id === 'source-language') {
                        syncDefaultOptions();
                    }
                    buildSnippet();
                });
                input.addEventListener('input', buildSnippet);
            });
            syncDefaultOptions();
            buildSnippet();
        }

        /* ---------------------------------------------------------------
         * Playground: AJAX translation
         * ------------------------------------------------------------ */
        var translateForm = $('#translate-form');
        if (translateForm) {
            var output = $('#translation-output');
            var outputWrap = $('#translation-result');
            var meta = $('#translation-meta');
            var rawBox = $('#translation-raw');
            var errorBox = $('#translation-error');
            var badges = $('#glossary-badges');
            var submit = $('#translate-submit');
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');

            translateForm.addEventListener('submit', function (event) {
                event.preventDefault();
                var text = $('#source-text').value.trim();
                var source = $('#source-language').value;
                var target = $('#target-language').value;
                var useGlossary = $('#use-glossary').checked;

                errorBox.hidden = true;
                errorBox.textContent = '';
                if (!text) {
                    errorBox.hidden = false;
                    errorBox.textContent = 'Enter some text to translate.';
                    return;
                }

                submit.disabled = true;
                submit.classList.add('is-busy');
                submit.setAttribute('data-label', submit.textContent);
                submit.textContent = 'Translating…';

                fetch('api/translate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfMeta ? csrfMeta.getAttribute('content') : ''
                    },
                    body: JSON.stringify({
                        text: text,
                        source: source,
                        target: target,
                        use_glossary: useGlossary
                    })
                }).then(function (response) {
                    return response.json().then(function (data) {
                        return { status: response.status, data: data };
                    });
                }).then(function (result) {
                    var data = result.data || {};
                    if (!data.ok) {
                        errorBox.hidden = false;
                        errorBox.textContent = data.error || ('Request failed with HTTP ' + result.status);
                    }
                    output.textContent = data.translated || data.raw_translation || '';
                    output.setAttribute('lang', target);
                    output.setAttribute('dir', data.rtl ? 'rtl' : 'ltr');
                    outputWrap.hidden = false;

                    var metaParts = [];
                    metaParts.push('HTTP ' + (data.http_status || result.status));
                    if (typeof data.elapsed_ms === 'number') metaParts.push(data.elapsed_ms + ' ms');
                    if (data.origin) metaParts.push('Origin ' + data.origin);
                    meta.textContent = metaParts.join(' · ');

                    badges.innerHTML = '';
                    var used = data.glossary_used || {};
                    Object.keys(used).forEach(function (sourceTerm) {
                        var chip = document.createElement('span');
                        chip.className = 'badge badge--green';
                        chip.textContent = sourceTerm + ' → ' + used[sourceTerm];
                        badges.appendChild(chip);
                    });
                    if (data.glossary_requested && Object.keys(used).length === 0) {
                        var none = document.createElement('span');
                        none.className = 'badge badge--muted';
                        none.textContent = 'No glossary term matched this text';
                        badges.appendChild(none);
                    }
                    if (data.request_text && data.request_text !== text) {
                        var protectedChip = document.createElement('span');
                        protectedChip.className = 'badge badge--indigo';
                        protectedChip.textContent = 'Terms protected before sending';
                        badges.appendChild(protectedChip);
                    }

                    rawBox.textContent = JSON.stringify(data, null, 2);
                }).catch(function (err) {
                    errorBox.hidden = false;
                    errorBox.textContent = 'Network error: ' + err.message;
                }).finally(function () {
                    submit.disabled = false;
                    submit.classList.remove('is-busy');
                    submit.textContent = submit.getAttribute('data-label') || 'Translate';
                });
            });

            var sampleButton = $('#load-sample');
            if (sampleButton) {
                sampleButton.addEventListener('click', function () {
                    $('#source-text').value = sampleButton.getAttribute('data-sample') || '';
                    $('#source-text').focus();
                });
            }
        }

        /* ---------------------------------------------------------------
         * SPA demo: inject the script the React/useEffect way
         * ------------------------------------------------------------ */
        var mountButton = $('#mount-plugin');
        if (mountButton) {
            var spaContainer = $('#spa-container');
            var spaState = $('[data-spa-state]');

            var setSpaState = function (state, text) {
                if (!spaState) return;
                spaState.setAttribute('data-state', state);
                spaState.textContent = text;
            };

            mountButton.addEventListener('click', function () {
                if ($('#bhashini-translation')) {
                    setSpaState('ready', 'Widget already mounted.');
                    return;
                }
                if (!spaContainer) return;
                spaContainer.hidden = false;
                spaContainer.innerHTML = '';

                var script = document.createElement('script');
                script.src = mountButton.getAttribute('data-src');
                script.async = true;
                var icon = spaContainer.getAttribute('data-icon-color');
                var langs = spaContainer.getAttribute('data-languages');
                var defaultLang = spaContainer.getAttribute('data-default-language');
                var sourceLang = spaContainer.getAttribute('data-source-language');
                if (icon) script.setAttribute('language-icon-color', icon);
                if (langs) script.setAttribute('translation-language-list', langs);
                if (defaultLang) script.setAttribute('default-translated-language', defaultLang);
                if (sourceLang && sourceLang !== 'en') script.setAttribute('page-source-language', sourceLang);
                document.body.appendChild(script);

                mountButton.disabled = true;
                setSpaState('waiting', 'Script injected — waiting for the widget to mount…');

                var tries = 0;
                var poll = window.setInterval(function () {
                    if ($('#bhashini-translation')) {
                        window.clearInterval(poll);
                        setSpaState('ready', 'Widget mounted after mount. Reload to reset the demo.');
                        setPluginStatus('ready', 'Widget mounted');
                    } else if (tries++ > 20) {
                        window.clearInterval(poll);
                        setSpaState('warn', 'Script loaded but no widget appeared. Check the console log.');
                        setPluginStatus('warn', 'Widget not found');
                    }
                }, 500);
            });

            var spaContentButton = $('#spa-update-content');
            if (spaContentButton) {
                spaContentButton.addEventListener('click', function () {
                    var target = $('#spa-content');
                    if (!target) return;
                    var count = parseInt(target.getAttribute('data-update-count') || '0', 10) + 1;
                    target.setAttribute('data-update-count', String(count));
                    target.innerHTML =
                        '<h3>Freshly rendered content (update #' + count + ')</h3>' +
                        '<p>This paragraph was inserted after the component mounted. The plugin watches the DOM and should translate it too.</p>' +
                        '<p><span class="bhashini-skip-translation">Bhashini</span> brand names can still be protected with the skip class.</p>';
                });
            }
        }

        /* ---------------------------------------------------------------
         * Skip page: dynamic DOM + retranslation demo
         * ------------------------------------------------------------ */
        var addCard = $('#add-dynamic-card');
        if (addCard) {
            addCard.addEventListener('click', function () {
                var zone = $('#dynamic-zone');
                if (!zone) return;
                var card = document.createElement('article');
                card.className = 'card card--soft dynamic-card';
                card.innerHTML =
                    '<span class="eyebrow">Inserted by JavaScript</span>' +
                    '<h3>New content appears after the page has loaded</h3>' +
                    '<p>This card did not exist when the plugin initialised. Select an Indic language and click this button to see whether the plugin picks up the new DOM.</p>' +
                    '<p class="muted">Protected inline term: <span class="bhashini-skip-translation">Bhashini</span></p>';
                zone.appendChild(card);
            });
        }

        var rerender = $('#simulate-rerender');
        if (rerender) {
            rerender.addEventListener('click', function () {
                var target = $('#rerender-target');
                var counter = $('#rerender-count');
                if (!target) return;
                var current = target.getAttribute('data-text') || target.textContent;
                target.setAttribute('data-text', current);
                // Replacing textContent is exactly the kind of DOM reload FAQ #3 warns about.
                target.textContent = current;
                if (counter) {
                    var n = parseInt(counter.textContent || '0', 10) + 1;
                    counter.textContent = String(n);
                }
            });
        }

        /* ---------------------------------------------------------------
         * Diagnostics: console + localStorage panels
         * ------------------------------------------------------------ */
        var consoleBox = $('#console-log');
        if (consoleBox) {
            var onlyBhashini = $('#only-bhashini');
            var renderConsole = function () {
                var filter = onlyBhashini && onlyBhashini.checked;
                var rows = logs.filter(function (entry) {
                    return !filter || entry.message.toLowerCase().indexOf('bhashini') !== -1;
                }).slice(-120);
                consoleBox.textContent = rows.map(function (entry) {
                    var time = entry.time.toLocaleTimeString();
                    return '[' + time + '] ' + entry.level.toUpperCase() + ' ' + entry.message;
                }).join('\n');
                consoleBox.scrollTop = consoleBox.scrollHeight;
            };
            renderConsole();
            window.setInterval(renderConsole, 1200);
            if (onlyBhashini) onlyBhashini.addEventListener('change', renderConsole);

            var clearLogs = $('#clear-console');
            if (clearLogs) {
                clearLogs.addEventListener('click', function () {
                    logs.length = 0;
                    renderConsole();
                });
            }
        }

        var storageBody = $('#storage-rows');
        if (storageBody) {
            var renderStorage = function () {
                storageBody.innerHTML = '';
                var rows = [];
                try {
                    for (var i = 0; i < localStorage.length; i++) {
                        var key = localStorage.key(i);
                        rows.push([key, localStorage.getItem(key)]);
                    }
                } catch (err) {
                    rows.push(['(error)', err.message]);
                }
                rows.sort(function (a, b) {
                    return a[0].localeCompare(b[0]);
                });
                if (!rows.length) {
                    var empty = document.createElement('tr');
                    empty.innerHTML = '<td colspan="2" class="muted">No localStorage keys yet. Translate the page first.</td>';
                    storageBody.appendChild(empty);
                    return;
                }
                rows.forEach(function (row) {
                    var tr = document.createElement('tr');
                    var th = document.createElement('td');
                    th.innerHTML = '<code></code>';
                    th.querySelector('code').textContent = row[0];
                    var td = document.createElement('td');
                    td.textContent = String(row[1] || '');
                    tr.appendChild(th);
                    tr.appendChild(td);
                    storageBody.appendChild(tr);
                });
            };
            renderStorage();
            window.setInterval(renderStorage, 2000);
        }

        // Keep the footer language chip fresh (e.g. after the plugin writes it).
        window.setInterval(refreshStorageBindings, 2000);
    });
})();
