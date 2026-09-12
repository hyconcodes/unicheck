<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebauthnCredential extends Model
{
    protected $fillable = [
        'user_id',
        'credential_id',
        'public_key',
        'authenticator_type',
        'is_resident_key',
        'device_name',
        'signature_count',
        'last_used_at',
    ];

    protected $casts = [
        'is_resident_key' => 'boolean',
        'signature_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markUsed(?int $newSignatureCount = null): void
    {
        $this->update([
            'last_used_at' => now(),
            'signature_count' => $newSignatureCount ?? $this->signature_count + 1,
        ]);
    }
}
