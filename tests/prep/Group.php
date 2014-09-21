<?php namespace SRLabs\Parley\tests\prep;

use SRLabs\Parley\Traits\Parleyable;
use Illuminate\Database\Eloquent;
 
class Group extends Eloquent\Model {

    use Parleyable;

    protected $fillable = ['name'];
} 