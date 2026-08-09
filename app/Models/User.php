<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
// use Laragear\WebAuthn\WebAuthnAuthentication;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $guarded = ['id'];

    public function quests(): HasMany
    {
        return $this->hasMany(Quest::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(Progress::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(ShopPurchase::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Turns cumulative EXP into a level, the EXP earned within that level,
     * and how much EXP the level needs. Each level-up costs 500 more than
     * the last (level 1->2 costs 1000, 2->3 costs 1500, 3->4 costs 2000,
     * ...) — this is the single source of truth for that curve, called
     * everywhere EXP changes (quest completion, penalties) so the growing
     * threshold never drifts out of sync between places.
     *
     * @return array{level: int, exp_into_level: int, exp_for_next_level: int}
     */
    public static function levelInfoForExp(int $exp): array
    {
        $remaining = max(0, $exp);
        $level = 1;
        $threshold = 1000;

        while ($remaining >= $threshold) {
            $remaining -= $threshold;
            $level++;
            $threshold += 500;
        }

        return [
            'level' => $level,
            'exp_into_level' => $remaining,
            'exp_for_next_level' => $threshold,
        ];
    }
}
