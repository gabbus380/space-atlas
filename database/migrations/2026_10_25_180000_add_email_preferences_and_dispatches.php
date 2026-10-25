<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('briefing_opt_out')->default(false)->after('marketing_opt_in');
        });

        Schema::create('email_dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('dedupe')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_dispatches');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('briefing_opt_out');
        });
    }
};
