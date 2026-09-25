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
- ✅ **`getActivitySummary()`'s `job_engagements` counts fixed in the Module 6 pass** (2026-09-25) —
  they now reuse `JobEngagement::activeForUser()`, closing an archived-engagement mismatch bug
  between this dashboard and the Projects Dashboard. See Module 6's findings for the detail.
  `calculateReviewStats()` (a `JobReview` aggregate, no engagement-scope overlap) is unaffected and
  still a reasonable use of a raw aggregate — not a finding.

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
- ✅ **Correction, 2026-09-25**: a Module 4 smoke test flagged `JobController::show()`'s
  poster-only authorization (`JobManagementService::authorizeJobView()`) as "looks backwards,"
  reasoning that a public job board shouldn't gate its detail page to the poster. That was a
  misdiagnosis — traced properly this time by checking every actual caller of the `jobs.show`
  route rather than just hitting it in isolation. `jobs.show` is **not** the public job-details
  page; it's exclusively linked from poster-only contexts: the "job posted" confirmation email
  ("View Your Project"), the poster's own "my posted jobs" grid
  (`jobBoard/posted/partials/jobs-grid.blade.php`), and the post-create/post-update redirects in
  `JobController::store()`/`update()`. The view itself (`jobs/show.blade.php`) has an "Edit" link
  and a "back to my-jobs" link — it's the poster's own job-management preview, parallel to
  `my-jobs`'s other pages, not a public listing detail page. The actual public job-details +
  apply flow is `jobs.apply` (`/{job:slug}/apply`), linked from the public browse listing
  (`jobs-list.blade.php`) and correctly carrying no ownership check at all — already noted above
  as the real job-details-display page, this just confirms `jobs.show` isn't a second one.
  `authorizeJobView()`'s poster-only check is correct as written; no fix needed. The stale
  "Move to a policy" comment on that method (a leftover TODO, unrelated to this) is a real,
  separate, low-priority item if `Module 3` ever gets a revisit.

---

## Module 4 — Applications & Hiring 🟢

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
- ✅ **`confirmHire()` had zero permission check at all — a real authorization bypass, not just a
  style deviation.** Any authenticated user could `POST` to `my-jobs/applications/{application}/
  confirm-hire` for an application they had no part in, creating the paid engagement and flipping
  the application to `hired`. Same class of bug as Module 5's `JobDeliverableController::submit()`
  bypass. Fixed alongside the authorization-centralization work below rather than as a narrow
  patch, since the same missing check pattern was the root cause of both.
- ✅ **Authorization centralized.** `Auth::user()->id === $application->job->user_id` was hand-rolled
  independently six times (a hard guard plus a duplicate boolean `authorizeXxx()` helper, in each
  of `ApplicationBrowsingService`, `ApplicationHiringService`, `ApplicationMessagingService`).
  Added `JobApplicationPolicy::manage()` (poster-only, reading the model's own `poster_id` column
  rather than traversing `->job->user_id` like the removed checks did) and wired it via
  `$this->authorize('manage', $application)` at the top of `showApplications`/`updateStatus`/
  `sendMessage`/`confirmHire` — closing the `confirmHire` gap and replacing all six duplicates plus
  the three now-fully-dead `authorizeXxx()` service methods (removed). No Helper class added —
  unlike Engagements, every one of these checks runs at an HTTP-authorization boundary, and
  `CONVENTIONS.md` item 4 reserves the static-Helper pattern for checks needed outside that
  context; none of that applies here.
- ✅ **`JobApplicationController` split by actor**, mirroring Module 5's one-controller-per-concern
  template. `JobApplicationController` keeps the applicant-side actions (`store`/`continueDraft`/
  `getUserApplications`/drafts/`archive`/`restore`/`destroy`/`show`). New
  `PostedJobApplicationController` takes the employer-side actions (`getUserPostedJobs`/
  `getJobApplications`/`showApplications`/`updateStatus`/`confirmHire`/`sendMessage`/archived-jobs
  handling) and the `unauthorizedError()` helper those use. Route names unchanged — only the
  controller class each route points at moved — so no Blade template needed updating.
