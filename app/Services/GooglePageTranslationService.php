<?php
declare(strict_types=1);

namespace App\Services;

use App\Support\{Cache,Logger};

final class GooglePageTranslationService
{
    private const SKIP_TAGS = ['script','style','code','pre','svg','textarea'];
    private const VOID_TAGS = ['area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr'];

    public function __construct(
        private readonly array $config,
        private readonly Cache $cache,
        private readonly Logger $logger
    ) {}

    public function translateHtml(string $html, string $targetLanguage): string
    {
        $targetLanguage = $this->targetCode($targetLanguage);
        if ($targetLanguage === 'en' || !$this->enabled()) return $html;

        $parts = preg_split('/(<[^>]+>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($parts)) return $html;

        $stack = [];
        $segments = [];

        foreach ($parts as $index => $part) {
            if ($part === '') continue;

            if ($part[0] === '<') {
                $this->updateStack($part, $stack);
                continue;
            }

            if ($this->skipCurrent($stack) || !preg_match('/\p{L}/u', html_entity_decode($part, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) {
                continue;
            }

            if (!preg_match('/^(\s*)(.*?)(\s*)$/us', $part, $matches)) continue;
            $core = $matches[2];
            if ($core === '' || !preg_match('/\p{L}/u', html_entity_decode($core, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) continue;

            $segments[] = [
                'index' => $index,
                'leading' => $matches[1],
                'text' => $core,
                'trailing' => $matches[3],
            ];
        }

        if (!$segments) return $html;

        $texts = array_column($segments, 'text');
        $translations = $this->translateSegments($texts, $targetLanguage);

        foreach ($segments as $i => $segment) {
            $translated = $translations[$i] ?? $segment['text'];
            $parts[$segment['index']] = $segment['leading'] . $translated . $segment['trailing'];
        }

        $result = implode('', $parts);
        $machineLang = $targetLanguage . '-x-mtfrom-en';
        $result = preg_replace('/<html\b([^>]*?)\blang=(["\'])[^"\']*\2([^>]*)>/i', '<html$1lang="' . $machineLang . '"$3>', $result, 1) ?? $result;

        return $result;
    }

    private function translateSegments(array $texts, string $target): array
    {
        $result = array_fill(0, count($texts), null);
        $missing = [];
        $missingIndexes = [];

        foreach ($texts as $i => $text) {
            $key = $this->cacheKey($target, $text);
            $cached = $this->cache->get($key);
            if (is_string($cached)) {
                $result[$i] = $cached;
                continue;
            }

            $missing[] = $text;
            $missingIndexes[] = $i;
        }

        foreach (array_chunk($missing, 100, true) as $chunkOffset => $chunk) {
            $chunkTexts = array_values($chunk);
            $translated = $this->request($chunkTexts, $target);
            if ($translated === null || count($translated) !== count($chunkTexts)) {
                foreach ($chunkTexts as $j => $source) {
                    $globalMissingIndex = ($chunkOffset * 100) + $j;
                    if (isset($missingIndexes[$globalMissingIndex])) {
                        $result[$missingIndexes[$globalMissingIndex]] = $source;
                    }
                }
                continue;
            }

            foreach ($translated as $j => $value) {
                $globalMissingIndex = ($chunkOffset * 100) + $j;
                if (!isset($missingIndexes[$globalMissingIndex], $chunkTexts[$j])) continue;
                $originalIndex = $missingIndexes[$globalMissingIndex];
                $result[$originalIndex] = $value;
                $this->cache->put($this->cacheKey($target, $chunkTexts[$j]), $value, max(300, (int)($this->config['cache_seconds'] ?? 604800)));
            }
        }

        return array_map(static fn($value, $source) => is_string($value) ? $value : $source, $result, $texts);
    }

    private function request(array $texts, string $target): ?array
    {
        if (!$texts) return [];
        $payload = json_encode([
            'q' => $texts,
            'target' => $target,
            'format' => 'html',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (!is_string($payload)) return null;

        $url = 'https://translation.googleapis.com/language/translate/v2';
        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json',
            'X-goog-api-key: ' . (string)$this->config['api_key'],
        ];
        $timeout = max(2, min(15, (int)($this->config['timeout_seconds'] ?? 6)));
        $body = null;
        $status = 0;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch !== false) {
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => $timeout,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_FOLLOWLOCATION => false,
                ]);
                $response = curl_exec($ch);
                $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                if (is_string($response)) $body = $response;
                curl_close($ch);
            }
        } elseif ((bool)ini_get('allow_url_fopen')) {
            $context = stream_context_create(['http' => [
                'method' => 'POST',
                'timeout' => $timeout,
                'ignore_errors' => true,
                'header' => implode("\r\n", $headers) . "\r\n",
                'content' => $payload,
            ]]);
            $response = @file_get_contents($url, false, $context);
            if (is_string($response)) $body = $response;
            if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $status = (int)$m[1];
        }

        if ($body === null || $status < 200 || $status >= 300) {
            $this->logger->error('Google page translation request failed.', ['status' => $status, 'target_language' => $target]);
            return null;
        }

        $decoded = json_decode($body, true);
        $items = $decoded['data']['translations'] ?? null;
        if (!is_array($items)) {
            $this->logger->error('Google page translation response was invalid.', ['target_language' => $target]);
            return null;
        }

        $translated = [];
        foreach ($items as $item) {
            $value = $item['translatedText'] ?? null;
            if (!is_string($value)) return null;
            $translated[] = $value;
        }

        return $translated;
    }

    private function enabled(): bool
    {
        return (bool)($this->config['enabled'] ?? false) && trim((string)($this->config['api_key'] ?? '')) !== '';
    }

    private function targetCode(string $language): string
    {
        return match (strtolower($language)) {
            'zh' => 'zh-TW',
            default => strtolower($language),
        };
    }

    private function cacheKey(string $target, string $text): string
    {
        return 'google-translate:' . $target . ':' . hash('sha256', $text);
    }

    private function updateStack(string $tag, array &$stack): void
    {
        if (preg_match('/^<\s*!|^<\s*\?/', $tag)) return;

        if (preg_match('/^<\s*\/\s*([a-z0-9:-]+)/i', $tag, $m)) {
            $closing = strtolower($m[1]);
            for ($i = count($stack) - 1; $i >= 0; $i--) {
                $entry = array_pop($stack);
                if (($entry['tag'] ?? '') === $closing) break;
            }
            return;
        }

        if (!preg_match('/^<\s*([a-z0-9:-]+)/i', $tag, $m)) return;
        $name = strtolower($m[1]);

        if (in_array($name, self::VOID_TAGS, true) || str_ends_with(trim($tag), '/>')) return;

        $parentSkip = $this->skipCurrent($stack);
        $selfSkip = in_array($name, self::SKIP_TAGS, true)
            || preg_match('/\btranslate\s*=\s*(["\'])no\1/i', $tag)
            || preg_match('/\bclass\s*=\s*(["\'])[^"\']*\bnotranslate\b[^"\']*\1/i', $tag);

        $stack[] = ['tag' => $name, 'skip' => $parentSkip || (bool)$selfSkip];
    }

    private function skipCurrent(array $stack): bool
    {
        if (!$stack) return false;
        return (bool)($stack[count($stack) - 1]['skip'] ?? false);
    }
}
