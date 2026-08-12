<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Record an audit trail entry for a deal.
     *
     * @param  array<string, array{old: mixed, new: mixed}>|null  $details
     */
    public static function log(int $dealId, string $action, ?array $details = null): void
    {
        ActivityLog::create([
            'deal_id' => $dealId,
            'user_id' => auth()->id(),
            'action' => $action,
            'details' => $details,
        ]);
    }

    /**
     * Normalize a model attribute for storage (enums -> scalar values).
     */
    public static function scalarize(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    /**
     * Build a field => ['old' => ..., 'new' => ...] map from a model's dirty attributes.
     *
     * @param  Model  $model
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function changes($model): array
    {
        $changes = [];

        foreach ($model->getDirty() as $field => $newValue) {
            $changes[$field] = [
                'old' => self::scalarize($model->getOriginal($field)),
                'new' => self::scalarize($newValue),
            ];
        }

        return $changes;
    }
}
