<?php

namespace App\Services\Logging;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    protected ?string $logName = null;
    protected ?string $description = null;
    protected ?Model $subject = null;
    protected ?Model $causer = null;
    protected array $properties = [];

    /**
     * Set log name/category
     */
    public function on(string $logName): self
    {
        $this->logName = $logName;
        return $this;
    }

    /**
     * Set description
     */
    public function withDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Set subject (the model being acted upon)
     */
    public function performedOn(Model $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set causer (who performed the action)
     */
    public function causedBy(Model $causer): self
    {
        $this->causer = $causer;
        return $this;
    }

    /**
     * Set properties/context
     */
    public function withProperties(array $properties): self
    {
        $this->properties = array_merge($this->properties, $properties);
        return $this;
    }

    /**
     * Log the activity
     */
    public function log(string $description = null): ActivityLog
    {
        if ($description) {
            $this->description = $description;
        }

        $log = ActivityLog::create([
            'tenant_id' => tenant()?->id,
            'user_id' => $this->causer?->id ?? auth()->id(),
            'log_name' => $this->logName,
            'description' => $this->description,
            'subject_type' => $this->subject ? get_class($this->subject) : null,
            'subject_id' => $this->subject?->id,
            'causer_type' => $this->causer ? get_class($this->causer) : ($this->causer = auth()->user() ? get_class(auth()->user()) : null),
            'causer_id' => $this->causer?->id ?? auth()->id(),
            'properties' => $this->properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);

        // Reset for next use
        $this->reset();

        return $log;
    }

    /**
     * Log model created event
     */
    public function logCreated(Model $model, ?User $user = null): ActivityLog
    {
        return $this->causedBy($user ?? auth()->user())
            ->performedOn($model)
            ->withProperties([
                'attributes' => $model->getAttributes(),
            ])
            ->log('created');
    }

    /**
     * Log model updated event
     */
    public function logUpdated(Model $model, array $oldAttributes, ?User $user = null): ActivityLog
    {
        $changes = [];
        foreach ($model->getDirty() as $key => $newValue) {
            $changes[$key] = [
                'old' => $oldAttributes[$key] ?? null,
                'new' => $newValue,
            ];
        }

        return $this->causedBy($user ?? auth()->user())
            ->performedOn($model)
            ->withProperties([
                'old' => $oldAttributes,
                'attributes' => $model->getAttributes(),
                'changes' => $changes,
            ])
            ->log('updated');
    }

    /**
     * Log model deleted event
     */
    public function logDeleted(Model $model, ?User $user = null): ActivityLog
    {
        return $this->causedBy($user ?? auth()->user())
            ->performedOn($model)
            ->withProperties([
                'attributes' => $model->getAttributes(),
            ])
            ->log('deleted');
    }

    /**
     * Reset logger state
     */
    protected function reset(): void
    {
        $this->logName = null;
        $this->description = null;
        $this->subject = null;
        $this->causer = null;
        $this->properties = [];
    }
}
