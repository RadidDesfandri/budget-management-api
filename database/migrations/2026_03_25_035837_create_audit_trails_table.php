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
        Schema::create("audit_trails", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("organization_id")
                ->constrained("organizations")
                ->cascadeOnDelete();
            $table
                ->foreignId("user_id")
                ->nullable()
                ->constrained("users")
                ->nullOnDelete();
            $table->string("action_type", 50);
            $table->text("description");
            $table->json("metadata")->nullable();
            $table->string("ip_address", 45)->nullable();
            $table->timestamps();

            // Indexes for common filter queries
            $table->index(["organization_id", "created_at"]);
            $table->index(["organization_id", "action_type"]);
            $table->index(["organization_id", "user_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("audit_trails");
    }
};
