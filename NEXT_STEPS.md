# Next steps

Written 2026-10-02, at the end of the staff-portal work. Everything up to this point is committed and pushed (`develop` and `main` at 4c98224 / 46c7313, 814 tests, CI and CD green).

## 1. Decisions

**Settled 2026-10-02** (full wording in `MARKETPLACE.md`, decision log):

| # | Decision | Outcome |
|---|---|---|
| D4 | Commission | Default rate per type (jobs, model sales), set by a Super admin; optional per-seller override; snapshotted per transaction |
| D5 | Licences | Two tiers: Standard and Extended; every purchase issues a licence record |
| D6 | Pricing | Fixed KES price per licence, or free; platform minimum price; no pay-what-you-want |
| D9 | Refunds | 7-day hold before earnings are withdrawable (configurable); refunds only for a broken or not-as-described file |

Already decided earlier: D1 KES only, D2 M-Pesa first (via `~/laravel-common`), D3 approved sellers only.

**Still to settle**
- Seller terms and the licence wording (to be written, then reviewed before launch).
- Not urgent: D7 search engine, D8 file storage provider.

## 2. Core product work, in order

1. **Money core and M-Pesa** (the biggest piece)
   - Foundations: ~~fee from settings instead of a constant~~ DONE 2026-10-02 (`FeePolicy`, Fees and payments page, per-seller override, minimum price). Marketplace storage disks are already configurable; the job side (images, dispute evidence, deliverables) still hard-codes `public`/`local` and belongs to the file-pipeline step. 
   - ~~Licence record~~ DONE 2026-10-02 (`LicenceTier`, `issued_licences`, `LicenceService`, My licences + certificate, Extended price on listings). Still to do for licences: staff view/revoke of a member's licences (with the refund flow), and lawyer review of the wording.
   - Decided 2026-10-02 (see MARKETPLACE.md log): slice 1 = ledger + gateway + model checkout, earnings with hold, withdrawals; slice 2 = move job escrow onto the ledger. Steps for slice 1: (a) ledger core (DONE 2026-10-02), (b) gateway interface + fake gateway + payments + checkout (DONE 2026-10-02; still to do for real money: the M-Pesa driver, see (e), and staff screens for payments needing review), (c) seller earnings page, hold release job, withdrawal requests + staff approval (DONE 2026-10-02), (d) staff payments, ledger browser + refunds (DONE 2026-10-02), (e) real M-Pesa: Daraja B2C added to `laravel-common` v3.1.0 (tagged), installed here, `DarajaGateway` (STK) + `DarajaPayoutGateway` (B2C) built and tested against faked Safaricom HTTP (DONE 2026-10-02); NOT yet exercised against real Safaricom: needs Daraja credentials, B2C enabled on the shortcode, and the go-live steps below.
   - Model checkout, licensed downloads, seller earnings and withdrawals.
   - Done when an engagement can really be paid and released, and a freelancer can withdraw.
2. **File pipeline**: private storage, direct uploads of large files (~500 MB), preview processing, signed downloads for buyers. Scope not re-checked; confirm how far it got. Needs D8.
3. **Search engine** (D7): Meilisearch, Typesense or managed. Search is database-backed today.
4. **Growth**: collections, discounts and promotions, subscriptions, recommendations. After the money core.

## 3. Going live

- No production target yet: need a server and domain for the deploy pipeline.
- When there is one, these migrations must run there: `platform_settings`, the two-step sign-in and session columns, `trusted_devices` (`2026_10_06_*`). New composer dependency: `bacon/bacon-qr-code`.
- First production Super admin: the seeded `admin@modelhub.com` is local-only. Use the create-staff-admin command.
- Make sure outgoing email works before turning on "require a sign-in code for staff", or a mail problem locks everyone out of the portal.
- Dev settings are strict (codes required for members and staff, 3 attempts with a 60-minute lockout, one session per account, 30-minute idle). Reset them on `/admin/settings/security` before demos.
- Dev staff login: `/admin/login`, `admin@modelhub.com` / `admin1234`. With codes required, tick "Trust this device" at `/admin/two-factor`; Mailpit is on port 8029.

## 4. Smaller leftovers

- Conflict-of-interest guards (staff acting on accounts related to them).
- Member account deletion and anonymising (privacy).
- A newly registered member is signed in without a code, even when codes are required.
- Business-logic tests (CONVENTIONS item 15) and the dead `engagements.cancel.form` route (both deferred earlier).
- Staff portal: dark mode; "select everything matching the filter" for bulk actions (page-only today); the reviews and disputes queues are still card lists, not tables.

## Suggested plan for the next session

1. Build the money-core foundations: fees and the 7-day hold as settings (replacing the 10% constant), configurable storage disk, a licence record in place of the `products.license` placeholder.
3. Start the ledger and the M-Pesa adapter against the sandbox.

If the numbers are not ready, do the going-live items (section 3) and the deferred cleanups (section 4).

**Dev demo money:** `php artisan db:seed --class=DemoMoneySeeder` (after DemoModelsSeeder) seeds ~29 payments over three weeks (sales in and past the hold, 3 refunds, 1 payment needing review, a waiting prompt, failed/cancelled), and withdrawals in every state, through the real services and fake gateway. Ledger rows cannot be deleted through the app, so it refuses to run twice; clear with raw SQL if a reset is wanted. Dev `.env` now has `MARKETPLACE_PURCHASES_ENABLED=true` and `PAYMENTS_FAKE_DELAY=4`.

**Going live with real M-Pesa (checklist):** set `PAYMENTS_GATEWAY=daraja`, `PAYMENTS_PAYOUT_GATEWAY=daraja`, `PAYMENTS_DARAJA_CALLBACK_SECRET` (16+ random chars), the `MPESA_*` credentials (consumer key/secret, shortcode, passkey, B2C initiator name + security credential built with `SecurityCredential::fromPassword` and Safaricom's certificate for that environment), `MPESA_ENVIRONMENT`; confirm in the Daraja portal the B2C endpoint version (we default to v1, `MPESA_B2C_VERSION` to change), the result codes and that B2C is enabled on the shortcode; the app must be reachable over public HTTPS (callbacks go to `/webhooks/payments/daraja?token=...`, `/webhooks/payouts/daraja?token=...` and `.../timeout`); GH_PAT/`github_token` must let CI and the Docker build fetch the private `godiah/laravel-common`; replace the KES 30 withdrawal fee placeholder with the real B2C tariff; run a sandbox end to end (STK, B2C result, a timeout) before production. Known limits: Daraja has no B2C status API, so a withdrawal whose result never arrives stays "being sent" until staff settle it from the Payouts page (they are told after `PAYMENTS_PAYOUT_STALE_HOURS`, 6); an STK payment settled by the status query has no receipt until its callback arrives.

**Slice 2 (job escrow on the ledger), decided 2026-10-02, steps:** (a) escrow account per engagement + funding by STK + status Awaiting funding + release per approved deliverable [DONE 2026-10-02, uncommitted, browser-verified]; (b) cancellation, partial payment and dispute resolution paying out of / refunding escrow, and the staff "escrow refunds due" queue [DONE 2026-10-02, uncommitted]; (c) freelancer Earnings page (job payments), client funding UI/receipts, staff views (engagement escrow, payments purpose, ledger links), dashboard attention items [DONE 2026-10-02, uncommitted]. Open for slice 2: a partial payment the freelancer never answers keeps the escrow frozen forever (needs an auto-accept or staff nudge); job offers above KES 150,000 cannot be funded (one M-Pesa payment); funding by installments or by card is not built; a funded job's deliverables, once approved, cannot be reversed.

