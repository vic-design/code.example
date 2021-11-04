<?php

namespace App\Policies\Api\V2;

use App\Models\User;
use App\Policies\BasePolicy;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientPolicy extends BasePolicy
{
    use HandlesAuthorization;

    public function index()
    {
        if (isSuperAdmin()) {
            return true;
        }
    }

    public function login()
    {
        if (isSuperAdmin()) {
            return true;
        }
    }

    public function logout(User $authUser)
    {
        return $authUser->impersonated()->exists();
    }
}
