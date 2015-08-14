<?php

namespace Chekhov;

use Parley\Contracts\ParleyableInterface;

class Group extends \Illuminate\Database\Eloquent\Model implements ParleyableInterface
{
    protected $fillable = ['name'];

    public function getAliasAttribute()
    {
        return $this->name;
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
