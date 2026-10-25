<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Notifications\WithdrawalReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/** The online withdrawal function for consumers in the EU, the EEA and the UK. */
class WithdrawalController extends Controller
{
    public function create(Request $request): View
    {
        return view('legal.withdraw', ['user' => $request->user()]);
    }

    public function store(Request $request): View
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:300'],
            'order_reference' => ['nullable', 'string', 'max:120'],
            'ordered_on' => ['nullable', 'date'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);
        $w = Withdrawal::create($data + ['user_id' => $request->user()?->id]);
        Notification::route('mail', config('atlas.contact'))->notify(new WithdrawalReceived($w, false));
        Notification::route('mail', $w->email)->notify(new WithdrawalReceived($w, true));

        return view('legal.withdraw-done', ['w' => $w]);
    }
}
