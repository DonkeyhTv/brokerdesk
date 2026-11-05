<?php

namespace App\Observers;

use App\Contracts\Auditable;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class ActivityObserver
{
    /**
     * Derived or technical columns, which say nothing about what someone changed.
     */
    private const array IGNORED_ATTRIBUTES = ['updated_at', 'search_name', 'path'];

    public function created(Model&Auditable $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model&Auditable $model): void
    {
        $changed = Arr::except($model->getChanges(), self::IGNORED_ATTRIBUTES);

        if ($changed === []) {
            return;
        }

        $changes = [];

        foreach ($changed as $attribute => $new) {
            $changes[$attribute] = ['old' => $model->getRawOriginal($attribute), 'new' => $new];
        }

        $this->record($model, 'updated', $changes);
    }

    public function deleted(Model&Auditable $model): void
    {
        $this->record($model, 'deleted');
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>|null  $changes
     */
    private function record(Model&Auditable $model, string $action, ?array $changes = null): void
    {
        $log = new ActivityLog;

        $log->forceFill([
            'brokerage_id' => $model->auditBrokerageId(),
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $model->getMorphClass(),
            'subject_id' => $model->getKey(),
            'subject_label' => $model->auditLabel(),
            'changes' => $changes,
        ])->save();
    }
}
