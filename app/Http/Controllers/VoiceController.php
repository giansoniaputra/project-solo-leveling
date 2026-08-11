<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class VoiceController extends Controller
{
    private const VOICE = 'nova';

    private const SPEED = 0.85;

    private const CONVERSATIONS_PER_PAGE = 10;

    /**
     * Synthesizes "The System"'s spoken replies via OpenAI TTS. Identical
     * phrases are cached on disk (keyed by text + voice + speed, so changing
     * either setting doesn't serve stale audio) so repeat lines (like the
     * "you up?" easter egg) don't re-hit the API every time.
     */
    public function speak(Request $request)
    {
        $data = $request->validate([
            // Long enough for a full "ask" answer paragraph or its Indonesian
            // translation — OpenAI TTS itself accepts up to ~4096 chars, so
            // this stays well under that. Previously capped at 500, which
            // silently fell back to the robotic browser voice for anything
            // longer (the validation failure was swallowed by the frontend's
            // error handler instead of surfacing as an obvious bug).
            'text' => 'required|string|max:2000',
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

            // The SDK's audio endpoint returns the raw response body as-is
            // without validating it's actually audio — an API error (e.g. an
            // invalid key) comes back as a JSON string here instead of
            // throwing, and would otherwise get cached and served as a
            // "successful" empty/broken mp3 forever. A real mp3 never starts
            // with '{', so this is a cheap, reliable way to catch that.
            if (str_starts_with(ltrim($audio), '{')) {
                $message = json_decode($audio, true)['error']['message'] ?? 'Unknown error';

                return response()->json(['message' => 'Failed to synthesize speech: '.$message], 502);
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

    /**
     * "Friday, may I ask?" flow: answers an open-ended question with a real
     * web search + citation. The installed openai-php/client SDK has no
     * Responses API resource, so this calls it directly over HTTP with the
     * web_search_preview tool (verified against the live API — returns
     * genuine, dated, cited answers, not hallucinated sources).
     */
    public function ask(Request $request)
    {
        $data = $request->validate([
            'question' => 'required|string|max:500',
        ]);

        $today = now()->format('F j, Y');

        $prompt = <<<PROMPT
        Today's date is {$today}. Search the web and answer this question, using that as "now" — not your training
        cutoff or any other assumed date: {$data['question']}

        Reply with ONE short, focused paragraph in plain English (2-4 sentences), suitable for text-to-speech —
        no markdown, no bullet lists, no headings, no "highlights" or extra tangential news, just the direct answer.
        Do NOT include inline citation links or parenthetical source mentions like "(site.com)" in the answer text
        itself — the source is shown separately, so the spoken text must read as plain natural sentences only.
        Base it on your single most authoritative, up-to-date source.
        PROMPT;

        try {
            $response = Http::withToken(config('services.openai.key'))
                ->timeout(30)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => 'gpt-4o-mini',
                    'tools' => [['type' => 'web_search_preview']],
                    'tool_choice' => ['type' => 'web_search_preview'],
                    'input' => $prompt,
                ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to reach the System (AI): '.$e->getMessage()], 502);
        }

        if (! $response->successful()) {
            return response()->json(['message' => 'The System could not find an answer.'], 502);
        }

        $message = collect($response->json('output', []))
            ->firstWhere('type', 'message');

        $answer = trim($message['content'][0]['text'] ?? '');

        // Defensive cleanup: strip any inline "([title](url))" citation
        // markdown the model included despite being told not to — it reads
        // badly out loud via TTS. The source is already returned separately.
        $answer = trim(preg_replace('/\s*\(\[[^\]]*\]\([^)]*\)\)/', '', $answer));

        if ($answer === '') {
            return response()->json(['message' => 'The System did not return an answer.'], 502);
        }

        $citation = collect($message['content'][0]['annotations'] ?? [])
            ->firstWhere('type', 'url_citation');

        $conversation = Conversation::create([
            'user_id' => $request->user()->id,
            'question' => $data['question'],
            'answer' => $answer,
            'mode' => 'reference',
            'source_title' => $citation['title'] ?? null,
            'source_url' => $citation['url'] ?? null,
        ]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'answer' => $answer,
            'source_title' => $citation['title'] ?? null,
            'source_url' => $citation['url'] ?? null,
        ]);
    }

    /**
     * "Friday, may I ask?" flow, "your suggestion" branch: The System's own
     * direct opinion — deliberately NOT a web search, so no citation comes
     * back (the frontend already handles a null source gracefully).
     */
    public function suggest(Request $request)
    {
        $data = $request->validate([
            'question' => 'required|string|max:500',
        ]);

        $today = now()->format('F j, Y');

        $prompt = <<<PROMPT
        Today's date is {$today}.

        A Hunter is asking for YOUR OWN direct opinion or suggestion — do NOT search the web or claim to have
        looked anything up, just answer from your own reasoning, in the blunt, encouraging tone of "The System"
        from Solo Leveling.

        Question: {$data['question']}

        Reply with ONE short, focused paragraph in plain English (2-4 sentences), suitable for text-to-speech —
        no markdown, no bullet lists, no headings, just your direct suggestion.
        PROMPT;

        $client = \OpenAI::client(config('services.openai.key'));

        try {
            $response = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are "The System" from Solo Leveling, giving your own direct opinion — never claim to have searched anything.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to reach the System (AI): '.$e->getMessage()], 502);
        }

        $answer = trim($response->choices[0]->message->content ?? '');

        if ($answer === '') {
            return response()->json(['message' => 'The System had nothing to suggest.'], 502);
        }

        $conversation = Conversation::create([
            'user_id' => $request->user()->id,
            'question' => $data['question'],
            'answer' => $answer,
            'mode' => 'suggestion',
            'source_title' => null,
            'source_url' => null,
        ]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'answer' => $answer,
            'source_title' => null,
            'source_url' => null,
        ]);
    }

    /**
     * Paginated list of past "Friday, may I ask?" Q&A pairs, for the
     * "Open Last Conversation" voice command — newest first, same
     * pagination shape as TaskController::history.
     */
    public function conversationHistory(Request $request)
    {
        $paginator = Conversation::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(
                self::CONVERSATIONS_PER_PAGE,
                ['*'],
                'page',
                max(1, (int) $request->query('page', 1))
            );

        return response()->json([
            'conversations' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * Translates an already-generated answer in place (used by the
     * "change bahasa" / "change english" voice commands) — no new search,
     * just a rewrite of the existing text.
     */
    public function translate(Request $request)
    {
        $data = $request->validate([
            'text' => 'required|string|max:2000',
            'target_language' => 'required|in:en,id',
        ]);

        $languageName = $data['target_language'] === 'id' ? 'Bahasa Indonesia' : 'English';

        $client = \OpenAI::client(config('services.openai.key'));

        try {
            $response = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => "Translate the user's text into {$languageName}. Keep it natural and concise, suitable for text-to-speech. Reply with ONLY the translated text, nothing else."],
                    ['role' => 'user', 'content' => $data['text']],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to translate: '.$e->getMessage()], 502);
        }

        $text = trim($response->choices[0]->message->content ?? '');

        if ($text === '') {
            return response()->json(['message' => 'Translation failed.'], 502);
        }

        return response()->json(['text' => $text]);
    }
}
