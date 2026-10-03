<?php

namespace App\Support\SupportChat;

/**
 * Mock support requests for the visual preview of "My requests", the public contact form and the staff queue.
 * DESIGN SCAFFOLDING ONLY: every person, reference and amount is invented, and this class is deleted when real tickets
 * (A-07 in modelhub-support/docs/10) replace it. The shapes are what the views render.
 */
final class PreviewTickets
{
    /** @return array<string, array<string, mixed>> keyed by reference */
    public static function all(): array
    {
        $trail = [
            'type' => 'trail',
            'title' => 'Large Iron Gate – Gate Entrance',
            'subtitle' => 'Standard licence · Payment MH7K2P9QX1',
            'amount' => 'Ksh1,500',
            'status' => ['label' => 'Needs staff attention', 'tone' => 'amber'],
            'steps' => [
                ['state' => 'done', 'label' => 'M-Pesa prompt sent', 'detail' => 'Today, 14:31'],
                ['state' => 'done', 'label' => 'Payment received', 'detail' => 'Ksh1,500 · receipt SHK1•••23 · 14:32'],
                ['state' => 'stuck', 'label' => 'Licence issued', 'detail' => 'Stopped here', 'note' => "Your money arrived, but the licence couldn't be created. Staff need to review this payment."],
                ['state' => 'pending', 'label' => 'Download unlocked'],
            ],
            'actions' => [['label' => 'Open payment', 'href' => '#']],
        ];

        $chat = [
            ['who' => 'You', 'text' => 'I paid but nothing happened'],
            ['who' => 'Assistant', 'text' => "I found it. Your M-Pesa payment went through, but the licence wasn't created, so the download is still locked. It needs a person at ModelHub to review."],
            ['who' => 'You', 'text' => 'Ask staff to look at this'],
        ];

        return [
            'SUP-1042' => [
                'ref' => 'SUP-1042', 'kind' => 'ticket', 'category' => 'Payment needs review', 'severity' => 'high',
                'status' => 'pending_member', 'source' => 'bot', 'verified' => true,
                'requester' => ['name' => 'Achieng Odhiambo', 'contact' => 'a••••@gmail.com · 07•• ••• 482', 'since' => 'Member since Mar 2026'],
                'summary' => 'Paid Ksh1,500 for Large Iron Gate. Payment received, licence not created.',
                'age_days' => 0, 'updated' => 'Today, 15:05', 'assignee' => 'Grace W.', 'unread' => true,
                'entities' => [['icon' => 'currency-dollar', 'label' => 'Payment MH7K2P9QX1'], ['icon' => 'cube', 'label' => 'Large Iron Gate']],
                'cards' => [$trail],
                'facts' => [
                    ['k' => 'Payment status', 'v' => 'Needs review (money arrived, no licence)', 'flag' => 'warn'],
                    ['k' => 'Arrived', 'v' => 'Ksh1,500 · receipt SHK1•••23'],
                    ['k' => 'Licence', 'v' => 'None issued'],
                    ['k' => 'Earlier requests', 'v' => 'None'],
                ],
                'rationale' => null,
                'messages' => [
                    ['from' => 'system', 'at' => 'Today, 14:40', 'text' => 'You handed this chat to staff.'],
                    ['from' => 'staff', 'name' => 'Grace', 'role' => 'ModelHub support', 'at' => 'Today, 15:05', 'text' => "Hi Achieng, thanks for sending this. I can see your payment arrived but the licence wasn't created. I'm checking it now.\n\nCould you confirm the phone number you paid from ends in 482?"],
                    ['from' => 'note', 'name' => 'Grace', 'at' => 'Today, 15:06', 'text' => 'Receipt matches the gateway. Checking whether the buyer already holds this licence.'],
                ],
                'transcript' => $chat,
            ],

            'SUP-1043' => [
                'ref' => 'SUP-1043', 'kind' => 'approval', 'category' => 'Refund request', 'severity' => 'normal',
                'status' => 'open', 'source' => 'bot', 'verified' => true,
                'requester' => ['name' => 'Achieng Odhiambo', 'contact' => 'a••••@gmail.com · 07•• ••• 482', 'since' => 'Member since Mar 2026'],
                'summary' => 'Refund for Alarm Clock 01 – Clock Time PBR (Ksh510). File would not open in Blender.',
                'age_days' => 1, 'updated' => 'Yesterday, 16:12', 'assignee' => null, 'unread' => false,
                'entities' => [['icon' => 'currency-dollar', 'label' => 'Payment MH4D8R2LZ6'], ['icon' => 'cube', 'label' => 'Alarm Clock 01']],
                'cards' => [[
                    'type' => 'trail',
                    'title' => 'Alarm Clock 01 – Clock Time PBR',
                    'subtitle' => 'Standard licence · Payment MH4D8R2LZ6',
                    'amount' => 'Ksh510',
                    'status' => ['label' => 'Paid', 'tone' => 'green'],
                    'steps' => [
                        ['state' => 'done', 'label' => 'M-Pesa prompt sent', 'detail' => '8 Sep, 11:02'],
                        ['state' => 'done', 'label' => 'Payment received', 'detail' => 'Ksh510 · receipt SIA8•••71'],
                        ['state' => 'done', 'label' => 'Licence issued', 'detail' => 'LIC-4F2K-9QX1-7M3D · 25 days ago'],
                        ['state' => 'done', 'label' => 'Downloaded', 'detail' => '3 downloads · last 8 Sep, 11:09'],
                    ],
                ]],
                'facts' => [
                    ['k' => 'Licence', 'v' => 'Active · issued 25 days ago'],
                    ['k' => 'Downloads', 'v' => '3 (last 8 Sep, 11:09)', 'flag' => 'warn'],
                    ['k' => 'Seller\'s share', 'v' => 'Ksh434 of Ksh510 (15% commission)'],
                    ['k' => 'Seller\'s balance', 'v' => 'Covers the refund'],
                    ['k' => 'Earlier refunds by this buyer', 'v' => 'None'],
                ],
                'rationale' => 'Buyer says the file would not open in Blender and was not as described. Purchase is outside the 7-day hold. Files were downloaded.',
                'proposed_reason' => 'Buyer reported the file would not open in Blender; not as described.',
                'messages' => [
                    ['from' => 'system', 'at' => 'Yesterday, 16:12', 'text' => 'The assistant asked staff to review a refund. The member was told staff decide and that nothing is promised.'],
                ],
                'transcript' => [
                    ['who' => 'You', 'text' => 'The file I bought will not open in Blender. I want my money back'],
                    ['who' => 'Assistant', 'text' => "Refunds are for a file that is broken or not as described. Staff decide each case, so I can't promise one. I've asked staff to review it."],
                ],
            ],

            'SUP-1051' => [
                'ref' => 'SUP-1051', 'kind' => 'ticket', 'category' => 'Account access', 'severity' => 'high',
                'status' => 'open', 'source' => 'form', 'verified' => false,
                'requester' => ['name' => 'Someone using wanjiru.k@example.com', 'contact' => 'Email typed into the form. Not checked.', 'since' => 'No account linked'],
                'summary' => "Lost my phone, can't sign in, no recovery codes. Please reset my two-step sign-in.",
                'age_days' => 2, 'updated' => 'Thursday, 09:30', 'assignee' => null, 'unread' => false,
                'entities' => [],
                'cards' => [],
                'facts' => [],
                'rationale' => null,
                'messages' => [
                    ['from' => 'system', 'at' => 'Thursday, 09:30', 'text' => 'Sent from the contact form by a visitor who was not signed in.'],
                ],
                'transcript' => [],
            ],

            'SUP-1060' => [
                'ref' => 'SUP-1060', 'kind' => 'ticket', 'category' => 'Scam or abuse', 'severity' => 'urgent',
                'status' => 'open', 'source' => 'bot', 'verified' => true,
                'requester' => ['name' => 'Peter Njoroge', 'contact' => 'p••••@gmail.com', 'since' => 'Member since Jan 2026'],
                'summary' => 'A freelancer asked me to pay outside ModelHub to "save the fee".',
                'age_days' => 0, 'updated' => 'Today, 10:20', 'assignee' => null, 'unread' => false,
                'entities' => [['icon' => 'briefcase', 'label' => 'Project: Lobby visualisation']],
                'cards' => [], 'facts' => [], 'rationale' => null,
                'messages' => [['from' => 'system', 'at' => 'Today, 10:20', 'text' => 'Marked urgent because the message mentions paying outside ModelHub.']],
                'transcript' => [],
            ],

            'SUP-1038' => [
                'ref' => 'SUP-1038', 'kind' => 'ticket', 'category' => 'Escrow refund overdue', 'severity' => 'high',
                'status' => 'open', 'source' => 'bot', 'verified' => true,
                'requester' => ['name' => 'Wanjiru Kamau', 'contact' => 'w••••@gmail.com · 07•• ••• 215', 'since' => 'Member since Feb 2026'],
                'summary' => 'Cancelled project 11 days ago. Escrow refund of Ksh12,000 still not sent.',
                'age_days' => 8, 'updated' => '25 Sep, 12:40', 'assignee' => 'Grace W.', 'unread' => false,
                'entities' => [['icon' => 'chat-bubble-left-right', 'label' => 'Villa interior rendering']],
                'cards' => [], 'facts' => [], 'rationale' => null,
                'messages' => [], 'transcript' => [],
            ],

            'SUP-1055' => [
                'ref' => 'SUP-1055', 'kind' => 'ticket', 'category' => 'Copyright claim', 'severity' => 'high',
                'status' => 'open', 'source' => 'form', 'verified' => false,
                'requester' => ['name' => 'Someone using claims@studio-example.test', 'contact' => 'Email typed into the form. Not checked.', 'since' => 'No account linked'],
                'summary' => 'A model on ModelHub copies our product line. Listing link and statement attached.',
                'age_days' => 3, 'updated' => 'Wednesday, 17:02', 'assignee' => null, 'unread' => false,
                'entities' => [], 'cards' => [], 'facts' => [], 'rationale' => null,
                'messages' => [], 'transcript' => [],
            ],

            'SUP-1031' => [
                'ref' => 'SUP-1031', 'kind' => 'ticket', 'category' => 'Withdrawal stuck', 'severity' => 'high',
                'status' => 'resolved', 'source' => 'bot', 'verified' => true,
                'requester' => ['name' => 'Achieng Odhiambo', 'contact' => 'a••••@gmail.com · 07•• ••• 482', 'since' => 'Member since Mar 2026'],
                'summary' => 'Withdrawal of Ksh2,000 stayed on "being sent" for a day.',
                'age_days' => 12, 'updated' => '21 Sep', 'assignee' => 'Grace W.', 'unread' => false,
                'entities' => [['icon' => 'banknotes', 'label' => 'Withdrawal PO2H9T4VX8']],
                'cards' => [], 'facts' => [], 'rationale' => null,
                'messages' => [], 'transcript' => [],
            ],

            'SUP-1020' => [
                'ref' => 'SUP-1020', 'kind' => 'ticket', 'category' => 'Question about fees', 'severity' => 'low',
                'status' => 'resolved', 'source' => 'form', 'verified' => true,
                'requester' => ['name' => 'Achieng Odhiambo', 'contact' => 'a••••@gmail.com', 'since' => 'Member since Mar 2026'],
                'summary' => 'Why was a 15% fee taken from my model sale?',
                'age_days' => 20, 'updated' => '13 Sep', 'assignee' => 'Grace W.', 'unread' => false,
                'entities' => [], 'cards' => [], 'facts' => [], 'rationale' => null,
                'messages' => [], 'transcript' => [],
            ],
        ];
    }

