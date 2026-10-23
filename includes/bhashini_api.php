<?php
/**
 * Thin PHP client for the same endpoint the Bhashini v3 utility uses.
 *
 * The public endpoint expects an Origin header. A browser sends it
 * automatically; PHP has to set it explicitly (see bhashini_api_origin()).
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Translate one string.
 *
 * @param string $text      UTF-8 source text
 * @param string $source    Source language code, e.g. "en"
 * @param string $target    Target language code, e.g. "hi"
 * @param array  $glossary  Optional local glossary pairs [{source,target}]
 * @return array Structured result (never throws)
 */
function bhashini_api_translate(string $text, string $source, string $target, array $glossary = []): array
{
    $started = microtime(true);
    $result  = [
        'ok'              => false,
        'http_status'     => 0,
        'translated'      => '',
        'raw_translation' => '',
        'request_text'    => $text,
        'source'          => $source,
        'target'          => $target,
        'origin'          => bhashini_api_origin(),
        'glossary_used'   => [],
        'elapsed_ms'      => 0,
        'error'           => null,
        'raw_response'    => '',
    ];

    if (!function_exists('curl_init')) {
        $result['error'] = 'PHP cURL extension is not enabled.';
        $result['elapsed_ms'] = (int) round((microtime(true) - $started) * 1000);
        return $result;
    }

    $text = trim($text);
    if ($text === '') {
        $result['error'] = 'Nothing to translate.';
        $result['elapsed_ms'] = (int) round((microtime(true) - $started) * 1000);
        return $result;
    }

    // Protect glossary terms with placeholders the engine tends to preserve.
    $prepared = $text;
    $protected = [];
    foreach (array_values($glossary) as $index => $pair) {
        $term = isset($pair['source']) ? trim((string) $pair['source']) : '';
        if ($term === '') {
            continue;
        }
        $pattern = '/' . preg_quote($term, '/') . '/iu';
        $token   = '[[G' . $index . ']]';
        $count   = 0;
        $prepared = (string) preg_replace($pattern, $token, $prepared, -1, $count);
        if ($count > 0) {
            $protected[$index] = $pair;
        }
    }
    $result['request_text'] = $prepared;

    $payload = [
        'targetLanguage' => $target,
        'textData'       => [$prepared],
        'sourceLanguage' => $source,
    ];

    $ch = curl_init(API_URL);
    $options = [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Origin: ' . bhashini_api_origin(),
            'X-Utility-Version: v3',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ];
    $caBundle = bhashini_ca_bundle();
    if ($caBundle !== null) {
        $options[CURLOPT_CAINFO] = $caBundle;
    }
    curl_setopt_array($ch, $options);

    $body      = curl_exec($ch);
    $status    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $result['http_status'] = $status;

    if ($body === false) {
        $message = $curlError !== '' ? $curlError : 'unknown cURL failure';
        if (stripos($message, 'certificate') !== false) {
            $message .= ' — check the CA bundle in certs/cacert.pem or set BHASHINI_CA_BUNDLE.';
        }
        $result['error'] = 'Network error: ' . $message;
        $result['elapsed_ms'] = (int) round((microtime(true) - $started) * 1000);
        return $result;
    }

    $result['raw_response'] = (string) $body;
    $data = json_decode((string) $body, true);

    if ($status < 200 || $status >= 300) {
        $message = 'HTTP ' . $status;
        if (is_array($data)) {
            if (isset($data['message']) && is_string($data['message'])) {
                $message .= ': ' . $data['message'];
            } elseif (isset($data['error']) && is_string($data['error'])) {
                $message .= ': ' . $data['error'];
            } elseif (isset($data['detail'])) {
                $message .= ': ' . (is_string($data['detail']) ? $data['detail'] : json_encode($data['detail'], JSON_UNESCAPED_UNICODE));
            }
        }
        $result['error'] = $message;
        $result['elapsed_ms'] = (int) round((microtime(true) - $started) * 1000);
        return $result;
    }

    $translated = null;
    if (is_array($data)) {
        if (isset($data[0]['target']) && is_string($data[0]['target'])) {
            $translated = $data[0]['target'];
        } elseif (isset($data['target']) && is_string($data['target'])) {
            $translated = $data['target'];
        } elseif (isset($data['translatedText']) && is_string($data['translatedText'])) {
            $translated = $data['translatedText'];
        }
    }

    if ($translated === null) {
        $result['error'] = 'Unexpected response shape from the translation API.';
        $result['elapsed_ms'] = (int) round((microtime(true) - $started) * 1000);
        return $result;
    }

    $result['raw_translation'] = $translated;
    $result['translated']      = $translated;

    // Restore glossary overrides. The engine may add spaces around brackets.
    if ($protected) {
        $used = [];
        $result['translated'] = (string) preg_replace_callback(
            '/\[\[\s*G?\s*(\d+)\s*\]\]/i',
            static function (array $match) use ($protected, &$used): string {
                $index = (int) $match[1];
                if (!isset($protected[$index])) {
                    return $match[0];
                }
                $used[$protected[$index]['source']] = $protected[$index]['target'];
                return $protected[$index]['target'];
            },
            $result['translated']
        );
        $result['glossary_used'] = $used;
    }

    $result['ok'] = true;
    $result['elapsed_ms'] = (int) round((microtime(true) - $started) * 1000);
    return $result;
}

/** Short label for a language code, used in UI chips. */
function bhashini_lang_name(string $code): string
{
    $languages = bhashini_languages();
    if (!isset($languages[$code])) {
        return $code;
    }
    return $languages[$code]['name'] . ' (' . $languages[$code]['native'] . ')';
}
