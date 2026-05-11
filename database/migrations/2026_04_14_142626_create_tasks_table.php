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

        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            //relacionamento com usuario e tipo de task
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('task_type_id')->nullable()->constrained()->nullOnDelete();

            // Dados básicos
            $table->string('name');
            $table->text('description')->nullable();

            // Tipagem
            $table->enum('status', ['pending', 'in_progress', 'completed', 'postponed', 'canceled'])->default('pending');
            $table->tinyInteger('priority')->default(1);

            // Controle de prazo/adiamentos
            $table->timestamp('original_due_date')->nullable();
            $table->timestamp('current_due_date')->nullable();
            $table->tinyInteger('postponed_count')->default(0);
            $table->timestamp('postponed_date_1')->nullable();
            $table->timestamp('postponed_date_2')->nullable();
            $table->timestamp('postponed_date_3')->nullable();

            // Websockets
            $table->boolean('is_being_viewed')->default(false);
            $table->timestamp('last_viewed_at')->nullable();

            // Soft Deletes
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};