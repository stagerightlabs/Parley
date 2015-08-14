<?php

namespace Parley\Traits;

use Parley\Exceptions\NonParleyableMemberException;
use Parley\Exceptions\NonReferableObjectException;

trait ParleyHelpersTrait
{
    /**
     * Helper Function: Determine if an member has the 'Parley\Traits\Parleyable' trait
     *
     * @param $object
     * @param bool $silent
     * @return bool
     * @throws NonParleyableMemberException
     */
    protected function confirmObjectIsParleyable($object, $silent = false)
    {
        // Does the object implement the ParleyableInterface? If not, thrown an exception.
        if (is_object($object) && in_array('Parley\Contracts\ParleyableInterface', class_implements($object))) {
            return true;
        }

        if (! $silent) {
            throw new NonParleyableMemberException;
        }

        return null;
    }

    /**
     * For now, To be referable an object must have a primary key that is an integer
     *
     * @param $object
     * @param bool|false $silent
     * @return bool|null
     * @throws NonReferableObjectException
     */
    protected function confirmObjectIsReferable($object, $silent = false)
    {
        if (is_int($object->getKey())) {
            return true;
        }

        if (! $silent) {
            throw new NonReferableObjectException;
        }

        return null;
    }

    /**
     * Convert an unknown entity (or entities) into a flattened array.
     *
     * @param $group
     * @return array
     */
    protected function ensureArrayable($group)
    {
        if (! is_array($group)) {
            $group = [$group];
        }

        return array_flatten($group);
    }
}
