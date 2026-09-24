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

See **`CONVENTIONS.md`** for the reference checklist (extracted from Module 5) used to judge
whether a module's code matches the app's own established pattern — read it before reviewing any
module's files.

---

## Module 1 — Auth & Identity 🟢

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
- **Shared components (new)**: `components/auth-card.blade.php`, `components/auth-illustration-panel.blade.php`

**Findings**
- ✅ Fixed earlier: login redirect defaulted to `route('home')` instead of `route('dashboard')`.
- ✅ Fixed earlier: `User::hasTwoFactorEnabled()` null-vs-bool TypeError on freshly-created models.
- ✅ **Fixed 2026-09-24 (review pass)**: `confirm-password.blade.php` was the one auth page never
  given the app's branded design — it rendered as plain unstyled Breeze stock while every sibling
  page had a custom card/illustration treatment. Now uses `<x-auth-card>`, the same component
  extracted from the other pages.
- ✅ **Fixed**: `login.blade.php`/`register.blade.php` had 100%-identical illustration-panel markup
  (only the description text differed) — extracted to `<x-auth-illustration-panel>`.
  `forgot-password`/`reset-password`/`verify-email`/`confirm-password` each duplicated the same
  card wrapper + header — extracted to `<x-auth-card>`.
- ✅ **Fixed**: `verify-email.blade.php` hand-rolled its own status banner instead of using the
  shared `<x-auth-session-status>` component `forgot-password.blade.php` already used. Extended
  that component's existing status-message map (it already had this exact pattern for
  `two-factor-code-sent`) to cover `verification-link-sent` too, rather than inlining a ternary at
  the call site — kept the mapping in one place.
- No controller/service/model issues found in this module — it's thin Breeze/Volt scaffolding by
  design, nothing to centralize beyond the view layer.

---

## Module 2 — User Profile & Main Dashboard 🟢

Profile editing, skills/software, social links, and the real landing dashboard (reviews, activity,
stats).

- **Routes**: `/profile`, `/dashboard` (now the real one)
- **Controller**: `DashBoardController`
- **Livewire**: `livewire/profile/{delete-user-form,social-links-form,two-factor-form,update-password-form,update-profile-information-form,user-profile-form}.blade.php`
- **Models**: `UserProfile`, `UserSocialLink`, `SocialNetwork`, `Skill`, `Software` (Skill/Software
  are shared with Module 3 — see cross-cutting note)
- **Views**: `resources/views/profile.blade.php`, `dashboard/index.blade.php` (641 lines)
- **Shared components (new)**: `components/section-header.blade.php`, `components/success-toast.blade.php`

**Findings**
- ✅ **Fixed**: two competing "dashboard" views. `dashboard` route now points directly at
  `DashBoardController@index`; the redundant `/my-dashboard` route group and the dead Breeze
  placeholder view are removed. The two nav bar links that pointed at `my-dashboard.index` were
  updated.
- ✅ **Fixed 2026-09-24 (review pass)**: the same gradient card-header markup was duplicated **8
  times** across all 6 profile Livewire components plus `dashboard/index.blade.php`, with zero
  reuse. Extracted to `<x-section-header>` (`variant: primary/danger`, optional `icon`/`action`
  slots).
- ✅ **Fixed, real bug**: `update-profile-information-form` and `user-profile-form` both dispatched
  the *same* browser event (`profile-updated`), and each had its own local success-toast listener
  for it — since both components render on the same `/profile` page, submitting **either** form
  popped **both** success toasts simultaneously. Renamed to `profile-info-updated` and
  `profile-details-updated`; verified via Volt component tests that each dispatches only its own
  event now.
- ✅ **Fixed, real bug**: `delete-user-form.blade.php` hand-rolled a full ~140-line Alpine modal
  (focus-trap, backdrop, transitions) that duplicated the app's existing `<x-modal>` component
  almost line for line — this is literally Breeze's stock delete-account pattern, which normally
  just uses `<x-modal>`; this file had reimplemented it from scratch instead. Now uses
  `<x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>`, preserving the
  reopen-with-errors-shown behavior (verified via a Volt test).
- ✅ **Fixed**: 4 duplicated Alpine success-toast blocks (2 layout variants: inline-next-to-button
  and fixed-top-right-banner) across `update-profile-information-form`, `update-password-form`,
  `user-profile-form`, `social-links-form` — extracted to `<x-success-toast>` (`variant:
  inline/floating`). Along the way, normalized a stray `top-10` vs `top-4` positioning drift
  between two of the floating instances.
- ✅ **Fixed**: two no-op `try { ... } catch (ValidationException $e) { throw $e; }` blocks
  (functionally identical to not having the try/catch at all) in `social-links-form::save()` and
  `user-profile-form::updateProfile()`, plus their now-unused imports.
- ✅ **Fixed**: `update-profile-information-form`'s inline "Verification email sent successfully!"
  banner was a third hand-rolled copy of the same status-message concept fixed in Module 1 — now
  uses `<x-auth-session-status>`.
- 🔴 `DashBoardController::calculateReviewStats()` and `getActivitySummary()` hand-roll raw
  `DB::table(...)` queries for counts that mostly duplicate what Eloquent relationships/scopes
  already express elsewhere (e.g. `job_engagements` status counts are already modeled in
  `JobEngagement` scopes used by `ProjectController` — see Module 6). Left open — lower value,
  touches Module 6 too, better done when that module's turn comes.

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
- ✅ **Fixed 2026-09-24**: `AdminDisputeController` imported `App\Services\PartialPaymentService`
  (doesn't exist) instead of `App\Services\Payments\PartialPaymentService`, throwing a fatal error
  on every request. One-line `use` fix, verified via container resolution and a real authenticated
  request to `/admin/disputes` (200 OK). The rest of Module 9's cleanup (inline validation instead
  of a FormRequest — see the cross-cutting note) is still open for when this module's full turn
  comes.

