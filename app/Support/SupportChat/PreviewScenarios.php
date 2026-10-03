<?php

namespace App\Support\SupportChat;

/**
 * Scripted conversations for the support-assistant UI preview. DESIGN SCAFFOLDING ONLY: every value here is
 * invented, there is no assistant behind it, and this class is deleted once the real assistant streams the same
 * card shapes (trail, blocker, escrow, ticket, approval) from its API.
 *
 * Card shapes are the contract the widget renders; keep them stable so the real thing can drop in.
 */
final class PreviewScenarios
{
    /** What the widget opens with, by the page the member is on. */
    public static function contextFor(?string $route): array
    {
        $route = (string) $route;

        return match (true) {
            $route === 'earnings.index' => [
                'key' => 'earnings',
                'label' => 'Earnings',
                'greeting' => "You're on Earnings. I can look up your balance, what's still held, and your withdrawals.",
                'cards' => [],
                'chips' => [
                    ['label' => "Why can't I withdraw?", 'key' => 'blocker'],
                    ['label' => 'Where is my withdrawal?', 'key' => 'withdrawal'],
                    ['label' => 'When is my next release?', 'key' => 'blocker'],
                ],
            ],
            $route === 'payments.show' => [
                'key' => 'payment',
                'label' => 'Payment',
                'greeting' => "Here's where this payment is right now.",
                'cards' => [self::paymentTrail()],
                'chips' => [
                    ['label' => 'I paid but nothing happened', 'key' => 'paid_nothing'],
                    ['label' => 'Ask for a refund', 'key' => 'refund'],
                ],
            ],
            str_starts_with($route, 'engagements.') => [
                'key' => 'engagement',
                'label' => 'Engagement',
                'greeting' => 'Ask me about this project: where the escrow money is, what is waiting on whom, or what a cancellation would do.',
                'cards' => [],
                'chips' => [
                    ['label' => 'Where is the escrow money?', 'key' => 'escrow'],
                    ['label' => "The client hasn't released payment", 'key' => 'escrow'],
                ],
            ],
            str_starts_with($route, 'seller.') => [
                'key' => 'seller',
                'label' => 'Selling',
                'greeting' => "I can check why a model isn't live or what's blocking a submission.",
                'cards' => [],
                'chips' => [
                    ['label' => "Why isn't my model live?", 'key' => 'readiness'],
                    ['label' => 'How do withdrawals work?', 'key' => 'policy'],
                ],
            ],
            default => [
                'key' => 'general',
                'label' => null,
                'greeting' => 'Hi. Ask about a payment, a withdrawal, a model or a job and I will look up what is happening on your account.',
                'cards' => [],
                'chips' => [
                    ['label' => 'I paid but nothing happened', 'key' => 'paid_nothing'],
                    ['label' => "Why can't I withdraw?", 'key' => 'blocker'],
                    ['label' => 'What is your refund policy?', 'key' => 'policy'],
                ],
            ],
        };
    }

    /**
     * A finished conversation for the static gallery: the greeting for a page, then [what the member said, scenario key] pairs.
     *
     * @param  list<array{0: string, 1: string}>  $pairs
     * @return list<array<string, mixed>>
     */
    public static function conversation(string $route, array $pairs): array
    {
        $context = self::contextFor($route);
        $scenarios = self::all();

        $messages = [[
            'role' => 'assistant', 'text' => $context['greeting'], 'tool' => null,
            'cards' => $context['cards'], 'citations' => [], 'chips' => [], 'first' => true,
        ]];

        foreach ($pairs as $i => [$said, $key]) {
            $s = $scenarios[$key];
            $last = $i === array_key_last($pairs);

            $messages[] = ['role' => 'user', 'text' => $said];
            $messages[] = [
                'role' => 'assistant', 'text' => $s['text'], 'cards' => $s['cards'] ?? [], 'citations' => $s['citations'] ?? [],
                'tool' => $s['tool'] ? ['label' => $s['tool'], 'done' => true] : null,
                'chips' => $last ? ($s['chips'] ?? []) : [],
            ];
        }

        return $messages;
    }

