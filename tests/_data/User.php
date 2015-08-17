<?php

namespace Chekhov;

use Parley\Contracts\ParleyableInterface;

class User extends \Illuminate\Database\Eloquent\Model implements ParleyableInterface
{
    protected $fillable = ['first_name', 'last_name', 'email'];

    public function getParleyAliasAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Each Parleyable object must provide an integer id value.  Usually this is can be
     * as simple as "return $this->attributes['id'];".
     *
     * @return int
     */
    public function getParleyIdAttribute()
    {
        return $this->attributes['id'];
    }
}
