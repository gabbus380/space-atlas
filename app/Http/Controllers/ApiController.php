<?php

namespace App\Http\Controllers;

use App\Support\AtlasData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The ATLAS API (v1). The same records the atlas shows, as JSON, with a timestamp and sources on every
 * record. Licensed under an Order Form: internal use, a set number of people, no redistribution.
 */
class ApiController extends Controller
{
    public function __construct(private AtlasData $data) {}

    private function out(Request $request, array $rows, array $meta = []): JsonResponse
    {
        $total = count($rows);
        $limit = max(1, min(500, (int) $request->query('limit', 100)));
        $offset = max(0, (int) $request->query('offset', 0));

        return $this->json(array_slice(array_values($rows), $offset, $limit), $meta + ['count' => min($limit, max(0, $total - $offset)), 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
    }

    private function json($data, array $meta = []): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => $meta + [
                'generated_at' => $this->data->generatedAt(),
                'source' => 'Sovereign Space ATLAS, EMMA Aerospace Consulting',
                'license' => 'Licensed for internal use under your Order Form. Do not redistribute.',
            ],
        ], 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function updated(): string
    {
        return $this->data->generatedAt() ?? (($this->data->atlas()['meta']['built'] ?? date('Y-m-d')).'T00:00:00Z');
    }

    private function iso($v): array
    {
        return array_values(array_filter(array_map(fn ($x) => is_array($x) ? ($x['iso3'] ?? null) : $x, (array) $v)));
    }

    public function countries(Request $request): JsonResponse
    {
        $a = $this->data->atlas();
        $member = [];
        foreach ($a['groups'] ?? [] as $id => $g) {
            if (($g['kind'] ?? '') === 'lens') {
                continue;
            }
            foreach ($g['members'] ?? [] as $iso) {
                $member[$iso][] = $id;
            }
        }
        $rows = [];
        foreach ($a['countries'] ?? [] as $c) {
            $c = array_diff_key($c, array_flip(['focus']));
            $c['memberships'] = $member[$c['iso3']] ?? [];
            $c['has_country_file'] = $this->data->hasCountryFile($c['iso3']);
            $c['updated_at'] = $this->updated();
            $rows[] = $c;
        }
        if ($r = $request->query('region')) {
            $rows = array_filter($rows, fn ($c) => ($c['region'] ?? null) === $r);
        }
        if ($m = $request->query('member')) {
            $rows = array_filter($rows, fn ($c) => in_array($m, $c['memberships'], true));
        }

        return $this->out($request, $rows, ['groups' => array_map(fn ($g) => ['label' => $g['label'] ?? '', 'kind' => $g['kind'] ?? ''], array_filter($a['groups'] ?? [], fn ($g) => ($g['kind'] ?? '') !== 'lens'))]);
    }

    public function countryFile(string $iso3): JsonResponse
    {
        $doc = $this->data->countryFile(strtoupper($iso3));
        if (! $doc) {
            return response()->json(['error' => ['code' => 'not_found', 'message' => 'No country file for '.strtoupper($iso3).'.']], 404);
        }
        $doc['updated_at'] = ($doc['asOf'] ?? null) ? $doc['asOf'].'T00:00:00Z' : $this->updated();

        return $this->json($doc);
    }

    private function merged(string $key): array
    {
        $base = $this->data->atlas()[$key] ?? [];
        $live = $this->data->live()['collections'][$key] ?? [];
        $m = [];
        foreach ($base as $x) {
            $m[$x['id'] ?? md5(json_encode($x))] = $x;
        }
        foreach ($live as $id => $x) {
            if (! empty($x['deleted'])) {
                unset($m[$id]);
            } else {
                $m[$id] = array_merge($m[$id] ?? [], $x, ['id' => $id]);
            }
        }

        return array_values($m);
    }

    public function programs(Request $request): JsonResponse
    {
        $rows = array_map(fn ($p) => $p + ['updated_at' => $this->updated()], $this->merged('projects'));
        if ($iso = strtoupper((string) $request->query('iso3'))) {
            $rows = array_filter($rows, fn ($p) => in_array($iso, $this->iso($p['members'] ?? []), true) || in_array($iso, $this->iso($p['lead'] ?? []), true));
        }
        if ($cat = $request->query('category')) {
            $rows = array_filter($rows, fn ($p) => ($p['category'] ?? null) === $cat);
        }

        return $this->out($request, $rows);
    }

    public function organizations(Request $request): JsonResponse
    {
        $rows = array_map(fn ($o) => $o + ['updated_at' => ($o['added'] ?? null) ?: $this->updated()], $this->merged('orgs'));
        if ($iso = strtoupper((string) $request->query('iso3'))) {
            $rows = array_filter($rows, fn ($o) => ($o['iso3'] ?? null) === $iso);
        }
        if ($cat = $request->query('category')) {
            $rows = array_filter($rows, fn ($o) => ($o['category'] ?? null) === $cat);
        }
        if ($dom = $request->query('domain')) {
            $rows = array_filter($rows, fn ($o) => in_array($dom, $o['domains'] ?? [], true));
        }

        return $this->out($request, $rows);
    }

    public function engagements(Request $request): JsonResponse
    {
        $rows = array_map(function ($e) {
            $out = ['id' => $e['id'] ?? null, 'company' => $e['competitor'] ?? null, 'iso3' => $this->iso($e['iso3'] ?? []), 'date' => $e['date'] ?? null,
                'type' => $e['type'] ?? null, 'title' => $e['title'] ?? null, 'summary' => $e['summary'] ?? null, 'sources' => $e['sources'] ?? [],
                'updated_at' => $this->updated()];

            return $out;
        }, $this->merged('blackhat'));
        usort($rows, fn ($x, $y) => strcmp((string) $y['date'], (string) $x['date']));
        if ($iso = strtoupper((string) $request->query('iso3'))) {
            $rows = array_filter($rows, fn ($e) => in_array($iso, $e['iso3'], true));
        }
        if ($co = $request->query('company')) {
            $rows = array_filter($rows, fn ($e) => stripos((string) $e['company'], $co) !== false);
        }
        if ($since = $request->query('since')) {
            $rows = array_filter($rows, fn ($e) => (string) $e['date'] >= $since);
        }

        return $this->out($request, $rows);
    }

    public function briefings(Request $request): JsonResponse
    {
        $rows = array_map(fn ($b) => ['date' => $b['date'] ?? null, 'headline' => $b['headline'] ?? null, 'generated_at' => $b['generated_at'] ?? null], $this->data->briefings());

        return $this->out($request, $rows);
    }

    public function briefing(string $date): JsonResponse
    {
        $all = $this->data->briefings();
        $b = $date === 'latest' ? (reset($all) ?: null) : collect($all)->first(fn ($x) => ($x['date'] ?? null) === $date);
        if (! $b) {
            return response()->json(['error' => ['code' => 'not_found', 'message' => 'No edition for '.$date.'. The archive keeps '.config('atlas.archive_days').' days.']], 404);
        }
        unset($b['audio'], $b['audio_script'], $b['audio_sections']);
        $b['updated_at'] = $b['generated_at'] ?? $this->updated();

        return $this->json($b);
    }

    public function events(Request $request): JsonResponse
    {
        $comm = $this->data->atlas()['comm'] ?? [];
        $rows = [];
        foreach ($comm['iac'] ?? [] as $i) {
            $rows[] = ['type' => 'congress', 'name' => 'International Astronautical Congress '.($i['year'] ?? ''), 'edition' => $i['n'] ?? null,
                'dates' => $i['dates'] ?? null, 'city' => $i['city'] ?? null, 'iso3' => $i['iso3'] ?? null, 'status' => $i['status'] ?? null,
                'theme' => $i['theme'] ?? null, 'url' => 'https://www.iafastro.org/events/iac/'];
        }
        foreach ($comm['conferences'] ?? [] as $c) {
            $rows[] = ['type' => 'conference', 'id' => $c['id'] ?? null, 'name' => $c['name'] ?? null, 'organizer' => $c['organizer'] ?? null,
                'cadence' => $c['cadence'] ?? null, 'next' => $c['next'] ?? null, 'url' => $c['url'] ?? null];
        }
        $latest = $this->data->briefings();
        foreach ((reset($latest) ?: [])['milestones'] ?? [] as $m) {
            $rows[] = ['type' => 'decision', 'name' => $m['event'] ?? ($m['title'] ?? null), 'date' => $m['date'] ?? null, 'iso3' => $this->iso($m['iso3'] ?? []), 'where' => $m['where'] ?? null];
        }
        if ($t = $request->query('type')) {
            $rows = array_filter($rows, fn ($r) => $r['type'] === $t);
        }
        $rows = array_map(fn ($r) => $r + ['updated_at' => $this->updated()], $rows);

        return $this->out($request, $rows);
    }
}
