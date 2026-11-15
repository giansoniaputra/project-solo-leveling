<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ClaudeService;
use App\Services\SpeaktorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VoiceController extends Controller
{
    private const CONVERSATIONS_PER_PAGE = 10;

    /**
     * Synthesizes "The System"'s spoken replies via Speaktor TTS (Claude has
     * no speech-synthesis API). Identical phrases are cached on disk (keyed by
     * text + voice/language/speed/emotion, so changing a setting doesn't serve
     * stale audio) so repeat lines (like the "you up?" easter egg) don't
     * re-hit the API every time. On failure the frontend falls back to the
     * browser's own voice.
     */
    public function speak(Request $request, SpeaktorService $speaktor)
    {
        $data = $request->validate([
            // Long enough for a full "ask" answer paragraph or its Indonesian
            // translation. Long text is chunked inside SpeaktorService.
            'text' => 'required|string|max:2000',
        ]);

        $path = 'voice-cache/'.md5($data['text'].'|'.$speaktor->fingerprint()).'.mp3';

        if (! Storage::disk('local')->exists($path)) {
            try {
                $audio = $speaktor->synthesize($data['text']);
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

        try {
            $text = (new ClaudeService())->text(
                'You are "The System" from Solo Leveling. Reply with plain spoken text only, nothing else.',
                $prompt,
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to summarize status: '.$e->getMessage()], 502);
        }

        if ($text === '') {
            return response()->json(['message' => 'The System had nothing to say.'], 502);
        }

        return response()->json(['text' => $text]);
    }

    /**
     * "Friday, may I ask?" flow: answers an open-ended question with a real
     * web search + citation, using Claude's server-side web_search tool
     * (returns real, dated, cited answers rather than invented sources).
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
            $result = (new ClaudeService())->searchWeb(
                'You are "The System" from Solo Leveling. Always search the web before answering, and reply with plain spoken text only.',
                $prompt,
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to reach the System (AI): '.$e->getMessage()], 502);
        }

        $answer = $result['answer'];

        // Defensive cleanup: strip any inline "([title](url))" citation
        // markdown the model included despite being told not to — it reads
        // badly out loud via TTS. The source is already returned separately.
        $answer = trim(preg_replace('/\s*\(\[[^\]]*\]\([^)]*\)\)/', '', $answer));

        if ($answer === '') {
            return response()->json(['message' => 'The System did not return an answer.'], 502);
        }

        $conversation = Conversation::create([
            'user_id' => $request->user()->id,
            'question' => $data['question'],
            'answer' => $answer,
            'mode' => 'reference',
            'source_title' => $result['source_title'],
            'source_url' => $result['source_url'],
        ]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'answer' => $answer,
            'source_title' => $result['source_title'],
            'source_url' => $result['source_url'],
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

        try {
            $answer = (new ClaudeService())->text(
                'You are "The System" from Solo Leveling, giving your own direct opinion — never claim to have searched anything.',
                $prompt,
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to reach the System (AI): '.$e->getMessage()], 502);
        }

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

        try {
            $text = (new ClaudeService())->text(
                "Translate the user's text into {$languageName}. Keep it natural and concise, suitable for text-to-speech. Reply with ONLY the translated text, nothing else.",
                $data['text'],
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to translate: '.$e->getMessage()], 502);
        }

        if ($text === '') {
            return response()->json(['message' => 'Translation failed.'], 502);
        }

        return response()->json(['text' => $text]);
    }

    /**
     * Cleans up one newly-dictated chunk of the "My Task" quest description
     * (see MyTaskController) while it's being spoken — speech recognition
     * regularly mishears homophones (e.g. "four" heard as "for"), so this
     * uses whatever's already been written as context to fix the new chunk
     * before it's appended to the textarea.
     */
    public function correctDictation(Request $request)
    {
        $data = $request->validate([
            'context' => 'nullable|string|max:4000',
            'text' => 'required|string|max:1000',
        ]);

        $context = $data['context'] ?? '';

        $prompt = <<<PROMPT
        Already written so far: "{$context}"
        Newly dictated (raw, possibly mis-heard by speech recognition): "{$data['text']}"
        PROMPT;

        try {
            $text = (new ClaudeService())->text(
                'You are cleaning up a live speech-to-text dictation. Fix only obvious mishearings/homophones (e.g. "for" heard instead of "four", "to" instead of "two") and grammar so the new text reads naturally as a continuation of what\'s already written, using the existing text only as context — do not repeat it back. Keep the same meaning and roughly the same length as the newly dictated text. Reply with ONLY the corrected sentence itself in plain text — no surrounding quotation marks, no markdown, nothing else.',
                $prompt,
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to correct dictation: '.$e->getMessage()], 502);
        }

        // Defensive cleanup: strip a stray wrapping quote pair if the model
        // added one despite being told not to.
        $text = trim($text, "\"'");

        if ($text === '') {
            return response()->json(['message' => 'Dictation correction failed.'], 502);
        }

        return response()->json(['text' => $text]);
    }
}
