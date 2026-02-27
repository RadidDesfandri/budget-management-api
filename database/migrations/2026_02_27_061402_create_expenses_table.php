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
        Schema::create("expenses", function (Blueprint $table) {
            $table->id();
            $table->string("title");
            $table->decimal("amount", 10, 2);
            $table->date("expense_date");
            $table->text("description");
            $table
                ->enum("status", ["pending", "approved", "rejected"])
                ->default("pending");
            $table->string("receipt_url")->nullable();

            $table->dateTime("approved_at")->nullable();
            $table->dateTime("rejected_at")->nullable();
            $table->string("rejected_reason")->nullable();

            $table
                ->foreignId("user_id")
                ->constrained("users")
                ->cascadeOnDelete();
            $table
                ->foreignId("approved_by")
                ->nullable()
                ->constrained("users")
                ->cascadeOnDelete();
            $table
                ->foreignId("category_id")
                ->constrained("categories")
                ->cascadeOnDelete();
            $table
                ->foreignId("organization_id")
                ->constrained("organizations")
                ->cascadeOnDelete();
            $table
                ->foreignId("budget_id")
                ->nullable()
                ->constrained("budgets")
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("expenses");
    }
};
