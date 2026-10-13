<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('profile_photo_path')->nullable()->after('phone');
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->timestamp('password_changed_at')->nullable()->after('must_change_password');
        });

        // Give every existing user a unique username made from their email
        $taken = [];
        foreach (DB::table('users')->orderBy('id')->get(['id', 'email']) as $u) {
            $base = strtolower(preg_replace('/[^A-Za-z0-9._-]/', '', strstr($u->email, '@', true) ?: $u->email));
            $base = substr($base !== '' ? $base : 'user', 0, 40);

            $name = $base;
            $n = 0;
            while (isset($taken[$name])) {
                $name = $base . ($u->id + $n++);
            }
            $taken[$name] = true;

            DB::table('users')->where('id', $u->id)->update(['username' => $name]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'phone', 'profile_photo_path', 'must_change_password', 'password_changed_at']);
        });
    }
};