<?php

namespace Parley\Traits;

use Parley\Exceptions\NonMemberObjectException;
use Parley\Exceptions\NonParleyableMemberException;
use Parley\Exceptions\NonReferableObjectException;
use ReflectionClass;


trait ParleyHelpersTrait
{
    /**
     * Helper Function: Determine if an member has the 'Parley\Traits\Parleyable' trait
     *
     * @param $object
     *
     * @return bool
     * @throws NonParleyableMemberException
     */
    protected function confirmObjectIsParleyable($object, $silent = false)
    {
        if (is_object($object)) {
            // Reflect on the Object
            $reflector = new ReflectionClass($object);

            // Does the member have the Parleyable trait? If not, thrown an exception.
            if (in_array('Parley\Traits\ParleyableTrait', $reflector->getTraitNames())) {
                return true;
            }
        }

        if (! $silent) {
            throw new NonParleyableMemberException;
        }

        return null;
    }


    /**
     * Confirm that an object has a valid Id field
     *
     * @param $object
     *
     * @return bool
     * @throws NonReferableObjectException
     */
    protected function confirmObjectHasId($object)
    {
        if (is_null($object->id)) {
            throw new NonReferableObjectException;
        }

        return true;
    }

    /**
     * Convert an unknown entity, or entities, into a flattened array.
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
