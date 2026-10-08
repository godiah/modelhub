<?php

namespace App\Enums;

/** Why a ticket ended, set by staff when they resolve it. The best feedback the assistant gets: what it could have done better. */
enum SupportResolutionTag: string
{
    case BotCouldHaveAnswered = 'bot_could_have_answered';
    case DocMissing = 'doc_missing';
    case ToolMissing = 'tool_missing';
    case PolicyDecision = 'policy_decision';
    case Bug = 'bug';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BotCouldHaveAnswered => 'The assistant could have answered',
            self::DocMissing => 'A help article was missing or wrong',
            self::ToolMissing => 'The assistant could not look that up',
            self::PolicyDecision => 'A decision only staff can make',
            self::Bug => 'A bug in ModelHub',
            self::Other => 'Other',
        };
    }
}
