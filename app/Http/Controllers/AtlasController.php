<?php

namespace App\Http\Controllers;

use App\Models\CountryNote;
use App\Models\FileUnlock;
use App\Support\AtlasData;
use App\Support\Plans;
use App\Support\Watchlists;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AtlasController extends Controller
{
    /** The atlas page: the full edition for Personal and Teams, the open edition for everyone else. */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tier = Plans::tier($user);
        $file = config('atlas.html_path').'/'.(Plans::isPaid($tier) ? 'index.full.html' : 'index.open.html');
        abort_unless(is_file($file), 503, 'The atlas is being updated. Try again in a minute.');
        $html = file_get_contents($file);
        $ent = '<script>window.ATLAS_ENT='.json_encode(Plans::ent($user, $request, $tier), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES).';</script>';
        $pos = strpos($html, '<!--ATLAS_ENT-->');
        if ($pos !== false) {
            $html = substr_replace($html, $ent, $pos, strlen('<!--ATLAS_ENT-->'));
        }
        if ($user) {
            $user->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'private, no-cache',
            'Vary' => 'Cookie',
        ]);
    }

    /** The page's data, filtered to the reader's plan. */
    public function live(Request $request, AtlasData $data): JsonResponse
    {
        $user = $request->user();
        $tier = Plans::tier($user);
        $payload = $data->payload($user, $tier);
        $payload['ent'] = Plans::ent($user, $request, $tier);

        return response()->json($payload, 200, ['Cache-Control' => 'private, no-cache', 'Vary' => 'Cookie'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** Narrated editions are part of the full briefing. */
    public function audio(Request $request, string $file): BinaryFileResponse
    {
        abort_unless(preg_match('/^brief_\d{4}-\d{2}-\d{2}\.mp4$/', $file), 404);
        abort_unless(Plans::features(Plans::tier($request->user()))['audio'], 403);
        $path = config('atlas.audio_path').'/'.$file;
        abort_unless(is_file($path), 404);

        return response()->file($path, ['Content-Type' => 'video/mp4', 'Cache-Control' => 'private, max-age=86400']);
    }

    /** A free account opens a limited number of full country files each month. */
    public function unlock(Request $request, AtlasData $data, string $iso3): JsonResponse
    {
        $user = $request->user();
        $iso3 = strtoupper($iso3);
        abort_unless(preg_match('/^[A-Z]{3}$/', $iso3) && $data->hasCountryFile($iso3), 404);
        if (! $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Verify your email address to open full country files.'], 403);
        }
        $tier = Plans::tier($user);
        if ($tier === 'free') {
            $month = FileUnlock::currentMonth();
            $open = Plans::unlockedThisMonth($user);
            if (! in_array($iso3, $open, true)) {
                if (count($open) >= (int) config('atlas.free_files_per_month')) {
                    return response()->json(['message' => 'You have opened all your free country files for this month.', 'ent' => Plans::ent($user, $request, $tier)], 409);
                }
                FileUnlock::firstOrCreate(['user_id' => $user->id, 'iso3' => $iso3, 'month' => $month]);
            }
        }

        return response()->json(['ok' => true, 'ent' => Plans::ent($user, $request, $tier)]);
    }

    public function saveWatchlist(Request $request): JsonResponse
    {
        $user = $request->user();
        $tier = Plans::tier($user);
        if (! Plans::isPaid($tier)) {
            return response()->json(['message' => 'Personal and Teams keep the watchlist on your account.'], 402);
        }
        $data = $request->validate([
            'iso3' => ['present', 'array', 'max:80'],
            'iso3.*' => ['string', 'regex:/^[A-Za-z]{3}$/'],
        ]);

        return response()->json(['doc' => Watchlists::save($user, $tier, $data['iso3'])]);
    }

    public function scope(Request $request): JsonResponse
    {
        $user = $request->user();
        $tier = Plans::tier($user);
        if ($tier !== 'teams') {
            return response()->json(['message' => 'The team watchlist comes with Teams.'], 402);
        }
        $data = $request->validate(['scope' => ['required', Rule::in(['me', 'team'])]]);
        $user->forceFill(['watch_scope' => $data['scope']])->save();

        return response()->json(['ent' => Plans::ent($user, $request, $tier)]);
    }

    public function saveNote(Request $request, string $iso3): JsonResponse
    {
        $user = $request->user();
        if (Plans::tier($user) !== 'teams') {
            return response()->json(['message' => 'Team notes come with Teams.'], 402);
        }
        $iso3 = strtoupper($iso3);
        abort_unless(preg_match('/^[A-Z]{3}$/', $iso3), 404);
        $data = $request->validate(['text' => ['required', 'string', 'max:4000']]);
        $note = CountryNote::updateOrCreate(['team_id' => $user->team_id, 'iso3' => $iso3], ['text' => trim($data['text']), 'updated_by' => $user->id]);

        return response()->json(['doc' => $note->load('author')->toDoc()]);
    }

    public function deleteNote(Request $request, string $iso3): JsonResponse
    {
        $user = $request->user();
        if (Plans::tier($user) !== 'teams') {
            return response()->json(['message' => 'Team notes come with Teams.'], 402);
        }
        CountryNote::where('team_id', $user->team_id)->where('iso3', strtoupper($iso3))->delete();

        return response()->json(['ok' => true]);
    }

    /** Polled by the page after checkout until the webhook has landed. */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['tier' => Plans::tier($request->user())]);
    }
}
