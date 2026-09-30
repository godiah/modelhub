@php
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
@endphp
<x-app-layout>
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <x-card class="mb-6 rounded-2xl">
            <div class="p-6">
                <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Policy') }}</p>
                <h1 class="mt-1 font-tertiary text-2xl font-semibold text-neutral-900">{{ $app }} {{ __('Cancellation & Payment Policy') }}</h1>
                <p class="mt-2 max-w-3xl text-sm text-neutral-700">
                    {{ __('This policy governs cancellations, payment processing, and dispute resolution for engagements between Clients and Freelancers on :app.', ['app' => $app]) }}
                </p>
                <p class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-tertiary">
                    <span>{{ __('Effective date: May 15, 2025') }}</span>
                    <span>{{ __('Last updated: May 15, 2025') }}</span>
                </p>
            </div>
        </x-card>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]" x-data="{
            active: @js(array_key_first($sections)),
            init() {
                const observer = new IntersectionObserver((entries) => {
                    const visible = entries.filter(entry => entry.isIntersecting);
                    if (visible.length) this.active = visible[0].target.id;
                }, { rootMargin: '-80px 0px -65% 0px' });
                this.$root.querySelectorAll('section[id]').forEach(section => observer.observe(section));
            },
        }">
            <!-- Table of contents -->
            <nav aria-label="{{ __('On this page') }}" class="lg:sticky lg:top-24">
                <div class="-mx-4 overflow-x-auto px-4 [scrollbar-width:none] lg:mx-0 lg:px-0 [&::-webkit-scrollbar]:hidden">
                    <p class="mb-2 hidden text-xs font-medium uppercase tracking-wide text-tertiary lg:block">{{ __('On this page') }}</p>
                    <ol class="flex gap-1 lg:flex-col">
                        @foreach ($sections as $id => $label)
                            <li class="shrink-0">
                                <a href="#{{ $id }}" :aria-current="active === '{{ $id }}' ? 'true' : null"
                                    :class="active === '{{ $id }}' ? 'bg-white font-medium text-neutral-900 shadow-sm ring-1 ring-neutral-200' : 'text-neutral-600 hover:bg-white/70 hover:text-neutral-900'"
                                    class="flex items-baseline gap-2.5 whitespace-nowrap rounded-lg px-3 py-2 text-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 lg:whitespace-normal">
                                    <span class="text-xs tabular-nums text-tertiary">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </nav>

            <article class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm sm:p-8">
                <x-policy.section id="cancellation-overview" number="1" :title="$sections['cancellation-overview']">
                    <p>Either the Client or the Freelancer may cancel an engagement at any time for any reason. All cancellations must include:</p>
                    <x-policy.list :items="[
                        'A stated reason for cancellation.',
                        'Selection of a cancellation type (e.g., mutual, early termination, dispute).',
                        'A review of submitted deliverables (if any).',
                    ]" />
                </x-policy.section>

                <x-policy.section id="handling-deliverables" number="2" title="Handling Deliverables Upon Cancellation">
                    <p>Upon cancellation, the platform will identify whether any deliverables have been:</p>
                    <x-policy.list :items="['Submitted but not approved.', 'Approved by the Client.']" />
                    <p>Deliverables that have already been approved are considered <strong class="font-semibold text-neutral-900">final and eligible for payment</strong>.</p>
                </x-policy.section>

                <x-policy.section id="payment-eligibility" number="3" :title="$sections['payment-eligibility']">
                    <h3 class="font-semibold text-neutral-900">A. Approved Deliverables</h3>
                    <x-policy.list :items="[
                        'Approved deliverables will be considered for payment regardless of who initiated the cancellation.',
                        'The Freelancer is eligible to receive payment for approved work.',
                        'Funds will be released from escrow or requested from the Client if not already funded.',
                    ]" />
                    <h3 class="pt-2 font-semibold text-neutral-900">B. Unapproved Deliverables</h3>
                    <x-policy.list :items="[
                        'Clients may choose to approve submitted work at the time of cancellation.',
                        'If no approval is granted, no payment will be processed for unapproved work unless the dispute process is triggered.',
                    ]" />
                </x-policy.section>

                <x-policy.section id="payment-processing" number="4" :title="$sections['payment-processing']">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-policy.callout title="If the Client Cancels">
                            <x-policy.list :items="[
                                'The Freelancer will be shown a breakdown of deliverables that were approved and the corresponding payable amount.',
                                'The Freelancer may accept the payment or request a dispute if they believe the payment is insufficient.',
                            ]" />
                        </x-policy.callout>
                        <x-policy.callout title="If the Freelancer Cancels">
                            <x-policy.list :items="[
                                'The Client will be prompted to review submitted deliverables and approve those they find satisfactory.',
                                'The platform will calculate the payment due based on approved work.',
                                'The Freelancer will then be notified of the payment status.',
                            ]" />
                        </x-policy.callout>
                    </div>
                    <p>Both parties will receive a detailed cancellation summary that outlines the approved deliverables, payment amounts, and next steps.</p>
                </x-policy.section>

                <x-policy.section id="escrow" number="5" :title="$sections['escrow']">
                    <x-policy.list :items="[
                        'Funds held in escrow will only be released for approved deliverables.',
                        'If no deliverables are approved, the full escrowed amount will be refunded to the Client.',
                        'For milestone-based engagements, only the current milestone amount is affected.',
                    ]" />
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-policy.callout title="Processing Time">
                            <p>Escrow funds are typically processed within 3-5 business days after approval.</p>
                        </x-policy.callout>
                        <x-policy.callout title="Fund Security">
                            <p>All escrowed funds are held in secure, third-party accounts separate from platform operating funds.</p>
                        </x-policy.callout>
                    </div>
                </x-policy.section>

                <x-policy.section id="dispute" number="6" :title="$sections['dispute']">
                    <p>If either party disagrees with the payment outcome:</p>
                    <x-policy.list :items="[
                        'A dispute can be raised by selecting “Dispute this decision” within 5 days of the cancellation notice.',
                        'Our admin team will review engagement history, deliverables, and communication logs.',
                        'A final resolution will be provided within 7–14 business days.',
                    ]" />

                    <h3 class="pt-2 font-semibold text-neutral-900">Dispute Process</h3>
                    <ol class="grid gap-3 md:grid-cols-3">
                        @foreach ([
                            ['File Dispute', 'Submit detailed reasoning for the dispute with any supporting evidence.'],
                            ['Review Process', 'Admin team evaluates all project communications, deliverables and contract terms.'],
                            ['Resolution', 'Final decision with detailed explanation and fund distribution instructions.'],
                        ] as [$step, $text])
                            <li class="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3.5">
                                <p class="text-xs font-semibold text-teal-700">Step {{ $loop->iteration }}</p>
                                <p class="mt-1 font-semibold text-neutral-900">{{ $step }}</p>
                                <p class="mt-1 text-neutral-700">{{ $text }}</p>
                            </li>
                        @endforeach
                    </ol>

                    <x-policy.callout title="Tips for Successful Dispute Resolution">
                        <x-policy.list :items="[
                            'Provide clear, factual evidence related to your claim',
                            'Reference specific contract terms or project milestones',
                            'Maintain professional communication throughout the process',
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
                        <p>{{ $app }} employs these rights to maintain platform integrity and protect all users from potential fraud and abuse. These measures help ensure fair transactions and maintain trust within our marketplace ecosystem.</p>
                    </x-policy.callout>
                </x-policy.section>

                <x-policy.section id="communication" number="8" :title="$sections['communication']">
                    <p>All actions in the cancellation flow will trigger platform notifications and email alerts to both parties to ensure transparency and timely resolution.</p>
                    <div class="grid gap-4 md:grid-cols-3">
                        <x-policy.callout title="In-App Notifications">
                            <p>Real-time updates directly within your dashboard for immediate awareness.</p>
                        </x-policy.callout>
                        <x-policy.callout title="Email Alerts">
                            <p>Detailed notifications sent to your registered email address with actionable information.</p>
                        </x-policy.callout>
                        <x-policy.callout title="Activity Timeline">
                            <p>Chronological record of all cancellation-related events for complete transparency.</p>
                        </x-policy.callout>
                    </div>
                    <x-policy.callout title="Communication Preferences">
                        <p>You can customize your notification preferences in your account settings to control how you receive cancellation and payment updates.</p>
                    </x-policy.callout>
                </x-policy.section>

                <x-policy.section id="policy-updates" number="9" :title="$sections['policy-updates']">
                    <p>This policy is subject to change. Users will be notified of significant changes via email and platform notification.</p>

                    <x-policy.callout title="Policy Version Control">
                        <p>Effective Date: <span class="font-medium text-neutral-900">January 15, 2025</span></p>
                        <p>Last Updated: <span class="font-medium text-neutral-900">May 1, 2025</span></p>
                    </x-policy.callout>

                    <h3 class="pt-2 font-semibold text-neutral-900">Update Process</h3>
                    <ol class="grid gap-3 md:grid-cols-3">
                        @foreach ([
                            ['Policy Review', 'Regular evaluation of policy effectiveness'],
                            ['Policy Update', 'Changes implemented based on platform needs'],
                            ['User Notification', 'Transparent communication of changes'],
                        ] as [$step, $text])
                            <li class="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3.5">
                                <p class="text-xs font-semibold text-teal-700">Step {{ $loop->iteration }}</p>
                                <p class="mt-1 font-semibold text-neutral-900">{{ $step }}</p>
                                <p class="mt-1 text-neutral-700">{{ $text }}</p>
                            </li>
                        @endforeach
                    </ol>

                    <h3 class="pt-2 font-semibold text-neutral-900">Notification Methods</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-policy.callout title="Email Notifications">
                            <p>Direct emails sent to users detailing significant policy changes with summary of key updates.</p>
                        </x-policy.callout>
                        <x-policy.callout title="Platform Notifications">
                            <p>In-app alerts highlighting policy changes with links to detailed documentation.</p>
                        </x-policy.callout>
                        <x-policy.callout title="Documentation Updates">
                            <p>Versioned policy documents accessible in user dashboard with change tracking.</p>
                        </x-policy.callout>
                        <x-policy.callout title="User Acknowledgment">
                            <p>For substantial policy changes that affect user rights or obligations, users may be required to acknowledge the updated terms before continuing to use the platform.</p>
                        </x-policy.callout>
                    </div>

                    <x-policy.callout title="User Feedback">
                        <p>We value your input on our policies. If you have questions or suggestions regarding policy updates, please contact our support team.</p>
                    </x-policy.callout>

                    <p class="font-medium text-neutral-900">By continuing to use the platform after policy updates, users agree to abide by the modified terms.</p>
                </x-policy.section>
            </article>
        </div>
    </div>
</x-app-layout>
