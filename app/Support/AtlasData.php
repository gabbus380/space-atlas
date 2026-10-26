<?php

namespace App\Support;

use App\Models\User;

/**
 * Reads the atlas datasets from resources/atlas/data and filters them by plan.
 *   live.json   the daily collections (briefings, country files, states, hubs, headlines)
 *   atlas.json  the full dataset the page is built from (countries, programs, registry, engagements, events)
 */
class AtlasData
{
    private ?array $live = null;

    private ?array $atlas = null;

    public function live(): array
    {
        return $this->live ??= $this->read('live.json');
    }

    public function atlas(): array
    {
        return $this->atlas ??= $this->read('atlas.json');
    }

    private function read(string $file): array
    {
        $path = config('atlas.data_path').DIRECTORY_SEPARATOR.$file;
        if (! is_file($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) ?: [];
    }

    public function generatedAt(): ?string
    {
        return $this->live()['generated_at'] ?? null;
    }

    /** Briefings, newest first. */
    public function briefings(): array
    {
        $b = $this->live()['collections']['briefings'] ?? [];
        uasort($b, fn ($x, $y) => strcmp((string) ($y['date'] ?? ''), (string) ($x['date'] ?? '')));

        return $b;
    }

    /**
     * The one full edition guests and free accounts can read and play.
     * It is the latest briefing filed in the current UTC month.
     * If this month has none yet, the newest edition on file is used.
     */
    public function demoBriefing(): ?array
    {
        $briefs = array_values($this->briefings());
        if ($briefs === []) {
            return null;
        }
        $month = now('UTC')->format('Y-m');
        $inMonth = array_values(array_filter($briefs, fn ($b) => str_starts_with((string) ($b['date'] ?? ''), $month)));
        $pool = $inMonth !== [] ? $inMonth : $briefs;
        usort($pool, function ($a, $b) {
            $byDate = strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));

            return $byDate !== 0 ? $byDate : strcmp((string) ($b['generated_at'] ?? ''), (string) ($a['generated_at'] ?? ''));
        });
        $pick = $pool[0];
        $pick['demo'] = true;

        return $pick;
    }

    public function demoAudioFile(): ?string
    {
        $date = $this->demoBriefing()['date'] ?? null;

        return is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? 'brief_'.$date.'.mp4' : null;
    }

    /** One country file: the daily version when there is one, else the one the page was built with. */
    public function countryFile(string $iso3): ?array
    {
        return $this->live()['collections']['country'][$iso3] ?? $this->atlas()['cfiles'][$iso3] ?? null;
    }

    public function hasCountryFile(string $iso3): bool
    {
        return isset($this->live()['collections']['country'][$iso3]) || isset($this->atlas()['cfiles'][$iso3]);
    }

    /** Collections for the page, filtered to what the reader's plan includes. */
    public function payload(?User $user, string $tier): array
    {
        $c = $this->live()['collections'] ?? [];
        $paid = Plans::isPaid($tier);
        $out = [];
        foreach (['states', 'hubs', 'feeds'] as $k) {
            if (isset($c[$k])) {
                $out[$k] = $c[$k];
            }
        }
        $briefs = $this->briefings();
        $unlocked = [];
        if ($paid) {
            $out['briefings'] = array_slice($briefs, 0, (int) config('atlas.archive_days'), true);
            $out['country'] = $c['country'] ?? [];
            foreach (['orgs', 'projects', 'blackhat'] as $k) {
                if (! empty($c[$k])) {
                    $out[$k] = $c[$k];
                }
            }
        } else {
            $demo = $this->demoBriefing();
            $out['briefings'] = $demo ? [$demo['date'] => $demo] : (object) [];
            $unlocked = $tier === 'free' && $user ? Plans::unlockedThisMonth($user) : [];
            $files = [];
            foreach ($c['country'] ?? [] as $iso => $doc) {
                $files[$iso] = in_array($iso, $unlocked, true) ? $doc : self::stub($doc);
            }
            foreach ($unlocked as $iso) {
                if (! isset($files[$iso]) && ($doc = $this->countryFile($iso))) {
                    $files[$iso] = $doc;
                }
            }
            $out['country'] = $files;
        }

        $notesStamp = null;
        if ($user && $paid) {
            $doc = Watchlists::doc($user, $tier);
            $out['config'] = $doc ? ['focus' => $doc] : (object) [];
        }
        if ($user && $tier === 'teams') {
            $notes = $user->team->notes()->with('author')->get();
            $out['notes'] = $notes->isEmpty() ? (object) [] : $notes->mapWithKeys(fn ($n) => [$n->iso3 => $n->toDoc()])->all();
            $notesStamp = $notes->max('updated_at')?->timestamp.':'.$notes->count();
        }
        foreach ($out as $k => $v) {
            if (is_array($v) && $v === []) {
                $out[$k] = (object) [];
            }
        }

        return [
            'generated_at' => $this->generatedAt(),
            'source' => 'Sovereign Space ATLAS, EMMA Aerospace Consulting',
            'rev' => md5(json_encode([$this->generatedAt(), $tier, $unlocked, $out['config'] ?? null, $notesStamp, $user?->watch_scope])),
            'collections' => $out,
        ];
    }

    /** The overview of a country file that the open edition shows. */
    public static function stub(array $d): array
    {
        $g = $d['government'] ?? [];
        $s = $d['strategy'] ?? [];
        $st = $d['stakeholders'] ?? [];
        $lead = array_values(array_filter($g['nodes'] ?? [], fn ($n) => ! empty($n['lead'])));

        return [
            'iso3' => $d['iso3'] ?? null,
            'asOf' => $d['asOf'] ?? null,
            'verify' => $d['verify'] ?? null,
            'locked' => true,
            'government' => [
                'head_of_state' => $g['head_of_state'] ?? null,
                'head_of_government' => $g['head_of_government'] ?? null,
                'nodes' => array_slice($lead, 0, 1),
            ],
            'strategy' => $s ? array_intersect_key($s, array_flip(['title', 'year', 'status', 'horizon', 'owner'])) : null,
            'counts' => [
                'goals' => count($s['goals'] ?? []),
                'gov' => count($g['nodes'] ?? []),
                'stake' => count($st['nodes'] ?? []),
                'sources' => count($s['sources'] ?? []) + count($g['sources'] ?? []) + count($st['sources'] ?? []),
            ],
        ];
    }

    /** Headline and summary of an edition, with counts of what the full edition holds. */
    public static function briefStub(array $b): array
    {
        $items = count($b['global'] ?? []);
        $desks = 0;
        foreach ($b['regions'] ?? [] as $r) {
            $n = count($r['items'] ?? []);
            $items += $n;
            $desks += $n ? 1 : 0;
        }
        foreach ($b['countries'] ?? [] as $cb) {
            $items += count($cb['items'] ?? []);
        }

        return [
            'date' => $b['date'] ?? null,
            'edition' => $b['edition'] ?? '06:00 UTC',
            'generated_at' => $b['generated_at'] ?? null,
            'headline' => $b['headline'] ?? '',
            'summary' => $b['summary'] ?? [],
            'bottom_line' => $b['bottom_line'] ?? null,
            'locked' => true,
            'counts' => [
                'items' => $items,
                'desks' => $desks,
                'countries' => count($b['countries'] ?? []),
                'milestones' => count($b['milestones'] ?? []),
                'sources' => $b['stats']['sources'] ?? null,
            ],
        ];
    }
}
