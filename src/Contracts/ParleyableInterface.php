<?php

namespace Parley\Contracts;

interface ParleyableInterface
{
    /**
     * Each Parleyable object must implement an 'alias' accessor which is used
     * as a display name associated with messages sent by this model.
     *
     * @return mixed
     */
    public function getParleyAliasAttribute();

    /**
     * Each Parleyable object must provide an integer id value.  Usually this is can be
     * as simple as "return $this->attributes['id'];".
     *
     * @return int
     */
    public function getParleyIdAttribute();
}
