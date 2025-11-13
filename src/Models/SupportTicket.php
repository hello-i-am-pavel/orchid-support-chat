<?php

namespace Hiap\OrchidSupportChat\Models;

use App\Models\User;
use Carbon\Carbon;
use Hiap\OrchidSupportChat\Models\Enum\TicketStatus;
use Hiap\OrchidSupportChat\Notifications\SupportTicketCreatedNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

/**
 * Class FeedbackForm
 * @package Hiap\OrchidSupportChat\Models
 *
 * @property User created_by
 * @property string number
 * @property TicketStatus status
 * @property ?User status_changed_by
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property Carbon $createdBy
 * @property string $id
 *
 * @method static create(array $array)
 */
class SupportTicket extends Model
{
    use HasFactory;
    use AsSource;
    use Attachable;
    use Filterable;

    /**
     * Use UUIDs as primary keys
     */
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'created_by',
        'number',
        'status',
        'status_changed_by',
    ];

    protected $casts = [
        'status' => TicketStatus::class,
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id');
    }

    public function firstMessage(): HasOne
    {
        return $this->hasOne(SupportTicketMessage::class, 'ticket_id')->oldestOfMany('created_at');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(SupportTicketStatusLog::class, 'ticket_id')->orderByDesc('created_at');
    }

    protected static function booted(): void
    {
        static::creating(static function (self $model) {
            if (empty($model->getKey())) {
                $model->{$model->getKeyName()} = (string)Str::uuid();
            }
        });

        static::created(static function (self $ticket) {
            // TODO Заработает после того, как исправят баг в орчиде (ищет по true, хотя в бд пишется 1)
            $recipients = User::byAccess('support.tickets.manage')->get();

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new SupportTicketCreatedNotification($ticket));
            }
        });
    }

    public static function getActiveCount(): int
    {
        return self::query()
            ->whereNotIn('status', [TicketStatus::CLOSED, TicketStatus::RESOLVED])
            ->count();
    }
}

