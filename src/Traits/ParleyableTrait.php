<?php

namespace Parley\Traits;

use ReflectionClass;
use Parley\Models\Thread;

trait ParleyableTrait
{
    /*
     * The parleyable object is responsible for defining its own alias.
     */
    abstract public function getAliasAttribute();
}
