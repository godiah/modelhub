@php
    use App\Support\Money;

    $app = config('app.name');
    $sections = [
        'cancellation-overview' => __('Cancellation overview'),
        'handling-deliverables' => __('Handling deliverables'),
        'payment-eligibility' => __('Payment eligibility'),
        'payment-processing' => __('Payment processing flow'),
        'escrow' => __('Escrow and fund release'),
        'dispute' => __('Dispute resolution'),
        'platform-rights' => __('Platform rights'),
        'communication' => __('Communication and notifications'),
        'policy-updates' => __('Policy updates'),
    ];

    // Numbers that are settings are read from them, so this page can never drift from what the platform does
    $reviewDays = (int) config('payments.escrow_cancel_window_days');
    $maxFunding = Money::formatMinor(((int) config('payments.max_kes')) * 100, 0);
@endphp
<x-app-layout title="Cancellation & Payment Policy">
    <x-legal.document :kicker="__('Policy')" :title="$app.' '.__('Cancellation & Payment Policy')" :sections="$sections"
        :intro="__('This policy explains what happens when an engagement between a Client and a Freelancer on :app is cancelled: what is paid, what is returned, and how disputes are decided. It describes how the platform works today.', ['app' => $app])"
        :effective="\Illuminate\Support\Carbon::parse(config('legal.effective'))->format('F j, Y')" :updated="__('October 3, 2026')">

                <x-policy.callout :title="__('Looking for how money is held and returned?')" class="mb-6">
                    <p>{{ __('This policy covers cancelling an engagement. How escrow, releases, refunds and withdrawals work, with examples, is in') }} <a href="{{ route('policies.payments') }}" class="font-medium text-teal-700 underline">{{ __('Payments and earnings') }}</a>.</p>
                </x-policy.callout>

                <x-policy.section id="cancellation-overview" number="1" :title="$sections['cancellation-overview']">
                    <p>Either the Client or the Freelancer may cancel an engagement at any time until it is completed. An engagement is completed automatically once every deliverable has been approved, and a completed engagement cannot be cancelled. Each person can cancel once.</p>
                    <p>Every cancellation needs:</p>
                    <x-policy.list :items="[
                        'A cancellation type (see below).',
                        'A reason category and a written reason of at least 10 characters.',
                        'Agreement to this policy.',
                    ]" />
                    <x-policy.callout title="Cancellation types">
                        <x-policy.list :items="[
                            'Mutual agreement: both sides agree to end the engagement. Either side can record it.',
                            'Client initiated: the Client ends it. Only the Client can choose this.',
                            'Freelancer initiated: the Freelancer ends it. Only the Freelancer can choose this.',
                            'Dispute: either side can cancel as a dispute, which opens a dispute for our staff to decide (see Dispute resolution).',
                        ]" />
                    </x-policy.callout>
                    <p>A Freelancer who declines an offer before accepting it is not cancelling an engagement: the offer simply ends and nothing was funded.</p>
                </x-policy.section>

                <x-policy.section id="handling-deliverables" title="Handling Deliverables Upon Cancellation" number="2">
                    <p>Upon cancellation, each deliverable is in one of these states:</p>
                    <x-policy.list :items="['Approved by the Client.', 'Submitted but not yet approved or rejected.', 'Not yet submitted.']" />
                    <p>Deliverables that have already been approved are considered <strong class="font-semibold text-neutral-900">final and eligible for payment</strong>. An approved deliverable cannot be changed or reversed afterwards.</p>
                    <p>If the Client wants to offer any further payment, the Client must first approve or reject every submitted deliverable.</p>
                </x-policy.section>

                <x-policy.section id="payment-eligibility" number="3" :title="$sections['payment-eligibility']">
                    <h3 class="font-semibold text-neutral-900">A. Approved Deliverables</h3>
                    <x-policy.list :items="[
                        'Approved work is paid for whoever cancelled.',
                        'When the job was funded through escrow, the Freelancer’s share for each deliverable is released as soon as the Client approves it, with no waiting period. Nothing about it changes on cancellation.',
                    ]" />
                    <h3 class="pt-2 font-semibold text-neutral-900">B. Unapproved Deliverables</h3>
                    <x-policy.list :items="[
                        'There is no automatic approval. Work that was submitted but not approved is not paid for unless the Client chooses to pay for it or staff decide otherwise in a dispute.',
                        'The Client can choose to pay extra for work that was never approved (a partial payment). This is the Client’s choice, and it can be any amount up to what is left for the Freelancer, or nothing more.',
                        'There can be only one partial payment per engagement.',
                    ]" />
                </x-policy.section>

                <x-policy.section id="payment-processing" number="4" :title="$sections['payment-processing']">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-policy.callout title="If the Client Cancels">
                            <x-policy.list :items="[
                                'The Client has a '.$reviewDays.'-day review window in which they can offer a partial payment.',
                                'The Freelancer can accept it, or dispute it if they think it is too low.',
                                'While an offer is waiting for an answer, what is left in escrow stays held.',
                                'If no offer is made, or the window ends, what is left in escrow becomes the Client’s to have back.',
                            ]" />
                        </x-policy.callout>
                        <x-policy.callout title="If the Freelancer Cancels">
                            <x-policy.list :items="[
                                'Approved work has already been paid. Nothing further is owed for unapproved work.',
                                'What is left in escrow becomes the Client’s to have back at once, with no review window.',
                            ]" />
                        </x-policy.callout>
                    </div>
                    <p>After a cancellation by mutual agreement, or one opened as a dispute, the same {{ $reviewDays }}-day window applies; for a dispute, nothing is returned until staff have decided.</p>
                    <p>Both parties are notified of what was approved, what is being offered, and the next steps.</p>
                </x-policy.section>

                <x-policy.section id="escrow" number="5" :title="$sections['escrow']">
                    <x-policy.list :items="[
                        'Money held in escrow is released to the Freelancer only for deliverables the Client has approved, or by a partial payment the Freelancer accepted, or by a decision of our staff in a dispute.',
                        'If no deliverables were approved and no payment is agreed, the Client can have the whole escrowed amount back.',
                        'Escrow for a job is paid in by one M-Pesa payment of up to '.$maxFunding.'.',
                        'Escrow is recorded per engagement in the platform’s books, so every release and every return can be traced.',
                    ]" />
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-policy.callout title="Returning money to the Client">
                            <p>A return is not automatic. Once it is due, our staff record it and send the money to the M-Pesa number the Client paid from. We do not state a time for this; if it seems late, contact support with your engagement details.</p>
                        </x-policy.callout>
                        <x-policy.callout title="If a Client does not respond">
                            <p>There is no automatic approval of submitted work. If a Client has not answered, the Freelancer should message them and, failing that, contact support.</p>
                        </x-policy.callout>
                    </div>
                </x-policy.section>

                <x-policy.section id="dispute" number="6" :title="$sections['dispute']">
                    <p>A dispute opens in two ways:</p>
                    <x-policy.list :items="[
                        'The Freelancer disputes a partial payment the Client has offered, instead of accepting it.',
                        'Either side cancels the engagement as a dispute.',
                    ]" />
                    <p>The person raising it chooses a reason, explains what happened, and can attach one file as evidence (an image, PDF or document, up to 10 MB). There is no fixed deadline for raising a dispute and no fixed time in which it will be decided: our staff review each one as it comes, and both parties are told the outcome.</p>

                    <h3 class="pt-2 font-semibold text-neutral-900">Dispute Process</h3>
                    <ol class="grid gap-3 md:grid-cols-3">
                        @foreach ([
                            ['File Dispute', 'Give your reason and details, with any evidence you have.'],
                            ['Review Process', 'Staff look at the engagement history, the deliverables and the private conversation between you. Each time staff read that conversation it is recorded.'],
                            ['Resolution', 'Staff decide a final amount for the Freelancer, paid out of what is left in escrow. The rest goes back to the Client.'],
                        ] as [$step, $text])
                            <li class="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3.5">
                                <p class="text-xs font-semibold text-teal-700">Step {{ $loop->iteration }}</p>
                                <p class="mt-1 font-semibold text-neutral-900">{{ $step }}</p>
                                <p class="mt-1 text-neutral-700">{{ $text }}</p>
                            </li>
                        @endforeach
                    </ol>

                    <x-policy.list :items="[
                        'While a dispute is open, nothing left in escrow is returned to the Client, and the chat between the two of you closes.',
                        'Staff cannot award more than what is left in escrow for the Freelancer, or more than the engagement was worth.',
                    ]" />

                    <x-policy.callout title="Tips for Successful Dispute Resolution">
                        <x-policy.list :items="[
                            'Provide clear, factual evidence related to your claim',
                            'Refer to the deliverables and what was agreed in the offer',
                            'Keep your messages professional',
                        ]" />
                    </x-policy.callout>
                </x-policy.section>

                <x-policy.section id="platform-rights" number="7" :title="$sections['platform-rights']">
                    <p>{{ $app }} reserves the right to:</p>
                    <x-policy.list :items="[
                        'Withhold or reverse payment in the event of fraud or policy violations.',
                        'Suspend accounts involved in repeated or malicious cancellations.',
                        'Use discretion in resolving disputes where platform policy or deliverable clarity is in question.',
                    ]" />
                    <x-policy.callout title="Platform Protection Measures">
                        <p>{{ $app }} uses these rights to protect all users from fraud and abuse and to keep transactions fair.</p>
                    </x-policy.callout>
                </x-policy.section>

                <x-policy.section id="communication" number="8" :title="$sections['communication']">
                    <p>Each step in the cancellation flow notifies the people it affects.</p>
                    <div class="grid gap-4 md:grid-cols-3">
                        <x-policy.callout title="In-App Notifications">
                            <p>Updates appear in your Notifications, under Hiring and engagements and Payments.</p>
                        </x-policy.callout>
                        <x-policy.callout title="Email Alerts">
                            <p>The same events are emailed to the address on your account.</p>
                        </x-policy.callout>
                        <x-policy.callout title="Activity Timeline">
                            <p>Each engagement keeps a record of what happened and when.</p>
                        </x-policy.callout>
                    </div>
                    <p>You can message the other person while an engagement is active or cancelled. Once a dispute is open, or the engagement is settled, the chat closes.</p>
                </x-policy.section>

                <x-policy.section id="policy-updates" number="9" :title="$sections['policy-updates']">
                    <p>This policy can change. We tell members about significant changes by notification and email. By continuing to use the platform after a change, you agree to the updated terms.</p>
                    <p>If you have questions or suggestions about this policy, please contact our support team.</p>
                </x-policy.section>
    </x-legal.document>
</x-app-layout>
