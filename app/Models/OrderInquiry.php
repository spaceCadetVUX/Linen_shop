<?php

namespace App\Models;

use App\Enums\OrderInquiryChannel;
use App\Enums\OrderInquiryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderInquiry extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'name',
        'phone',
        'email',
        'message',
        'channel',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'channel' => OrderInquiryChannel::class,
            'status' => OrderInquiryStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
