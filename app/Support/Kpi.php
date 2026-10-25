<?php

namespace App\Support;

use App\Models\ApiKey;
use App\Models\FileUnlock;
use App\Models\Team;
use App\Models\User;
use Laravel\Paddle\Subscription;

/** First-year targets: 3 to 4 companies on Teams, 20 to 30 Personal readers, no API customer yet. */
class Kpi
{
    public static function snapshot(): array
    {
        $p = config('atlas.prices');
        $subs = Subscription::query()->valid()->with('items')->get();
        $personal = $subs->where('type', 'personal');
        $teams = $subs->where('type', 'teams');
        $interval = fn ($s) => Billing::interval($s) ?? 'year';

        $personalYear = $personal->filter(fn ($s) => $interval($s) === 'year')->count();
        $personalMonth = $personal->count() - $personalYear;
        $seatsYear = $teams->filter(fn ($s) => $interval($s) === 'year')->sum(fn ($s) => Billing::quantity($s));
        $seatsMonth = $teams->sum(fn ($s) => Billing::quantity($s)) - $seatsYear;
        $compPersonal = User::where('plan_override', 'personal')->whereDate('override_until', '>=', today())->count();
        $compTeams = Team::whereDate('override_until', '>=', today())->count();
        $apiKeys = ApiKey::where('active', true)->where(fn ($q) => $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', today()))->count();

        $arr = $personalYear * $p['personal']['year'] + $personalMonth * $p['personal']['month'] * 12
             + $seatsYear * $p['teams']['year'] + $seatsMonth * $p['teams']['month'] * 12
             + $apiKeys * $p['api']['year'];
        $paidPlans = $personal->count() + $teams->count();

        return [
            'teams' => ['paid' => $teams->count(), 'comp' => $compTeams, 'seats' => $seatsYear + $seatsMonth, 'target' => config('atlas.kpi.teams')],
            'personal' => ['paid' => $personal->count(), 'comp' => $compPersonal, 'year' => $personalYear, 'month' => $personalMonth, 'target' => config('atlas.kpi.personal')],
            'api' => ['keys' => $apiKeys, 'target' => config('atlas.kpi.api')],
            'annual_share' => $paidPlans ? round(100 * ($personalYear + $teams->filter(fn ($s) => $interval($s) === 'year')->count()) / $paidPlans) : null,
            'arr' => $arr,
            'users' => User::count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'signups_30d' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'marketing_opt_in' => User::where('marketing_opt_in', true)->count(),
            'top_unlocks' => FileUnlock::where('month', FileUnlock::currentMonth())->selectRaw('iso3, count(*) as n')->groupBy('iso3')->orderByDesc('n')->limit(10)->get(),
        ];
    }
}
