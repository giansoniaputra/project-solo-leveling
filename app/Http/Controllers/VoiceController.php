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

    /**
     * Has "The System" summarize (not read out) the Hunter's current stats
     * and give one piece of advice, for the "what is my status" voice
     * command. Weight/height/age are deliberately left out.
     */
    public function statusSummary(Request $request)
    {
        $user = $request->user();

        $prompt = <<<PROMPT
        You are "The System" from the anime Solo Leveling, briefing a Hunter on their current stats.

        Hunter data:
        - Level: {$user->level}
        - EXP: {$user->exp}
        - STR: {$user->str}, AGI: {$user->agi}, PER: {$user->per}, VIT: {$user->vit}, INT: {$user->intelligence}

        Task: This will be read aloud by text-to-speech, so in 2-3 short spoken sentences, summarize the Hunter's
        overall standing in plain language — do NOT just list the raw numbers one by one, describe their
        strengths/weaknesses instead (e.g. "your strength is solid but your intelligence is lagging behind").
        Then give exactly ONE concise, actionable piece of advice on which stat to focus on next.
        Speak in the blunt, encouraging tone of "The System". Keep the whole reply under 70 words.
        Reply with ONLY the spoken text, no labels, no markdown, no numbered list.
        PROMPT;

        $client = \OpenAI::client(config('services.openai.key'));

        try {
            $response = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are "The System" from Solo Leveling. Reply with plain spoken text only, nothing else.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to summarize status: '.$e->getMessage()], 502);
        }

        $text = trim($response->choices[0]->message->content ?? '');

        if ($text === '') {
            return response()->json(['message' => 'The System had nothing to say.'], 502);
        }

        return response()->json(['text' => $text]);
    }
}
