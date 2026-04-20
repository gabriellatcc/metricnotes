<?php

namespace App\Policies;

use App\Models\NoteType;
use App\Models\User;

class NoteTypePolicy
{
    protected function isAdmin(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function before(User $user): ?bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return null;
    }

    public function show(User $user, NoteType $noteType): bool
    {
        return $user->id === $noteType->user_id;
    }

    public function update(User $user, NoteType $noteType): bool
    {
        return $user->id === $noteType->user_id;
    }

    public function delete(User $user, NoteType $noteType): bool
    {
        return $user->id === $noteType->user_id;
    }

    public function restore(User $user, NoteType $noteType): bool
    {
        return false;
    }

    public function forceDelete(User $user, NoteType $noteType): bool
    {
        return false;
    }
}
