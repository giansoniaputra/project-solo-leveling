<?php

namespace App\Http\Controllers;

use App\Models\Progress;
use App\Models\Quest;
use App\Services\PenaltyService;
use App\Services\QuestGeneratorService;
use Illuminate\Http\Request;

class QuestController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'daily');
        $user = $request->user();

        $query = Quest::query()->where('user_id', $user->id)->where('type', $type)->with('progress');

        if ($type === 'main') {
            $quests = $query->whereDoesntHave('progress', fn ($q) => $q->where('status', true))->get();
        } else {
            $quests = $query->where('date_assigned', now()->toDateString())->get();
        }

        return response()->json(['quests' => $quests]);
    }

    public function generate(Request $request, QuestGeneratorService $generator)
    {
        $data = $request->validate([
            'type' => 'required|in:daily,main',
            'stat' => 'required_if:type,main|nullable|in:' . implode(',', Quest::STATS),
        ]);

        try {
            $quests = $generator->generate($request->user(), $data['type'], $data['stat'] ?? null);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json(['quests' => $quests]);
    }

    public function complete(Request $request, Quest $quest)
    {
        abort_if($quest->user_id !== $request->user()->id, 403);

        $progress = Progress::firstOrNew([
            'user_id' => $request->user()->id,
            'quest_id' => $quest->id,
        ]);

        if ($progress->status) {
            return response()->json(['message' => 'Quest already completed'], 422);
        }

        $progress->status = true;
        $progress->completed_at = now();
        $progress->save();

        $user = $request->user();
        $user->exp += (int) $quest->exp_reward;

        if ($quest->stat && in_array($quest->stat, Quest::STATS, true)) {
            $user->{$quest->stat} += (int) $quest->stat_reward;

            // Points are a separate, spendable currency (see ShopController)
            // deliberately earned off the small stat_reward scale (1-8 per
            // quest) rather than exp_reward (20-800) — shop treats should
            // take real effort to save up for, not come free with EXP.
            $user->points += (int) $quest->stat_reward;
        }

        $user->level = (string) (intdiv($user->exp, 1000) + 1);
        $user->save();

        return response()->json([
            'message' => 'Quest completed!',
            'exp' => $user->exp,
            'level' => $user->level,
            'points' => $user->points,
            'stat' => $quest->stat,
            'stat_reward' => $quest->stat_reward,
            'stats' => $this->statsPayload($user),
        ]);
    }

    public function checkPenalties(Request $request, PenaltyService $penalty)
    {
        $user = $request->user();
        $missed = $penalty->applyForUser($user);

        return response()->json([
            'penalized' => $missed->map(fn ($quest) => [
                'title' => $quest->title,
                'exp_reward' => $quest->exp_reward,
            ])->values(),
            'exp' => $user->exp,
            'level' => $user->level,
            'stats' => $this->statsPayload($user),
        ]);
    }

    private function statsPayload($user): array
    {
        return [
            'str' => $user->str,
            'agi' => $user->agi,
            'per' => $user->per,
            'vit' => $user->vit,
            'intelligence' => $user->intelligence,
        ];
    }
}
