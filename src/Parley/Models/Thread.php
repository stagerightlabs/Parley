<?php namespace SRLabs\Parley\Models;

use SRLabs\Parley\Models\Message;

class Thread extends \Eloquent {

    protected $table = 'parley_threads';

    protected $fillable   = ['subject', 'object_id', 'object_type', 'resolved_at', 'resolve_by_id', 'resolved_by_type' ];

    public function getDates()
    {
        return ['created_at', 'updated_at', 'resolved_at'];
    }

    public function messages()
    {
        return $this->hasMany('SRLabs\Parley\Models\Message', 'parley_thread_id');
    }

    public function reply() {
        $message = Message::create(

        );

        // Fire Reply Event

        return $thread;
    }

    public function isMember($object) {
        return (bool) \DB::table('parley_members')
            ->where('parleyable_id', $object->id)
            ->where('parleyable_type', $object->getModel())
            ->count();
    }

    public function addMember($member) {
        \DB::table('')->insert(array(
            'parley_thread_id' => $this->id,
            'parleyable_id' => $member->id,
            'parleyable_type' => $member->getModel()
        ));
    }

    public function resolve( $resolver ) {
        $this->is_resolved = 1;
        $this->resolved_by_id = $resolver->id;
        $this->resolved_by_type = $resolver->type;
        $this->save();

    }

    public function unresolve() {
        $this->is_resolved = 0;
        $this->resolved_by_id = 0;
        $this->resolved_by_type = '';
        $this->save();
    }

    public function is_resolved() {
        return (bool) $this->is_resolved;
    }

//    public function resolved_by() {
//        return $this->morphOne()
//    }
}
