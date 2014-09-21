<?php namespace SRLabs\Parley\tests\prep;

use Illuminate\Database\Eloquent;

/**
 * Created by Ryan C. Durham
 * Contact: ryan@stagerightlabs.com
 * Project: Parley
 * Date: 9/18/2014
 */
 
class Widget extends Eloquent\Model {

    protected $fillable = ['first_name', 'last_name', 'email'];
}