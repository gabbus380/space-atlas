<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use App\Models\User;
use App\Support\Kpi;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AtlasAdmin extends Command
{
    protected $signature = 'atlas:admin {action : make-admin | grant | api-key | kpi} {email?} {--days=30} {--name=} {--quota=} {--users=}';

    protected $description = 'ATLAS operations: make an admin, grant Personal, issue an API key, print the KPI snapshot';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = $email ? User::whereRaw('lower(email) = ?', [Str::lower($email)])->first() : null;

        switch ($this->argument('action')) {
            case 'make-admin':
                if (! $user) {
                    return $this->fail1('Create the account at /register first.');
                }
                $user->forceFill(['is_admin' => true, 'email_verified_at' => $user->email_verified_at ?? now()])->save();
                $this->info($user->email.' can open /admin.');
                break;
            case 'grant':
                if (! $user) {
                    return $this->fail1('No account uses that address.');
                }
                $user->forceFill(['plan_override' => 'personal', 'override_until' => today()->addDays((int) $this->option('days'))])->save();
                $this->info($user->email.' has Personal until '.$user->override_until->toDateString().'.');
                break;
            case 'api-key':
                [$key, $plain] = ApiKey::issue([
                    'name' => $this->option('name') ?: ($email ?: 'API customer'), 'contact_email' => $email,
                    'daily_quota' => (int) ($this->option('quota') ?: config('atlas.api.starter_quota')),
                    'max_users' => (int) ($this->option('users') ?: config('atlas.api.starter_users')),
                    'expires_on' => today()->addYear(),
                ]);
                $this->info('Key for '.$key->name.' (shown once):');
                $this->line($plain);
                break;
            case 'kpi':
                $k = Kpi::snapshot();
                $this->table(['Measure', 'Now', 'Year-one target'], [
                    ['Companies on Teams', $k['teams']['paid'].' paid, '.$k['teams']['comp'].' pilot', implode(' to ', $k['teams']['target'])],
                    ['Personal readers', $k['personal']['paid'].' paid, '.$k['personal']['comp'].' complimentary', implode(' to ', $k['personal']['target'])],
                    ['API customers', $k['api']['keys'], '0'],
                    ['Billed yearly', $k['annual_share'] === null ? 'n/a' : $k['annual_share'].'%', ''],
                    ['Annual run rate', '$'.number_format($k['arr']), ''],
                    ['Accounts', $k['users'].' ('.$k['verified'].' verified, '.$k['signups_30d'].' in 30 days)', ''],
                ]);
                break;
            default:
                return $this->fail1('Unknown action.');
        }

        return self::SUCCESS;
    }

    private function fail1(string $msg): int
    {
        $this->error($msg);

        return self::FAILURE;
    }
}
