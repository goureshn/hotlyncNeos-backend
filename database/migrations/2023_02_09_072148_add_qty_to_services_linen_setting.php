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
        if(!Schema::hasColumn('services_linen_setting', 'qty')){
            Schema::table('services_linen_setting', function (Blueprint $table) {
                $table->integer('qty')->nullable();
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
        if(Schema::hasColumn('services_linen_setting', 'qty')){
            Schema::table('services_linen_setting', function (Blueprint $table) {
                $table->dropColumn('qty');
            });
        }
    }
};
