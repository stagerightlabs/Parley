<?php

use Illuminate\Database\Eloquent;
use Parley\Exceptions\NonParleyableMemberException;
use Parley\Models\Thread;

class TestingEnvironmentTests extends ParleyTestCase
{

    public function test_database_exists()
    {
        $thread = \DB::table('parley_threads')->insert(array(
            'subject' => 'test thread',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ));

        $threads = Thread::all();
        $this->assertEquals($threads->count(), 1);
        $this->assertEquals($threads->first()->id, 1);
    }

    public function test_object_instantiation()
    {
        $object = new NonParleyableMemberException();
        $this->assertInstanceOf('Parley\Exceptions\NonParleyableMemberException', $object);
    }
}
