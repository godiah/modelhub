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

## Module 3 — Job Postings 🟢

Creating, editing, browsing, and closing job listings. The "supply" side of the board.

- **Controller**: `JobController`
- **Services**: `Jobs\{JobManagementService,JobBrowsingService,JobSlugService,JobImageService}`
- **Helper**: `Helpers\Jobs\JobCacheHelper` (similar-jobs caching)
- **Trait**: `Traits\JobFilterTrait` (search/skills/software/sort query scopes)
- **Models**: `ModelJob`, `JobImage`
- **Console**: `Console\Commands\DeactivateExpiredJobs` (hourly scheduled)
- **Views**: `resources/views/jobBoard/jobs/*`, `components/jobs/*`

**Findings**
- ✅ **`JobFilterTrait` wired up.** `JobBrowsingService::browseJobs()` now calls the trait's
  `scopeWithSearch()`/`scopeWithSorting()` instead of reimplementing them as private methods.
  Skill/software filters keep their id→name resolution in the service (ModelJob.skills/software
  store names, not ids) but delegate the actual query condition to `scopeWithSkills()`/
  `scopeWithSoftware()`.
- ✅ **`JobImageService` cross-domain leak removed.** `handlePortfolioFiles()`/`deletePortfolioFiles()`
  (pure passthroughs to `Applications\ApplicationFileHelper`) deleted; `ApplicationManagementService`
  now calls `ApplicationFileHelper` directly instead of proxying through the Jobs domain.
- ✅ **`jobBoard/jobs/browse.blade.php` no longer queries models directly in the view.**
  `Skill`/`Software` lists now come from `JobBrowsingService::getFilterOptions()` via the
  controller (`CONVENTIONS.md` item 14).
- ✅ **`jobs/new.blade.php` and `jobs/edit.blade.php` componentized.** Both shared a form header
  banner and a Budget/Deadline field pair (identical markup, minor value/state differences).
  Extracted to `components/jobs/{form-header,budget-field,deadline-field}.blade.php` — the header
  takes `title`/`subtitle` props plus `icon`/`decoration` slots; the fields take value/required/
  checked/dimmed-style props so both the "create" (empty, `old()`-backed) and "edit" (prefilled
  from `$job`) states reuse the same markup. `new.blade.php` dropped ~110 lines, `edit.blade.php`
  ~80.
- 🟡 **Correction to an earlier note in this file**: `jobs/apply.blade.php` (1,100 lines) is *not*
  a job-posting form like `new.blade.php`/`edit.blade.php` — it's a job-details display +
  application-submission form (different concern, shares no real markup with the posting forms).
  It's a large view and still a componentization candidate, but on its own terms — possibly
  alongside `jobs/show.blade.php`, which also displays job details — not lumped in with this
  batch.

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
- ✅ Flash-message handling already fixed app-wide (including this module) by the cross-cutting
  `FlashAlertHelper` pass — no longer a Module 4-specific finding.
- 🔴 **No rate limiting on `applications.store` (applying to a job)** — `CONVENTIONS.md` item 13.
- 🔴 **`MessageTemplateController` returns raw Eloquent collections via `response()->json()`**
  (`index()`/`store()`) instead of an API Resource — `CONVENTIONS.md` item 14.

---

## Module 5 — Job Engagements (contract lifecycle) 🟡

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
- ✅ **Duplicated authorization logic between a Policy and a Helper — consolidated.** Turned out to
  be three drift-prone duplicates, not one: `JobEngagementPolicy::view()` vs. the (then-unused)
  `EngagementAuthorizationHelper::canView()` vs. a third private copy of the same poster/applicant/
  admin check hand-rolled in `EngagementCancellationService::canViewCancelledEngagement()` (actually
  called, from `getCancelledEngagementDetails()`/`getDisputedEngagementDetails()`). Also found
  `JobEngagementPolicy::respondToOffer()` (unused) duplicated by both
  `EngagementAuthorizationHelper::canRespondToOffer()` (used by `EngagementResponseService`) and a
  third inline copy in `VerifyJobEngagementOwnership` middleware. `JobEngagementPolicy` is now the
  single source of truth for both `view` and `respondToOffer`; the Helper's `canView()`/
  `canRespondToOffer()` are thin `Gate::forUser($user)->allows(...)` wrappers (keeping the
  Service→Helper calling convention this module already uses elsewhere); the middleware and
  `EngagementCancellationService` now call the Helper instead of re-implementing the boolean logic.
  The unused, wrongly-homed `JobEngagementPolicy::viewResponseForm(User, JobApplication)` (lived on
  the Engagement policy but took a JobApplication — would not have resolved via Laravel's policy
  auto-discovery if ever wired up) was deleted as dead code rather than force-fit into the
  consolidation. Verified with real HTTP requests through the middleware/controller and direct
  service calls, covering allowed and denied users at each layer, before committing.
