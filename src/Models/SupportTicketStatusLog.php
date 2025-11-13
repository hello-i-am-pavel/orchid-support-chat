<?php

namespace Hiap\OrchidSupportChat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketStatusLog extends Model
{
    use HasFactory;

    public $timestamps = false; // only created_at is stored

    protected $fillable = [
        'ticket_id',
        'old_status',
        'new_status',
        'changed_by',
        'created_at',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
