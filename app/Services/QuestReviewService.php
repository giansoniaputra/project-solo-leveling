<?php

namespace App\Services;

use App\Models\Quest;
use App\Models\User;
use RuntimeException;

class QuestReviewService
{
    /**
     * Grades a Hunter's free-text self-report of what they completed and
     * proposes EXP/stat rewards for it. Does not persist anything — the
     * caller decides whether/when to save the proposal.
     *
     * @return array{summary: string, exp_awarded: int, stats: array<string, int>}
     */
    public function review(User $user, string $description): array
    {
        $client = \OpenAI::client(config('services.openai.key'));

        $statsSummary = "STR {$user->str}, AGI {$user->agi}, PER {$user->per}, VIT {$user->vit}, INT {$user->intelligence}";

        $prompt = <<<PROMPT
        You are "The System" from the anime Solo Leveling. A Hunter has just self-reported, in their own words,
        what real-world activities they completed today. Review their report and grade it.

        Hunter data:
        - Level: {$user->level}, Total EXP: {$user->exp}
        - Stats: {$statsSummary}

        Hunter's self-report:
        """
        {$description}
        """

        Task:
        1. Identify each distinct real-world activity described. Ignore anything that isn't a concrete completed
           activity (vague filler, questions, or clearly fictional/joke text earns nothing).
        2. Map each activity to the stat it trains: str (physical strength/exercise), agi (speed/agility/cardio),
           per (focus/mindfulness/observation), vit (health/sleep/nutrition/rest), intelligence (learning/reading/study).
           A single report may span multiple stats — weight each stat's award by how much of the report it represents.
        3. Award total EXP for the WHOLE report: 20-400, weighted by effort/quantity/duration described. Trivial or
           single small activities should land low (20-80); a full, substantial day of effort should land high
           (250-400). Never award for activities not actually described.
        4. Award each relevant stat 0-6 points, weighted the same way. Stats not touched by the report must be 0.
        5. Write "summary": 2-4 short spoken sentences (this will be read aloud by text-to-speech and shown to the
           Hunter) in the blunt, in-character tone of "The System", restating what was logged and what was awarded.
           Do not just repeat the raw report — grade it.

        Reply with ONLY the following JSON format, no other text:
        {"summary": "...", "exp_awarded": 120, "stats": {"str": 2, "agi": 0, "per": 0, "vit": 1, "intelligence": 0}}
        PROMPT;

        try {
            $response = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => 'You are "The System" from Solo Leveling. Always reply with valid JSON in the requested format.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Failed to reach the System (AI): ' . $e->getMessage(), previous: $e);
        }

        $content = $response->choices[0]->message->content ?? null;
        $data = json_decode((string) $content, true);

        if (! is_array($data) || empty($data['summary'])) {
            throw new RuntimeException('The System could not evaluate this report.');
        }

        $stats = [];
        foreach (Quest::STATS as $stat) {
            $stats[$stat] = max(0, min(6, (int) ($data['stats'][$stat] ?? 0)));
        }

        return [
            'summary' => $data['summary'],
            'exp_awarded' => max(0, min(400, (int) ($data['exp_awarded'] ?? 0))),
            'stats' => $stats,
        ];
    }
}
