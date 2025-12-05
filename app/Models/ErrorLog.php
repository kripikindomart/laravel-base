<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorLog extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'error_type',
        'error_message',
        'error_code',
        'file',
        'line',
        'trace',
        'context',
        'severity',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get resolver
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Mark as resolved
     */
    public function resolve(User $user): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $user->id,
        ]);
    }

    /**
     * Check if critical
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }
}
