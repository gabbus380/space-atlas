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
        $copy = $this->copy($list);

        if ($request->isMethod('get') || $request->isMethod('head')) {
            return response()->view('mail.unsubscribed', [
                'heading' => $copy['confirmHeading'],
                'detail' => $copy['confirmDetail'],
                'action' => $request->fullUrl(),
            ])->header('Cache-Control', 'no-store');
        }

        $this->apply($user, $list);

        if ($this->oneClick($request)) {
            return response('unsubscribed', 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Cache-Control' => 'no-store',
            ]);
        }

        return response()->view('mail.unsubscribed', [
            'heading' => $copy['heading'],
            'detail' => $copy['detail'],
            'action' => null,
        ])->header('Cache-Control', 'no-store');
    }

    private function apply(User $user, string $list): void
    {
        if ($list === 'news') {
            $user->forceFill(['marketing_opt_in' => false])->save();

            return;
        }

        $user->forceFill(['briefing_opt_out' => true])->save();
    }

    /** Apple Mail, Gmail, Yahoo, and Outlook post this exact body for the inbox button. */
    private function oneClick(Request $request): bool
    {
        $raw = $request->getContent();

        return str_contains($raw, 'List-Unsubscribe=One-Click')
            || $request->input('List-Unsubscribe') === 'One-Click';
    }

    /** @return array{confirmHeading: string, confirmDetail: string, heading: string, detail: string} */
    private function copy(string $list): array
    {
        if ($list === 'news') {
            return [
                'confirmHeading' => 'Stop the monthly note',
                'confirmDetail' => 'This stops the monthly note about Sovereign Space ATLAS. The Unsubscribe button in Apple Mail, Gmail, Outlook, and Yahoo does the same thing. Account and billing messages still arrive.',
                'heading' => 'The monthly note is off',
                'detail' => 'This address will no longer receive the monthly note. Account and billing messages still arrive.',
            ];
        }

        return [
            'confirmHeading' => 'Stop the morning briefing',
            'confirmDetail' => 'This stops the morning briefing. The Unsubscribe button in Apple Mail, Gmail, Outlook, and Yahoo does the same thing. You can turn it back on from the account page while a paid plan is active.',
            'heading' => 'The morning briefing is off',
            'detail' => 'This address will no longer receive the morning briefing. You can turn it back on from the account page while a paid plan is active.',
        ];
    }
}
