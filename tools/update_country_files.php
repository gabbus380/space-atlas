<?php

declare(strict_types=1);

/**
 * Import the reviewed public country-file projection from space-atlas-editions.
 *
 * Usage: php tools/update_country_files.php /path/to/database.json
 */

const EXPECTED_FILES = 235;
const EXPECTED_VERIFIED = 62;
const EXPECTED_PARTIAL = 173;
const JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP;

$source = $argv[1] ?? null;
if (! is_string($source) || ! is_file($source)) {
    fail('Usage: php tools/update_country_files.php /path/to/database.json');
}

$root = dirname(__DIR__);
$atlasPath = "$root/resources/atlas/data/atlas.json";
$livePath = "$root/resources/atlas/data/live.json";
$countsPath = "$root/resources/atlas/data/counts.json";
$htmlPaths = [
    'full' => "$root/resources/atlas/index.full.html",
    'open' => "$root/resources/atlas/index.open.html",
];

try {
    $database = readJson($source);
    $atlas = readJson($atlasPath);
    $live = readJson($livePath);
    $counts = readJson($countsPath);
    $html = array_map(
        fn (string $path): string => readText($path),
        $htmlPaths,
    );

    $files = validateImport($database, $atlas);
    $counts['cfiles'] = count($files);
    $atlas['cfiles'] = $files;
    $live['collections']['country'] = $files;

    $fullAtlas = replaceEmbeddedAtlas($html['full'], $files);
    $openAtlas = replaceEmbeddedAtlas($html['open'], array_map('countryStub', $files));

    $outputs = [
        $atlasPath => encodeJson($atlas)."\n",
        $livePath => encodeJson($live)."\n",
        $countsPath => encodeJson($counts)."\n",
        $htmlPaths['full'] => updateHtmlMetadata($fullAtlas, $counts),
        $htmlPaths['open'] => updateHtmlMetadata($openAtlas, $counts),
    ];

    // Nothing is written until every source and generated output has passed validation.
    foreach ($outputs as $path => $content) {
        if ($content === '') {
            fail("Refusing to write an empty output: $path");
        }
    }
    foreach ($outputs as $path => $content) {
        writeAtomically($path, $content);
    }

    fwrite(STDOUT, sprintf(
        "Imported %d country files (%d verified, %d partial).\n",
        count($files),
        EXPECTED_VERIFIED,
        EXPECTED_PARTIAL,
    ));
} catch (Throwable $exception) {
    fail($exception->getMessage());
}

/** @return array<string, mixed> */
function readJson(string $path): array
{
    $decoded = json_decode(readText($path), true, 512, JSON_THROW_ON_ERROR);
    if (! is_array($decoded)) {
        throw new RuntimeException("Expected a JSON object in $path");
    }

    return $decoded;
}

function readText(string $path): string
{
    $content = file_get_contents($path);
    if ($content === false) {
        throw new RuntimeException("Unable to read $path");
    }

    return $content;
}

/**
 * @param  array<string, mixed>  $database
 * @param  array<string, mixed>  $atlas
 * @return array<string, array<string, mixed>>
 */
function validateImport(array $database, array $atlas): array
{
    if (($database['schema'] ?? null) !== 2) {
        throw new RuntimeException('Country database schema must be 2.');
    }

    $files = $database['files'] ?? null;
    if (! is_array($files) || count($files) !== EXPECTED_FILES) {
        throw new RuntimeException('Country database must contain exactly '.EXPECTED_FILES.' public files.');
    }

    $countryCodes = [];
    foreach ($atlas['countries'] ?? [] as $country) {
        if (is_array($country) && is_string($country['iso3'] ?? null)) {
            $countryCodes[] = $country['iso3'];
        }
    }
    sort($countryCodes);
    $fileCodes = array_keys($files);
    sort($fileCodes);
    if ($countryCodes !== $fileCodes) {
        throw new RuntimeException('Country file keys must exactly match the countries on the atlas map.');
    }

    $statuses = ['verified' => 0, 'partial' => 0];
    foreach ($files as $iso3 => $file) {
        if (! is_array($file) || ($file['iso3'] ?? null) !== $iso3) {
            throw new RuntimeException("Country file $iso3 has a missing or mismatched iso3.");
        }
        $verify = $file['verify'] ?? null;
        if (! array_key_exists((string) $verify, $statuses)) {
            throw new RuntimeException("Country file $iso3 has an unsupported verification status.");
        }
        $statuses[$verify]++;
        foreach (['asOf', 'government', 'stakeholders', 'strategy'] as $field) {
            if (! array_key_exists($field, $file)) {
                throw new RuntimeException("Country file $iso3 is missing $field.");
            }
        }
        validateMedia($iso3, $file['media'] ?? null);
    }

    if ($statuses['verified'] !== EXPECTED_VERIFIED || $statuses['partial'] !== EXPECTED_PARTIAL) {
        throw new RuntimeException(sprintf(
            'Expected %d verified and %d partial files; received %d and %d.',
            EXPECTED_VERIFIED,
            EXPECTED_PARTIAL,
            $statuses['verified'],
            $statuses['partial'],
        ));
    }

    ksort($files);

    return $files;
}

