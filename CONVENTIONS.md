# ModelHub — Architecture Conventions

Reference checklist for the module-by-module cleanup tracked in `MODULES.md`. Derived from Module
5 (Engagements) — the most consistently-structured part of the codebase, per the 2026-09-24 audit.
Use this to judge whether a module's code matches the app's own best-established pattern, not an
external framework opinion. When a module deviates, that's a finding for `MODULES.md`; when a
whole module already matches, say so and move on.

**Important**: Module 5 is the best reference in this codebase, not a perfect one. A few of its own
files deviate from its own dominant pattern (noted below as "known exceptions") — copy the pattern,
not those specific files.

Items 1–10 come from the Module 5 audit. Items 11–15 were added 2026-09-24 from a set of
externally-suggested conventions, each individually verified against the actual codebase (grepped,
not assumed) before being written down here — so each one says explicitly whether it's already
true, a real confirmed gap, or a deliberate decision already made.

---

## 1. Controllers are thin dispatchers

A controller method's job is: resolve input (via a FormRequest), call one Service method, decide
which view/redirect to return based on the result. No query building, no authorization logic, no
validation logic, no direct `Mail::`/`->notify()` calls inside a controller method body.

```php
public function cancelEngagement(JobEngagement $engagement, CancelEngagementRequest $request)
{
    $result = $this->engagementCancellationService->cancelEngagement($engagement, $request->getCancellationData());

    return $result['success']
        ? redirect()->route('engagements.index', $engagement)->with(['success' => $result['message'], 'alert' => $result['alert']])
        : back()->with('error', $result['error']);
}
```

**Known exception to fix, not copy**: `JobDeliverableController` does inline `Auth::id() !== ...`
authorization checks and inline `$request->validate([...])` instead of delegating — it's in Module
5 but breaks Module 5's own pattern.

## 2. Business logic lives in focused Services

One Service class per concern within a domain, not one giant service per controller. Engagements
has five: `EngagementManagementService` (listing/archiving), `EngagementResponseService` (accept/
decline), `EngagementReviewService` (reviews), `EngagementCancellationService` (cancel/dispute),
`EngagementPaymentService` (payment wrapper). A controller injects only the services its own actions
need.

Services that produce a user-facing outcome return a structured array:

```php
['success' => bool, 'message'|'error' => string, 'alert' => FlashAlertHelper::success(...)['alert']]
```

Services that hit a genuine "this shouldn't be reachable" case (not-found, invalid state) throw an
`\Exception` with a user-safe message; the controller catches generically and flashes the message.
Don't mix the two styles within one method — pick one per method based on whether the failure is a
normal outcome (return array) or an invariant violation (throw).

## 3. Validation always goes through a FormRequest

Dedicated `Http\Requests\{Domain}\{Action}Request` class, never inline `$request->validate([...])`
in a controller or service. The FormRequest also owns any request-data shaping (`getCancellationData()`,
`getReviewData()`, etc.) so the controller never touches `$request->input()` directly.

**Known exceptions to fix, not copy**: `JobDeliverableController`, `PartialPaymentController`,
`Admin\AdminDisputeController` all validate inline.

## 4. Authorization: Policies are the source of truth

Prefer a Laravel Policy (`app/Policies/{Model}Policy.php`), invoked via `$this->authorize()` or
`Gate::allows()`. Where a check needs to run outside an HTTP-authorization context (inside a
Service, or from a Blade view that isn't gating a route), a static Helper class
(`App\Helpers\{Domain}\{Domain}AuthorizationHelper`) is acceptable — **but it must be the only
place that logic lives**. Never let a Policy and a Helper both independently implement the same
check.

**Known exception to fix, not copy**: `JobEngagementPolicy::view()` and
`EngagementAuthorizationHelper::canView()` currently implement the identical poster/applicant/admin
check independently. Consolidate to one (the Helper should call `Gate::allows()` internally, not
duplicate the boolean logic) before treating this file as a template for authorization.

## 5. Notifications centralized per domain

A `{Domain}NotificationHelper` static class (e.g. `EngagementNotificationHelper`) is the only place
that calls `Mail::to()->queue()` or `$model->notify()` for that domain's events. Controllers and
Services call the helper's named method (`sendResponseNotification`, `sendCancellationNotification`,
...); they never construct a Mailable/Notification inline.

