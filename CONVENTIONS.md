# ModelHub — Architecture Conventions

Reference checklist for the module-by-module cleanup tracked in `MODULES.md`. Derived from Module
5 (Engagements) — the most consistently-structured part of the codebase, per the 2026-09-24 audit.
Use this to judge whether a module's code matches the app's own best-established pattern, not an
external framework opinion. When a module deviates, that's a finding for `MODULES.md`; when a
whole module already matches, say so and move on.

**Important**: Module 5 is the best reference in this codebase, not a perfect one. A few of its own
files deviate from its own dominant pattern (noted below as "known exceptions") — copy the pattern,
not those specific files.

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
