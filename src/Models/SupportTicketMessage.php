<?php

namespace Hiap\OrchidSupportChat\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

/**
 * Class SupportTicketMessage
 * @package Hiap\OrchidSupportChat\Models
 *
 * @property int ticket_id
 * @property SupportTicket ticket
 * @property User sent_by
 * @property string message
 * @property Carbon created_at
 * @property Carbon updated_at
 *
 * @method static create(array $array)
 */
class SupportTicketMessage extends Model
{
    use HasFactory;
    use AsSource;
    use Attachable;
    use Filterable;

    protected $fillable = [
        'ticket_id',
        'sent_by',
        'message',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}