    /** Minutes until the next reply is due (negative = overdue). Null when nobody on our side owes the next move. */
    public static function dueMinutes(string $ref): ?int
    {
        return [
            'SUP-1060' => -35,
            'SUP-1055' => 95,
            'SUP-1051' => -1500,
            'SUP-1038' => -4320,
            'SUP-1043' => 230,
        ][$ref] ?? null;
    }

    /** "Due in 1h 35m" / "Overdue 35 min", with a tone for the cell. @return array{label: string, tone: string}|null */
    public static function dueLabel(?int $minutes): ?array
    {
        if ($minutes === null) {
            return null;
        }

        $abs = abs($minutes);
        $span = match (true) {
            $abs >= 1440 => intdiv($abs, 1440).' d'.(intdiv($abs % 1440, 60) ? ' '.intdiv($abs % 1440, 60).' h' : ''),
            $abs >= 60 => intdiv($abs, 60).' h'.($abs % 60 ? ' '.($abs % 60).' min' : ''),
            default => $abs.' min',
        };

        if ($minutes < 0) {
            return ['label' => 'Overdue '.$span, 'tone' => 'red'];
        }

        return ['label' => 'Due in '.$span, 'tone' => $minutes <= 120 ? 'amber' : 'neutral'];
    }

    /** The team, with what is on each person's plate. */
    public static function team(): array
    {
        return [
            ['name' => 'Grace W.', 'role' => 'Support lead', 'open' => 4, 'overdue' => 1],
            ['name' => 'Brian K.', 'role' => 'Support', 'open' => 2, 'overdue' => 0],
            ['name' => 'Amina S.', 'role' => 'Finance', 'open' => 1, 'overdue' => 0],
            ['name' => 'Joseph M.', 'role' => 'Trust and safety', 'open' => 3, 'overdue' => 2],
        ];
    }

