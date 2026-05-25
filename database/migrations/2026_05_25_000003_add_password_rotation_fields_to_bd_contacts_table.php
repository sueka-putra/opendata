<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bd_contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('bd_contacts', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('password');
            }
            if (!Schema::hasColumn('bd_contacts', 'password_generated_at')) {
                $table->timestamp('password_generated_at')->nullable()->after('must_change_password');
            }
            if (!Schema::hasColumn('bd_contacts', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('password_generated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bd_contacts', function (Blueprint $table) {
            $drop = [];
            foreach (['must_change_password', 'password_generated_at', 'password_changed_at'] as $column) {
                if (Schema::hasColumn('bd_contacts', $column)) {
                    $drop[] = $column;
                }
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
