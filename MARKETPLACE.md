# ModelHub — Marketplace Architecture Note

**Status: proposal, written 2026-10-01. Nothing here is built yet.** It records the direction, what the
codebase looks like today against it, and the order we intend to build in, so later work does not
paint us into a corner. Decisions still open are in [Open decisions](#open-decisions); when one is made,
move it into the relevant section and say so in the [Decision log](#decision-log).

## 1. Direction

ModelHub is a freelance job board today (post a project → apply → hire → engagement with deliverables →
completion / cancellation / dispute → reviews). The plan is for it to also be a **hub for 3D models
where users sell and buy assets**, similar to CGTrader. Two pillars, one account and one brand:

| Pillar | Who | Core loop |
|---|---|---|
| **Hire talent** (exists) | Clients and freelancers | project → application → engagement → deliverables → payment → review |
| **Models marketplace** (planned) | Sellers and buyers | upload model → listing → purchase → licensed download → review; seller is paid out |

Any user can already be both a poster and a freelancer. Seller/buyer is a third *capability* of the same
account, not a separate account type. The same person may hire a freelancer, sell a model and buy one.

## 2. Where the codebase stands (verified 2026-10-01)

Reusable as is:

- **Identity, profile and reputation**: `User`, `UserProfile`, skills/software pivots, public profile
  overview with work history, ratings (`JobReview`).
- **The app shell and design system**: sidebar/breadcrumb shell (`App\Support\Navigation\SidebarMenu`),
  the Blade component kit (`CONVENTIONS.md` §16), `<x-pager>`, confirm dialogs, form kit.
- **Notifications and email**: `NotificationCategory`, the notifications page, and the single branded email
  template (`emails/layouts/master.blade.php`, `<x-mail.*>`, `BrandedMail`).
- **Conventions**: thin controllers → services → FormRequests → policies, end-to-end Pest tests that double
  as N+1 guards (`CONVENTIONS.md`).

Gaps the marketplace would hit:

- **No real payments.** "Escrow" is bookkeeping on `JobEngagement` (`payment_escrowed_at`, statuses) and
  `JobPartialPayment`; there is no gateway, ledger, wallet, payout or refund flow. The platform fee is a
  constant, `ApplicationCalculationHelper::SERVICE_FEE_PERCENTAGE = 0.10`, copied onto each application and
  engagement at creation (good: it is already snapshotted). Currency is a single symbol,
  `config('app.currency_symbol')` (`Ksh`).
- **Files are small and on the app server's local disk.** Job images (`JobImageService`) and application
  portfolios (`ApplicationFileHelper`) go to the `public` disk. Engagement deliverables
  (`JobDeliverableController`) and dispute evidence (`PartialPaymentController`) already use the private
  `local` disk with authorised downloads, which is the right pattern to extend. Limits are ~5–10 MB per file
  (FormRequests). Fine for images and PDFs; not for 3D assets, and nothing streams from object storage or a
  CDN yet.
- **Search is SQL `LIKE`** (`JobBrowsingService`). No index, no facets, no relevance.
- **"Reviews" belong to engagements** (`JobReview.engagement_id`), not to arbitrary things.
- **Vocabulary**: the UI calls jobs "projects" (Browse projects, Post a project, Posted projects). A
  marketplace has its own nouns; they must not collide.
- **Public pages** (landing, `jobs/index`, guest navbar and footer) are still the old style and freelance-only.

## 3. Principles

1. **Two verticals, one platform.** The marketplace is its own module (own tables, services, routes, views)
   that *uses* shared platform pieces. It does not bend `jobBoard` tables or views to fit.
2. **Money is a platform service, not a feature of either vertical.** Jobs and orders both move money
   through one ledger. Neither writes balances directly.
3. **Files are a platform service too.** One storage pipeline (private by default, signed access, previews)
   serves model assets, deliverables and attachments.
4. **Snapshot, don't reference, anything that must not change later**: fee percentage, price, currency,
   licence terms at purchase time.
5. **Every state change is a transaction** (`CONVENTIONS.md` §7) and every money movement is an immutable
   ledger entry.
6. **Nothing ships without the same standard as the job board**: policies, FormRequests, tests asserting OK
   first, eager loading, one template for emails, one pager.

## 4. Vocabulary and navigation

Words, decided once and used everywhere (UI, routes, tables, emails, notifications):

| Concept | Word | Notes |
|---|---|---|
| A client's request for work | **Project** (as today) | unchanged; route prefix `jobs`, tables `model_jobs` |
| A seller's 3D asset for sale | **Model** | never "product" in the UI; tables/classes may use `Product` if we prefer, decided in §5 |
| A purchase | **Order** | |
| What a buyer is allowed to do with a model | **Licence** | |
| Money the platform holds for someone | **Balance** | seller/freelancer earnings, payable via **Payout** |

Sidebar (target), extending the current groups Overview / Find work / Hire / Delivery:

- **Overview**: Dashboard, Notifications (unchanged)
- **Find work**: Browse projects, My applications (unchanged)
- **Hire**: Post a project, Posted projects (unchanged)
- **Delivery**: Engagements (unchanged)
- **Marketplace** (new): Browse models, My models (seller), Purchases (buyer), Earnings (seller + freelancer
  balance, payouts)

Public navbar (target): **Models · Hire talent · Find work · Sell** plus sign in / join. The landing page,
guest navbar and footer are designed around both pillars from the start.

## 5. Domain model (sketch)

New, marketplace-owned (names indicative):

- `Model` listing (seller, title, description, category, price, licence set, status draft → in review →
  published → unpublished / rejected) and `ModelVersion` (version label, changelog).
- `ModelFile` (version, disk path, original name, size, checksum, format: FBX/OBJ/BLEND/MAX/GLB/…, scan
  status) and `ModelPreview` (images, turntable/360, generated GLB for the in-browser viewer).
- Facets: categories, tags, supported formats, compatible `Software` (the existing table), poly count,
  rigged/animated/textured flags.
- `Licence` (royalty-free, editorial, extended, …) and the per-model price for each.
- `Order`, `OrderItem` (snapshot of price, currency, fee %, licence), `Download` (who, when, which file),
  `IssuedLicence` (what the buyer holds; the legal record).
- `Wishlist`, `Collection`, `Report` (copyright/abuse), `Takedown`.

Generalisations of existing tables (do these when the marketplace needs them, not before):

- **Reviews**: make reviewable polymorphic (engagement or model order) or add a parallel `ModelReview`.
  Recommendation: parallel table first, unify only if the profile rating needs one number.
- **Notifications**: add categories (Orders, Sales, Payouts) to `NotificationCategory`.

Shared platform tables (see §6 and §7): `ledger_accounts`, `ledger_entries`, `payouts`, `files`.

## 6. The money core (build before either vertical takes real money)

Goal: one auditable way to move money, used by engagements today and orders tomorrow.

- **Double-entry ledger.** `ledger_accounts` (platform revenue, platform escrow/holding, per-user
  earnings, per-user payable) and append-only `ledger_entries` (debit/credit, amount in minor units,
  currency, reference to the business object: engagement, partial payment, order item, payout, refund).
  Balances are derived from entries, never edited.
- **Payment intake** through gateway adapters behind one interface (M-Pesa STK first via the existing
  `laravel-common` package; cards/PayPal/Stripe later). Webhooks are idempotent and verified.
- **Holding and release.** Job payments sit in escrow until the engagement logic releases them (replaces the
  flags on `JobEngagement`). Model sales are available to the seller after a refund window (configurable).
- **Payouts.** Sellers/freelancers request payout to M-Pesa/bank; minimums, schedules, status, failures and
  reversals are first-class.
- **Fees.** Commission is configuration, not a constant: a platform default plus optional per-seller or
  per-category overrides, always **snapshotted onto the transaction** (as applications/engagements already
  do).
- **Refunds and disputes.** Refund = compensating ledger entries, never deletions. Model disputes reuse the
  job-dispute patterns where they fit.
- **Currency.** Store `amount` in minor units plus `currency` on every money row from day one, even if only
  KES is enabled at launch. Open decision D1.

## 7. Files and delivery pipeline

- **Storage**: an S3-compatible disk (Backblaze B2, Cloudflare R2, S3 or MinIO) configured per environment;
  local disks only in dev. Model assets are **private** (as deliverables and dispute evidence already are).
  Public images (covers, avatars, previews) may stay public or move behind a CDN.
- **Uploads**: direct-to-storage (presigned / multipart) for large files, with the app only recording
  metadata and verifying size, type (by content, not extension) and checksum on completion.
- **Processing** (queued): virus scan, format detection, thumbnail and preview generation, GLB conversion
  for the in-browser viewer, poly/vertex count extraction. Listing stays in review until processing passes.
- **Downloads**: authorised by ownership of an `IssuedLicence`, served by short-lived signed URLs, logged in
  `Download`, rate-limited.
- **Reuse**: engagement deliverables and dispute evidence move from the local `local` disk onto the same
  pipeline so they also become large-file capable and survive moving off a single server; application
  portfolios and job images use its public-image path.
- **Preview in browser**: `<model-viewer>` / three.js on the product page (GLB), lazy-loaded.

## 8. Search and discovery

Replace `LIKE` for models with a proper engine (Meilisearch or Typesense via Laravel Scout) with facets
(category, format, software, price range, licence, rating, poly count, free/paid) and sensible relevance.
The job board can adopt the same engine later. Cache category/landing queries; serve thumbnails through a CDN.

## 9. Sellers, trust and legal

- **Becoming a seller** is an opt-in on the existing account: payout details, identity/tax info where
  required, seller terms acceptance. Whether sellers are approved or open to all is open decision D3.
- **Moderation**: new models go through an automated check plus, at first, manual approval in an admin queue.
- **IP and takedowns**: a report flow, takedown workflow and repeat-infringer policy. This is a legal
  requirement for a marketplace, not polish.
- **Policies** to write: seller terms, buyer terms, licence definitions, refund policy (today there is only
  the "Cancellation & payment policy" page).
- **Admin**: moderation queue, reports, payouts, refunds, ledger browser, fee overrides.

## 10. Notifications and email

Reuse `NotificationCategory` (add Orders, Sales, Payouts, Moderation) and the single email template; every
new email is added to the list in `tests/Feature/EmailTemplateTest.php`. New emails: order receipt with
download links, sale notification, payout sent/failed, listing approved/rejected, takedown notice.

## 11. Build order

Each phase ends with the job board still fully working and tests green.

0. **Foundations (small, do soon)**
   - Fee from config/DB instead of a constant (keep snapshotting it).
   - Configurable storage disk for uploads; stop hard-coding `public` / `local` at each call site.
   - Decide vocabulary (§4) and the two-pillar public structure; then **build the landing, guest navbar and
     footer around it** (next piece of work).
1. **Money core**: ledger, gateway adapter (M-Pesa), payout model; move engagement escrow/partial
   payments onto the ledger behind the existing services. Exit: an engagement can be paid and released for
   real, and a freelancer can withdraw.
2. **File pipeline**: private storage, direct uploads, processing jobs, signed downloads; migrate
   deliverables. Exit: a 500 MB file uploads, previews and downloads only for authorised users.
3. **Marketplace MVP**: seller onboarding → create/upload/preview listing → moderation → browse/search/
   product page → checkout → licensed download → seller earnings and payouts → reviews.
4. **Growth**: wishlists, collections, discounts/promotions, seller storefronts, subscriptions, recommendations,
   and the job board on the shared search engine.

## 12. Non-goals (for now)

Physical goods or shipping; a native mobile app; an auction or bidding model; a public API; multi-vendor
carts with split payment at checkout beyond what the ledger gives us for free.

## Open decisions

| # | Decision | Options | Affects |
|---|---|---|---|
| ~~D1~~ | ~~Currency scope~~ | **Decided 2026-10-01: KES only at launch; every money row still stores its currency** | ledger design, gateway, pricing UI |
| ~~D2~~ | ~~Payment methods at launch~~ | **Decided 2026-10-01: M-Pesa first (via `laravel-common`), cards later** | gateway adapters, payout rails |
| ~~D3~~ | ~~Who can sell~~ | **Decided 2026-10-01: approved sellers (apply, then an admin reviews)** | onboarding, moderation load |
| ~~D4~~ | ~~Commission model~~ | **Decided 2026-10-02: a platform default rate per type (jobs, model sales), editable by a Super admin, with an optional per-seller override; the rate used is snapshotted on every transaction** | fee config, admin |
| ~~D5~~ | ~~Licence types~~ | **Decided 2026-10-02: two tiers at launch, Standard and Extended; every purchase issues a licence record** | listings, `IssuedLicence`, terms |
| ~~D6~~ | ~~Model pricing~~ | **Decided 2026-10-02: fixed price per licence or free, with a platform minimum price; no pay-what-you-want at launch** | checkout, listing form |
| D7 | Search engine | Meilisearch vs Typesense vs managed | infra, cost |
| D8 | File storage provider | S3 vs R2 vs B2 vs MinIO self-hosted | cost, egress, CDN |
| ~~D9~~ | ~~Refund window~~ | **Decided 2026-10-02: 7-day hold before sale earnings are withdrawable (configurable); no refunds once downloaded except a broken or not-as-described file, which staff can refund within the hold** | balances, policy |

## Decision log

| Date | Decision |
|---|---|
| 2026-10-01 | Direction agreed: freelance job board plus a CGTrader-style models marketplace on one platform. This note written; nothing else decided yet. |
| 2026-10-01 | D1: KES only at launch, currency stored on every money row. D2: M-Pesa first, cards later. D3: approved sellers only. Build order: file pipeline and catalogue first; money core in parallel. |
| 2026-10-01 | Code naming: the 3D model listing is `Product` in code (tables `products`, `product_files`, …) to avoid clashing with Eloquent's `Model` and the existing `ModelJob`; the UI word stays "Model". |
| 2026-10-01 | Built: seller onboarding (apply at `/sell`, reviewers approve/reject/suspend at `/admin/sellers`, permission `review sellers`, notifications under category Marketplace). Seller terms (commission, licences, payouts) are still to be written before a first listing can be published. |
| 2026-10-01 | Built (slice 2): model listings. Categories seeded CGTrader-style (22 top-level, ~100 sub-categories, shipped by migration); `products`, `product_files`, `product_images`, `product_software`; seller "My models" (draft -> upload previews/files -> details -> review); reviewers' queue at `/admin/models` (permission `review models`: publish / ask for changes / take down); public catalogue `/models` and model pages. Limits: 50 MB per file (PHP 60M/post 64M, nginx 64M), 20 files, 10 images of 5 MB. Files private on `MARKETPLACE_FILES_DISK` (local for now; S3 before launch); purchases disabled (`MARKETPLACE_PURCHASES_ENABLED=false`). Licence is one fixed "standard" licence until seller terms are written. |
| 2026-10-01 | Built (slice 3): seller storefronts at `/sellers/{slug}` (store name, bio, published models, category chips and sort; never the member's own name or email; approved sellers only; slug made once from the store name and stable) and wishlists (`/wishlist`, heart on every card and a Save button on model pages, optimistic toggle that still works without JS, members-who-saved count on model pages and on the seller's My models). Suspending a seller now hides all their models from the catalogue and storefront (`Product::published()` requires an approved seller); owner and reviewers can still preview. |
| 2026-10-01 | Built: store settings for approved sellers at `/sell/store`: logo (square, 200 px+, 2 MB), tagline, bio, what they make, public website link (separate from the private application portfolio link), and a store name that can change once every 30 days with reviewers notified in-app. The storefront address never changes. Reviewers can remove an unsuitable logo from the sellers queue. The member's personal profile photo is never used as the store logo. |
| 2026-10-01 | Built (slice 4): ratings and reviews. 1-5 stars with a written review (10-2000 characters), by **verified buyers only**: a completed `purchases` row for that member and model (checkout will create these; until then the demo seeder does), one review per buyer per model, never by the seller. Shown as "Amina O." (first name and surname initial) with a "Verified buyer" badge; authors can edit or delete their own. The seller gets **one public reply** per review (editable/removable; the buyer is told on the first). Any other signed-in member can **report** a visible review (spam / abusive / fake / other, once each); reviewers with the new permission `moderate reviews` work a queue at `/admin/reviews` (Reported / Hidden / All): **hide with a required reason** (the author is shown it, open reports are closed), restore, dismiss reports, remove an inappropriate seller reply. Hidden reviews drop out of the average; sellers cannot delete or hide reviews. `products.rating_avg` / `rating_count` are recomputed from visible reviews on every change and shown on cards, model pages, the seller's My models, plus a "Top rated" catalogue sort. Reviewing is gated on `Product::canBeReviewedBy()`, so it keeps working unchanged when checkout lands (refunded purchases will not count). |
| 2026-10-01 | Built: store rating. `seller_profiles.rating_avg` / `rating_count` average the visible reviews of all the seller's **published, not-deleted** models together (the reviews themselves, not each model's average), kept in step by `ProductRatingService::recomputeStore()`, which runs on every review change and whenever a model's status changes or it is deleted/restored (hooked on `Product` events, so every path is covered). Shown only from **3 reviews** (`marketplace.min_store_reviews`) on the storefront header and the seller card on model pages (labelled "store rating"); below that, "Not rated yet". On the member's own profile sellers get a separate "Model store" card; the freelance rating (from `JobReview`) is deliberately not mixed with it. |
| 2026-10-01 | Built: the landing page now leads with both halves of the platform. New two-pillar hero ("Find 3D models. Hire 3D talent.") with a model search that goes to `/models?q=`, popular-category chips and the open-browsing note; real **Browse by category** tiles (cover = newest model's preview, live counts), **Top-rated models** (best-rated with 2+ reviews, falling back to **New in the catalogue** until 4 qualify), **Top-rated stores** (public ratings only, best first) and a **Sell your models** section linking to `/sell`; the "Coming soon" teaser is gone and the nav's "Models" now links to `/models` (first in the list). Each marketplace section hides itself when it has nothing real to show (`LandingMarketplaceService`). Public browsing is deliberate, CGTrader-style: `/models`, model pages and storefronts need no account; login is needed to save, review, sell and (when checkout lands) buy. Not cached yet: if the landing gets heavy traffic, cache `LandingMarketplaceService` results for a few minutes. |
| 2026-10-01 | Landing page, second pass (user liked CGTrader's gallery look): the category tiles are replaced by **Browse by type** (Free, Animated, Rigged, PBR, Low-poly, 3D print ready: image tiles with live counts, linking to the catalogue's own `features[]` filters; types with no models are left out, and neighbouring tiles use different covers where they can) plus **By file format** chips (`?format=`, counted from files on published models), and the model cards are replaced by a **gallery**: image-first square tiles (`x-models.tile`) with title, store, rating and price shown on hover or focus (always on small screens), a big 2x2 first tile once there are 9+ models, and 9 or 21 models so every row is full at 2, 3, 4 and 6 columns. Categories stay one click away in the hero's popular chips and the catalogue's own category bar. The catalogue page itself keeps the information-rich cards (it has filters and sorting to serve). |
| 2026-10-01 | Catalogue filters: added **Textures** (`has_textures`) and **VR / AR** (`is_vr_ready`) to the quick-filter pills (`features[]=textures|vr`, combinable with the others), and to the landing page's Browse by type tiles (now up to eight; more than six wrap into even rows of four). Sellers already set both flags on the listing form. "Scanned" is not a flag: scanned assets live in the Scanned Models category. |
| 2026-10-01 | Signed-in navigation regrouped by product. Sidebar: **Overview** (Dashboard, Notifications), **Models** (Browse models, Wishlist; approved sellers get a "Selling" sub-section with My models and My store, everyone else a plain "Sell models" entry), **Projects** (Browse projects, My applications, Post a project, Posted projects, Engagements), then Administration for staff. Replaces the old Find work / Hire / Marketplace / Delivery split; every entry now has its own icon (enforced by a test). Profile dropdown rebuilt around account-level links (Your profile, Edit profile, Security, linking to the profile tabs by hash), a "Your store" block for approved sellers (store settings + public storefront), Help & legal (Terms, Privacy, Cancellation policy) and Log out, with a scroll cap on short screens. |
| 2026-10-01 | The top bar's primary button follows the page's sidebar group (`SidebarMenu::primaryAction()`): **Post a project** on Projects pages, **Add a model** (approved sellers) or **Sell your models** (everyone else) on Models pages, and a **Create** menu with both on the dashboard, notifications, profile and admin pages. It is left out on the page it would lead to. |
| 2026-10-01 | Dashboard enriched for models, on one merged page (`ModelsDashboardService`, merged into `DashboardOverviewService`). Approved sellers get a second **Models** row of tiles (Live models with how many are in review, Need changes, Store rating once public, Saves) under a **Projects** row, a **My models** card (latest four with status, price, rating, saves; links to the public page when live, to the editor otherwise) and **Reviews of your models**. The single "Needs your attention" list now also carries model items: a listing sent back or taken down (with the reviewer's reason), reviews from the last 30 days still without a reply (one row per model), and a seller application that was turned down or suspended (shown to non-sellers too). Items share one urgency scale with the job-board ones. For sellers the "Needs your action" tile is dropped (it counted both products) and the greeting count links to the list instead. Not built, by choice: a saved-models strip and a staff work-queue panel. |
| 2026-10-01 | **Avatars replace initials and uploaded pictures everywhere**, modelled on FreelanceDock's picker but deeper and random by default. A fixed catalogue of 720 SVGs: **people** (members) in 9 styles of 48 (Notionists, Notionists Neutral, Lorelei, Lorelei Neutral, Open Peeps, Avataaars, Avataaars Neutral, Pixel Art, Pixel Art Neutral) and **stores** in 6 brand-style sets of 48 (Shapes, Rings, Thumbs, Robots, Robot Faces, Identicons). Only CC0 / free-for-commercial styles, so no credits are needed (the generator refuses any style that would need one). Every new member and store gets a random avatar at creation (`User` / `SellerProfile` `creating` hooks), existing ones were backfilled at random by the migration, and a pick is stored as `style/seed` in `users.avatar` / `seller_profiles.avatar`. Members pick on Profile > Edit profile (saved at once, plus "Surprise me"); sellers pick on My store (saved with the other store details, with the live preview). **Uploads are gone**: profile photos and store logos, their upload UI, the reviewers' "remove logo" tool, `max_logo_mb`, and the old photo step in profile completeness; the migration deletes the old files and drops `user_profiles.avatar` and `seller_profiles.logo_path` (not reversible). `getInitials()`, `SellerProfile::initials()` and the live initials preview are removed, and a test fails if the word comes back. Files are generated, not hand-made: edit `resources/avatars/catalogue.json` and run `npm run avatars:generate` (DiceBear libraries as dev dependencies); a test checks the files on disk match the catalogue. A query that loads a user without the `avatar` column throws outside production, like lazy loading, instead of quietly showing the wrong picture. |
| 2026-10-01 | **Staff have their own accounts and their own portal.** Until now staff were ordinary members with a role (one of five, three of them never assigned), and 7 of the 14 permissions were checked by nothing. Now: a separate `staff` table and session guard (`staff`, sign-in at `/admin/login`, own password resets), no member features, so no conflicts of interest and a clean place for 2FA later. Roles and permissions attach to staff only (spatie guard `staff`); members have none. The permission list lives in code (`App\Support\Staff\StaffAccess`): moderation (`review models`, `review sellers`, `moderate reviews`), disputes (`view disputes`, `resolve disputes`), access (`manage staff`, `manage roles`), system (`view audit log`), each enforced by a route. Roles are **editable in the portal and a person can hold several**; the defaults are Super admin (locked, always holds every permission, passes any check via `Gate::before`), Marketplace moderator, Dispute manager and Support. The portal (`routes/staff.php`, `layouts/staff`, `StaffMenu`) has its own dark shell with live queue counts, a **dashboard of work queues** (count and how long the oldest has waited), notifications, an account page (name, avatar, password), **Staff** (invite by email, roles, deactivate/reactivate, search; the last active Super admin can never be demoted or deactivated, nor yourself), **Roles**, and an **Activity log** (who did what, when, from where; moderation decisions, dispute assign/resolve, staff and role changes, sign-ins). New accounts get a random password nobody knows and an emailed set-your-password link. The first Super admin is created with `php artisan staff:create-admin {email}` (prompts for a strong password); the old hard-coded `admin@modelhub.com` / `admin1234` seeder now runs in local development only. The migration moved existing staff over keeping their ids, so every "reviewed by / hidden by / assigned to / resolved by" in the data still points at the right person, re-pointed those columns at `staff`, deleted the old member-side roles and permissions (not reversible), and made `job_partial_payments.processed_by` nullable (a payment created by settling a dispute names the staff member in `finalized_by`). The shared dispute page is now two: members see their own dispute with no resolution form and "ModelHub support" instead of a staff name; staff get `/admin/disputes/{cancellation}` with the evidence, both parties and the resolve form. Staff preview unpublished models on the public page while signed in to the portal. Dropped from the member app: the Administration sidebar group and every admin override (`hasRole('admin')`). Not built yet: 2FA for staff, a members list with suspend, conflict-of-interest guards. |
| 2026-10-01 | **Staff can now see and manage the whole platform**, not just their queues. New in the staff portal, each behind its own permission (18 in all, up from 8; a read permission and an action permission per area): **Platform overview** (`view platform overview`: members, open projects, hires in progress, money in escrow, live models, stores, applications and model reviews, with 7-day trends and 12-week bar charts, cached 5 minutes); **Members** (`view members`, `view contact details`, `manage members`: searchable directory with status and activity filters; a member page with their activity, projects, applications, hires, models, store and private staff notes; **suspend** with a reason (signs them out, blocks sign-in, hides their projects, models and storefront until reinstated, emails them the reason), **reinstate**, **notes**, send a **password reset** link or resend **verification**; staff never see or set a password; no impersonation); **Projects** (`view projects`, `moderate projects`: every project in every state; **take down** with a reason the poster sees, which also stops the poster reopening it, and **restore**); **Hires** (`view engagements`: read-only money, escrow dates and deliverable progress; never the messages); **All models** (`view models`: every model in every status with sort and review history; the review queue stays the work list, `review models` stays the action permission) and **All stores** (`view sellers`; suspend/reinstate use the existing `review sellers` action). **Privacy:** emails and phone numbers are masked (`j••••@gmail.com`, `•••• 678`) unless the role holds `view contact details`; the email search is ignored without it, so masked addresses cannot be probed; seeing real contact details is logged (once an hour per member). Private messages are not browsable anywhere: staff holding `read dispute messages` can read the conversation attached to a dispute, logged. New default roles: **Platform manager** and **Auditor** (read-only, no contact details); Support now sees members and contact details; existing roles keep their edits, and the migration gave roles that review models/sellers or resolve disputes the matching view permissions. Schema: `users.suspended_at/suspended_reason/suspended_by/last_login_at`, `member_notes`, `model_jobs.taken_down_at/taken_down_reason/taken_down_by`. A project is open only if not taken down and its poster is not suspended (one scope, `ModelJob::openForApplications`). |
| 2026-10-01 | **The staff dashboard is now a working page, built from what the person may do** (`StaffDashboardService`; a block they hold no permission for is never queried). Order, by the user's choice: **Needs attention** (the actual items from each queue the person works, oldest first, 12 at most; amber after 3 days, red after 7; a headline counts items and how many are late; disputes nobody has taken on carry an *Unassigned* tag), then **Your work** (disputes you are handling, your decisions in the last 7 days by area, what you did last; sign-in noise excluded) with recent notifications, then **the platform**: **Platform pulse** (`view platform overview`, tiles with trends and sparklines), **role feeds** per read permission (Members: newest, suspended, unverified; Projects: newest, quiet for a week with no applicants, taken down; Hires: active hires with overdue deliverables; Models: newest published, most saved this week), and **Team and security** (`view audit log` for decisions per person, who is active today and failed sign-ins in 24 hours; `manage staff` for accounts with no role, never signed in, deactivated). Cards align to the top of their row rather than stretching to the tallest neighbour. |
| 2026-10-01 | **Platform settings, starting with Security** (`/admin/settings/security`, Super admin only; no permission can be handed out for it). Settings are declared in code with defaults and limits (`SecuritySettings`), only changed values are stored (`platform_settings`), reads are cached, and every save is written to the activity log with old and new values (area *Settings*). Defaults reproduce the old behaviour, so nothing changes until a Super admin changes it. **Sign-in codes:** a second step after the password, by authenticator app (TOTP, our own RFC 6238 code; QR drawn server-side with `bacon/bacon-qr-code`; 8 single-use recovery codes, shown once) or emailed code. *Require a code for members* and *for staff* are separate switches; when on, everyone is asked, those without an app are emailed a code. Members keep their own opt-in; staff get the same step (previously none). Credentials are checked without signing anyone in, so no session, `Login` event or remember cookie exists until the code is right. Wrong codes are counted per account (limit is a setting), so restarting the sign-in gives no new guesses; a code can be used once. Switching apps off falls back to email. **Trusted devices** (off by default): after a correct code a browser can skip the code for N days; only a hash of its token is stored, and a password change, a staff reset or switching it off ends it. **Passwords:** minimum length, mixed case, number, symbol, applied by `Password::defaults()` everywhere (staff keep a 12-character floor); the hints on the forms now show the real rule. **Sign-in protection:** failed attempts and lockout minutes, for members and staff. **Sessions** (members and staff separately): idle timeout, longest session, and one active session (signing in elsewhere ends the other; a per-account token, works with Redis sessions). A longest-session limit also switches off "Keep me signed in". **Lockouts:** staff can reset a member's (`manage members`) or colleague's (`manage staff`) two-step setup, which emails the account and is logged. Not built: social login ("Sign in with Google" does not exist; the platform rule covers the app and email methods), registration still signs a new member in without a code. |
| 2026-10-02 | **The staff portal now looks and works like the member app.** Research (current SaaS admin patterns: quiet chrome and high density, hierarchy from type and space, tables first, command palette, empty states with a next step) and a side-by-side audit found the staff side read as a different, plainer product. Direction chosen: *match the member app*. **Shell:** paper background, light collapsible rail (the member `x-sidebar`, now shared with a staff label and teal count badges), breadcrumb top bar with a search box, notifications and account menu, and `wire:navigate` page changes. **Dashboard:** a row of queue tiles first (count, age of the oldest item, amber/red when late), then Needs attention and Your work, then *The platform*: KPI tiles with trend chips and sparklines, a weekly trend chart (Members, Projects, Models, Applications) and recent staff activity, then the role feeds and the team block. **Shared parts:** `x-stat-tile`, `x-sparkline`, `x-trend-chart` (SVG, no chart library), `x-staff.header`, `x-staff.toolbar` (status tabs with counts, search, removable filter chips with Clear all), `x-staff.table` (sticky header from md up, sortable columns, right-aligned numerals, comfortable/compact density remembered in the browser, pager footer) and `x-staff.row` (whole row clickable). Members, Projects, Hires, All models, All stores and Staff are tables; `ListSort` whitelists the sortable columns per list (`?sort=&dir=`), so nothing arbitrary can reach a query. **Command palette (Ctrl/Cmd+K, or the top-bar search):** pages and quick actions filter instantly; typing searches members, projects, hires, models, stores, disputes and staff (`/admin/search`), each group only for people who may view that area, contact details masked as on the member list (email only matched with *view contact details*). The member page now states which two-step method applies, including when the platform requires it. Not built: bulk actions on the moderation queues (decisions need a per-item reason or note), dark mode. |
| 2026-10-02 | **Staff bulk actions.** Tick items on a list and act on all of them: **Model reviews** (publish, ask for changes), **Seller applications** (approve, reject), **Review reports** (dismiss reports, hide), **Disputes** (assign to me), **Members** (suspend, reinstate), **Projects** (take down, restore), **Staff** (deactivate, reactivate) and **Notifications** (mark as read). Each is the one-at-a-time action run over the selection, never a second implementation: the same service call, the same permission (checked per action, so a role only sees the buttons it may use), the same per-item rules, the same audit entry and the same notification to the person affected; plus one summary entry per batch (`bulk.*`, filterable under *Bulk actions* in the activity log). Anything that tells the person why (reject, hide, suspend, take down, ask for changes) takes **one shared reason** (5-500 characters) for the batch. An item that cannot take the action (wrong state, already done, a safeguard) is **skipped with its reason and listed afterwards**, never half-done, and one failure does not stop the rest; limits: at most 50 items a batch, this page only (no "select everything matching the filter"). Safeguards that still hold in bulk: you cannot deactivate yourself or the last active Super admin (checked per item, in order), a dispute a colleague is handling is never taken off them, and settling a dispute stays one at a time. Only eligible rows get a tick box (models in review, pending applications, reviews with open reports, unassigned disputes, unread notifications, everyone but yourself). One endpoint (`POST /admin/bulk/{action}`), one registry (`BulkActions`), one service (`BulkActionService`), one UI wrapper (`x-staff.bulk`: tick boxes, a bar that appears with the selection, one dialog). |
| 2026-10-02 | **Money decisions D4, D5, D6, D9.** **Commission (D4):** a platform default rate for each of jobs and model sales (jobs stay at today's 10%), set by a Super admin in platform settings instead of the `SERVICE_FEE_PERCENTAGE` constant, with an optional per-seller override for special deals; whichever rate applied is snapshotted on the transaction, as applications and engagements already do. Tiers by sales volume or by category are not built, and can be added later on top of the same override mechanism. **Licences (D5):** two tiers, *Standard* (personal and commercial use in one end product such as a project, render or game; the model itself may not be resold or redistributed) and *Extended* (higher-volume or multi-seat commercial use, priced higher by the seller). Every purchase issues a licence record the buyer can download, the legal record of what they hold (replaces the unused `products.license` placeholder). **Pricing (D6):** each listing is free or has a fixed KES price per licence, never below a platform minimum so M-Pesa charges cannot swallow the sale; no pay-what-you-want at launch. **Refunds (D9):** model sale earnings are held 7 days, then become withdrawable (the length is a setting). Digital files are not refundable once downloaded, except a file that is broken or not as described, which staff can refund within the hold with compensating ledger entries. **Numbers confirmed 2026-10-02:** model-sales commission 15% (jobs 10%), minimum price KES 100. **Still open:** seller terms and the licence wording (written by us, reviewed before launch). |
| 2026-10-02 | **Fees are now settings** (first money-core foundation). A Super admin sets, on *Platform settings > Fees and payments* (`/admin/settings/fees`, next to Security): commission on jobs (10%), commission on model sales (15%), the lowest price for a paid model (KES 100) and the sale-earnings hold (7 days). `FeePolicy` reads them (`jobsRate()`, `modelsRate($seller)`, `minModelPriceMinor()`, `saleHoldDays()`) and replaces the `SERVICE_FEE_PERCENTAGE` constant, so a job offer is priced at the rate in force and keeps it (it was already snapshotted on the application and engagement). A seller can have their own model-sales rate, set by a Super admin on the store page (`seller_profiles.commission_percent`, empty = platform rate; each change is logged). A paid listing must cost at least the minimum, and the listing form tells the seller the minimum and their rate. Changes are audited like any setting (area *Settings*). The 7-day hold is stored but nothing reads it yet: it applies when model sales and earnings exist. |