Don't leave stub methods that are never called (`EngagementNotificationHelper::sendReviewNotification()`
and `::sendPaymentNotification()` are currently empty and unwired — either implement them when the
feature is actually built, or delete them; an empty stub isn't a template).

## 6. Flash messages via `FlashAlertHelper`

Every user-facing redirect that needs a message uses `App\Helpers\FlashAlertHelper::success()`/
`error()`/`info()`/`warning()` (or `make()` for a dynamic type) — never a hand-built
`['type' => ..., 'title' => ..., 'text' => ...]` array. See `partials/flash-messages.blade.php` for
how the `alert` payload is actually rendered (a specific toast title when set, a generic
success/error/info/warning toast as fallback for a plain flat message).

## 7. Multi-step writes wrapped in a transaction

Any operation that touches more than one model (or one model in more than one step where a
mid-failure would leave inconsistent state) wraps in `DB::beginTransaction()`/`commit()`/`rollBack()`
(or `DB::transaction(fn () => ...)` for the simple case with no custom rollback logic needed).

## 8. Route-level ownership via middleware, not repeated per-action checks

When a whole route group needs the same "this resource belongs to the acting user" gate,
express it once as middleware (`VerifyJobEngagementOwnership`) rather than repeating the check in
every controller action it applies to.

## 9. View organization: split by concern once a directory gets large

Module 5's `jobBoard/engagements/partials/` (~20 files) is organized into `details/`, `cancelled/`,
`components/modals/`, `disputed/` — grouped by what the partial is *for*, not just dumped flat.
Treat any single Blade file over roughly 300 lines, or any repeated markup block (a modal, a card,
a status badge), as a componentization candidate: extract to `partials/` (view-local, one-off
composition) or `components/` (reusable, takes props) depending on whether it's reused elsewhere.

**Known exceptions, not yet fixed**: `jobs/apply.blade.php` (1,100 lines), `engagements/policy.blade.php`
(1,078 lines) — both flagged in `MODULES.md`, not yet split.

## 10. One controller per bounded concern

A controller represents one actor's workflow over one concern — not two unrelated actors sharing a
class because their models are related. Module 5's controllers each own one clear slice
(`JobEngagementController` = lifecycle, `JobDeliverableController` = deliverables,
`PartialPaymentController` = payment actions). Contrast with `JobApplicationController` (Module 4),
which mixes the applicant's own-application actions with the employer's applicant-review/hiring
actions in one class with almost no shared state — a split candidate, not a pattern to copy.

## 11. Data layer discipline

- **Eager loading** — listings already consistently load relationships via `with()`. **Not yet
  done**: `Model::preventLazyLoading(! app()->isProduction())` is not set anywhere in
  `AppServiceProvider::boot()`. Cheap, safe addition — add it so N+1s fail loudly in local/testing
  instead of silently shipping.
- **Query scopes over repeated `where` chains** — already the consistent, established pattern
  (`scopeActive`, `scopeArchived`, `scopeActiveForUser`/`scopeArchivedForUser`, `scopeDraft`, etc.
  across `ModelJob`, `JobApplication`, `JobEngagement`). Nothing to change, just keep following it.
- **Statuses as backed enums** — **not currently used anywhere**. Every status
  (`JobEngagement`, `JobPaymentDispute`, `JobPartialPayment`) is a `const STATUS_X = 'x'` class
  constant; `JobApplication`/`ModelJob` don't even have constants, just bare string literals in
  services and Blade. Genuinely valuable, but converting one touches every service/policy/Blade
  file that references that status — do it module-by-module as each module's cleanup pass reaches
  it (e.g. convert `JobEngagement` statuses when Module 5 comes up), not as one app-wide sweep.
- **`$fillable`, never `$guarded = []`** — already fully compliant, zero exceptions found.
- **Money as `decimal:2` casts, never floats** — already fully compliant
  (`offer_amount`/`service_fee`/`net_amount`/`agreed_amount` etc. all cast correctly).
