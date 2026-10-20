<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    public const SIMULATION_PHONE_PREFIX = '000';

    protected $fillable = [
        'phone',
        'name',
        'department',
        'is_active',
        'is_simulated',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_simulated' => 'boolean',
        ];
    }

    /**
     * Normalize a phone number to bare digits (628xxx without + or suffix).
     */
    public static function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    public static function isReservedSimulationPhone(?string $phone): bool
    {
        return str_starts_with(self::normalizePhone($phone), self::SIMULATION_PHONE_PREFIX);
    }

    public static function nextSimulationPhone(): string
    {
        $sequence = self::withTrashed()
            ->where('phone', 'like', self::SIMULATION_PHONE_PREFIX.'%')
            ->count() + 1;

        do {
            $phone = self::SIMULATION_PHONE_PREFIX.str_pad((string) $sequence, 9, '0', STR_PAD_LEFT);
            $sequence++;
        } while (self::withTrashed()->where('phone', $phone)->exists());

        return $phone;
    }

    public static function createSimulation(?string $name = null): self
    {
        $number = self::withTrashed()->where('is_simulated', true)->count() + 1;

        return self::create([
            'phone' => self::nextSimulationPhone(),
            'name' => $name ?: 'Simulasi '.$number,
            'department' => 'Simulasi',
            'is_active' => true,
            'is_simulated' => true,
        ]);
    }

    public function isSimulated(): bool
    {
        return (bool) $this->is_simulated;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSimulated($query)
    {
        return $query->where('is_simulated', true);
    }

    public function scopeReal($query)
    {
        return $query->where('is_simulated', false);
    }

    /** @return HasMany<ChatSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
