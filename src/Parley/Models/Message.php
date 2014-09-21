<?php namespace SRLabs\Parley\Models;

class Message extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'parley_messages';

    protected $fillable   = ['body', 'is_read', 'parley_thread_id', 'author_id', 'author_type', 'author_alias'];

    public function getDates()
    {
        return ['created_at', 'updated_at', 'sent_at'];
    }

    public function thread()
    {
        return $this->belongsTo('SRLabs\Parley\Models\Thread', 'parley_thread_id');
    }

    public function is_read() {
        return (bool) $this->is_read;
    }

}