- ✅ **`JobDeliverableController` auth/validation inconsistency fixed — and a real authorization
  bypass found along the way.** `store`/`destroy`/`approve`/`reject` had four separate inline copies
  of `Auth::id() !== $engagement->poster->id`; `update` had its own inline poster-or-applicant copy.
  Worse: `submit()` — the action the *applicant* uses to upload their actual deliverable files — had
  **no permission check at all**. Any authenticated user could `POST` to
  `deliverables/{deliverable}/submit` for an engagement they had no part in and overwrite someone
  else's submission files/notes; only route-model binding stood between the request and the
  deliverable. Fixed by adding `canManageDeliverables()` (poster-only), `canEditDeliverable()`
  (poster or applicant), and `canSubmitDeliverable()` (applicant-only) to
  `EngagementAuthorizationHelper`, used at all six call sites. Validation moved to five new
  `Http\Requests\Deliverable\*` FormRequest classes (`Store`/`Update`/`Submit`/`Approve`/
  `RejectDeliverableRequest`), matching this module's established convention
  (`authorize()` always `true`, permission checks stay in the Helper — same pattern as the
  `Engagement\*` requests). As a side effect this also fixed a real bug in `store()`: validation used
  to run *inside* a `try/catch (\Exception $e)` block, so a `ValidationException` (which extends
  `Exception`) was being swallowed into a generic "Something went wrong" flash instead of Laravel's
  normal field-level error response — FormRequest validation now runs before the controller method,
  outside that catch entirely. Verified with real HTTP requests covering every role×action
  combination (including the fixed `submit()` bypass) before committing.
- ✅ **`PartialPaymentController`/`AdminDisputeController` FormRequests added; a third copy of the
  "is applicant" auth check found and consolidated too.** `processDisputePartialPayment()`'s inline
  `$request->validate()` → new `ProcessDisputePartialPaymentRequest`; `AdminDisputeController::
  resolve()`'s inline validate → new `ResolveDisputeRequest` (admin-only is already enforced at the
  route level via `role:admin` middleware, unaffected). While in this code, found the same
  poster/applicant duplication pattern again: `$authUser->id !== $engagement->application->
  applicant_id` was hand-rolled independently in the controller's `disputePartialPayment()` (GET
  form) and in `PartialPaymentService::acceptPartialPayment()`/`disputePartialPayment()` — three
  copies of one check. Added `EngagementAuthorizationHelper::canRespondToPartialPayment()` and used
  it at all three. `PartialPaymentService::canProcessPayment()` has a fourth, related but *not*
  identical duplicate (poster-or-admin, entangled with cancelled-status/pending-deliverables
  business rules in one method) — left alone this pass since unwinding it risks the business logic
  it's mixed with; tracked as its own item below.
- ✅ **`PartialPaymentService::resolveDispute()` dead/wrong code removed.** `$client = $engagement->
  poster->user;` / `$freelancer = $engagement->applicant->user;` always resolved to `null` (`poster`/
  `applicant` are already `User` models via `hasOneThrough`) and were never used — deleted, replaced
  with a one-line comment for whoever wires up the commented-out notification calls next.
