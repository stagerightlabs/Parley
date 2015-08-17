<?php

namespace Parley\Models;

use Parley\Traits\ParleyHelpersTrait;
use ReflectionClass;
use Parley\Exceptions\NonMemberObjectException;
use Parley\Exceptions\NonReferableObjectException;

class Message extends \Illuminate\Database\Eloquent\Model
{
    use ParleyHelpersTrait;

    /*****************************************************************************
     * Eloquent Configuration
     *****************************************************************************/
    protected $table = 'parley_messages';
    protected $fillable = ['body', 'is_read', 'parley_thread_id', 'author_id', 'author_type', 'author_alias'];
    protected $dates = ['created_at', 'updated_at', 'sent_at'];

    /**
     * The thread that owns this message
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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

    /**
     * Set the authoring object for this message
     *
     * @param string|null $alias
     * @param mixed $author
     * @return bool
     * @throws NonMemberObjectException
     * @throws NonReferableObjectException
     */
    public function setAuthor($author, $alias = null)
    {
        // Make sure the author has a valid primary key
        $this->confirmObjectIsReferable($author);

        // If an author alias was explicitly specified, use that value instead of the default model alias
        $alias  = ($alias ? $alias : $author->parley_alias);

        // Associate the new Author with this Message
        $this->author_alias = $alias;
        $this->author_id = $author->getParleyIdAttribute();
        $this->author_type = get_class($author);

        return $this->save();
    }
}
