<?php

namespace App\Traits\User;

use App\Models\Permission;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

trait Mutators
{
    /**
     * Add Default Avatar
     *
     */
    public function getAvatarAttribute($value)
    {
        return empty($value) ? 'https://www.gravatar.com/avatar/'.md5(Str::lower($this->email)).'.jpg?s=200&d=mm' : url($value);
    }

    /**
     * Get Can Attribute
     *
     */
    public function getCanAttribute()
    {
        $permissions = [];

        foreach (Permission::all() as $permission) {
            if ($this->can($permission->name)) {
                $permissions[$permission->name] = true;
            } else {
                $permissions[$permission->name] = false;
            }
        }

        return $permissions;
    }

    /**
     * Get Name From Profile
     *
     */
    public function getNameAttribute()
    {
        return $this->fname.' '.$this->lname;
    }

    /**
     * Get All Permissions Attribute
     *
     */
    public function getPermissionListAttribute()
    {
        return $this->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * Get Can Attribute
     *
     */
    public function getRoleListAttribute()
    {
        return $this->getRoleNames()->toArray();
    }

    /**
     * Set Email To Correct Format
     *
     */
    public function setEmailAttribute($email)
    {
        $this->attributes['email'] = $email;
    }

    /**
     * Set Password Hash
     *
     */
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::make($password);
    }

    /**
     * Set Username To Correct Format
     *
     */
    public function setUsernameAttribute($username)
    {
        $this->attributes['username'] = strtolower(str_replace(' ', '_', $username));
    }
}
