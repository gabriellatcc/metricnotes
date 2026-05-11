<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('sessions', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('id')->change();
        });

        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $newUuid = (string) Str::orderedUuid();

            DB::table('users')->where('id', $user->id)->update(['id' => $newUuid]);
            DB::table('sessions')->where('user_id', $user->id)->update(['user_id' => $newUuid]);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->id()->change();
        });

        Schema::enableForeignKeyConstraints();
    }
};