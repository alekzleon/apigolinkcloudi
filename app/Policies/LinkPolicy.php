<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Link;
use App\Models\User;

class LinkPolicy
{
    public function view(User $user, Link $link): bool
    {
        return $link->user_id === $user->id;
    }

    public function update(User $user, Link $link): bool
    {
        return $link->user_id === $user->id;
    }

    public function delete(User $user, Link $link): bool
    {
        return $link->user_id === $user->id;
    }
}
