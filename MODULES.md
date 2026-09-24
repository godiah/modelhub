# ModelHub — Module Map & Cleanup Tracker

Authoritative tracker for the codebase audit/cleanup initiative started 2026-09-24. This app was
built before agentic coding tools were part of the workflow — nobody has read it end-to-end as one
system until this pass. This document is step one: understand what exists and group it into
modules. Cleanup then proceeds module by module, checked off here as each one lands.

ModelHub is a freelance job board with an escrow-flavored engagement/payment lifecycle: post a
job → freelancers apply → employer hires → work happens as an "engagement" with deliverables →
completion, cancellation, partial payment, or dispute → reviews.

## How to read this doc

Each module lists the files that make it up (controller → services → helpers → models → views) and
a **Findings** subsection with concrete issues found while reading the code — not guesses, actual
things confirmed by reading or grepping the source. Findings are the real backlog; the module list
is just the map to navigate by. Status per module: 🔴 not started · 🟡 in progress · 🟢 done.

---

## Module 1 — Auth & Identity 🔴

Breeze + Livewire Volt scaffolding, extended with custom email-OTP two-factor auth (not Breeze
stock).

- **Routes**: `routes/auth.php`
- **Volt pages**: `resources/views/livewire/pages/auth/{login,register,forgot-password,reset-password,verify-email,confirm-password}.blade.php`
- **Controllers**: `Auth\VerifyEmailController`
- **Livewire**: `Livewire\Forms\LoginForm`, `Livewire\Actions\Logout`
- **Model logic**: `User`'s 2FA methods (`generateTwoFactorCode`, `verifyTwoFactorCode`,
  `enableTwoFactor`/`disableTwoFactor`, `hasTwoFactorEnabled`)
- **Mail**: `Mail\TwoFactorCode` + `emails/two-factor-code.blade.php`
- **Layout**: `layouts/guest.blade.php`

**Findings**
- ✅ Fixed this session: login redirect defaulted to `route('home')` instead of `route('dashboard')`.
- ✅ Fixed this session: `User::hasTwoFactorEnabled()` null-vs-bool TypeError on freshly-created models.
- 🔴 **`/dashboard` is Breeze's untouched placeholder** ("You're logged in!") — see Module 2. Since
  it's literally the post-login redirect target, every user's first stop after signing in is a dead
  page, not the real dashboard at `/my-dashboard`.

---

## Module 2 — User Profile & Main Dashboard 🔴

Profile editing, skills/software, social links, and the real landing dashboard (reviews, activity,
stats).

- **Routes**: `/profile`, `/dashboard` (stock, see finding below), `/my-dashboard` (real one)
- **Controller**: `DashBoardController`
- **Livewire**: `livewire/profile/{delete-user-form,social-links-form,two-factor-form,update-password-form,update-profile-information-form,user-profile-form}.blade.php`
- **Models**: `UserProfile`, `UserSocialLink`, `SocialNetwork`, `Skill`, `Software` (Skill/Software
  are shared with Module 3 — see cross-cutting note)
- **Views**: `resources/views/profile.blade.php`, `dashboard.blade.php` (stock, dead),
  `dashboard/index.blade.php` (real, 641 lines)

**Findings**
- 🔴 **Two competing "dashboard" views.** `dashboard.blade.php` (Breeze stock placeholder) is bound
  to the `dashboard` route name and is the post-login redirect target. `dashboard/index.blade.php`
  (641 lines, the actual reviews/stats/activity dashboard) is bound to `/my-dashboard` via
  `DashBoardController`. Needs a decision: either point `dashboard` route at the controller and
  drop `/my-dashboard`, or rename routes so `dashboard` unambiguously means the real page. Every
  `route('dashboard', ...)` call site (login redirect, email verification redirect) needs checking
  once this is resolved.
- 🔴 `DashBoardController::calculateReviewStats()` and `getActivitySummary()` hand-roll raw
  `DB::table(...)` queries for counts that mostly duplicate what Eloquent relationships/scopes
  already express elsewhere (e.g. `job_engagements` status counts are already modeled in
  `JobEngagement` scopes used by `ProjectController` — see Module 6).

