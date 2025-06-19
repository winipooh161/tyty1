<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
   Schema::table('acquired_templates', function (Blueprint $table) {
    $table->boolean('is_series')->default(false);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acquired_templates', function (Blueprint $table) {
            //
        });
    }
};
