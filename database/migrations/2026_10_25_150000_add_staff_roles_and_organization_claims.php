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
            $table->string('staff_role', 16)->nullable()->after('is_admin');
            $table->string('organization_id', 160)->nullable()->after('organization');
            $table->string('job_title', 160)->nullable()->after('organization_id');
            $table->boolean('show_on_organization')->default(false)->after('job_title');
        });

        DB::table('users')->where('is_admin', true)->whereNull('staff_role')->update(['staff_role' => 'admin']);

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('status', 16)->default('open')->after('message');
            $table->text('staff_note')->nullable()->after('status');
            $table->timestamp('handled_at')->nullable()->after('staff_note');
            $table->foreignId('handled_by')->nullable()->after('handled_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handled_by');
            $table->dropColumn(['status', 'staff_note', 'handled_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['staff_role', 'organization_id', 'job_title', 'show_on_organization']);
        });
    }
};
