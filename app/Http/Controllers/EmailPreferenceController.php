<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EmailPreferenceController extends Controller
{
    public function update(Request $request, User $user, string $list): View|Response
    {
        abort_unless(in_array($list, ['news', 'briefing'], true), 404);

        if ($list === 'news') {
            $user->forceFill(['marketing_opt_in' => false])->save();
            $heading = 'The monthly note is off';
            $detail = 'This address will no longer receive the monthly note about Sovereign Space ATLAS. Account and billing messages still arrive.';
        } else {
            $user->forceFill(['briefing_opt_out' => true])->save();
            $heading = 'The morning briefing is off';
            $detail = 'This address will no longer receive the morning briefing. You can turn it back on from the account page while a paid plan is active.';
        }

        if ($request->isMethod('post')) {
            return response('unsubscribed', 200);
        }

        return view('mail.unsubscribed', ['heading' => $heading, 'detail' => $detail]);
    }
}