---

## Module 3 — Job Postings 🔴

Creating, editing, browsing, and closing job listings. The "supply" side of the board.

- **Controller**: `JobController`
- **Services**: `Jobs\{JobManagementService,JobBrowsingService,JobSlugService,JobImageService}`
- **Helper**: `Helpers\Jobs\JobCacheHelper` (similar-jobs caching)
- **Trait**: `Traits\JobFilterTrait` (search/skills/software/sort query scopes)
- **Models**: `ModelJob`, `JobImage`
- **Console**: `Console\Commands\DeactivateExpiredJobs` (hourly scheduled)
- **Views**: `resources/views/jobBoard/jobs/*`, `components/jobs/*`

**Findings**
- 🔴 **`JobFilterTrait` is attached to `ModelJob` but never called.** It defines
  `scopeWithSearch`/`scopeWithSkills`/`scopeWithSoftware`/`scopeWithSorting` — exactly the logic
  `JobBrowsingService` needs — but `JobBrowsingService::browseJobs()` reimplements the same four
  filters as private methods instead of calling the scopes it already has for free
  (`$query->withSearch($search)->withSkills($skills)...`). Either wire the service to the trait, or
  delete the trait — right now it's dead weight duplicated by hand.
- 🔴 `JobImageService` has `handlePortfolioFiles()`/`deletePortfolioFiles()` methods that just
  delegate to `Helpers\Applications\ApplicationFileHelper` — a Jobs-domain service reaching into
  the Applications domain for something that isn't job-image-related at all (it's applicant
  portfolio files). These two methods look misplaced; likely belong directly on
  `ApplicationFileHelper`'s call sites instead of proxied through `JobImageService`.
- 🟡 `jobs/apply.blade.php` is **1,100 lines**, `jobs/new.blade.php` is 469 — both are job-posting-shaped
  forms (skills picker, software picker, image upload) that likely share significant markup with
  each other and with `jobs/edit.blade.php` (181 lines). Prime componentization target.

---

## Module 4 — Applications & Hiring 🔴

Covers both sides of the applicant/employer relationship before an engagement exists: applying,
withdrawing, drafts, the employer's applicant review queue, messaging applicants, and confirming a
hire (which hands off to Module 5).

- **Controller**: `JobApplicationController` (see finding — does two jobs)
- **Services**: `Applications\{ApplicationManagementService,ApplicationBrowsingService,ApplicationHiringService,ApplicationMessagingService}`
- **Helpers**: `Helpers\Applications\{ApplicationCalculationHelper,ApplicationFileHelper}`
- **Policy**: `JobApplicationPolicy`
- **Observer**: `JobApplicationObserver` (keeps `model_jobs.applicants_count` in sync)
- **Model**: `JobApplication`
- **Related, smaller**: `MessageTemplateController` + `MessageTemplate` model (canned messages
  employers send to applicants — tightly coupled to `ApplicationMessagingService`'s email flow)
- **Views**: `resources/views/jobBoard/applications/*` (applicant side), `resources/views/jobBoard/posted/*`
  (employer side: posted jobs, applicants-per-job, archived)

**Findings**
- 🔴 **`JobApplicationController` is really two controllers.** Roughly the first half (`store`,
  `continueDraft`, `getUserApplications`, drafts, archive/restore/destroy, `show`) is the
  *applicant's* view of their own applications. The second half (`getUserPostedJobs`,
  `getJobApplications`, `showApplications`, `updateStatus`, `confirmHire`, `sendMessage`,
  archived-jobs handling) is the *employer's* review/hiring queue. They share almost no state
  (different services injected for different halves) and even have their own separate
  `unauthorizedError()` helper duplicate. Natural split: `JobApplicationController` (applicant) +
  `PostedJobApplicationController` or similar (employer).
