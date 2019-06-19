<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('url');
            $table->string('deviceId');
            $table->string('version');
            $table->string('agent');
            $table->string('ip');
            $table->string('host');
            $table->string('method');
            $table->string('request')->nullable();
            $table->string('response');
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
        Schema::dropIfExists('log_requests');
    }
}