- **Soft deletes vs. archiving flags** — this is *not* a single "pick one" rule here. `JobApplication`
  uses both `SoftDeletes` (real deletion) and its own `is_archived` flag (hide-from-view) —
  legitimately different concepts. `JobEngagement` uses **per-actor** flags
  (`is_archived_by_applicant`/`is_archived_by_poster`) because the two parties need to archive
  independently of each other. `ModelJob` uses one global flag because it only has one owner. Three
  different shapes, each matching that model's actual ownership structure — document the
  distinction, don't force uniformity onto it.
- **Migrations** — never edit one that's already merged/deployed; new foreign keys use
  `foreignId()->constrained()` with an explicit `onDelete()` (already the consistent pattern in
  sampled migrations), and index columns you filter on.

## 12. Side effects and async

- **Queue anything slow or external** — Mail is already consistently queued
  (`->queue()` throughout). Nothing else external exists to extend this to yet — the payment
  gateway integration is still a commented-out stub in `PartialPaymentService`. Apply this the
  moment a real third-party call lands; anything payment-related should use `ShouldBeUnique` or an
  explicit lock so a retried job can't double-process a payment.
- **Notifications: static Helper, not Events/Listeners** — **confirmed decision, 2026-09-24**: the
  `{Domain}NotificationHelper` static-class pattern (item 5) stays as the *only* mechanism.
  Introducing Laravel Events/Listeners now would create two competing ways to do the same thing,
  and nothing in the current codebase needs the decoupling Events would buy — revisit only if a
  real case for multiple independent listeners on the same domain event shows up.
- **Log on the throw path** — when a service throws a user-safe exception message, log the internal
  detail with context first if there's anything non-obvious that would otherwise be lost (a bare
  `throw new \Exception('short, already-safe message')` doesn't need it). Currently inconsistent —
  e.g. `PartialPaymentService` logs before most of its throws, `EngagementManagementService::getResponseFormData()`
  throws with nothing logged. Worth tightening up when each service's module comes around, not
  urgent on its own.

## 13. Security and boundaries

- **`env()` never called outside `config/`** — already fully compliant, zero exceptions found.
- **File uploads: validate in the FormRequest, store on a private disk, serve via an authorized
  route** — **fixed 2026-09-24.** `JobDeliverableController::submit()` and
  `PartialPaymentController::processDisputePartialPayment()` now store to the `local` disk instead
  of `public`, with two new authorization-gated download routes
  (`EngagementAuthorizationHelper::canView()` — poster, applicant, or admin only) replacing the raw
  `Storage::url()`/`asset('storage/...')` links that used to expose files at a guessable public URL.
- **Named routes + route-model binding** — already fully compliant app-wide.
- **`scopeBindings()` for nested resources** — not directly applicable; routes are flat custom
  routes, not Laravel nested-resource controllers. Revisit only if that structure changes.
- **Rate limiting on sensitive actions** — **payment processing and dispute submission fixed
  2026-09-24** (`throttle:10,1` on `process-partial-payment`/`accept-partial-payment`/
  `process-dispute-partial-payment`, Module 5). `apply` (Module 4) still has none — pick up when
  that module's turn comes. Login (a custom `RateLimiter` inside `LoginForm`) and email verification
  (`throttle:6,1`) were already protected.

## 14. Structure and consistency

- **Service result shape** — **confirmed decision, 2026-09-24**: keep the
  `['success' => bool, 'message'|'error' => ..., 'alert' => FlashAlertHelper::...]` array
  convention (item 2), not a typed `ServiceResult` DTO. It's consistent across ~15 service classes
  and was just fully centralized through `FlashAlertHelper` — a DTO would touch every service
  method signature app-wide for a mostly cosmetic/type-safety win.
- **Constructor injection, not static-heavy services** — already the consistent pattern for all
  Services. Not currently using PHP 8's readonly-promoted-property constructor shorthand (every
  service still declares the property then assigns it in the constructor body) — purely cosmetic,
  low priority; fine to modernize opportunistically as each service's module comes up, not worth a
  dedicated pass.
- **No queries in Blade** — fully compliant. The one confirmed violation,
  `jobBoard/jobs/browse.blade.php` querying `Skill::where(...)`/`Software::where(...)` directly in
  the view, was fixed during the Module 3 pass (data now comes from
  `JobBrowsingService::getFilterOptions()` via the controller).
- **API Resources for JSON endpoints** — not currently used anywhere; e.g.
  `MessageTemplateController` returns raw Eloquent collections via `response()->json($templates)`.
  Real, but low priority — this app has very few JSON endpoints so far.