- 🔴 **Authorization is inline and repeated instead of centralized**, unlike Module 5's Engagement
  services which all delegate to `EngagementAuthorizationHelper`. Ten occurrences across this
  module of the same shape (`Auth::user()->id === $application->job->user_id` /
  `Auth::id() !== $engagement->poster->id`, etc.) live independently in
  `ApplicationBrowsingService::authorizeApplicationView`,
  `ApplicationHiringService::{authorizeStatusUpdate,updateStatus}`,
  `ApplicationMessagingService::{authorizeMessageSending,sendMessage}`, and more. `JobApplicationPolicy`
  already exists and only covers `view`/`update`/`delete` — extending it (or an
  `ApplicationAuthorizationHelper` mirroring Module 5's pattern) would collapse all ten into one
  place, the same fix that already happened for Engagements.
- 🔴 The flash-message `'alert' => ['type' => ..., 'title' => ..., 'text' => ...]` response shape
  is repeated **11 times** in this controller alone (23 times app-wide) — see the cross-cutting
  finding at the bottom of this doc.

---

## Module 5 — Job Engagements (contract lifecycle) 🔴

The best-architected part of the codebase — worth using as the reference pattern for cleaning up
Modules 3–4. Covers everything from "offer accepted" through deliverables, completion,
cancellation, disputes, and partial payment.

- **Controllers**: `JobEngagementController`, `JobDeliverableController`, `PartialPaymentController`,
  `PolicyManagementController` (single static page)
- **Services**: `Engagements\{EngagementManagementService,EngagementResponseService,EngagementReviewService,EngagementCancellationService,EngagementPaymentService}`,
  `Payments\PartialPaymentService`
- **Helpers**: `Helpers\Engagements\{EngagementAuthorizationHelper,EngagementNotificationHelper}`
- **Middleware**: `VerifyJobEngagementOwnership` (route-level, for the respond-to-offer flow)
- **Policy**: `JobEngagementPolicy`
- **Models**: `JobEngagement`, `JobDeliverable`, `JobCancellation`, `JobPartialPayment`,
  `JobPaymentDispute`, `JobReview`
- **Views**: `resources/views/jobBoard/engagements/*` — the biggest view subtree in the app (~35
  files including a `partials/` directory already split into `details/`, `cancelled/`,
  `components/modals/`, `disputed/` — this module already does the componentization the user wants
  more of elsewhere)

**Findings**
- 🔴 **Duplicated authorization logic between a Policy and a Helper.** `JobEngagementPolicy::view()`
  and `EngagementAuthorizationHelper::canView()` implement the *exact same* check (poster, applicant,
  or admin) independently — one will drift from the other the next time someone edits just one of
  them. Collapse to one source of truth (the Policy is the idiomatic Laravel mechanism; have the
  helper/services call `Gate::allows()` instead of re-implementing the boolean logic).
- 🔴 **`JobDeliverableController` breaks the module's own pattern.** Every other controller in this
  module delegates auth checks to `EngagementAuthorizationHelper` and validation to FormRequest
  classes. `JobDeliverableController` does neither — permission checks are inline
  (`Auth::id() !== $engagement->poster->id`, repeated 4 times in this one file) and validation is
  inline `$request->validate([...])` instead of a dedicated Request class. It also defines its own
  local `respondWith()`/`respondWithSuccess()`/`respondWithError()` trio — a third independent
  implementation of the same flash-alert shape every other controller hand-rolls differently.
- 🔴 **`PartialPaymentController`/`AdminDisputeController` also skip FormRequests** — inline
  `$request->validate()` calls, same inconsistency.
- 🔴 **`App\Services\Payments\PartialPaymentService::resolveDispute()` has dead/wrong code**:
  `$client = $engagement->poster->user;` and `$freelancer = $engagement->applicant->user;` — but
  `$engagement->poster` and `$engagement->applicant` are *already* `User` models (via
  `hasOneThrough` on `JobEngagement`), so `->user` resolves to `null` every time. Currently harmless
  only because the resulting variables are never used (the notification-sending code below them is
  commented out) — but it's wrong on its face and will bite whoever implements those notifications
  next, copying the existing (broken) pattern.
- 🔴 `EngagementNotificationHelper::sendReviewNotification()` and `::sendPaymentNotification()` are
  empty stub methods — not called from anywhere, not implemented. Either wire them up when review/
  payment notifications are actually built, or remove until then.
- 🟡 `jobs/engagements/policy.blade.php` (1,078 lines, a single static cancellation-policy page) and
  `engagements/disputed-engagements.blade.php` (600 lines) are the largest views in the module and
  candidates for breaking into partials, following the pattern the rest of this module already
  uses well.

---

## Module 6 — Projects Dashboard 🔴

A read-only, tab-filtered overview of a user's engagements ("My Projects"). Functionally a second
lens over the same data Module 5 already models.

- **Controller**: `ProjectController`
- **Views**: `resources/views/projects/*`

**Findings**
- 🔴 **Significant overlap with `EngagementManagementService`.** `ProjectController` hand-rolls its
  own tab-based status filtering (`getEngagementsByTab()`) and stats calculation
  (`getEngagementStats()`) directly against `JobEngagement`, duplicating query patterns
  `EngagementManagementService::getUserEngagements()` already expresses (same `activeForUser`/
  `archivedForUser` scopes, same status enumeration). This controller has no dedicated Service
  class at all — everything lives in two private controller methods. Worth folding into
  `EngagementManagementService` (or a new `ProjectDashboardService`) both for consistency with
  every other controller in the app and to stop the stats logic from silently diverging from the
  engagements list logic over time.

---

## Module 7 — Messaging / Chat 🔴

Per-engagement chat between client and freelancer.

- **Controller**: `MessageController`
- **Model**: `Message`
- **Event**: `Events\NewMessageEvent`

**Findings**
- 🔴 **`NewMessageEvent` is dead code.** It's fully written (broadcasts on a private channel per
  engagement) but never dispatched anywhere in the codebase, and it doesn't even implement
  `ShouldBroadcast` — so even if something did `event(new NewMessageEvent($message))`, Laravel
  wouldn't actually broadcast it. Chat currently works purely via polling/AJAX
  (`MessageController::getEngagementData`/`store` return plain JSON). Either this was scaffolded
  for a real-time upgrade that never landed, or it should be deleted. Needs a decision, not a
  silent fix — implementing real-time chat is a feature decision, not a cleanup one.