    /** Scripted assistant turns, keyed by what the member tapped or typed. */
    public static function all(): array
    {
        return [
            'paid_nothing' => [
                'tool' => 'Checked your payments',
                'text' => "I found it. Your M-Pesa payment went through, but the licence wasn't created, so the download is still locked. This is not something you did wrong, and it needs a person at ModelHub to review.",
                'cards' => [self::paymentTrail()],
                'chips' => [
                    ['label' => 'Ask staff to look at this', 'key' => 'ask_staff'],
                    ['label' => 'Ask for a refund', 'key' => 'refund'],
                ],
            ],
            'blocker' => [
                'tool' => 'Checked your balance and withdrawals',
                'text' => "You're Ksh50 short of the minimum, and some of your money is still in the hold period. Here's how each requirement looks.",
                'cards' => [[
                    'type' => 'blocker',
                    'title' => "Why you can't withdraw yet",
                    'rows' => [
                        ['ok' => false, 'label' => 'Available balance is at least Ksh500', 'have' => 'You have Ksh450'],
                        ['ok' => true, 'label' => 'No other withdrawal is open', 'have' => 'None open'],
                        ['ok' => true, 'label' => 'Amount is under the Ksh150,000 limit', 'have' => 'Fine'],
                    ],
                    'summary' => 'Ksh1,190 is still in the hold period and becomes available on 5 Oct. After that you can withdraw.',
                    'actions' => [['label' => 'Open earnings', 'href' => '#', 'variant' => 'secondary']],
                ]],
                'citations' => [['title' => 'Withdrawing your earnings', 'updated' => '1 Oct']],
                'chips' => [],
            ],
            'withdrawal' => [
                'tool' => 'Checked your withdrawals',
                'text' => "Your withdrawal is with staff for approval. They approve each one, then it's sent to your M-Pesa. Nothing is wrong with it so far.",
                'cards' => [[
                    'type' => 'trail',
                    'title' => 'Withdrawal to M-Pesa',
                    'subtitle' => 'Request PO4J8M2KQ7',
                    'amount' => 'Ksh2,000',
                    'status' => ['label' => 'Waiting for staff', 'tone' => 'amber'],
                    'steps' => [
                        ['state' => 'done', 'label' => 'Request sent', 'detail' => 'Today, 09:12 · to 07•• ••• 482'],
                        ['state' => 'current', 'label' => 'Staff approval', 'detail' => 'Waiting. You can still cancel it.'],
                        ['state' => 'pending', 'label' => 'Sending to M-Pesa'],
                        ['state' => 'pending', 'label' => 'Paid'],
                    ],
                    'actions' => [['label' => 'Open earnings', 'href' => '#', 'variant' => 'secondary']],
                ]],
                'chips' => [],
            ],
            'policy' => [
                'tool' => 'Searched the help articles',
                'text' => "Refunds are for a file that is broken or not as described, reported within the hold period. Our policy says staff decide each case, so I can't promise one. If a refund is approved, it is recorded and the money is sent back to your M-Pesa by hand.",
                'cards' => [],
                'citations' => [
                    ['title' => 'Refunds for model purchases', 'updated' => '1 Oct'],
                    ['title' => 'Payments and earnings', 'updated' => '1 Oct'],
                ],
                'chips' => [['label' => 'Ask for a refund', 'key' => 'refund']],
            ],
            'refund' => [
                'tool' => 'Checked your payment and licence',
                'text' => "I've asked staff to review a refund for this purchase. I can't promise the outcome, and I don't make the decision. You'll be notified either way.",
                'cards' => [[
                    'type' => 'approval',
                    'title' => 'Refund request sent to staff',
                    'status' => ['label' => 'Waiting for staff', 'tone' => 'amber'],
                    'rows' => [
                        ['k' => 'Payment', 'v' => 'Large Iron Gate · Ksh1,500'],
                        ['k' => 'Sent', 'v' => 'Today, 14:40'],
                        ['k' => 'If approved', 'v' => 'Returned by hand to 07•• ••• 482'],
                    ],
                    'note' => 'Staff look at your download history and the purchase date before they decide.',
                    'actions' => [['label' => 'View request', 'href' => '#', 'variant' => 'secondary']],
                ]],
                'chips' => [],
            ],
            'escrow' => [
                'tool' => 'Checked this engagement',
                'text' => "Part of the escrow is waiting to come back to you. It was cancelled, so there's a 7-day window before staff record the refund and send it to your M-Pesa.",
                'cards' => [[
                    'type' => 'escrow',
                    'title' => 'Villa interior rendering',
                    'state' => ['label' => 'Refund waiting', 'tone' => 'amber'],
                    'segments' => [
                        ['label' => 'Paid to freelancer', 'value' => 'Ksh12,000', 'pct' => 40, 'tone' => 'teal'],
                        ['label' => 'Held in escrow', 'value' => 'Ksh6,000', 'pct' => 20, 'tone' => 'slate'],
                        ['label' => 'To be refunded to you', 'value' => 'Ksh12,000', 'pct' => 40, 'tone' => 'amber'],
                    ],
                    'note' => 'The review window ends on 9 Oct. After that, staff record your refund.',
                    'actions' => [['label' => 'Open engagement', 'href' => '#', 'variant' => 'secondary']],
                ]],
                'chips' => [],
            ],
            'readiness' => [
                'tool' => 'Checked your listing',
                'text' => "It's still a draft because two things are missing. Once both are done you can send it for review.",
                'cards' => [[
                    'type' => 'blocker',
                    'title' => 'Before you can submit',
                    'rows' => [
                        ['ok' => true, 'label' => 'Title and category', 'have' => 'Done'],
                        ['ok' => false, 'label' => 'At least one preview image', 'have' => 'None added'],
                        ['ok' => false, 'label' => 'A model file (native, exchange or archive)', 'have' => 'None uploaded'],
                    ],
                    'summary' => 'Images can be jpg, png or webp, up to 5 MB each.',
                    'actions' => [['label' => 'Open listing', 'href' => '#', 'variant' => 'secondary']],
                ]],
                'citations' => [['title' => 'Listing checklist', 'updated' => '1 Oct']],
                'chips' => [],
            ],
            'ask_staff' => [
                'tool' => null,
                'text' => "Done. I've sent this to staff with the payment details attached, so you won't need to explain it again.",
                'cards' => [self::ticket()],
                'chips' => [],
            ],
            'human' => [
                'tool' => null,
                'text' => "I've passed this to the ModelHub team and attached our chat so you won't have to repeat yourself.",
                'cards' => [self::ticket('Chat handed to staff', 'You asked to talk to a person. The chat is attached.')],
                'chips' => [],
            ],
            'frustrated' => [
                'tool' => null,
                'text' => "I'm sorry this isn't getting you what you need. Rather than keep guessing, I can hand it to a person now, with everything we've covered.",
                'cards' => [],
                'chips' => [['label' => 'Hand this to a person', 'key' => 'human']],
            ],
            'unknown' => [
                'tool' => 'Searched the help articles',
                'text' => "I couldn't find a reliable answer to that, and I'd rather not guess. I can send it to staff with this chat attached.",
                'cards' => [],
                'chips' => [['label' => 'Send this to staff', 'key' => 'human']],
            ],
        ];
    }

