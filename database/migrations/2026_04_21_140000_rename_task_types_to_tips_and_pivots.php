<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('task_types') && ! Schema::hasTable('tips')) {
            Schema::rename('task_types', 'tips');
        }

        if (Schema::hasTable('task_task_type') && ! Schema::hasTable('task_tip')) {
            $rows = DB::table('task_task_type')->get();
            Schema::drop('task_task_type');
            Schema::create('task_tip', function (Blueprint $table) {
                $table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignUuid('tip_id')->constrained('tips')->cascadeOnDelete();
                $table->timestamps();
                $table->primary(['task_id', 'tip_id']);
            });
            foreach ($rows as $row) {
                DB::table('task_tip')->insert([
                    'task_id' => $row->task_id,
                    'tip_id' => $row->task_type_id,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }

        if (Schema::hasTable('note_task_type') && ! Schema::hasTable('note_tip')) {
            $rows = DB::table('note_task_type')->get();
            Schema::drop('note_task_type');
            Schema::create('note_tip', function (Blueprint $table) {
                $table->foreignUuid('note_id')->constrained('notes')->cascadeOnDelete();
                $table->foreignUuid('tip_id')->constrained('tips')->cascadeOnDelete();
                $table->timestamps();
                $table->primary(['note_id', 'tip_id']);
            });
            foreach ($rows as $row) {
                DB::table('note_tip')->insert([
                    'note_id' => $row->note_id,
                    'tip_id' => $row->task_type_id,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tips') && ! Schema::hasTable('task_types')) {
            Schema::rename('tips', 'task_types');
        }

        if (Schema::hasTable('task_tip') && ! Schema::hasTable('task_task_type')) {
            $rows = DB::table('task_tip')->get();
            Schema::drop('task_tip');
            Schema::create('task_task_type', function (Blueprint $table) {
                $table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignUuid('task_type_id')->constrained('task_types')->cascadeOnDelete();
                $table->timestamps();
                $table->primary(['task_id', 'task_type_id']);
            });
            foreach ($rows as $row) {
                DB::table('task_task_type')->insert([
                    'task_id' => $row->task_id,
                    'task_type_id' => $row->tip_id,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }

        if (Schema::hasTable('note_tip') && ! Schema::hasTable('note_task_type')) {
            $rows = DB::table('note_tip')->get();
            Schema::drop('note_tip');
            Schema::create('note_task_type', function (Blueprint $table) {
                $table->foreignUuid('note_id')->constrained('notes')->cascadeOnDelete();
                $table->foreignUuid('task_type_id')->constrained('task_types')->cascadeOnDelete();
                $table->timestamps();
                $table->primary(['note_id', 'task_type_id']);
            });
            foreach ($rows as $row) {
                DB::table('note_task_type')->insert([
                    'note_id' => $row->note_id,
                    'task_type_id' => $row->tip_id,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }
    }
};
