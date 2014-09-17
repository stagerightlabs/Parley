<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ParleyTables extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        // Create the 'Parley Models' table
        Schema::create('parley_threads', function($table){
            $table->increments('id');
            $table->string('subject');
            $table->integer('object_id')->nullable();
            $table->string('object_type')->nullable();
            $table->string('type')->nullable();
            $table->boolean('resolved_at')->nullable();
            $table->integer('resolved_by_id')->nullable();
            $table->string('resolved_by_type')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->timestamp('deleted_at')->nullable();
        });

        // Create the 'Parley Messages' table
        Schema::create('parley_messages', function($table){
            $table->increments('id');
            $table->text('body');
            $table->boolean('is_read')->default(0);
            $table->integer('parley_thread_id');
            $table->integer('owner_id');
            $table->string('owner_type');
            $table->timestamp('sent_at');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });

        // Create the 'Parley Members' table
        Schema::create('parley_members', function($table){
            $table->integer('parley_thread_id');
            $table->integer('parleyable_id');
            $table->string('parleyable_type');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        //Drop the 'Parley Models' table
        Schema::drop('parley_threads');

        // Drop the 'Parley Messages' table
        Schema::drop('parley_messages');

        // Drop the 'Parley Memebers' table
        Schema::drop('parley_members');
	}

}
