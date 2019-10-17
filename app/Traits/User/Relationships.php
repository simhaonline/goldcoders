<?php

namespace App\Traits\User;

use App\Models\User;
use App\Models\Ctrader;
use App\Models\Subscription;
use App\Models\CtraderAccount;

trait Relationships
{
    /**
     * @return mixed
     */
    public function accounts()
    {
        return $this->hasMany(CtraderAccount::class, 'paymaster_id');
    }

    /**
     * @return mixed
     */
    public function ctraderToken()
    {
        return $this->hasOne(Ctrader::class, 'user_id');
    }

    /**
     * @return mixed
     */
    public function paymaster()
    {
        return $this->belongsTo(User::class, 'paymaster_id');
    }

    /**
     * Referrals Relationship
     *
     */
    public function referrals()
    {
        return $this->hasMany(User::class, 'sp_id');
    }

    /**
     * Sponsor Relationship
     *
     */
    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sp_id');
    }

    /**
     * @return mixed
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @return mixed
     */
    public function underlings()
    {
        return $this->hasMany(User::class, 'paymaster_id');
    }
}
