<?php

namespace Epiphyte;

use Parley\Traits\ParleyableTrait;
use Illuminate\Database\Eloquent;

class Group extends Eloquent\Model {

    use ParleyableTrait;

    protected $fillable = ['name'];
}