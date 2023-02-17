<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasColumn('common_guest', 'guest_img')){
            Schema::table('common_guest', function (Blueprint $table) {
                $table->longText('guest_img')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Schema::hasColumn('common_guest', 'guest_img')){
            Schema::table('common_guest', function (Blueprint $table) {
                $table->dropColumn('guest_img');
            });
        }
    }
};
