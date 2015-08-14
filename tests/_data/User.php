<?php

namespace Epiphyte;

use Parley\Contracts\ParleyableInterface;

class User extends \Illuminate\Database\Eloquent\Model implements ParleyableInterface
{
    protected $fillable = ['first_name', 'last_name', 'email'];

    public function getAliasAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Each Parleyable object must provide an integer id value.  Usually this is can be
     * as simple as "return $this->attributes['id'];".
     *
     * @return int
     */
    public function parleyId()
    {
        return $this->attributes['id'];
    }
}
