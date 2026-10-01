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
| D4 | Commission model | flat % / tiered by seller level / per category; separate rate for jobs vs models | fee config, admin |
| D5 | Licence types | which tiers and their legal text | listings, `IssuedLicence`, terms |
| D6 | Model pricing | fixed price only vs pay-what-you-want/free models | checkout, listing form |
| D7 | Search engine | Meilisearch vs Typesense vs managed | infra, cost |
| D8 | File storage provider | S3 vs R2 vs B2 vs MinIO self-hosted | cost, egress, CDN |
| D9 | Refund window | days before sale earnings become withdrawable | balances, policy |

## Decision log

| Date | Decision |
|---|---|
| 2026-10-01 | Direction agreed: freelance job board plus a CGTrader-style models marketplace on one platform. This note written; nothing else decided yet. |
| 2026-10-01 | D1: KES only at launch, currency stored on every money row. D2: M-Pesa first, cards later. D3: approved sellers only. Build order: file pipeline and catalogue first; money core in parallel. |
| 2026-10-01 | Code naming: the 3D model listing is `Product` in code (tables `products`, `product_files`, …) to avoid clashing with Eloquent's `Model` and the existing `ModelJob`; the UI word stays "Model". |
| 2026-10-01 | Built: seller onboarding (apply at `/sell`, reviewers approve/reject/suspend at `/admin/sellers`, permission `review sellers`, notifications under category Marketplace). Seller terms (commission, licences, payouts) are still to be written before a first listing can be published. |
