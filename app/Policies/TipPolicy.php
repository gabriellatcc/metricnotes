<?php

namespace App\Policies;

use App\Models\Tip;
use App\Models\User;

class TipPolicy
{
    protected function isAdmin(User $user): bool
    {
        return $user->is_admin === true;
    }

    /**
     * O método before é executado antes de qualquer outra regra.
     * Se retornar true, o acesso é liberado imediatamente (ideal para Admins).
     */
    public function before(User $user): ?bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return null;
    }

    public function show(User $user, Tip $tip): bool
    {
        return $user->id === $tip->user_id;
    }

    public function update(User $user, Tip $tip): bool
    {
        return $user->id === $tip->user_id;
    }

    public function delete(User $user, Tip $tip): bool
    {
        return $user->id === $tip->user_id;
    }

    public function restore(User $user, Tip $tip): bool
    {
        return false;
    }

    public function forceDelete(User $user, Tip $tip): bool
    {
        return false;
    }
}
