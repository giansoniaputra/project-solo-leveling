<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VoiceController extends Controller
{
    private const VOICE = 'nova';

    private const SPEED = 0.85;

    /**
     * Synthesizes "The System"'s spoken replies via OpenAI TTS. Identical
     * phrases are cached on disk (keyed by text + voice + speed, so changing
     * either setting doesn't serve stale audio) so repeat lines (like the
     * "you up?" easter egg) don't re-hit the API every time.
     */
    public function speak(Request $request)
    {
        $data = $request->validate([
            'text' => 'required|string|max:500',
        ]);

        $path = 'voice-cache/'.md5($data['text'].'|'.self::VOICE.'|'.self::SPEED).'.mp3';

        if (! Storage::disk('local')->exists($path)) {
            $client = \OpenAI::client(config('services.openai.key'));

            try {
                $audio = $client->audio()->speech([
                    'model' => 'tts-1',
                    'voice' => self::VOICE,
                    'input' => $data['text'],
                    'response_format' => 'mp3',
                    'speed' => self::SPEED,
                ]);
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Failed to synthesize speech: '.$e->getMessage()], 502);
            }

            Storage::disk('local')->put($path, $audio);
        }

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => 'audio/mpeg',
        ]);
    }
}
