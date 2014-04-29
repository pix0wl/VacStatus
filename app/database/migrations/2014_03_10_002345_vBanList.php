<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class VBanList extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
    Schema::create('vBanList', function($table) {
      $table->bigIncrements('id');
      $table->bigInteger('steam_user_id');
      $table->bigInteger('v_ban_user_id');
      $table->timestamps();
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('vBanList');
	}

}