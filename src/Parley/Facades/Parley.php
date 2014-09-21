<?php namespace SRLabs\Parley\Facades;

use Illuminate\Support\Facades\Facade;

class Parley extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'parley'; }

}