    private static function paymentTrail(): array
    {
        return [
            'type' => 'trail',
            'title' => 'Large Iron Gate – Gate Entrance',
            'subtitle' => 'Standard licence · Payment MH7K2P9QX1',
            'amount' => 'Ksh1,500',
            'status' => ['label' => 'Needs staff attention', 'tone' => 'amber'],
            'steps' => [
                ['state' => 'done', 'label' => 'M-Pesa prompt sent', 'detail' => 'Today, 14:31'],
                ['state' => 'done', 'label' => 'Payment received', 'detail' => 'Ksh1,500 · receipt SHK1•••23 · 14:32'],
                [
                    'state' => 'stuck',
                    'label' => 'Licence issued',
                    'detail' => 'Stopped here',
                    'note' => "Your money arrived, but the licence couldn't be created. Staff need to review this payment.",
                ],
                ['state' => 'pending', 'label' => 'Download unlocked'],
            ],
            'actions' => [['label' => 'Open payment', 'href' => '#', 'variant' => 'secondary']],
        ];
    }

    private static function ticket(string $topic = 'Payment needs review', string $summary = 'Paid Ksh1,500 for Large Iron Gate. Payment received, licence not created.'): array
    {
        return [
            'type' => 'ticket',
            'reference' => 'SUP-1042',
            'topic' => $topic,
            'summary' => $summary,
            'reply' => 'First reply within 4 business hours (weekdays, 8am to 6pm EAT).',
            'channel' => "You'll get an email and a notification when staff reply, and you can answer from your requests.",
            'actions' => [['label' => 'View request', 'href' => '#', 'variant' => 'secondary']],
        ];
    }
}
