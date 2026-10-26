<?php

namespace App\Support;

use App\Models\User;

/** Who on the EMMA staff may open which part of /admin. */
class Staff
{
    public const ADMIN = 'admin';

    public const MANAGER = 'manager';

    public const SALES = 'sales';

    public const ANALYST = 'analyst';

    public const ROLES = [
        self::ADMIN => 'Admin',
        self::MANAGER => 'Manager',
        self::SALES => 'Sales',
        self::ANALYST => 'Data analyst',
    ];

    /** @var array<string, list<string>> */
    private const ABILITIES = [
        'dashboard' => [self::ADMIN, self::MANAGER, self::SALES, self::ANALYST],
        'customers' => [self::ADMIN, self::MANAGER, self::SALES],
        'keys' => [self::ADMIN, self::MANAGER],
        'roles' => [self::ADMIN, self::MANAGER],
        'settings' => [self::ADMIN],
        'data' => [self::ADMIN, self::MANAGER, self::ANALYST],
    ];

    public static function allows(?User $user, string $ability): bool
    {
        $role = $user?->staffRole();

        return $role !== null && in_array($role, self::ABILITIES[$ability] ?? [], true);
    }

    /** @return array<string, string> href => label */
    public static function navigation(?User $user): array
    {
        $links = [];
        if (self::allows($user, 'dashboard')) {
            $links['/admin'] = 'Scoreboard';
        }
        if (self::allows($user, 'customers')) {
            $links['/admin/users'] = 'Users';
            $links['/admin/teams'] = 'Teams';
            $links['/admin/withdrawals'] = 'Withdrawals';
            $links['/admin/coupons'] = 'Coupons';
            $links['/admin/api-requests'] = 'API requests';
        }
        if (self::allows($user, 'keys')) {
            $links['/admin/api-keys'] = 'API keys';
        }
        if (self::allows($user, 'data')) {
            $links['/admin/directory'] = 'Directory';
        }
        if (self::allows($user, 'settings')) {
            $links['/admin/connectors'] = 'Connectors';
            $links['/admin/sign-in'] = 'Sign-in';
        }

        return $links;
    }
}
