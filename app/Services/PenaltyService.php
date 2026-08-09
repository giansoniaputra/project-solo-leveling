<?php

namespace App\Services;

use App\Models\Quest;
use App\Models\User;
use Illuminate\Support\Collection;

class PenaltyService
{
    /**
     * Deduct EXP for any past daily quests the user never completed,
     * and mark them as penalized so they are never charged twice.
     *
     * @return Collection<int, Quest> quests that were just penalized
     */
    public function applyForUser(User $user): Collection
    {
        $missed = Quest::where('user_id', $user->id)
            ->where('type', 'daily')
            ->where('date_assigned', '<', now()->toDateString())
            ->whereNull('penalized_at')
            ->whereDoesntHave('progress', fn ($q) => $q->where('status', true))
            ->get();

        if ($missed->isEmpty()) {
            return $missed;
        }

        $totalPenalty = 0;

        foreach ($missed as $quest) {
            $totalPenalty += (int) $quest->exp_reward;
            $quest->penalized_at = now();
            $quest->save();
        }

        $user->exp = max(0, $user->exp - $totalPenalty);
        $user->level = (string) User::levelInfoForExp($user->exp)['level'];
        $user->save();

        return $missed;
    }
}
