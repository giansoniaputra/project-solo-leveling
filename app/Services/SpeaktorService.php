<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Text-to-speech through Speaktor's (Transkriptor) developer API. The call is
 * synchronous: it returns a short-lived pre-signed URL to an MP3, which is
 * downloaded right away so callers get the raw audio bytes.
 */
class SpeaktorService
{
    private const ENDPOINT = 'https://api.tor.app/developer/text_to_speech';

    // Pro voices (beta) accept at most 1000 characters per request, so long
    // text is split into chunks below that and the MP3s are concatenated.
    private const MAX_CHUNK = 900;

    /**
     * Anything that changes the produced audio, so cached files are never
     * served stale after the voice/language/speed/emotion settings change.
     */
    public function fingerprint(): string
    {
        return implode('|', [
            config('services.speaktor.voice'),
            config('services.speaktor.language'),
            config('services.speaktor.speed'),
            config('services.speaktor.emotion'),
        ]);
    }

    /**
     * @return string raw MP3 bytes
     *
     * @throws RuntimeException
     */
    public function synthesize(string $text): string
    {
        $audio = '';

        foreach ($this->chunk($text) as $chunk) {
            $audio .= $this->synthesizeChunk($chunk);
        }

        return $audio;
    }

    private function synthesizeChunk(string $text): string
    {
        $payload = array_filter([
            'text' => $text,
            'language' => config('services.speaktor.language'),
            'voice_name' => config('services.speaktor.voice'),
            'speed_rate' => config('services.speaktor.speed') !== null && config('services.speaktor.speed') !== ''
                ? (float) config('services.speaktor.speed')
                : null,
            // Ignored by Lite voices, applied by Pro voices.
            'emotion' => config('services.speaktor.emotion') ?: null,
        ], fn ($value) => $value !== null && $value !== '');

        $response = Http::withToken((string) config('services.speaktor.key'))
            ->acceptJson()
            ->timeout(60)
            ->post(self::ENDPOINT, $payload);

        $audioUrl = $response->json('audioUrl');

        if (! $response->successful() || ! $audioUrl) {
            throw new RuntimeException('Speaktor error (HTTP '.$response->status().'): '.($response->json('message') ?: 'no audio returned'));
        }

        $audio = Http::timeout(60)->get($audioUrl);

        // A real MP3 never starts with '{' or '<' — guards against caching an
        // error document as if it were audio.
        if (! $audio->successful() || $audio->body() === '' || in_array($audio->body()[0], ['{', '<'], true)) {
            throw new RuntimeException('Speaktor audio download failed (HTTP '.$audio->status().').');
        }

        return $audio->body();
    }

    /**
     * Splits on sentence boundaries into pieces of at most MAX_CHUNK chars.
     *
     * @return list<string>
     */
    private function chunk(string $text): array
    {
        $text = trim($text);

        if (mb_strlen($text) <= self::MAX_CHUNK) {
            return [$text];
        }

        $chunks = [];
        $current = '';

        foreach (preg_split('/(?<=[.!?])\s+/u', $text) as $sentence) {
            // A single sentence longer than the limit gets hard-wrapped.
            while (mb_strlen($sentence) > self::MAX_CHUNK) {
                if ($current !== '') {
                    $chunks[] = $current;
                    $current = '';
                }
                $chunks[] = mb_substr($sentence, 0, self::MAX_CHUNK);
                $sentence = mb_substr($sentence, self::MAX_CHUNK);
            }

            if ($current !== '' && mb_strlen($current) + 1 + mb_strlen($sentence) > self::MAX_CHUNK) {
                $chunks[] = $current;
                $current = $sentence;
            } else {
                $current = $current === '' ? $sentence : $current.' '.$sentence;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }
}
