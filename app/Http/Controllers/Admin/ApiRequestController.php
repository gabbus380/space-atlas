<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ApiRequest;
use App\Support\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $requests = ApiRequest::query()
            ->with('user')
            ->when(isset(ApiRequest::STATUSES[$status]), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.api-requests.index', [
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    public function show(ApiRequest $apiRequest): View
    {
        $apiRequest->load(['user', 'apiKey']);

        return view('admin.api-requests.show', [
            'apiRequest' => $apiRequest,
            'canIssue' => Staff::allows(auth()->user(), 'keys') && $apiRequest->api_key_id === null,
        ]);
    }

    public function update(Request $request, ApiRequest $apiRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(ApiRequest::STATUSES))],
            'staff_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $apiRequest->forceFill([
            'status' => $data['status'],
            'staff_note' => $data['staff_note'] ?? null,
            'handled_at' => now(),
            'handled_by' => $request->user()->id,
        ])->save();

        return back()->with('status', 'API request from '.$apiRequest->organization.' marked '.ApiRequest::STATUSES[$data['status']].'.');
    }

    public function issueKey(Request $request, ApiRequest $apiRequest): RedirectResponse
    {
        abort_unless(Staff::allows($request->user(), 'keys'), 404);
        if ($apiRequest->api_key_id) {
            return back()->with('status', 'This request already has an API key.');
        }
        $data = $request->validate([
            'daily_quota' => ['required', 'integer', 'min:100', 'max:1000000'],
            'max_users' => ['required', 'integer', 'min:1', 'max:10000'],
            'expires_on' => ['nullable', 'date', 'after:today'],
        ]);
        [$key, $plain] = ApiKey::issue([
            'name' => $apiRequest->organization,
            'contact_email' => $apiRequest->email,
            'daily_quota' => $data['daily_quota'],
            'max_users' => $data['max_users'],
            'expires_on' => $data['expires_on'] ?? null,
            'notes' => 'Issued from API request #'.$apiRequest->id,
        ]);
        $apiRequest->forceFill([
            'api_key_id' => $key->id,
            'status' => 'approved',
            'handled_at' => now(),
            'handled_by' => $request->user()->id,
        ])->save();

        return back()->with('status', 'API key issued for '.$key->name.'. Copy it now, it is shown only once.')->with('plain_key', $plain);
    }
}