---

## Cross-cutting concerns (span every module)

- ✅ **Fixed 2026-09-24**: the flash-message `'alert' => [...]` pattern, reimplemented ~37 times
  across 11 files (controllers, a middleware, and services), is now centralized in
  `App\Helpers\FlashAlertHelper` (`success()`/`error()`/`info()`/`warning()`, plus `make()` for a
  dynamic type). Bigger discovery made along the way: the rich `alert` array was **dead data** —
  `partials/flash-messages.blade.php` only ever read the flat `session('success'/'error'/...)`
  strings with a hardcoded generic title, so all that per-action title effort
  (`'Application Archived!'`, `'Payment Processed'`, etc.) was invisible to users, who only ever
  saw a generic "Success"/"Error" toast. The partial now reads `session('alert')` first for a
  specific toast title (collapsing to title-only when title and text are identical, avoiding a
  redundant repeated sentence), falling back to the old generic-title toast for the ~23 call sites
  that only ever set a flat message with no natural distinct title — those were left alone.
  Also found and fixed a real duplicate-toast bug surfaced by this same work: `jobs/show.blade.php`
  and `applications/drafts.blade.php` each carried their own byte-for-byte-identical local copy of
  a success toast on top of the one the shared partial already renders — every success flash on
  those two pages fired twice. Both dead copies removed.
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

1. ✅ **Module 9 fix first, standalone** — the `AdminDisputeController` namespace bug. Done
   2026-09-24.
2. ✅ **Dashboard routing** — pulled forward from Module 2 alongside the fix above since it was
   the other quick, standalone win. Done 2026-09-24.
3. ✅ **Cross-cutting flash-alert helper** — done 2026-09-24. Turned out to also fix a dead-data bug
   (rich toast titles were never rendered) and a duplicate-toast bug found along the way.
4. ✅ **Module 1 (Auth) → Module 2 (Profile/Dashboard)** — done 2026-09-24. Found and fixed two real
   bugs beyond the componentization work: an event-name collision causing duplicate success toasts,
   and a hand-rolled modal duplicating the existing `<x-modal>` component.
5. **Module 5 (Engagements)** — already the best-structured module; cleanup here is mostly
   consolidating the Policy/Helper duplication and fixing `JobDeliverableController`'s
   inconsistency, plus some view componentization. Low risk, good template-setting work.
6. **Module 4 (Applications) using Module 5 as the template** — the controller-split and
   authorization-centralization work benefits from having just done the equivalent in Module 5.
7. **Module 3 (Jobs)** — wire up `JobFilterTrait`, resolve the `JobImageService` cross-domain
   leak, componentize `apply.blade.php`/`new.blade.php`.
8. **Module 6 (Projects)** — fold into `EngagementManagementService` once Module 5 is settled.
   `DashBoardController`'s raw `DB::table()` stat queries (flagged in Module 2) fit naturally here
   too.
9. **Module 7 (Messaging)** — needs a product decision (real-time or not) before code changes.
10. **Module 8 (Notifications)** — hasn't had a deep pass yet; do that pass as part of this
    module's turn.

This order is a proposal, not a commitment — reorder freely based on what matters most next.

## Status log

- **2026-09-24**: Initial full-codebase read and module map written. No cleanup changes made yet
  beyond what was already fixed in earlier sessions (footer-secondary include paths, 2FA bool cast,
  login redirect target, Pint formatting, dependency security updates).
- **2026-09-24 (later)**: Two quick fixes landed — `AdminDisputeController`'s fatal-error import bug
  (Module 9) and the `/dashboard` routing consolidation (Module 2). Both verified with real
  authenticated requests before committing.
- **2026-09-24 (later still)**: Cross-cutting flash-alert cleanup landed — `FlashAlertHelper`
  centralizes the ~37 call sites, `flash-messages.blade.php` now actually renders the specific
  toast titles that used to be dead data, and a real duplicate-toast bug (two views double-firing
  on success) was found and fixed along the way. 4 commits, verified with a real render test before
  committing.
- **2026-09-24 (Module 1/2 pass)**: `CONVENTIONS.md` written (extracted from the Module 5 audit) as
  the standing reference checklist for every future module review. Then read every remaining
  Auth/Profile/Dashboard file not yet covered by earlier sessions (Volt auth pages, all 6 profile
  Livewire components, dashboard/index.blade.php, navigation.blade.php) and applied it: 4 new shared
  components (`auth-card`, `auth-illustration-panel`, `section-header`, `success-toast`) replacing
  duplicated markup at ~15 call sites, plus two real bugs found and fixed — an event-name collision
  causing duplicate success toasts on the profile page, and a hand-rolled modal in
  `delete-user-form` duplicating the existing `<x-modal>` component. One self-inflicted bug caught
  and fixed before commit: a nested-double-quote Blade attribute (`:subtitle="__("...")"`) broke
  the whole component tag — caught by the verification test suite, not by inspection, which is
  exactly why every change in this pass was verified with a real render/request before committing,
  not just Pint+tests. 2 commits (Module 1, Module 2), both pushed. Next up: Module 5 (Engagements).
