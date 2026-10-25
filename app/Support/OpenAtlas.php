<?php

namespace App\Support;

/**
 * What a free or guest reader is allowed to see in the atlas page.
 * Personal and Teams receive the unfiltered edition.
 */
class OpenAtlas
{
    /** Fields the open edition must not carry. The map still receives names, regions, and space budgets. */
    private const DROP = [
        'gdp', 'gdpYear', 'gdpNote', 'gerd', 'gerdYear',
        'def', 'defPct', 'defYear', 'natoPct',
        'spacePct', 'spacePc', 'sats', 'ready',
    ];

    public static function embedded(string $html): ?array
    {
        $token = 'window.ATLAS=';
        $pos = strpos($html, $token);
        if ($pos === false) {
            return null;
        }
        $start = $pos + strlen($token);
        $end = self::jsonEnd($html, $start);
        if ($end === null) {
            return null;
        }
        $data = json_decode(substr($html, $start, $end - $start + 1), true);

        return is_array($data) ? $data : null;
    }

    public static function redactHtml(string $html): string
    {
        $token = 'window.ATLAS=';
        $pos = strpos($html, $token);
        if ($pos === false) {
            return $html;
        }
        $start = $pos + strlen($token);
        $end = self::jsonEnd($html, $start);
        if ($end === null) {
            return $html;
        }
        $data = json_decode(substr($html, $start, $end - $start + 1), true);
        if (! is_array($data)) {
            return $html;
        }
        $json = json_encode(self::redact($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);

        return substr($html, 0, $start).$json.substr($html, $end + 1);
    }

    public static function redact(array $data): array
    {
        $countries = [];
        foreach ($data['countries'] ?? [] as $country) {
            if (! is_array($country)) {
                continue;
            }
            foreach (self::DROP as $key) {
                unset($country[$key]);
            }
            if (isset($country['esa']) && is_array($country['esa'])) {
                unset($country['esa']['cm25'], $country['esa']['cm22'], $country['esa']['contrib'], $country['esa']['contribYear']);
            }
            $countries[] = $country;
        }
        $data['countries'] = $countries;

        $files = [];
        foreach ($data['cfiles'] ?? [] as $code => $doc) {
            if (is_array($doc)) {
                $files[$code] = AtlasData::stub($doc);
            }
        }
        $data['cfiles'] = $files;
        $data['projects'] = [];
        $data['orgs'] = [];
        $data['blackhat'] = [];

        return $data;
    }

    private static function jsonEnd(string $html, int $start): ?int
    {
        $length = strlen($html);
        $depth = 0;
        $inString = false;
        $escaped = false;
        for ($i = $start; $i < $length; $i++) {
            $char = $html[$i];
            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }
                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }
                if ($char === '"') {
                    $inString = false;
                }
                continue;
            }
            if ($char === '"') {
                $inString = true;
                continue;
            }
            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }
}
