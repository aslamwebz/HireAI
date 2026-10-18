<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageRecord extends Model
{
    protected $fillable = [
        'user_id',
        'feature',
        'period',
        'count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function incrementUsage(User $user, string $feature): void
    {
        $period = now()->format('Y-m');

        $record = static::firstOrCreate(
            [
                'user_id' => $user->id,
                'feature' => $feature,
                'period' => $period,
            ],
            ['count' => 0]
        );

        $record->increment('count');
    }

    public static function getMonthlyUsage(User $user, string $feature): int
    {
        $period = now()->format('Y-m');

        return static::where('user_id', $user->id)
            ->where('feature', $feature)
            ->where('period', $period)
            ->value('count') ?? 0;
    }

    public static function canUse(User $user, string $feature, int $freeLimit): bool
    {
        $usage = static::getMonthlyUsage($user, $feature);

        return $usage < $freeLimit || $user->isPro();
    }
}
