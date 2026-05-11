<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends BasePolicy
{
    public function before(User $user): ?bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return null;
    }

    public function index(User $user): bool
    {
        return true;
    }

    public function show(User $user, User $model): bool
    {
        return $this->checkSelf($user, $model);
    }

    public function update(User $user, User $model): bool
    {
        return $this->checkSelf($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->checkSelf($user, $model);
    }
}
