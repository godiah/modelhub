@php
    $app = config('app.name');
    $entity = config('legal.entity');
    $email = config('legal.email');
    $jurisdiction = config('legal.jurisdiction');
    $minAge = config('legal.minimum_age');
    $fee = rtrim(rtrim(number_format(\App\Helpers\Applications\ApplicationCalculationHelper::getServiceFeePercentage() * 100, 1), '0'), '.');
    $effective = \Illuminate\Support\Carbon::parse(config('legal.effective'))->format('F j, Y');
    $sections = [
        'agreement' => __('Agreeing to these terms'),
        'account' => __('Your account'),
        'how-it-works' => __('How :app works', ['app' => $app]),
        'projects' => __('Projects and applications'),
        'engagements' => __('Engagements and deliverables'),
        'fees' => __('Fees and payments'),
        'cancellations' => __('Cancellations and disputes'),
        'reviews' => __('Reviews'),
        'content' => __('Your content'),
        'conduct' => __('Acceptable use'),
        'marketplace' => __('3D models marketplace'),
        'ending' => __('Suspension and closure'),
        'liability' => __('Disclaimers and liability'),
        'changes' => __('Changes to these terms'),
        'law' => __('Governing law'),
        'contact' => __('Contact us'),
    ];
@endphp
<x-app-layout title="Terms of Service">
    <x-legal.document :title="__('Terms of Service')" :sections="$sections" :effective="$effective" :updated="$effective"
        :intro="__('These terms are the agreement between you and :entity about using :app. Please read them before you create an account or use the platform.', ['entity' => $entity, 'app' => $app])">

        <x-policy.section id="agreement" number="1" :title="$sections['agreement']">
            <p>{{ __('By creating an account, or by using :app in any way, you agree to these Terms of Service, to our', ['app' => $app]) }} <a href="{{ route('legal.privacy') }}" class="font-medium text-teal-700 hover:underline">{{ __('Privacy Policy') }}</a> {{ __('and to our') }} <a href="{{ route('engagements.policy') }}" class="font-medium text-teal-700 hover:underline">{{ __('Cancellation & Payment Policy') }}</a>, {{ __('which form part of this agreement. If you do not agree, please do not use the platform.') }}</p>
            <p>{{ __('You must be at least :age years old, and able to enter a binding contract, to hold an account.', ['age' => $minAge]) }}</p>
        </x-policy.section>

        <x-policy.section id="account" number="2" :title="$sections['account']">
            <x-policy.list :items="[
                __('Give accurate details when you register and keep them up to date.'),
                __('Keep your password and any sign-in codes private. You are responsible for what happens under your account.'),
                __('Tell us straight away if you think someone else has used your account.'),
                __('One person should hold one account. Do not create accounts in someone else\'s name or to get around a suspension.'),
            ]" />
        </x-policy.section>

        <x-policy.section id="how-it-works" number="3" :title="$sections['how-it-works']">
            <p>{{ __(':app is a platform that lets people who need 3D work done (Clients) find and work with people who do it (Freelancers). The same account can act as either.', ['app' => $app]) }}</p>
            <p>{{ __('We provide the tools: posting projects, applications, messaging, deliverables, reviews and dispute handling. We are not a party to the agreement between a Client and a Freelancer, we do not employ Freelancers, and we do not guarantee the quality, safety or legality of any work, or that any project will be completed.') }}</p>
        </x-policy.section>

        <x-policy.section id="projects" number="4" :title="$sections['projects']">
            <h3 class="font-semibold text-neutral-900">{{ __('Clients posting a project') }}</h3>
            <x-policy.list :items="[
                __('Describe the work, the budget and the deadline honestly and completely. Do not post anything you do not intend to hire for.'),
                __('You decide whom to hire. A project closes to new applications when the Freelancer you hire accepts your offer, and reopens if that engagement is cancelled.'),
                __('You may close, archive or restore a project you own, but not while someone is working on it.'),
            ]" />
            <h3 class="pt-2 font-semibold text-neutral-900">{{ __('Freelancers applying') }}</h3>
            <x-policy.list :items="[
                __('You can send one application per project, and not to a project you posted yourself.'),
                __('Your offer is the price you are willing to work for. It can include a service fee (see Fees and payments), and the application shows you what you would receive.'),
                __('If a Client hires you, you receive an offer that you can accept or decline. The engagement starts only when you accept.'),
                __('You can withdraw an application until you are hired. Withdrawing is final: you cannot apply to that project again. A draft you delete is removed completely.'),
            ]" />
        </x-policy.section>

        <x-policy.section id="engagements" number="5" :title="$sections['engagements']">
            <p>{{ __('Once an offer is accepted, the work is tracked as an engagement. The Client and the Freelancer agree the deliverables and their due dates. The Freelancer submits each deliverable for review, and the Client approves it or asks for changes.') }}</p>
            <x-policy.list :items="[
                __('Do the work you agreed, to the standard described, by the dates agreed. If something changes, say so early and agree it in writing in the engagement.'),
                __('Review submissions promptly and give clear reasons if you ask for changes.'),
                __('Keep your communication on the platform where you can, so there is a record if a disagreement needs to be reviewed.'),
            ]" />
        </x-policy.section>

        <x-policy.section id="fees" number="6" :title="$sections['fees']">
            <p>{{ __('Using :app to find work is free to join. When a Freelancer is hired, a service fee of :fee% of the agreed amount applies and is deducted from the Freelancer\'s offer. The fee, and the amount the Freelancer will receive, are shown on the application and on the engagement before work starts.', ['app' => $app, 'fee' => $fee]) }}</p>
            <p>{{ __('How money is held, released, refunded or paid out for an engagement is set out in the Cancellation & Payment Policy. We may change our fees for future engagements; the fee shown when an application is sent is the fee that applies to it.') }}</p>
            <p>{{ __('You are responsible for any taxes that apply to what you earn or pay.') }}</p>
        </x-policy.section>

        <x-policy.section id="cancellations" number="7" :title="$sections['cancellations']">
            <p>{{ __('Either side can cancel an engagement. Work that has already been approved is eligible for payment, and a partial payment can be made for work done. If you cannot agree on the outcome, either side can raise a dispute, and an administrator will review the engagement history, deliverables and messages and make a decision. The full process, time limits and your rights are in the') }} <a href="{{ route('engagements.policy') }}" class="font-medium text-teal-700 hover:underline">{{ __('Cancellation & Payment Policy') }}</a>.</p>
            <p>{{ __('We may withhold or reverse a payment, or take other action, where we find fraud or a breach of these terms.') }}</p>
        </x-policy.section>

        <x-policy.section id="reviews" number="8" :title="$sections['reviews']">
            <p>{{ __('After an engagement, each side can review the other. Reviews must be honest, based on your own experience of the work, and free of abuse, personal information and anything unlawful. We may remove a review that breaks these rules. You may not offer or ask for payment, gifts or favours in exchange for a review.') }}</p>
        </x-policy.section>

        <x-policy.section id="content" number="9" :title="$sections['content']">
            <p>{{ __('Your profile, projects, applications, messages, files and reviews are your content. You keep ownership of it. You give :entity permission to store it, to show it to the people it is meant for (for example, a Client sees your application and portfolio samples) and to process it to run the platform.', ['entity' => $entity]) }}</p>
            <p>{{ __('You promise that you have the right to share what you upload and that it does not infringe anyone else\'s rights. Who owns the finished work is for the Client and the Freelancer to agree between themselves; we do not claim ownership of deliverables.') }}</p>
        </x-policy.section>

        <x-policy.section id="conduct" number="10" :title="$sections['conduct']">
            <p>{{ __('Do not use :app to:', ['app' => $app]) }}</p>
            <x-policy.list :items="[
                __('break the law or ask others to, or share content that is illegal, hateful, threatening or sexually exploitative;'),
                __('deceive anyone, including with fake profiles, fake projects, fake offers or fake reviews;'),
                __('harass, impersonate or spam other users;'),
                __('upload malware, or try to gain access to accounts, data or systems that are not yours;'),
                __('scrape, copy or resell the platform\'s content or data without our written permission; or'),
                __('interfere with how the platform works.'),
            ]" />
        </x-policy.section>

        <x-policy.section id="marketplace" number="11" :title="$sections['marketplace']">
            <p>{{ __('We plan to add a marketplace where members can sell and buy 3D models. It is not available yet. When it launches, additional terms for sellers and buyers (including licences, refunds and payouts) will apply to it, and you will be asked to accept them before you use it.') }}</p>
        </x-policy.section>

        <x-policy.section id="ending" number="12" :title="$sections['ending']">
            <p>{{ __('You can stop using :app and delete your account from your profile settings at any time. Deleting your account is permanent: it removes your profile and the content linked to it, including your projects, applications and engagement history, and it cannot be undone. Think about the people you are working with before you delete.', ['app' => $app]) }}</p>
            <p>{{ __('We may suspend or close an account, remove content or restrict features if we reasonably believe these terms or our policies have been broken, if an account is being used for fraud or abuse, or if we are required to by law. Where we can, we will tell you why.') }}</p>
        </x-policy.section>

        <x-policy.section id="liability" number="13" :title="$sections['liability']">
            <p>{{ __(':app is provided "as is" and "as available". We do not promise that it will be uninterrupted or error free, or that any Client or Freelancer will meet their commitments.', ['app' => $app]) }}</p>
            <p>{{ __('To the extent the law allows, :entity is not liable for losses that arise from the conduct of other users, from work that is late, incomplete or of poor quality, or for indirect or consequential losses such as lost profits. Our total liability to you for any claim related to the platform is limited to the fees you paid to us for the engagement the claim relates to. Nothing in these terms limits liability that cannot be limited by law, or your rights as a consumer.', ['entity' => $entity]) }}</p>
        </x-policy.section>

        <x-policy.section id="changes" number="14" :title="$sections['changes']">
            <p>{{ __('We may update these terms. If a change matters, we will tell you by email or in the platform before it takes effect, and the date at the top of this page will change. If you keep using :app after that date, you accept the updated terms.', ['app' => $app]) }}</p>
        </x-policy.section>

        <x-policy.section id="law" number="15" :title="$sections['law']">
            <p>{{ __('These terms are governed by the laws of :jurisdiction, and the courts of :jurisdiction have jurisdiction over any dispute about them, unless the law where you live gives you rights that cannot be waived.', ['jurisdiction' => $jurisdiction]) }}</p>
        </x-policy.section>

        <x-policy.section id="contact" number="16" :title="$sections['contact']">
            <p>{{ __('Questions about these terms? Write to us at') }} <a href="mailto:{{ $email }}" class="font-medium text-teal-700 hover:underline">{{ $email }}</a>@if (config('legal.address')) {{ __('or at') }} {{ config('legal.address') }}@endif.</p>
        </x-policy.section>
    </x-legal.document>
</x-app-layout>