- ✅ **The orphaned manual-amount payment flow is now wired up as an option on the live route**,
  per an explicit product decision (manual override should ship, not be deleted). Consolidated to a
  single implementation instead of two competing ones: `PartialPaymentController::
  processPartialPayment()` now takes the (already-live, already-namespaced)
  `ProcessPartialPaymentRequest`, whose `payment_amount` is now `nullable` rather than `required`
  — omitted, it auto-calculates from approved deliverables exactly as before; provided, it overrides
  the calculation. `PartialPaymentService::processPartialPayment($engagement, $manualAmount, $notes)`
  gained a `$notes` param and a fix: the old code only validated `amount <= 0` on the *auto-calculated*
  branch, so a manual amount of 0 or less would have silently skipped that guard; also added an
  upper bound (`$amount > $engagement->net_amount` is rejected) since a free-text override with no
  ceiling is a real risk on a money field. Deleted the now-fully-superseded duplicate:
  `JobEngagementController::processPartialPayment()` and `EngagementPaymentService::
  processPartialPayment()` (the latter's other 4 methods — `getPaymentInfo`/`canProcessPayment`/
  `getLatestPayment`/`calculatePartialPayment` — are still live and untouched). Added an optional
  amount input (blank = auto-calculate, with the calculated amount shown as a placeholder) to the
  "Process Payment" form in `payment-details.blade.php`. Verified auto-calc, manual override,
  the zero/negative rejection, and the net-amount ceiling with real HTTP requests before committing.
  Side note, not chased further this pass: `EngagementPaymentService::canProcessPayment()` and
  `::calculatePartialPayment()` turned out to also have zero external callers (only `getPaymentInfo`/
  `getLatestPayment` are actually used) — left alone since it wasn't part of the ask, but worth a
  look next time this service is touched.
- ✅ **`EngagementNotificationHelper::sendPaymentNotification()` wired up** — `Notifications\
  PartialPaymentProcessedNotification` (mail + database + broadcast, plus its email Blade view) was
  fully built but never dispatched anywhere. Wired the helper method to send it to
  `$engagement->applicant` and called it from `PartialPaymentService::processPartialPayment()`,
  replacing a commented-out call to a *different*, never-built class name
  (`PartialPaymentReadyNotification`) that had been sitting there as a stale reminder. Verified with
  `Notification::fake()` that the freelancer (not the poster) receives it.
- 🔴 **Still open, needs a product decision, not a silent build:** `sendReviewNotification()` has no
  backing `Notification` class at all (unlike the payment one, which just needed wiring up) — building
  one is new feature work, not a gap-fill. Same story for two other commented-out call sites found
  in `PartialPaymentService` referencing notification classes that were never created:
  `PaymentAcceptedNotification` (in `acceptPartialPayment()`) and `PaymentDisputedNotification` (in
  `disputePartialPayment()`). All three need someone to decide what the notification should say and
  who receives it before there's anything to wire up.
- ✅ **Deliverable submissions and dispute evidence moved off the public disk.**
  `JobDeliverableController::submit()`/`::destroy()`'s file cleanup and
  `PartialPaymentController::processDisputePartialPayment()` now `store(...,'local')` (Laravel's
  private disk, rooted outside the symlinked `public/storage` path) instead of `'public'`. Since
  these files were no longer reachable via `Storage::url()`/`asset('storage/...')`, added two
  authorization-gated download routes — `GET deliverables/{deliverable}/files/{index}/download`
  (`JobDeliverableController::downloadSubmissionFile()`) and
  `GET engagements/disputes/{dispute}/evidence/{index}/download`
  (`PartialPaymentController::downloadDisputeEvidence()`) — both gated by
  `EngagementAuthorizationHelper::canView()` (poster, applicant, or admin only), streaming the file
  via `Storage::disk('local')->download()` rather than exposing a public path. Updated the four
  Blade call sites that built raw `Storage::url()`/`asset('storage/...')` links
  (`engagements-list.blade.php` ×3, `deliverables-details.blade.php`, `disputed-engagements.blade.php`)
  to link to the new routes instead, indexing into the `submission_files`/`supporting_evidence`
  arrays by position rather than trusting any client-supplied path. No migration of already-uploaded
  files was needed — checked the storage directory directly and found none exist yet (this app has
  no production deployment so there was no real user data at the old public paths). Verified with
  `Storage::fake()` that new uploads land on `local` and not `public`, and that download access is
  correctly granted/denied by role, before committing.
- ✅ **Rate limiting added to payment processing and dispute submission routes.** `throttle:10,1`
  (matching the limit style already used for email verification elsewhere in this app) now wraps
  `process-partial-payment`, `accept-partial-payment`, and `process-dispute-partial-payment` as a
  route-group middleware. `CONVENTIONS.md` item 13 updated. Verified with 11 rapid requests that the
  11th returns 429 before committing. `admin.disputes.resolve` left alone — already gated by
  `role:admin`, a stronger protection than a public-facing throttle, and not part of the flagged
  finding.
