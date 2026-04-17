<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_task_type', function (Blueprint $table) {
            $table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignUuid('task_type_id')->constrained('task_types')->cascadeOnDelete();
            $table->primary(['task_id', 'task_type_id']);
            $table->timestamps();
        });

        if (Schema::hasColumn('tasks', 'task_type_id')) {
            $now = now();
            DB::table('tasks')
                ->whereNotNull('task_type_id')
                ->orderBy('id')
                ->chunk(500, function ($tasks) use ($now): void {
                    foreach ($tasks as $task) {
                        DB::table('task_task_type')->insert([
                            'task_id' => $task->id,
                            'task_type_id' => $task->task_type_id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                });

            Schema::table('tasks', function (Blueprint $table) {
                $table->dropForeign(['task_type_id']);
                $table->dropColumn('task_type_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignUuid('task_type_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        $rows = DB::table('task_task_type')
            ->select('task_id', 'task_type_id')
            ->orderBy('task_id')
            ->get();

        foreach ($rows as $row) {
            DB::table('tasks')
                ->where('id', $row->task_id)
                ->whereNull('task_type_id')
                ->update(['task_type_id' => $row->task_type_id]);
        }

        Schema::dropIfExists('task_task_type');
    }
};
