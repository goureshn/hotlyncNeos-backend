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
        Schema::table('ivr_call_center_skill_group', function (Blueprint $table) {
            if(!Schema::hasColumn('ivr_call_center_skill_group', 'duration')){
                $table->integer('duration')->nullable();
            }
            if(!Schema::hasColumn('ivr_call_center_skill_group', 'email')){
                $table->integer('email')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ivr_call_center_skill_group', function (Blueprint $table) {
            if(Schema::hasColumn('ivr_call_center_skill_group', 'duration')){
                $table->dropColumn('duration');
            }
            if(Schema::hasColumn('ivr_call_center_skill_group', 'email')){
                $table->dropColumn('email');
            }
        });
    }
};
