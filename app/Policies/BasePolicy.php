<?php

namespace App\Policies;

use Illuminate\Database\Eloquent\Model;

class BasePolicy
{
    protected function isAdmin(object $user): bool
    {
        return (bool) ($user->is_admin ?? false);
    }

    protected function checkOwner(object $user, Model $model, string $ownerField = 'user_id'): bool
    {
        if (! isset($model->{$ownerField})) {
            return false;
        }

        return (int) $user->id === (int) $model->{$ownerField};
    }

    protected function checkSelf(object $user, Model $model): bool
    {
        return (int) $user->id === (int) $model->id;
    }
}