- `MessageController` has a large commented-out `unreadCount()` method (~30 lines) — dead code to
  remove or finish.

---

## Module 8 — Notifications & Transactional Email 🔴

Cross-cutting: every other module fires into this one.

- **Controller**: `NotificationController`
- **Notifications** (7): `DisputeCreatedNotification`, `EngagementCancelledNotification`,
  `EngagementResponseNotification`, `HiredNotification`, `JobPostedNotification`,
  `NewApplicationMessage`, `PartialPaymentProcessedNotification`
- **Mail** (5): `ApplicationHired`, `ApplicationMessage`, `DeliverableSubmitted`, `JobPostedMail`,
  `TwoFactorCode`
- **Views**: `resources/views/notifications/*`, `resources/views/emails/*`

**Findings** (lighter pass so far — this module hasn't been read file-by-file yet, only its
call sites from other modules)
- `NotificationController::markAsRead()` has a 4-branch `if/elseif` chain keyed on the
  notification's fully-qualified class name string (`'App\Notifications\NewApplicationMessage'`
  etc.) to decide both the AJAX response shape and the redirect target. This is the kind of thing
  that's easy to forget to extend when a new notification type ships (nothing enforces it) — worth
  a closer look when this module's turn comes, possibly a method on each Notification class instead
  of a controller-side switch.

---

## Module 9 — Admin & Dispute Resolution 🔴

- **Controller**: `Admin\AdminDisputeController`
- **Views**: `resources/views/admin/disputes/*`
- **Access control**: `role:admin` middleware + Spatie permissions (`RolesAndPermissionsSeeder`)

