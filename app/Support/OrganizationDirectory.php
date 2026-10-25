<?php

namespace App\Support;

use App\Models\AtlasEdit;
use App\Models\User;

/** Reads the atlas organization directory and applies staff edits on top. */
class OrganizationDirectory
{
    /** Mail hosts that are not a company address. */
    public const FREE_HOSTS = [
        'gmail.com', 'googlemail.com', 'outlook.com', 'hotmail.com', 'live.com', 'msn.com',
        'yahoo.com', 'yahoo.co.uk', 'icloud.com', 'me.com', 'mac.com', 'proton.me', 'protonmail.com',
        'pm.me', 'aol.com', 'gmx.com', 'gmx.net', 'mail.com', 'yandex.com', 'yandex.ru',
    ];

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $orgs = app(AtlasData::class)->atlas()['orgs'] ?? [];
        $edits = AtlasEdit::query()->where('collection', 'orgs')->get()->keyBy('record_id');

        return array_map(function (array $org) use ($edits) {
            $edit = $edits->get($org['id'] ?? '');
            if ($edit) {
                $org = array_merge($org, $edit->payload ?? []);
            }
            $org['email_domains'] = self::domains($org);

            return $org;
        }, $orgs);
    }

    /** @return list<array<string, mixed>> */
    public static function search(string $query, int $limit = 8): array
    {
        $needle = self::normalize($query);
        if (strlen($needle) < 2) {
            return [];
        }
        $scored = [];
        foreach (self::all() as $org) {
            $name = self::normalize((string) ($org['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $score = 0;
            if ($name === $needle) {
                $score = 100;
            } elseif (str_starts_with($name, $needle) || str_starts_with($needle, $name)) {
                $score = 80;
            } elseif (str_contains($name, $needle) || str_contains($needle, $name)) {
                $score = 60;
            }
            if ($score > 0) {
                $scored[] = ['score' => $score, 'org' => $org];
            }
        }
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_map(fn ($row) => $row['org'], array_slice($scored, 0, $limit));
    }

    public static function find(string $id): ?array
    {
        foreach (self::all() as $org) {
            if (($org['id'] ?? null) === $id) {
                return $org;
            }
        }

        return null;
    }

    /** @param  array<string, mixed>  $org */
    public static function domains(array $org): array
    {
        $raw = $org['email_domains'] ?? [];
        if (is_string($raw)) {
            $raw = preg_split('/\s*,\s*/', $raw) ?: [];
        }

        return array_values(array_filter(array_map(
            fn ($host) => strtolower(ltrim(trim((string) $host), '@')),
            is_array($raw) ? $raw : [],
        )));
    }

    public static function emailHost(?string $email): string
    {
        $host = strtolower((string) substr((string) strrchr((string) $email, '@'), 1));

        return $host;
    }

    public static function isFreeMailbox(?string $email): bool
    {
        return in_array(self::emailHost($email), self::FREE_HOSTS, true);
    }

    /**
     * A matched organization requires a company email and a title.
     * Known email domains, when the directory has them, must match too.
     *
     * @param  array<string, mixed>  $org
     * @return array<string, string>
     */
    public static function claimErrors(array $org, User $user, ?string $title): array
    {
        $errors = [];
        if (trim((string) $title) === '') {
            $errors['job_title'] = 'Add your title at '.$org['name'].'.';
        }
        $domains = self::domains($org);
        $host = self::emailHost($user->email);
        if ($domains !== []) {
            if (! in_array($host, $domains, true)) {
                $errors['organization'] = 'Use your company email at '.implode(' or ', $domains).' to claim '.$org['name'].'.';
            }
        } elseif (self::isFreeMailbox($user->email)) {
            $errors['organization'] = 'A matched organization has to be claimed with a company email address, not a personal mailbox.';
        }

        return $errors;
    }

    public static function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
