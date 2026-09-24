<?php

namespace App\Enums;

enum EngagementStatus: string
{
    case EmployerAccepted = 'employer_accepted';
    case ApplicantAccepted = 'applicant_accepted';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Disputed = 'disputed';
    case Settled = 'settled';

    public function label(): string
    {
        return match ($this) {
            self::EmployerAccepted => 'Pending',
            self::ApplicantAccepted => 'Accepted',
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Cancelled => 'Withdrawn',
            self::Disputed => 'Disputed',
            self::Settled => 'Settled',
        };
    }

    public function badgeClasses(): array
    {
        return match ($this) {
            self::EmployerAccepted, self::ApplicantAccepted => [
                'bg' => 'bg-accent/10',
                'text' => 'text-accent',
                'border' => 'border-accent/20',
            ],
            self::Active => [
                'bg' => 'bg-secondary/10',
                'text' => 'text-secondary',
                'border' => 'border-secondary/20',
            ],
            self::Completed => [
                'bg' => 'bg-green-100',
                'text' => 'text-green-800',
                'border' => 'border-green-200',
            ],
            self::Cancelled => [
                'bg' => 'bg-red-100',
                'text' => 'text-red-800',
                'border' => 'border-red-200',
            ],
            self::Disputed => [
                'bg' => 'bg-rose-100',
                'text' => 'text-rose-800',
                'border' => 'border-rose-200',
            ],
            self::Settled => [
                'bg' => 'bg-blue-100',
                'text' => 'text-blue-800',
                'border' => 'border-blue-200',
            ],
        };
    }

    public function iconPath(): string
    {
        return match ($this) {
            self::EmployerAccepted, self::ApplicantAccepted => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />',
            self::Active => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
            self::Completed => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
            self::Cancelled => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
            self::Disputed => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />',
            self::Settled => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />',
        };
    }
}
