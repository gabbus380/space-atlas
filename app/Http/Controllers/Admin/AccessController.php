<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Coupon;
use App\Support\OrganizationDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccessController extends Controller
{
    public function keys(): View
    {
        return view('admin.keys.index', ['keys' => ApiKey::latest()->get()]);
    }

    public function organizations(Request $request): JsonResponse
    {
        $matches = OrganizationDirectory::search(trim((string) $request->query('q', '')), 8);

        return response()->json(array_map(function (array $org) {
            $account = OrganizationDirectory::mainAccount($org);

            return [
                'id' => $org['id'] ?? '',
                'name' => $org['name'] ?? '',
                'iso3' => $org['iso3'] ?? '',
                'email' => $account?->email,
                'account' => $account?->name,
            ];
        }, $matches));
    }

    public function issueKey(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'contact_email' => ['nullable', 'email'],
            'daily_quota' => ['required', 'integer', 'min:100', 'max:1000000'],
            'max_users' => ['required', 'integer', 'min:1', 'max:10000'],
            'expires_on' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        [$key, $plain] = ApiKey::issue($data);

        return back()->with('status', 'API key for '.$key->name.' created. Copy it now, it is shown only once.')->with('plain_key', $plain);
    }

    public function toggleKey(ApiKey $key): RedirectResponse
    {
        $key->forceFill(['active' => ! $key->active])->save();

        return back()->with('status', $key->name.' is '.($key->active ? 'active' : 'switched off').'.');
    }

    public function coupons(): View
    {
        return view('admin.coupons.index', ['coupons' => Coupon::latest()->get()]);
    }

    public function storeCoupon(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9-]+$/', Rule::unique('coupons', 'code')],
            'name' => ['required', 'string', 'max:160'],
            'months' => ['required', 'integer', 'in:1,3,12'],
            'plan' => ['required', 'in:personal,teams'],
            'max_redemptions' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'expires_on' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        Coupon::create([
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'months' => $data['months'],
            'plan' => $data['plan'],
            'max_redemptions' => $data['max_redemptions'] ?? null,
            'expires_on' => $data['expires_on'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Coupon '.strtoupper($data['code']).' is ready.');
    }

    public function toggleCoupon(Coupon $coupon): RedirectResponse
    {
        $coupon->forceFill(['active' => ! $coupon->active])->save();

        return back()->with('status', $coupon->code.' is '.($coupon->active ? 'active' : 'switched off').'.');
    }
}