## 15. Testing: end-to-end first

Tests describe what an actor does, not how a class works. Every user-facing workflow gets a
browser test that drives the real UI from start to finish as that actor would (e.g. applicant
applies → poster accepts → applicant submits deliverable → poster pays → both review). Unit tests
are not the default and aren't expected per Service.

**Current state (2026-09-24)**: the entire test suite is 26 tests, all of them Breeze's stock
Auth/Profile scaffolding — **zero coverage of any actual business logic** (Jobs, Applications,
Engagements, Payments, Disputes, Messaging). This is a green-field adoption, not a migration away
from an existing convention.

**Tooling — confirmed decision, 2026-09-24**: Pest browser testing (Playwright-based), not Laravel
Dusk — keeps the whole suite in one framework, matching the already-established Pest-only
convention, with no second test runner/ChromeDriver process to manage. **Not yet installed**:
`composer.json` currently pins `pestphp/pest: ^3.7` (locked at 3.8.7) — bumping to `^4.0` is needed
before the first browser test can be written. Do that bump deliberately when starting the first
E2E suite, not as a drive-by dependency change.

**Organize by workflow, not by class**: `tests/Browser/Engagements/CancelEngagementTest.php`, not
`tests/Unit/Services/EngagementCancellationServiceTest.php`. One file per workflow, one test per
meaningful path through it (happy path, the main rejection/error path, and any branch that changes
what the user ends up seeing).

**Selectors**: target `dusk="..."` (or `data-test`) attributes, never CSS classes or copy text —
keeps tests stable while views are still being split into partials/components under item 9.

**State**: build preconditions with factories, never by clicking through earlier steps. A cancel
test starts from a factory-made accepted engagement, not from a fresh job post. Use
`DatabaseTruncation`/`DatabaseMigrations` (not `RefreshDatabase` — the browser runs in a separate
process, so an in-memory sqlite `RefreshDatabase` transaction wrapping the test process wouldn't be
visible to it).

**External services**: never hit real payment, KYC, or mail providers from a browser test. Point
the testing environment at sandbox credentials or a fake gateway once the payment integration
exists; use Mailpit or the `log` mail driver for asserting notifications (`Mail::fake()` doesn't
cross into the browser process).

**Assert what the user sees**: the redirect lands on the right page, the `FlashAlertHelper` toast
shows the right title/text, and the resulting state is visible in the UI. A database assertion is
fine as a final check, but it doesn't replace the visible outcome.

**Allowed non-E2E tests (the only exceptions)**:
- Authorization matrices: HTTP feature tests covering each actor × action combination against a
  Policy (item 4) — a browser test per combination is slow and adds nothing a Policy test doesn't
  already prove.
- Money and calculation logic: plain Pest tests for amounts, partial-payment splits, rounding, and
  currency handling (e.g. `ApplicationCalculationHelper`, `PartialPaymentService::calculatePartialPayment()`).

**Review finding**: a workflow with no E2E test is a finding under that module in `MODULES.md`,
same as any other deviation — not a separate, lower-priority category of gap.

---

## How to use this doc during a module review

For each file in the module being reviewed, check it against the numbered items above. A finding
is either:
- **Matches** — nothing to do, don't manufacture busywork.
- **Deviates, worth fixing now** — a real inconsistency with the established pattern that's small/
  safe to fix as part of this module's pass.
- **Deviates, needs a decision** — fixing it changes behavior, scope, or requires a judgment call
  (like the flash-alert dead-data discovery did) — surface it, don't silently pick a direction.

Record findings in `MODULES.md` under that module's section, same format as existing findings.

## Status log

- **2026-09-24**: Extracted from the Module 5 audit in `MODULES.md`, ahead of the Module 1/2 review.
- **2026-09-24 (later)**: Added items 11–15 (data layer, side effects/async, security, structure,
  testing) from a set of externally-suggested conventions — each verified against the actual
  codebase before being written down, not assumed. Three items needed the user's own decision
  (Events vs. Helper, ServiceResult DTO, E2E tooling) — all three resolved and recorded above. One
  finding (deliverables/dispute-evidence stored on the public disk) flagged as higher-priority than
  a typical style deviation — it's a real confidentiality gap, not just an inconsistency.