- 🔴 **No backed enums for engagement/payment/dispute statuses** — `JobEngagement`,
  `JobPaymentDispute`, and `JobPartialPayment` each use `const STATUS_X = 'x'` string constants
  instead. Good candidate module to convert first, given `CONVENTIONS.md` item 11 recommends doing
  this module-by-module rather than as one sweep.
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
- 🔴 **`Model::preventLazyLoading()` is never set.** `AppServiceProvider::boot()` only registers the
  `JobApplicationObserver` — no lazy-loading guard for local/testing. Cheap, safe, app-wide fix
  (`CONVENTIONS.md` item 11); do it whenever any module's turn touches `AppServiceProvider`, no
  need to wait for a dedicated pass.
- 🔴🔴 **Zero test coverage of any actual business logic.** The whole suite is 26 tests, all Breeze's
  stock Auth/Profile scaffolding — nothing covers Jobs, Applications, Engagements, Payments,
  Disputes, or Messaging. `CONVENTIONS.md` item 15 sets the going-forward testing convention
  (E2E-first, Pest browser testing once `pestphp/pest` is bumped to `^4.0`). A workflow with no
  test is itself a finding under that workflow's module, same as any other deviation — not tracked
  separately here.

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
7. ✅ **Module 3 (Jobs)** — done 2026-09-24. `JobFilterTrait` wired up, `JobImageService`
   cross-domain leak resolved, query-in-Blade fixed, form-header/budget/deadline componentized
   across `new.blade.php`/`edit.blade.php`. `apply.blade.php`'s own componentization is deferred —
   turned out not to be the same shape as the posting forms (see Module 3 note above).
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
- **2026-09-24 (conventions review)**: User supplied a second batch of suggested conventions (data
  layer, async/side-effects, security, structure, testing). Each was individually verified against
  the actual codebase before being added — most turned out to already be followed
  (`$fillable`, `decimal:2` money, `env()` discipline, query scopes, constructor injection, named
  routes/route-model-binding), a handful are real confirmed gaps now tracked per-module above
  (no `preventLazyLoading`, no backed enums, no rate limiting on sensitive actions, one
  query-in-Blade, one raw-model JSON response, inconsistent logging-before-throw), and one is a
  real confidentiality issue rather than a style gap (deliverables/dispute-evidence on the public
  disk — Module 5). Three items needed the user's own decision and got one: keep the
  `EngagementNotificationHelper`-style static Helper pattern (no Events), keep the array-shaped
  service result (no `ServiceResult` DTO), and Pest browser testing over Dusk for the E2E suite
  once it starts. `CONVENTIONS.md` items 11–15 record all of this. No code changed in this pass —
  it was a conventions/documentation update only.
- **2026-09-24 (Module 3 pass)**: User opted to jump to Module 3 (Jobs) ahead of Module 5 in the
  proposed order. `JobBrowsingService::browseJobs()` wired to `JobFilterTrait`'s scopes instead of
  hand-duplicated filter methods (verified with real query tests); `JobImageService`'s cross-domain
  passthrough to `ApplicationFileHelper` removed, `ApplicationManagementService` now calls it
  directly; `browse.blade.php`'s inline `Skill`/`Software` queries replaced with controller-supplied
  data via a new `getFilterOptions()` method (also fixed a loop-variable shadowing bug this
  surfaced); `new.blade.php`/`edit.blade.php` had their shared form-header banner and Budget/
  Deadline field markup extracted into `components/jobs/{form-header,budget-field,deadline-field}
  .blade.php`. Along the way, found and fixed an unrelated but serious bug: `/jobs/create` and
  `POST /jobs` had no `auth` middleware at all (public job-creation routes were fatal-erroring for
  guests instead of redirecting to login) — fixed by adding the middleware and re-ordering the route
  group so the now-more-specific static routes register before the `{job:slug}` wildcard. Also
  created `ModelJobFactory` (didn't exist before), needed for the new verification tests. All of it
  verified with real HTTP/component tests before committing; temp tests deleted after. 2 commits,
  both pushed. Corrected an earlier note in this file: `apply.blade.php` is not a job-posting form
  like `new`/`edit` — it's job-details display + application submission, a different concern.
