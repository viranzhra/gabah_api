<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Traits\HasPermissions;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name'];

    public function user()
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id');
    }

    public function permission()
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }
}