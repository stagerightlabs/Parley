<?php namespace SRLabs\Parley\Models;

class Message extends \Illuminate\Database\Eloquent {

    protected $table = 'parley_messages';

    protected $fillable   = ['body', 'is_read', 'parley_thread_id', 'owner_id', 'owner_type', 'sent_at' ];

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
