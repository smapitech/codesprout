<?php

namespace App\Enums;

enum RoleName: string
{
    case Administrator = 'administrator';
    case Teacher = 'teacher';
    case Parent = 'parent';
    case Child = 'child';

    /**
     * @return array<int, self>
     */
    public static function priority(): array
    {
        return [
            self::Administrator,
            self::Teacher,
            self::Parent,
            self::Child,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Teacher => 'Teacher',
            self::Parent => 'Parent',
            self::Child => 'Child',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Administrator => 'admin.dashboard',
            self::Teacher => 'teacher.dashboard',
            self::Parent => 'parent.dashboard',
            self::Child => 'child.dashboard',
        };
    }
}