    /** Saved replies. Variables in {braces} are filled in when a reply is inserted. */
    public static function savedReplies(): array
    {
        return [
            ['id' => 1, 'title' => 'Payment needs review: what happens next', 'category' => 'Payments', 'used' => 42, 'edited' => '2 Oct · Grace W.', 'shared' => true,
                'body' => "Hi {member_name},\n\nThanks for sending this. Your payment arrived, but it did not turn into a licence, so a person has to put it right. I'm looking at it now.\n\nI'll write again as soon as I know more, usually within {first_reply_time}. You don't need to do anything else.\n\n{staff_name}\nModelHub support"],
            ['id' => 2, 'title' => 'Refund: how it is paid back', 'category' => 'Refunds', 'used' => 31, 'edited' => '28 Sep · Grace W.', 'shared' => true,
                'body' => "Hi {member_name},\n\nYour refund has been recorded and the licence has ended. The money is returned by hand to the M-Pesa number you paid from. I can't give an exact time, but I'll tell you if anything holds it up.\n\nReference {reference}.\n\n{staff_name}"],
            ['id' => 3, 'title' => 'Lock-out: we will call you back', 'category' => 'Account access', 'used' => 18, 'edited' => '25 Sep · Joseph M.', 'shared' => true,
                'body' => "Hi {member_name},\n\nWe can't change anything on an account from a message alone. We'll contact you using the email or phone already on the account to check it's really you, and then help you back in.\n\nPlease don't send passwords, codes or your M-Pesa PIN. We'll never ask for them.\n\n{staff_name}"],
            ['id' => 4, 'title' => 'Withdrawal: why it is still being sent', 'category' => 'Withdrawals', 'used' => 27, 'edited' => '21 Sep · Brian K.', 'shared' => true,
                'body' => "Hi {member_name},\n\nYour withdrawal was approved and sent to M-Pesa, but we haven't had the confirmation back yet. That can happen; we check it against the M-Pesa portal by hand.\n\nThe minimum withdrawal is {min_withdrawal}. Reference {reference}.\n\n{staff_name}"],
            ['id' => 5, 'title' => 'Not enough to go on: ask for details', 'category' => 'General', 'used' => 64, 'edited' => '18 Sep · Brian K.', 'shared' => true,
                'body' => "Hi {member_name},\n\nThanks for getting in touch. To look into this I need a little more:\n\n- The payment reference or M-Pesa receipt code\n- What you expected to happen, and what happened instead\n- A screenshot if you have one\n\n{staff_name}"],
            ['id' => 6, 'title' => 'My own sign-off', 'category' => 'General', 'used' => 9, 'edited' => '3 Oct · Grace W.', 'shared' => false,
                'body' => "Thanks for your patience, {member_name}.\n\n{staff_name}"],
        ];
    }

