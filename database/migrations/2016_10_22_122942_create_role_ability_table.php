<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoleAbilityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_ability', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('ability_id');
            $table->timestamps();

            $table->foreign('role_id', 'ra_role_id')->references('id')->on('roles')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('ability_id', 'ra_ability_id')->references('id')->on('abilities')->onDelete('cascade')->onUpdate('cascade');
        });

        DB::table('role_ability')->insert([
                [
                    'role_id'    => 1,
                    'ability_id' => 1,
                    'created_at' => '2016-08-18 13:02:00',
                    'updated_at' => '2016-08-18 13:02:00',
                ],
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('role_ability');
    }
}
