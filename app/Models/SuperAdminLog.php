<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuperAdminLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'action',
        'target_tenant_id',
        'description',
        'before_data',
        'after_data',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get admin user
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get target tenant
     */
    public function targetTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'target_tenant_id');
    }

    /**
     * Get changes
     */
    public function getChanges(): array
    {
        if (!$this->before_data || !$this->after_data) {
            return [];
        }

        $changes = [];
        foreach ($this->after_data as $key => $value) {
            if (($this->before_data[$key] ?? null) !== $value) {
                $changes[$key] = [
                    'old' => $this->before_data[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }
}
