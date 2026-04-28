<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleTranslateService
{
    /**
     * @var int
     */
    private const CHUNK_LIMIT = 4000;

    /**
     * Translate one plain-text string.
     */
    public function translateText(string $text, ?string $source = null, ?string $target = null): array
    {
        $text = trim($text);

        if ($text === '') {
            return [
                'ok' => true,
                'text' => '',
                'provider' => null,
            ];
        }

        $source = $source ?: $this->getSourceLocale();
        $target = $target ?: $this->getTargetLocale();

        $chunks = $this->chunkText($text);
        $translated = [];
        $provider = null;

        foreach ($chunks as $chunk) {
            $response = $this->translateChunk($chunk, $source, $target);

            if (! $response['ok']) {
                return $response;
            }

            $provider = $provider ?: ($response['provider'] ?? null);
            $translated[] = trim((string) $response['text']);
        }

        return [
            'ok' => true,
            'text' => trim(implode("\n\n", array_filter($translated, static function ($chunk) {
                return $chunk !== '';
            }))),
            'provider' => $provider,
        ];
    }

    /**
     * @return string
     */
    public function getSourceLocale(): string
    {
        return trim((string) config('services.google_translate.source', 'hr')) ?: 'hr';
    }

    /**
     * @return string
     */
    public function getTargetLocale(): string
    {
        return trim((string) config('services.google_translate.target', 'en')) ?: 'en';
    }

    /**
     * @return array
     */
    private function translateChunk(string $text, string $source, string $target): array
    {
        if ($this->getApiKey() !== '') {
            return $this->translateWithOfficialApi($text, $source, $target);
        }

        if ($this->usePublicEndpoint()) {
            return $this->translateWithPublicEndpoint($text, $source, $target);
        }

        return [
            'ok' => false,
            'error' => 'Google Translate nije konfiguriran.',
        ];
    }

    /**
     * @return array
     */
    private function translateWithOfficialApi(string $text, string $source, string $target): array
    {
        try {
            $response = Http::timeout(20)
                ->asForm()
                ->post('https://translation.googleapis.com/language/translate/v2', [
                    'key' => $this->getApiKey(),
                    'q' => $text,
                    'source' => $source,
                    'target' => $target,
                    'format' => 'text',
                ]);

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'error' => (string) ($response->json('error.message') ?: $response->body() ?: 'Google Translate API greška.'),
                ];
            }

            return [
                'ok' => true,
                'text' => (string) $response->json('data.translations.0.translatedText', ''),
                'provider' => 'google_official',
            ];
        } catch (\Throwable $e) {
            Log::warning('Google Translate official API failed', [
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array
     */
    private function translateWithPublicEndpoint(string $text, string $source, string $target): array
    {
        try {
            $response = Http::timeout(20)->get('https://translate.googleapis.com/translate_a/single', [
                'client' => 'gtx',
                'sl' => $source,
                'tl' => $target,
                'dt' => 't',
                'q' => $text,
            ]);

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'error' => (string) ($response->body() ?: 'Google public translate endpoint greška.'),
                ];
            }

            $payload = json_decode((string) $response->body(), true);
            $parts = [];

            foreach ((array) ($payload[0] ?? []) as $item) {
                if (isset($item[0]) && $item[0] !== null) {
                    $parts[] = $item[0];
                }
            }

            return [
                'ok' => true,
                'text' => trim(implode('', $parts)),
                'provider' => 'google_public',
            ];
        } catch (\Throwable $e) {
            Log::warning('Google Translate public endpoint failed', [
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array
     */
    private function chunkText(string $text): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($text));
        $paragraphs = preg_split("/\n{2,}/", $normalized) ?: [];
        $chunks = [];
        $buffer = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                continue;
            }

            if (mb_strlen($paragraph) > self::CHUNK_LIMIT) {
                if ($buffer !== '') {
                    $chunks[] = $buffer;
                    $buffer = '';
                }

                foreach ($this->sliceLongText($paragraph) as $piece) {
                    $chunks[] = $piece;
                }

                continue;
            }

            $candidate = $buffer === '' ? $paragraph : $buffer . "\n\n" . $paragraph;

            if (mb_strlen($candidate) > self::CHUNK_LIMIT) {
                $chunks[] = $buffer;
                $buffer = $paragraph;
                continue;
            }

            $buffer = $candidate;
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return $chunks ?: [$normalized];
    }

    /**
     * @return array
     */
    private function sliceLongText(string $text): array
    {
        $pieces = [];
        $offset = 0;
        $length = mb_strlen($text);

        while ($offset < $length) {
            $pieces[] = mb_substr($text, $offset, self::CHUNK_LIMIT);
            $offset += self::CHUNK_LIMIT;
        }

        return $pieces;
    }

    /**
     * @return string
     */
    private function getApiKey(): string
    {
        return trim((string) config('services.google_translate.api_key', ''));
    }

    /**
     * @return bool
     */
    private function usePublicEndpoint(): bool
    {
        return (bool) config('services.google_translate.use_public_endpoint', true);
    }
}
