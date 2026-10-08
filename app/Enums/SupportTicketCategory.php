<?php

namespace App\Enums;

/** What a ticket is about. The assistant suggests one from what it showed the member; the member can change it. */
enum SupportTicketCategory: string
{
    case PaymentIssue = 'payment_issue';
    case WithdrawalIssue = 'withdrawal_issue';
    case EscrowIssue = 'escrow_issue';
    case AccountAccess = 'account_access';
    case DisputeHelp = 'dispute_help';
    case ListingAppeal = 'listing_appeal';
    case Bug = 'bug';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PaymentIssue => 'A payment or licence',
            self::WithdrawalIssue => 'A withdrawal',
            self::EscrowIssue => 'Money held for a job',
            self::AccountAccess => 'Signing in or my account',
            self::DisputeHelp => 'A dispute',
            self::ListingAppeal => 'A model that was rejected or taken down',
            self::Bug => 'Something is broken',
            self::Other => 'Something else',
        };
    }

    /** Where a ticket of this kind starts, before the live records and the words in it are looked at. */
    public function baseSeverity(): SupportTicketSeverity
    {
        return match ($this) {
            self::AccountAccess => SupportTicketSeverity::High, // locked out: they cannot help themselves
            self::Other => SupportTicketSeverity::Low,
            default => SupportTicketSeverity::Normal,
        };
    }
}
