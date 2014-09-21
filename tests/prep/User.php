<?php namespace SRLabs\Parley\tests\prep;

use SRLabs\Parley\Traits\Parleyable;
use Illuminate\Database\Eloquent;

/**
 * Created by Ryan C. Durham
 * Contact: ryan@stagerightlabs.com
 * Project: Parley
 * Date: 9/18/2014
 */
 
class User extends Eloquent\Model {
    
    use Parleyable;

    protected $fillable = ['first_name', 'last_name', 'email'];
}