<?php

namespace App\Enums;

/** What a payment gateway says happened to a payment request. */
enum GatewayState: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case TimedOut = 'timed_out';
}