- ✅ Flash-message handling already fixed app-wide (including this module) by the cross-cutting
  `FlashAlertHelper` pass — no longer a Module 4-specific finding.
- ✅ **`applications.store` rate limited** (`throttle:10,1`, matching the value Module 5 established
  for the payment/dispute routes) — `CONVENTIONS.md` item 13.
- ✅ **`MessageTemplateController` → `MessageTemplateResource`**, the first API Resource in the app
  — `CONVENTIONS.md` item 14. Required adding `JsonResource::withoutWrapping()` in
  `AppServiceProvider` so responses stay flat (matching what `resources/js/templates.js` already
  expects) rather than getting wrapped in a `data` envelope. Also fixed `store()`'s inline
  `$request->validate([...])` while in this file (a `CONVENTIONS.md` item 3 violation that was
  never actually on that item's known-exceptions list — an unflagged oversight, not a deliberate
  deviation) with a new `StoreMessageTemplateRequest`. One harmless wire-format side effect:
  Laravel auto-sets `201` instead of the previous unconditional `200` when the resource wraps a
  freshly-created model — still a 2xx `response.ok` for the frontend's `fetch()` call, and more
  correct REST semantics; left as-is.
- ✅ **`JobApplication::status` converted to a backed enum** (`App\Enums\ApplicationStatus`),
  per `CONVENTIONS.md` item 11's standing note that this was due "when Module 3/4's turn comes."
  Same one-model-at-a-time, fully-verified approach as Module 5's three conversions. Surfaced a
  real, would-have-been-serious bug: `JobApplicationObserver` (the only thing keeping
  `model_jobs.applicants_count` in sync) compared the hydrated `status` attribute against raw
  strings in `created()`/`updated()`/`deleted()` — casting the column without fixing the Observer
  would have silently frozen every job's applicant count the moment this shipped, with no error
  anywhere. Fixed alongside the cast. Also fixed a string-concatenation site in
  `ApplicationManagementService::validateApplicationConstraints()` that would have thrown a hard
  `TypeError` (enums aren't `Stringable`) the first time a user tried to draft over an
  already-decided application — this one wouldn't have failed silently, it would have 500'd a real
  user path. ~30 raw-string comparisons/`ucfirst()`/array-key lookups across 5 Blade views
  converted to enum comparisons via `@use('App\Enums\ApplicationStatus')`, matching Module 5's
  `respond.blade.php` convention.
- Cross-cutting, done alongside this module since `AppServiceProvider` was already being touched
  for the API Resource work: `Model::preventLazyLoading()` enabled in local/testing per
  `CONVENTIONS.md` item 11. Smoke-tested against every read-heavy page in Modules 2–4 with real
  data — all clean. Not exercised against Modules 6–9 (not yet started) or re-verified against
  Module 5 beyond its own prior audit; worth a quick re-check when each of those modules' turn
  comes.
- A `JobManagementService::authorizeJobView()` oddity flagged here in passing during the
  `preventLazyLoading` smoke test turned out to be a misdiagnosis, not a real bug — corrected in
  Module 3's own findings section below after tracing the route's actual callers.

---

## Module 5 — Job Engagements (contract lifecycle) 🟢

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
- ✅ **All three missing notifications built and wired up**, per explicit user decisions on
  content/recipient (asked rather than assumed, since this was new feature work):
  `ReviewSubmittedNotification` (reviewee, shows the star rating, not the full review text — chosen
  to keep the email short since the public review page already shows the full text),
  `PaymentAcceptedNotification` (poster, when the freelancer accepts a partial payment), and
  `PaymentDisputedNotification` (poster, when the freelancer disputes one). All three follow the
  established pattern exactly (`mail`+`database`+`broadcast`, a dedicated
  `resources/views/emails/engagements/*` Blade view). The dispute path's admin-notify leg reuses the
  existing `EngagementNotificationHelper::sendDisputeNotification()` (→ `DisputeCreatedNotification`)
  rather than a new admin-specific class, per the user's explicit call — note its email content pulls
  `reason_category`/`reason_details` from the `JobCancellation` record, which for a *payment* dispute
  still holds the original cancellation's reason (this path doesn't update those fields), not the
  payment-dispute-specific reason (that lives on the `JobPaymentDispute` instead); the email still
  correctly alerts admins to go review, and the admin disputes list page already shows the accurate
  reason, so this wasn't treated as blocking. Also found while tracing this: `PartialPaymentService::
  disputePartialPayment()`'s `if (! $cancellation)` branch creates a `JobCancellation` without the
  table's two required (`NOT NULL`, no default) `reason_category`/`reason_details` columns — a latent
  bug, but currently unreachable given the business rules (a partial payment can only exist for an
  engagement that went through the normal cancellation flow, which always creates a `JobCancellation`
  with those fields already set) — not fixed, just noted here since it was found in passing.
  Verified all three notifications dispatch to the correct recipient and their mail views render
  without error, via real HTTP requests through the actual controller actions, before committing.
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
- ✅ **All three models converted to backed enums** — `App\Enums\{DisputeStatus,PartialPaymentStatus,
  EngagementStatus}`, each with a `label()` method. Every raw string comparison, `in_array`,
  `@switch`, array-key lookup, and display call site had to be found and converted across the whole
  codebase — an enum instance never equals a raw string via `===`/`==`/`switch`, so a missed site is
  a silent bug, not a loud error. Three real, confirmed pre-existing bugs turned up along the way:
  1. `JobEngagement::STATUS_PENDING = 'pending'` was a dead constant — `'pending'` was never a valid
     value in the DB enum (`employer_accepted`/`applicant_accepted`/`active`/`completed`/`cancelled`/
     `disputed`/`settled`) and nothing ever set it.
  2. `resources/views/jobBoard/engagements/respond.blade.php`'s notes-textarea background class
     checked `$engagement->status !== 'pending'` while every sibling condition in the same block
     correctly checked `!== 'employer_accepted'` — a copy-paste slip that made the textarea
     permanently show the "already responded" grey background, even for a fresh, editable offer.
     Fixed to match its siblings.
  3. `JobEngagement::getStatusClasses()`/`getStatusLabelAttribute()`/`getStatusIconPathAttribute()`
     had no case for the real (if vestigial — confirmed nothing in the app ever assigns it)
     `applicant_accepted` status, silently falling through to a gray "Unknown" badge everywhere
     these three helpers are used. Converting the `match` blocks to switch on the enum's own cases
     (rather than raw strings with a `default` catch-all) closed this gap and, going forward, makes
     a genuinely missed case a loud `UnhandledMatchError` instead of a silent visual bug.
  Also found while touching `ProjectController` (forced, since it's a direct consumer of
  `JobEngagement::status` — not a Module 6 cleanup pass): `project.engagement.show`/
  `.archive`/`.unarchive` routes point to `ProjectController::show()`/`archive()`/`unarchive()`
  methods that **don't exist on the controller at all** — only `index()` is implemented. Any request
  to those three routes throws immediately. Not fixed here — it's squarely Module 6's territory and
  unclear what those actions should even do; flagged for when that module's turn comes.
  Verified with real HTTP requests and direct model assertions across every status of all three
  enums (including the previously-mishandled `applicant_accepted` case and the respond.blade.php fix)
  before committing each of the three conversions.
- ✅ **`policy.blade.php` componentized.** All 9 numbered policy sections shared the exact same
  wrapper chrome (bordered card, gradient header bar, icon+numbered-title row, content padding) —
  extracted to `<x-policy.section id number title>` (icon via a named slot, body via the default
  slot). 1078 → 1013 lines even before accounting for the ~15 lines of wrapper markup the component
  itself now owns once instead of 9 times. Verified all 9 sections still render with correct IDs,
  titles, and spot-checked body content before committing.
- ✅ **`disputed-engagements.blade.php` componentized.** Two real duplications found: (1) six
  "info card" blocks (Dispute Reason, Detailed Description, Supporting Evidence, Filed By, Current
  Status, Resolution Notes) shared identical wrapper chrome (rounded card, icon-box + title header
  row) — extracted to `<x-disputes.info-card title color>`, icon via a named slot, body via the
  default slot (a 7th similar-looking card, "Partial Payment Information", genuinely differs in
  structure — a bigger section with its own 3-column grid — so it was correctly left alone rather
  than force-fit); (2) the page-header's admin-vs-non-admin branches were near-identical (same
  heading, same button shape) differing only in destination route, icon, and label — collapsed to
  one block with a single `$isAdminViewer` conditional instead of two full copies. 601 → 582 lines
  even before counting the six-times-duplicated card chrome now living once in the component.
  Verified both header variants and all six info cards (including the pending/resolved dispute
  states and evidence-file rendering) with real HTTP requests before committing.

Also removed `EngagementPaymentService::canProcessPayment()`/`::calculatePartialPayment()` — the two
dead methods flagged earlier this module's pass, confirmed still zero callers, deleted rather than
left to bit-rot further.

**Module 5 status: closed out.** The one remaining open item —
`PartialPaymentService::canProcessPayment()`'s auth-check entangled with business-rule validation
(deliberately not unwound, low risk to leave) — is low-priority and independently actionable
whenever picked back up; it doesn't block moving to another module.

---

## Module 6 — Projects Dashboard 🟢

A read-only, tab-filtered overview of a user's engagements ("My Projects"). Functionally a second
lens over the same data Module 5 already models.

- **Controller**: `ProjectController`
- **Service (new)**: `Projects\ProjectDashboardService`
- **Views**: `resources/views/projects/*`

**Findings**
- ✅ **Three dead routes deleted, not implemented — traced every actual caller first.** `MODULES.md`
  had flagged `project.engagement.show`/`archive`/`unarchive` as pointing to `ProjectController`
  methods that don't exist, framed as "needs a decision on what these actions should do." Tracing
  it properly this pass (same misdiagnosis-avoidance approach as Module 3's `jobs.show` correction):
  grepped every view and JS file for `project.engagement.*` and found **zero references anywhere**.
  `projects/partials/projects-list.blade.php`'s own "View Details"/"Archive"/"Restore" controls
  already point at the Engagements module's own working routes (`engagements.archived-details`,
  `engagements.archive`, `engagements.restore` → `JobEngagementController`), which fully implement
  this functionality (authorization-checked view, per-actor archive/restore) and are what the page
  has actually been using all along. No decision was needed — the routes weren't a missing feature,
  they were dead scaffolding for a second implementation of something that already existed and
  already worked. Deleted the three route registrations; kept `project.index`.
- ✅ **`ProjectController`'s tab-filtering and stats logic extracted to a new
  `Projects\ProjectDashboardService`** (`getEngagementsByTab()`/`getStats()`), matching every other
  module's controller-thin/service-owns-logic pattern (`CONVENTIONS.md` items 1–2). Kept as its own
  service rather than folding into `EngagementManagementService` — same `JobEngagement` model and
  `activeForUser`/`archivedForUser` scopes, but a genuinely different shape (in-memory tab/stat
  breakdown for a dashboard, not `EngagementManagementService`'s paginated/searchable listing) and a
  different consumer, so it gets its own namespace the same way Applications/Jobs/Engagements each
  do.
- ✅ **Fixed a real inefficiency found while extracting the service.** The old controller computed
  the full `getEngagementStats()` query (a second `JobEngagement` fetch) on *every* request,
  including the AJAX tab-switch requests that only ever render `projects-list.blade.php` — a partial
  that never references `$stats` at all. `ProjectController::index()` now only calls
  `getStats()` on the full-page (non-AJAX) branch, cutting one wasted query per tab click.
- ✅ **`DashBoardController::getActivitySummary()`'s engagement counts fixed** — pulled forward from
  Module 2's own note ("touches Module 6 too, better done when that module's turn comes").
  `active_engagements_count`/`completed_engagements_count` hand-rolled a raw `DB::table('job_engagements')
  ->join('job_applications', ...)` that duplicated the `activeForUser` scope this module already
  uses — and, worse, didn't apply the archived-by-applicant/poster filter that scope does. That was
  a real, user-visible bug: the main dashboard's "Active Engagements" count and the Projects
  Dashboard's own "Active" tab count could disagree for any user with an archived active engagement,
  since one respected archiving and the other didn't. Now both read `JobEngagement::activeForUser($userId)
  ->where('status', EngagementStatus::Active|Completed)->count()`, so the two widgets can no longer
  drift. `calculateReviewStats()` (a `JobReview` aggregate, unrelated to engagements) and
  `applications_count`/`jobs_posted_count` (different domains, no engagement-scope overlap) were left
  as-is — not part of this finding.
- Verified all of the above with real HTTP requests (temp Pest tests, deleted after): stats/tab
  correctness including an archived-active engagement excluded from the active count and counted
  under archived; the three dead routes now 404; the dashboard's activity-summary counts matching
  the fix. Full existing suite (26 tests) re-run clean before and after.
- Found in passing, **not fixed here — spans Module 5 too, flagged as cross-cutting instead**:
  `JobEngagement::getTotalDeliverablesCount()`/`getCompletedDeliverablesCount()` call
  `$this->deliverables()` (the relation *query builder*) instead of `$this->deliverables` (the
  loaded collection), so they issue a fresh query per call regardless of eager loading —
  `completionPercentage()` right next to them in the same model does it the correct way
  (`$this->deliverables->count()`). `projects-list.blade.php` calls both count methods per row, so
  the Projects Dashboard N+1s on deliverables despite the controller's own `with(['deliverables'])`.
  Same two methods are also called from three Module 5 views
  (`engagements-list.blade.php`, `deliverables-details.blade.php`, `payment-details.blade.php`) and
  `PartialPaymentService` — a real fix means touching already-closed Module 5 files and a payment
  service, not just this module, so it's recorded under Cross-cutting concerns below rather than
  patched narrowly here.

---

## Module 7 — Messaging / Chat 🟢

Per-engagement chat between poster and applicant, rendered as an Alpine modal inside Module 5's
`engagements-list.blade.php` (the only place it's used).

- **Controller**: `MessageController`
- **Service (new)**: `Messaging\MessagingService`
- **Model**: `Message`
- **Request (new)**: `Message\StoreMessageRequest`
- **Resource (new)**: `Http\Resources\MessageResource`

**Findings**
- ✅ **Real-time scaffolding removed — user decision: delete, keep polling.** `NewMessageEvent` was
  fully written (broadcast on a private `engagement.{id}` channel) but never dispatched, didn't
  implement `ShouldBroadcast`, and `routes/channels.php` had no authorizer for that channel anyway —
  three independent signs of an abandoned attempt, not a near-complete feature. Confirmed with the
  user rather than assumed (same as Module 5's payment-flow and public-disk decisions): delete
  rather than finish. Deleted `NewMessageEvent` entirely (zero other references, verified by grep)
  and the matching commented-out `unreadCount()` controller method plus its already-commented route.
  Chat's existing per-engagement unread badge (`engagement-details.blade.php`'s
  `id="unread-count-{{ $engagement->id }}"`) is **not** part of this dead scaffolding — it's a real,
  working, server-rendered count that the frontend correctly hides after `markAsRead()` succeeds;
  initially mischaracterized as dead when first scoping this module, corrected before any code
  changed.
- ✅ **Real, confirmed bug fixed: `getEngagementData()` compared `Auth::id()` against
  `$engagement->client_id`.** `job_engagements` has no `client_id`/`freelancer_id` columns at
  all (checked every migration) — the real ownership lives on `application->poster_id`/
  `application->applicant_id`, which every other file in the app correctly goes through. Accessing
  the nonexistent attribute always returns `null`, so `Auth::id() === null` was always false,
  meaning the "other chat participant" always resolved to the poster — correct by coincidence for
  an applicant viewing the chat, wrong for a poster viewing their own chat (would show themselves as
  the "other user"). Currently invisible to users only because the frontend never rendered
  `other_user_name`/`other_user_initials` in the modal header, but a real bug once something reads
  that field. Fixed in the new `MessagingService::getChatData()`, which resolves the other party via
  `application->poster_id`/`applicant_id` like `JobEngagementPolicy` does. The matching dead
  `JobEngagement::client()`/`freelancer()` relations (same phantom columns, zero other callers) were
  deleted too.
- ✅ **Real bug fixed: `store()`'s error response was a malformed array.**
  `response()->json(['error', 'Failed to send message'], 500)` used a comma instead of `=>`,
  producing a numerically-indexed JSON array instead of `{"error": "..."}`. The frontend's
  `errorData.error || 'Failed to send message'` fallback masked the practical impact (same generic
  text shown either way), but it's a real defect now fixed as part of the controller rewrite.
- ✅ **Controller brought in line with the rest of the app's conventions.** `MessageController` had
  no Service, no FormRequest (inline `$request->validate()`), and hand-rolled the message JSON shape
  independently in two places (`getEngagementData()` and `store()`). Extracted `Messaging\
  MessagingService` (`getChatData()`/`sendMessage()`/`markAsRead()` — this is where the `client_id`
  fix lives), `Message\StoreMessageRequest`, and `Http\Resources\MessageResource` (used by both
  actions that return a message, closing the duplicate-shaping gap and matching the
  `MessageTemplateResource` precedent from Module 4). Also removed layered `catch
  (AuthorizationException)`/`catch (ValidationException)` blocks and per-request `Log::info()` calls
  on the success path — Laravel's default exception handler already renders both as the correct JSON
  status/shape for `expectsJson()` requests (verified: no custom handler in `bootstrap/app.php`),
  and `MessageTemplateController` (the app's other JSON-only controller) doesn't wrap these either,
  so the heavy try/catch wasn't this app's own convention to begin with. Also dropped the
  `application`/`job` null-checks (404 fallbacks) in `getEngagementData()` — `job_engagements
  .application_id` → `job_applications.job_id`/`applicant_id`/`poster_id` are all
  `onDelete('cascade')` foreign keys, so a `JobEngagement` that exists via route-model-binding can
  never have a missing `application` or `job`; the checks were guarding an unreachable state.
- Verified all of the above with real HTTP requests (temp Pest tests, deleted after): a poster and
  an applicant on the same engagement each see the *other* person's name (the actual bug scenario,
  not just "it still returns 200"); sending a message returns the right `is_own`/content shape and
  persists; empty content is rejected with 422 via the new FormRequest; `markAsRead()` flips only
  the other party's unread messages, not the viewer's own; a third-party user gets 403 from both the
  view and message endpoints. Full existing 26-test suite re-run clean before and after (31 with
  the temp verification tests, deleted once confirmed); `composer dump-autoload` run after deleting
  `NewMessageEvent.php` to clear its stale classmap entry.
- Found in passing, **not fixed — cosmetic, not a bug**: the chat modal's `showError()` uses a
  blocking `alert()`, while the app's other AJAX-driven UI (`resources/js/templates.js`) uses a
  non-blocking toast. Left alone since it's a UX-consistency nit rather than a defect and wasn't
  part of this module's original findings; worth a look if `resources/js` ever gets its own pass.

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
- ✅ **`Model::preventLazyLoading()` enabled 2026-09-25 (Module 4)** — stale note, corrected here.
  See `CONVENTIONS.md` item 11 for the smoke-testing status per module.
- 🔴 **`JobEngagement::getTotalDeliverablesCount()`/`getCompletedDeliverablesCount()` bypass eager
  loading.** Found during the Module 6 pass (2026-09-25): both call `$this->deliverables()` (the
  relation query builder, always a fresh query) instead of `$this->deliverables` (the loaded
  collection) — `completionPercentage()` in the same model does it correctly. Causes N+1 queries on
  every page that lists engagements and shows deliverable counts per row, despite those controllers
  already eager-loading `deliverables`: `projects/partials/projects-list.blade.php` (Module 6) and
  three Module 5 views (`engagements-list.blade.php`, `deliverables-details.blade.php`,
  `payment-details.blade.php`) plus `PartialPaymentService`. Not fixed in the Module 6 pass since a
  real fix touches already-closed Module 5 files and a payment service — worth a dedicated look
  rather than a drive-by change to a money-adjacent service.
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
6. ✅ **Module 4 (Applications) using Module 5 as the template** — done 2026-09-25. Controller
   split, authorization centralization (which surfaced a real `confirmHire` bypass), rate
   limiting, an API Resource, and a `JobApplication` status enum conversion.
7. ✅ **Module 3 (Jobs)** — done 2026-09-24. `JobFilterTrait` wired up, `JobImageService`
   cross-domain leak resolved, query-in-Blade fixed, form-header/budget/deadline componentized
   across `new.blade.php`/`edit.blade.php`. `apply.blade.php`'s own componentization is deferred —
   turned out not to be the same shape as the posting forms (see Module 3 note above).
8. ✅ **Module 6 (Projects)** — done 2026-09-25. New `ProjectDashboardService`, three dead routes
   deleted, `DashBoardController`'s raw `DB::table()` engagement counts (flagged in Module 2) fixed
   alongside.
9. ✅ **Module 7 (Messaging)** — done 2026-09-25. User decided: delete the dead real-time
   scaffolding rather than build it out. Also fixed a real `client_id`/`freelancer_id` bug and gave
   the controller a Service/FormRequest/Resource matching the rest of the app.
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
- **2026-09-24/25 (Module 5 pass, closed out)**: 13 commits across the module's full findings list.
  Consolidated three separate Policy/Helper/inline duplicates of the view and respond-to-offer
  authorization checks into `JobEngagementPolicy` as single source of truth. Fixed a real
  authorization bypass in `JobDeliverableController::submit()` (zero permission check existed) plus
  a second instance of the same pattern across `PartialPaymentController`/`PartialPaymentService`.
  Wired up an orphaned manual-payment-amount code path as a real option on the live partial-payment
  flow and moved deliverable/dispute files off the public disk onto private storage with
  authorization-gated download routes — both per explicit user decisions rather than default
  assumptions. Wired up a fully-built-but-never-dispatched payment notification. Added rate limiting
  to payment/dispute routes. Converted all three status fields (`JobEngagement`/`JobPaymentDispute`/
  `JobPartialPayment`) to backed PHP enums, one model at a time, each fully verified — surfaced 4
  real pre-existing bugs along the way (a dead `STATUS_PENDING` constant matching no real DB value,
  a copy-paste status-string typo permanently greying out an editable textarea, three `match` blocks
  silently missing the `applicant_accepted` case, and — flagged for Module 6, not fixed here —
  `project.engagement.show`/`archive`/`unarchive` routes pointing to `ProjectController` methods
  that don't exist at all). Componentized both oversized views: `policy.blade.php`'s 9 near-identical
  section wrappers into `<x-policy.section>` (1078→1013 lines) and `disputed-engagements.blade.php`'s
  6 near-identical info cards into `<x-disputes.info-card>` plus its duplicated admin/non-admin page
  header (601→582 lines). Removed two confirmed-dead `EngagementPaymentService` methods. Every
  change verified with real HTTP requests or direct model/service assertions, temp tests deleted
  after, before committing — including, for the enum conversions specifically, exhaustive
  per-file greps to catch every raw-string comparison site (an enum instance silently never equals
  a raw string via `===`/`switch`/array-key-lookup, so a missed site is a silent bug, not a loud
  error). Two large/risky items (full three-model enum scope, and what to do about two
  explicitly-flagged decisions — the orphaned payment code and the public-disk files) were confirmed
  with the user before proceeding rather than assumed. Module 5 marked 🟢; two small, genuinely
  low-priority items left open and documented in the module's own findings section, not blocking
  further work.
- **2026-09-25 (Module 4 pass, closed out)**: 6 commits. Found and fixed a real authorization
  bypass on `confirmHire()` (zero permission check anywhere — same class of bug as Module 5's
  `JobDeliverableController::submit()`) as part of centralizing the module's six duplicated
  poster-ownership checks into a new `JobApplicationPolicy::manage()` ability, used directly via
  `$this->authorize()` (no Helper class — every check here runs at an HTTP boundary, unlike
  Engagements). Split `JobApplicationController` into applicant-side and a new
  `PostedJobApplicationController` for the employer side, following Module 5's
  one-controller-per-concern template; route names unchanged, no Blade changes needed. Rate
  limited `applications.store`. Gave `MessageTemplateController` the app's first API Resource
  (`MessageTemplateResource`), which required `JsonResource::withoutWrapping()` app-wide to keep
  `resources/js/templates.js` working, and fixed an unflagged inline-validation gap in the same
  file. Converted `JobApplication::status` to a backed enum (`App\Enums\ApplicationStatus`),
  surfacing a real bug in `JobApplicationObserver` that would have silently frozen every job's
  applicant count, plus a string-concatenation site that would have thrown a hard `TypeError` on
  a real user path — both fixed alongside the cast, not after. Also enabled
  `Model::preventLazyLoading()` app-wide (a cross-cutting item, done here since
  `AppServiceProvider` was already being touched) after smoke-testing it against every read-heavy
  page in Modules 2–4 with real data. Flagged a suspected backwards authorization check in
  Module 3's `JobController::show()` from that smoke test — **corrected same day**: traced every
  actual caller of the route and found `jobs.show` is exclusively a poster-only "my posted job"
  preview (not the public job-details page, which is `jobs.apply`), so the poster-only check was
  correct all along; see Module 3's findings section for the full trace. Every code change
  verified with real HTTP requests or direct model/service assertions, temp tests deleted after,
  before committing; ran the full existing suite after each risk-bearing change. All of Module 4's
  own findings resolved; Modules 5/6-9 not smoke-tested against `preventLazyLoading` remains open,
  worth a quick look when each of those modules' turn comes.
- **2026-09-25 (Module 6 pass, closed out)**: 1 commit. Traced every actual caller of the three
  routes flagged as pointing to nonexistent `ProjectController` methods and found they were pure
  dead scaffolding — nothing linked to them, and `projects-list.blade.php` already used the
  Engagements module's own working view/archive/restore routes instead. Deleted the three routes
  rather than implementing them; no product decision needed once traced (same pattern as Module 3's
  `jobs.show` correction). Extracted `ProjectController`'s inline tab-filtering/stats logic to a new
  `Projects\ProjectDashboardService`, matching every other module's thin-controller convention, and
  fixed a real inefficiency found in the process (the full stats query used to run on every AJAX
  tab-switch request even though the AJAX partial never uses it). Pulled forward and fixed Module
  2's `DashBoardController::getActivitySummary()` finding: its raw-SQL engagement counts didn't
  respect archiving the way the `activeForUser` scope does, a real bug that could make the main
  dashboard and the Projects Dashboard disagree on a user's active-engagement count. Verified with
  temp Pest HTTP tests (stats/tab correctness, archived-engagement exclusion, the three routes now
  404ing, the dashboard count fix) before committing; full 26-test suite re-run clean.
- **2026-09-25 (Module 7 pass, closed out)**: 1 commit. Asked the user to decide on the abandoned
  real-time-chat scaffolding (`NewMessageEvent` unimplemented/undispatched, no channel authorizer,
  a commented-out `unreadCount()` method) rather than assume — decision: delete it, current
  poll-on-open chat works fine. Deleted `NewMessageEvent`, the dead `JobEngagement::client()`/
  `freelancer()` relations it depended on, the commented method, and its already-commented route;
  ran `composer dump-autoload` afterward. While scoping the delete, corrected an initial
  misreading — the per-engagement unread badge is real, working, server-rendered code, not part of
  the dead scaffolding. Found and fixed a real, confirmed bug along the way:
  `getEngagementData()`'s "who's the other chat participant" check compared against
  `$engagement->client_id`, a column that has never existed on `job_engagements` (checked every
  migration) — always null, so the check always resolved to the poster, silently wrong for a poster
  viewing their own chat. Rebuilt the controller around a new `Messaging\MessagingService`,
  `Message\StoreMessageRequest`, and `MessageResource` (fixing the bug in one place instead of two
  independent copies), matching Module 4's `MessageTemplateController`/`MessageTemplateResource`
  precedent. Also fixed a malformed error array (`['error', '...']` instead of `['error' => '...']`)
  and removed redundant `AuthorizationException`/`ValidationException` catches and success-path
  logging that Laravel's default JSON exception handling already covers. Verified with real HTTP
  requests (temp Pest tests, deleted after) covering the actual bug scenario (poster vs. applicant
  each seeing the *other* party's name), message send/validate/mark-read, and third-party
  authorization denial; full 26-test suite re-run clean before and after.
