<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('note_note_type');

        Schema::dropIfExists('note_types');

        Schema::create('note_task_type', function (Blueprint $table) {
            $table->foreignUuid('note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignUuid('task_type_id')->constrained('task_types')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['note_id', 'task_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_task_type');

        Schema::create('note_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('note_note_type', function (Blueprint $table) {
            $table->foreignUuid('note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignUuid('note_type_id')->constrained('note_types')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['note_id', 'note_type_id']);
        });
    }
};
