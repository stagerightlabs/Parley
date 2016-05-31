<?php

namespace Parley\Models;

use ReflectionClass;
use Parley\Exceptions\NonMemberObjectException;
use Parley\Exceptions\NonReferableObjectException;

class Message extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'parley_messages';

    protected $fillable = ['body', 'is_read', 'parley_thread_id', 'author_id', 'author_type', 'author_alias'];

    /**
     * Thread
     * @return [type] [description]
     */
    public function thread()
    {
        return $this->belongsTo('Parley\Models\Thread', 'parley_thread_id');
    }

    /**
     * Get the Authoring Object of this Message
     *
     * @return mixed
     */
    public function getAuthor()
    {
        if ($this->author_type == '') {
            return null;
        }

        return \App::make($this->author_type)->find($this->author_id);
    }

    public function setAuthor($alias, $member)
    {
        // Confirm that this is a valid author
        $this->isValidAuthor($member);

        // Associate the new Author with this Message
        $this->author_alias = $alias;
        $this->author_id = $member->id;
        $this->author_type = get_class($member);
        return $this->save();
    }

    /**
     * Make sure this Object is willing and able to contribute to this thread
     *
     * @param $author
     *
     * @throws NonMemberObjectException
     * @throws NonReferableObjectException
     */
    protected function isValidAuthor($author)
    {
        // Make sure the author has a valid id property
        if (is_null($author->id)) {
            throw new NonReferableObjectException;
        }

        // Make sure the author is in fact a member of this thread
        if (! $this->thread->isMember($author)) {
            throw new NonMemberObjectException;
        }
    }
}
