<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('total_view_time_seconds')->default(0);
        });

        Schema::create('task_view_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('task_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'user_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_view_sessions');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('total_view_time_seconds');
        });
    }
};
