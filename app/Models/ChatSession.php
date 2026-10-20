<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    use HasFactory;

    public const MODE_AI = 'ai';

    public const MODE_HUMAN = 'human';

    protected $fillable = [
        'employee_id',
        'mode',
        'attempt_count',
        'awaiting_ticket_confirmation',
        'ticket_id',
        'last_message_at',
        'started_at',
    ];

    protected function casts(): array
    {
        return [
            'awaiting_ticket_confirmation' => 'boolean',
            'last_message_at' => 'datetime',
            'started_at' => 'datetime',
        ];
    }

    public function isAiMode(): bool
    {
        return $this->mode === self::MODE_AI;
    }

    public function isHumanMode(): bool
    {
        return $this->mode === self::MODE_HUMAN;
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** @return HasMany<Message, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'session_id');
    }
}
