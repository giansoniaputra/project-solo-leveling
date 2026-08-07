<?php

namespace App\Services;

use App\Models\Quest;
use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;

class QuestGeneratorService
{
    /**
     * Generate quests for the given user via OpenAI and persist them.
     *
     * @param  string|null  $stat  Required for 'main' quests: which stat (Quest::STATS) to train.
     * @return Collection<int, Quest>
     */
    public function generate(User $user, string $type, ?string $stat = null): Collection
    {
        $client = \OpenAI::client(config('services.openai.key'));

        $profile = $user->weight && $user->height && $user->age
            ? "Weight: {$user->weight} kg, Height: {$user->height} cm, Age: {$user->age} years."
            : 'Physical profile not yet provided, generate safe and generic quests for a beginner.';

        $statsSummary = "STR {$user->str}, AGI {$user->agi}, PER {$user->per}, VIT {$user->vit}, INT {$user->intelligence}";

        if ($type === 'main') {
            $stat = in_array($stat, Quest::STATS, true) ? $stat : 'str';
            $statName = $this->statName($stat);

            $countInstruction = "Generate EXACTLY 1 Main Quest that specifically trains {$statName} ({$stat}). "
                ."It must be a single big challenge with exp_reward 300-800 and stat_reward 3-8.";
        } else {
            $stat = 'str';
            $countInstruction = 'Generate 2 to 4 light Daily Quests focused on physical strength training '
                .'(push-ups, sit-ups, squats, running, etc.) that can be completed today. '
                .'Each quest exp_reward 20-150, stat_reward 1-5.';
        }

        $prompt = <<<PROMPT
        You are "The System" from the anime Solo Leveling, assigning quests to a Hunter to raise their stats.

        Hunter data:
        - Level: {$user->level}, Total EXP: {$user->exp}
        - Stats: {$statsSummary}
        - {$profile}

        Task: {$countInstruction}
        Scale the difficulty to the Hunter's level/EXP/stats and physical profile.
        Write the title and description in English, short and in the tone of "The System" (blunt, like an in-game quest order).

        Reply with ONLY the following JSON format, no other text:
        {"quests": [{"title": "...", "description": "...", "exp_reward": 100, "stat_reward": 2}]}
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

        if (! is_array($data) || empty($data['quests'])) {
            throw new RuntimeException('The System did not return a valid quest.');
        }

        return collect($data['quests'])->map(function (array $item) use ($user, $type, $stat) {
            return Quest::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $item['title'] ?? 'Untitled Quest',
                'description' => $item['description'] ?? '',
                'exp_reward' => (int) ($item['exp_reward'] ?? 50),
                'stat' => $stat,
                'stat_reward' => max(1, (int) ($item['stat_reward'] ?? 1)),
                'date_assigned' => now()->toDateString(),
            ]);
        });
    }

    private function statName(string $stat): string
    {
        return match ($stat) {
            'str' => 'Strength',
            'agi' => 'Agility',
            'per' => 'Perception',
            'vit' => 'Vitality',
            'intelligence' => 'Intelligence',
            default => 'Strength',
        };
    }
}
