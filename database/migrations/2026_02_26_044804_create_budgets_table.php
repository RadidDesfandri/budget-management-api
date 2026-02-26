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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->decimal("amount", 10, 2);
            $table->date("month");
            $table->foreignId("category_id")->constrained("categories")->cascadeOnDelete();
            $table->foreignId("organization_id")->constrained("organizations")->cascadeOnDelete();

            $table->unique(["month", "category_id", "organization_id"]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
