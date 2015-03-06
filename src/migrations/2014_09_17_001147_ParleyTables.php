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
            $table->string('hash',10)->nullable()->unique();
            $table->string('subject');
            $table->integer('object_id')->nullable();
            $table->string('object_type')->nullable();
            $table->string('type')->nullable();
            $table->boolean('closed_at')->nullable();
            $table->integer('closed_by_id')->nullable();
            $table->string('closed_by_type')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->timestamp('deleted_at')->nullable();
        });

        // Create the 'Parley Messages' table
        Schema::create('parley_messages', function($table){
            $table->increments('id');
            $table->string('hash', 10)->nullable()->unique();
            $table->text('body');
            $table->string('author_alias');
            $table->integer('author_id');
            $table->string('author_type');
            $table->integer('parley_thread_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
        });

        // Create the 'Parley Members' table
        Schema::create('parley_members', function($table){
            $table->integer('parley_thread_id');
            $table->integer('parleyable_id');
            $table->string('parleyable_type');
            $table->boolean('is_read')->default(0);
            $table->boolean('notified')->default(0);
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
