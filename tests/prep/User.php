<?php

namespace Epiphyte;

use Parley\Traits\ParleyableTrait;
use Illuminate\Database\Eloquent;

class User extends Eloquent\Model
{
    use ParleyableTrait;

    protected $fillable = ['first_name', 'last_name', 'email'];
}
