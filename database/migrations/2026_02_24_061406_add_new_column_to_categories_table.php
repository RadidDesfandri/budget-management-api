<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table("categories", function (Blueprint $table) {
            $table->string("icon");
            $table->string("icon_color")->nullable();
            $table->string("background_color")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("categories", function (Blueprint $table) {
            $table->dropColumn("icon");
            $table->dropColumn("icon_color");
            $table->dropColumn("background_color");
        });
    }
};