**Findings**
- 🔴🔴 **Critical, currently-broken feature.** `AdminDisputeController` imports
  `App\Services\PartialPaymentService`, but that class doesn't exist — the real class is
  `App\Services\Payments\PartialPaymentService` (note the `Payments` sub-namespace). Since it's
  constructor-injected, **every single request to `/admin/disputes` throws a fatal
  `Class "App\Services\PartialPaymentService" not found" error.** The entire admin dispute
  resolution panel — the only place `JobPaymentDispute` records ever get resolved — is dead on
  arrival right now. This should be the first fix when this module's turn comes (it's a one-line
  `use` statement fix), independent of the rest of the module-by-module cleanup order.

---

## Cross-cutting concerns (span every module)

- **The flash-message `'alert' => [...]` pattern is reimplemented at least 4 separate ways**: ad
  hoc inline arrays in most controllers (23 occurrences total), `JobController`'s
  `unauthorizedError()`/`jobClosedError()`, `JobApplicationController`'s own
  `unauthorizedError()`, and `JobDeliverableController`'s `respondWith()` family. All four produce
  the same `['success'|'error' => ..., 'alert' => ['type','title','text']]` shape. This is the
  single highest-leverage cleanup: one trait (e.g. `HasFlashAlerts`, `success()`/`error()` methods)
  used by every controller would remove ~30+ near-duplicate lines and make the shape consistent
  everywhere (right now some controllers set `'text'` differently, some omit `'icon'`, etc.).
- **Existing Blade componentization is real but narrow.** `components/jobs/*` (star-rating,
  benefit cards, skill/software SVGs) and `jobBoard/engagements/partials/*` (~20 files, well
  organized into `details/`, `cancelled/`, `modals/`) show the pattern already works well here.
  Modules 3 and 4's views (`jobs/apply.blade.php` 1,100 lines, `jobs/new.blade.php` 469 lines,
  `applications/continue-draft.blade.php` 498 lines) haven't had the same treatment — same
  technique, just not applied yet.
- **FormRequest usage is inconsistent.** Modules 3–4 and most of Module 5 use dedicated
  `Http\Requests\*` classes consistently. `JobDeliverableController`, `PartialPaymentController`,
  and `AdminDisputeController` all validate inline instead. Worth standardizing one way.
- **Authorization: three different mechanisms coexist** — Policies (`JobApplicationPolicy`,
  `JobEngagementPolicy`), static Helpers (`EngagementAuthorizationHelper`), and inline
  `Auth::id() === ...` checks scattered through services and controllers. Module 5 shows the
  Helper pattern works well when used consistently; Module 4 shows what happens when it isn't
  (10 independent inline copies of the same check). Worth picking one mechanism and using it
  everywhere — Policies are the Laravel-idiomatic choice given two already exist.

---

## Proposed cleanup order

1. **Module 9 fix first, standalone** — the `AdminDisputeController` namespace bug is a one-line,
   zero-risk fix that unbreaks a completely dead feature. Do this immediately, not as part of a
   full module pass.
2. **Cross-cutting flash-alert trait** — highest leverage, touches every module, best done once
   before further module work so later modules don't add a 5th reimplementation of the same thing.
3. **Module 1 (Auth) → Module 2 (Profile/Dashboard)** — small, foundational, and Module 2 has a
   real routing decision (`/dashboard` vs `/my-dashboard`) that other modules' links depend on.
4. **Module 5 (Engagements)** — already the best-structured module; cleanup here is mostly
   consolidating the Policy/Helper duplication and fixing `JobDeliverableController`'s
   inconsistency, plus some view componentization. Low risk, good template-setting work.
5. **Module 4 (Applications) using Module 5 as the template** — the controller-split and
   authorization-centralization work benefits from having just done the equivalent in Module 5.
6. **Module 3 (Jobs)** — wire up `JobFilterTrait`, resolve the `JobImageService` cross-domain
   leak, componentize `apply.blade.php`/`new.blade.php`.
7. **Module 6 (Projects)** — fold into `EngagementManagementService` once Module 5 is settled.
8. **Module 7 (Messaging)** — needs a product decision (real-time or not) before code changes.
9. **Module 8 (Notifications)** — hasn't had a deep pass yet; do that pass as part of this module's
   turn.

This order is a proposal, not a commitment — reorder freely based on what matters most next.

## Status log

- **2026-09-24**: Initial full-codebase read and module map written. No cleanup changes made yet
  beyond what was already fixed in earlier sessions (footer-secondary include paths, 2FA bool cast,
  login redirect target, Pint formatting, dependency security updates).