    /** Service levels by severity: first reply in business hours, resolution in business days. */
    public static function serviceLevels(): array
    {
        return [
            ['severity' => 'urgent', 'label' => 'Urgent', 'when' => 'Fraud in progress, money at risk', 'first' => '1 business hour', 'resolve' => 'Same day'],
            ['severity' => 'high', 'label' => 'High', 'when' => 'Payment needs review, stuck withdrawal, lock-out', 'first' => '4 business hours', 'resolve' => '1 business day'],
            ['severity' => 'normal', 'label' => 'Normal', 'when' => 'Most questions and refund requests', 'first' => '1 business day', 'resolve' => '3 business days'],
            ['severity' => 'low', 'label' => 'Low', 'when' => 'Fee questions, feedback', 'first' => '2 business days', 'resolve' => 'Best effort'],
        ];
    }

    /** What a member sees: only their own requests, in their words. */
    public static function memberList(): array
    {
        return array_values(array_filter(self::all(), fn ($t) => ($t['requester']['name'] ?? '') === 'Achieng Odhiambo'));
    }

    public static function find(string $ref): ?array
    {
        return self::all()[$ref] ?? null;
    }

    /** The member's label for a status: plain words, and "Waiting for you" is the only one that asks them to act. */
    public static function memberStatus(string $status): array
    {
        return [
            'open' => ['label' => 'With staff', 'tone' => 'blue'],
            'pending_member' => ['label' => 'Waiting for you', 'tone' => 'amber'],
            'resolved' => ['label' => 'Resolved', 'tone' => 'green'],
        ][$status] ?? ['label' => ucfirst($status), 'tone' => 'neutral'];
    }

    /** Staff see who owes the next move. */
    public static function staffStatus(string $status): array
    {
        return [
            'open' => ['label' => 'Needs staff', 'tone' => 'amber'],
            'pending_member' => ['label' => 'Waiting for member', 'tone' => 'blue'],
            'resolved' => ['label' => 'Resolved', 'tone' => 'green'],
        ][$status] ?? ['label' => ucfirst($status), 'tone' => 'neutral'];
    }
}
