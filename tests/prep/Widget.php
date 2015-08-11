<?php

namespace Epiphyte;

use Illuminate\Database\Eloquent;

class Widget extends Eloquent\Model
{
    protected $fillable = ['first_name', 'last_name', 'email'];
}
