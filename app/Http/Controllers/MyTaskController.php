<?php

namespace App\Http\Controllers;

use App\Models\MyQuestLog;
use App\Models\Quest;
use App\Models\User;
use App\Services\QuestReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MyTaskController extends Controller
{
    /**
     * Grades the Hunter's free-text report via AI and caches the proposal
     * (keyed per-user, 15 min TTL) so confirm() applies exactly what was
     * shown to the Hunter rather than trusting numbers echoed back by the
     * browser.
     */
    public function review(Request $request, QuestReviewService $service)
    {
        $data = $request->validate([
            'description' => 'required|string|max:2000',
        ]);

        try {
            $proposal = $service->review($request->user(), $data['description']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $proposal['description'] = $data['description'];

        Cache::put("my_task_pending:{$request->user()->id}", $proposal, now()->addMinutes(15));

        return response()->json($proposal);
    }

    /**
     * Applies the cached proposal from review() to the Hunter's EXP/stats
     * and logs it, the same way QuestController::complete() applies a
     * quest's reward.
     */
    public function confirm(Request $request)
    {
        $user = $request->user();
        $proposal = Cache::pull("my_task_pending:{$user->id}");

        if (! $proposal) {
            return response()->json(['message' => 'This proposal has expired. Please submit your quest again, sir.'], 422);
        }

        $log = MyQuestLog::create([
            'user_id' => $user->id,
            'description' => $proposal['description'],
            'summary' => $proposal['summary'],
            'exp_awarded' => $proposal['exp_awarded'],
            'str_awarded' => $proposal['stats']['str'],
            'agi_awarded' => $proposal['stats']['agi'],
            'per_awarded' => $proposal['stats']['per'],
            'vit_awarded' => $proposal['stats']['vit'],
            'intelligence_awarded' => $proposal['stats']['intelligence'],
        ]);

        $user->exp += $proposal['exp_awarded'];

        foreach (Quest::STATS as $stat) {
            $amount = $proposal['stats'][$stat];
            if ($amount > 0) {
                $user->{$stat} += $amount;
            }
        }

        $levelInfo = User::levelInfoForExp($user->exp);
        $user->level = (string) $levelInfo['level'];
        $user->save();

        return response()->json([
            'message' => 'Quest logged, sir.',
            'summary' => $log->summary,
            'exp_awarded' => $log->exp_awarded,
            'stats_awarded' => [
                'str' => $log->str_awarded,
                'agi' => $log->agi_awarded,
                'per' => $log->per_awarded,
                'vit' => $log->vit_awarded,
                'intelligence' => $log->intelligence_awarded,
            ],
            'exp' => $user->exp,
            'level' => $user->level,
            'exp_into_level' => $levelInfo['exp_into_level'],
            'exp_for_next_level' => $levelInfo['exp_for_next_level'],
        ]);
    }
}
