<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->json('watchlist')->nullable();
            $table->timestamp('watchlist_updated_at')->nullable();
            // complimentary or invoiced access granted by EMMA (pilots, invoice customers)
            $table->date('override_until')->nullable();
            $table->unsignedInteger('override_seats')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('organization')->nullable()->after('email');
            $table->boolean('is_admin')->default(false)->after('password');
            $table->foreignId('team_id')->nullable()->after('is_admin')->constrained('teams')->nullOnDelete();
            $table->timestamp('team_joined_at')->nullable()->after('team_id');
            $table->string('watch_scope', 8)->default('me')->after('team_joined_at');
            $table->json('watchlist')->nullable()->after('watch_scope');
            $table->timestamp('watchlist_updated_at')->nullable()->after('watchlist');
            $table->boolean('marketing_opt_in')->default(false)->after('watchlist_updated_at');
            $table->timestamp('terms_accepted_at')->nullable()->after('marketing_opt_in');
            // complimentary Personal access granted by EMMA
            $table->string('plan_override', 16)->nullable()->after('terms_accepted_at');
            $table->date('override_until')->nullable()->after('plan_override');
            $table->timestamp('last_seen_at')->nullable()->after('override_until');
        });

        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('token_hash', 64)->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'email']);
        });

        Schema::create('country_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->char('iso3', 3);
            $table->text('text');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['team_id', 'iso3']);
        });

        Schema::create('file_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('iso3', 3);
            $table->char('month', 7);
            $table->timestamps();
            $table->unique(['user_id', 'iso3', 'month']);
            $table->index(['month', 'iso3']);
        });

        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_email')->nullable();
            $table->string('prefix', 16);
            $table->string('key_hash', 64)->unique();
            $table->unsignedInteger('daily_quota');
            $table->unsignedInteger('max_users');
            $table->boolean('active')->default(true);
            $table->date('expires_on')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('api_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('count')->default(0);
            $table->unique(['api_key_id', 'day']);
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('address')->nullable();
            $table->string('order_reference')->nullable();
            $table->date('ordered_on')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('next_billed_at')->nullable()->after('ends_at');
            $table->string('billing_interval', 8)->nullable()->after('next_billed_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['next_billed_at', 'billing_interval']);
        });
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('api_usage');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('file_unlocks');
        Schema::dropIfExists('country_notes');
        Schema::dropIfExists('team_invitations');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn(['organization', 'is_admin', 'team_joined_at', 'watch_scope', 'watchlist', 'watchlist_updated_at',
                'marketing_opt_in', 'terms_accepted_at', 'plan_override', 'override_until', 'last_seen_at']);
        });
        Schema::dropIfExists('teams');
    }
};
