<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class Sequence extends Model
{
    protected $fillable = [
        'name',
        'value',
    ];

    /**
     * Atomically increment and return the next value for the named sequence.
     * Safe against concurrent workers thanks to a locked transaction.
     */
    public static function next(string $name, int $start = 1): int
    {
        return DB::transaction(function () use ($name, $start) {
            $sequence = static::query()->where('name', $name)->lockForUpdate()->first();

            if (! $sequence) {
                try {
                    $sequence = static::create(['name' => $name, 'value' => $start]);

                    return (int) $sequence->value;
                } catch (UniqueConstraintViolationException) {
                    $sequence = static::query()->where('name', $name)->lockForUpdate()->firstOrFail();
                }
            }

            $sequence->increment('value');

            return (int) $sequence->fresh()->value;
        });
    }
}