function validateMedia(string $iso3, mixed $media): void
{
    if ($media === null) {
        return;
    }
    if (! is_array($media) || ! is_array($media['outlets'] ?? null) || ! is_array($media['none'] ?? null)) {
        throw new RuntimeException("Country file $iso3 has malformed media data.");
    }
    foreach ($media['outlets'] as $outlet) {
        if (! is_array($outlet) || ! is_string($outlet['name'] ?? null)) {
            throw new RuntimeException("Country file $iso3 has a malformed media outlet.");
        }
        $url = $outlet['url'] ?? null;
        if (! is_string($url) || ! isWebUrl($url)) {
            throw new RuntimeException("Country file $iso3 has an unsafe media URL.");
        }
        foreach (['space_url', 'rss'] as $field) {
            $value = $outlet[$field] ?? null;
            if ($value !== null && $value !== '' && (! is_string($value) || ! isWebUrl($value))) {
                throw new RuntimeException("Country file $iso3 has an unsafe media $field.");
            }
        }
    }
}

function isWebUrl(string $url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false
        && (str_starts_with($url, 'https://') || str_starts_with($url, 'http://'));
}

/** @param array<string, mixed> $file */
function countryStub(array $file): array
{
    $government = is_array($file['government'] ?? null) ? $file['government'] : [];
    $strategy = is_array($file['strategy'] ?? null) ? $file['strategy'] : [];
    $stakeholders = is_array($file['stakeholders'] ?? null) ? $file['stakeholders'] : [];
    $lead = array_values(array_filter(
        $government['nodes'] ?? [],
        fn (mixed $node): bool => is_array($node) && ! empty($node['lead']),
    ));

    return [
        'iso3' => $file['iso3'] ?? null,
        'asOf' => $file['asOf'] ?? null,
        'verify' => $file['verify'] ?? null,
        'locked' => true,
        'government' => [
            'head_of_state' => $government['head_of_state'] ?? null,
            'head_of_government' => $government['head_of_government'] ?? null,
            'nodes' => array_slice($lead, 0, 1),
        ],
        'strategy' => $strategy === [] ? null : array_intersect_key(
            $strategy,
            array_flip(['title', 'year', 'status', 'horizon', 'owner']),
        ),
        'counts' => [
            'goals' => count($strategy['goals'] ?? []),
            'gov' => count($government['nodes'] ?? []),
            'stake' => count($stakeholders['nodes'] ?? []),
            'sources' => count($strategy['sources'] ?? [])
                + count($government['sources'] ?? [])
                + count($stakeholders['sources'] ?? []),
        ],
    ];
}

/**
 * @param  array<string, array<string, mixed>>  $files
 */
function replaceEmbeddedAtlas(string $html, array $files): string
{
    $token = 'window.ATLAS=';
    $start = strpos($html, $token);
    if ($start === false) {
        throw new RuntimeException('Embedded window.ATLAS payload is missing.');
    }
    $jsonStart = $start + strlen($token);
    $jsonEnd = jsonObjectEnd($html, $jsonStart);
    $atlas = json_decode(substr($html, $jsonStart, $jsonEnd - $jsonStart + 1), true, 512, JSON_THROW_ON_ERROR);
    if (! is_array($atlas)) {
        throw new RuntimeException('Embedded window.ATLAS payload is invalid.');
    }
    $atlas['cfiles'] = $files;

    return substr($html, 0, $jsonStart).encodeJson($atlas).substr($html, $jsonEnd + 1);
}

function jsonObjectEnd(string $text, int $start): int
{
    $depth = 0;
    $inString = false;
    $escaped = false;
    $length = strlen($text);
    for ($position = $start; $position < $length; $position++) {
        $character = $text[$position];
        if ($inString) {
            if ($escaped) {
                $escaped = false;
            } elseif ($character === '\\') {
                $escaped = true;
            } elseif ($character === '"') {
                $inString = false;
            }
            continue;
        }
        if ($character === '"') {
            $inString = true;
        } elseif ($character === '{') {
            $depth++;
        } elseif ($character === '}' && --$depth === 0) {
            return $position;
        }
    }

    throw new RuntimeException('Embedded window.ATLAS JSON object is not terminated.');
}

/** @param array<string, mixed> $counts */
function updateHtmlMetadata(string $html, array $counts): string
{
    $html = str_replace('81 country files', EXPECTED_FILES.' country files', $html);
    $replacement = 'window.ATLAS_COUNTS='.encodeJson($counts).';';
    $updated = preg_replace('/window\.ATLAS_COUNTS=\{.*?\};/', $replacement, $html, 1, $replacements);
    if (! is_string($updated) || $replacements !== 1) {
        throw new RuntimeException('Unable to update window.ATLAS_COUNTS.');
    }

    return $updated;
}

/** @param array<string, mixed> $value */
function encodeJson(array $value): string
{
    return json_encode($value, JSON_FLAGS | JSON_THROW_ON_ERROR);
}

function writeAtomically(string $path, string $content): void
{
    $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
    if (file_put_contents($temporary, $content, LOCK_EX) === false || ! rename($temporary, $path)) {
        @unlink($temporary);
        throw new RuntimeException("Unable to atomically write $path");
    }
}

function fail(string $message): never
{
    fwrite(STDERR, $message."\n");
    exit(1);
}
