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
        Schema::table('interview_queue', function (Blueprint $table) {
            // Check if the old column exists before trying to rename it.
            if (Schema::hasColumn('interview_queue', 'queue_position')) {
                $table->renameColumn('queue_position', 'order_key');
            }
        });

        Schema::table('interview_queue', function (Blueprint $table) {
            // Use the change() method for safer type modification.
            // This requires the doctrine/dbal package.
            if (Schema::hasColumn('interview_queue', 'order_key')) {
                $table->double('order_key')->default(0)->change();
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
        Schema::table('interview_queue', function (Blueprint $table) {
            // Use the change() method to revert the type first.
            if (Schema::hasColumn('interview_queue', 'order_key')) {
                $table->integer('order_key')->change();
            }
        });

        Schema::table('interview_queue', function (Blueprint $table) {
            // Check if the new column exists before trying to rename it back.
            if (Schema::hasColumn('interview_queue', 'order_key')) {
                $table->renameColumn('order_key', 'queue_position');
            }
        });
    }
};
