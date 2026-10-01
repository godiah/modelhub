@php
    $app = config('app.name');
    $entity = config('legal.entity');
    $email = config('legal.email');
    $minAge = config('legal.minimum_age');
    $effective = \Illuminate\Support\Carbon::parse(config('legal.effective'))->format('F j, Y');
    $sections = [
        'who' => __('Who we are'),
        'collect' => __('What we collect'),
        'use' => __('How we use it'),
        'sharing' => __('Who can see it'),
        'cookies' => __('Cookies'),
        'third-parties' => __('Third-party services'),
        'retention' => __('How long we keep it'),
        'security' => __('Security'),
        'rights' => __('Your rights'),
        'children' => __('Children'),
        'changes' => __('Changes to this policy'),
        'contact' => __('Contact us'),
    ];
@endphp
<x-app-layout title="Privacy Policy" crumb="Privacy policy">
    <x-legal.document :title="__('Privacy Policy')" :sections="$sections" :effective="$effective" :updated="$effective"
        :intro="__('This policy explains what personal information :app collects, why, who can see it, and the choices you have.', ['app' => $app])">

        <x-policy.section id="who" number="1" :title="$sections['who']">
            <p>{{ __(':entity runs :app and is responsible for the personal information described here. If you have a question or want to use your rights, contact us at', ['entity' => $entity, 'app' => $app]) }} <a href="mailto:{{ $email }}" class="font-medium text-teal-700 hover:underline">{{ $email }}</a>.</p>
        </x-policy.section>

        <x-policy.section id="collect" number="2" :title="$sections['collect']">
            <h3 class="font-semibold text-neutral-900">{{ __('What you give us') }}</h3>
            <x-policy.list :items="[
                __('Account details: your name, email address and password (stored only in scrambled, hashed form).'),
                __('Profile details you choose to add: photo, professional information, location, telephone number, skills, software and links to your social profiles.'),
                __('Your activity on the platform: projects you post, applications and proposals you send, portfolio samples, messages, deliverables and the files attached to them, dispute evidence, reviews and your notification history.'),
                __('Engagement and payment records: agreed amounts, fees, cancellations, partial payments and their status.'),
                __('Messages you send us, for example to support.'),
            ]" />
            <h3 class="pt-2 font-semibold text-neutral-900">{{ __('What we collect automatically') }}</h3>
            <x-policy.list :items="[
                __('Sign-in and security information: your IP address, browser and device details, and when you were last active, kept with your session.'),
                __('Two-factor sign-in codes we email you (kept only until they expire).'),
                __('Basic technical records, such as errors, used to keep the platform working.'),
            ]" />
            <p>{{ __('We do not use advertising trackers or sell your personal information.') }}</p>
        </x-policy.section>

        <x-policy.section id="use" number="3" :title="$sections['use']">
            <x-policy.list :items="[
                __('To create and run your account, and to sign you in securely.'),
                __('To provide the service: showing your projects and applications, tracking engagements and deliverables, handling cancellations, payments and disputes, and recording reviews.'),
                __('To send you messages about your activity, such as new applications, offers and deadlines, by email and in the platform.'),
                __('To keep the platform safe, prevent fraud and abuse, and enforce our terms.'),
                __('To fix problems and improve the platform.'),
                __('To meet legal obligations and handle claims.'),
            ]" />
            <p>{{ __('We rely on the need to provide the service you asked for, our legitimate interest in running a safe and reliable platform, your consent where we ask for it, and legal obligations.') }}</p>
        </x-policy.section>

        <x-policy.section id="sharing" number="4" :title="$sections['sharing']">
            <h3 class="font-semibold text-neutral-900">{{ __('Other users') }}</h3>
            <p>{{ __('Some of what you share is meant for the people you work with. A Client who receives your application sees your name, photo, email address, offer, proposal, portfolio samples and ratings. A Freelancer sees the project they apply to and the name of the Client who posted it. People working together on an engagement see its messages, deliverables and history. Reviews are shown to the people involved and can appear on profiles.') }}</p>
            <h3 class="pt-2 font-semibold text-neutral-900">{{ __('Our service providers') }}</h3>
            <p>{{ __('We use providers that process information for us, such as hosting, email delivery and file storage. They may only use it to provide their service to us.') }}</p>
            <h3 class="pt-2 font-semibold text-neutral-900">{{ __('Where the law requires') }}</h3>
            <p>{{ __('We may disclose information to authorities or others when the law requires it, or to protect the rights, safety or property of our users or ourselves.') }}</p>
        </x-policy.section>

        <x-policy.section id="cookies" number="5" :title="$sections['cookies']">
            <p>{{ __('We use only the cookies the platform needs to work. We do not use advertising or analytics cookies.') }}</p>
            <x-policy.list :items="[
                __('A session cookie keeps you signed in and protects forms from forgery (a security token is stored with it).'),
                __('A \"remember me\" cookie, only if you tick that box when signing in.'),
                __('A small preference cookie that remembers whether you collapsed the side menu.'),
            ]" />
            <p>{{ __('You can delete cookies in your browser settings, but the platform will not work properly without the session cookie.') }}</p>
        </x-policy.section>

        <x-policy.section id="third-parties" number="6" :title="$sections['third-parties']">
            <p>{{ __('To display pages, your browser loads fonts and some scripts and icons from third-party content networks (including Google Fonts, jsDelivr and cdnjs). Those providers receive your IP address and browser details when your browser fetches the files, under their own privacy policies. We plan to host these files ourselves in future.') }}</p>
            <p>{{ __('Where we link to other sites, such as a freelancer\'s social profile, their policies apply and not ours.') }}</p>
        </x-policy.section>

        <x-policy.section id="retention" number="7" :title="$sections['retention']">
            <p>{{ __('We keep your information while your account is open. If you delete your account, your profile and the content linked to it, including your projects, applications and engagement history, are permanently removed. Where the law requires us to keep certain records, we keep only what is required, and only for as long as it is required.') }}</p>
        </x-policy.section>

        <x-policy.section id="security" number="8" :title="$sections['security']">
            <p>{{ __('We protect your information with measures such as hashed passwords, encrypted (HTTPS) connections, private storage for deliverables and dispute evidence, and access limited to people who need it. No system is perfectly secure, so please use a strong, unique password and keep it private. If we learn of a breach that affects you, we will tell you and the authorities as the law requires.') }}</p>
        </x-policy.section>

        <x-policy.section id="rights" number="9" :title="$sections['rights']">
            <p>{{ __('Under the data protection law that applies to us you can ask to:') }}</p>
            <x-policy.list :items="[
                __('see the personal information we hold about you and get a copy;'),
                __('correct information that is wrong or out of date (you can edit most of it in your profile);'),
                __('delete your information or your account, subject to the records we must keep;'),
                __('object to, or ask us to restrict, certain uses of your information;'),
                __('withdraw consent where we rely on it; and'),
                __('complain to the data protection regulator in your country.'),
            ]" />
            <p>{{ __('To use these rights, write to') }} <a href="mailto:{{ $email }}" class="font-medium text-teal-700 hover:underline">{{ $email }}</a>. {{ __('We may need to confirm who you are first.') }}</p>
        </x-policy.section>

        <x-policy.section id="children" number="10" :title="$sections['children']">
            <p>{{ __(':app is not for anyone under :age. We do not knowingly collect information from children, and will delete it if we learn we have.', ['app' => $app, 'age' => $minAge]) }}</p>
        </x-policy.section>

        <x-policy.section id="changes" number="11" :title="$sections['changes']">
            <p>{{ __('We may update this policy, for example when we add features such as a models marketplace. If a change matters, we will tell you by email or in the platform, and the date at the top of this page will change.') }}</p>
        </x-policy.section>

        <x-policy.section id="contact" number="12" :title="$sections['contact']">
            <p>{{ __('Questions about this policy or your information? Write to') }} <a href="mailto:{{ $email }}" class="font-medium text-teal-700 hover:underline">{{ $email }}</a>@if (config('legal.address')) {{ __('or at') }} {{ config('legal.address') }}@endif.</p>
        </x-policy.section>
    </x-legal.document>
</x-app-layout>
