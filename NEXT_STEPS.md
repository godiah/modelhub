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
   - Foundations: ~~fee from settings instead of a constant~~ DONE 2026-10-02 (`FeePolicy`, Fees and payments page, per-seller override, minimum price). Marketplace storage disks are already configurable; the job side (images, dispute evidence, deliverables) still hard-codes `public`/`local` and belongs to the file-pipeline step. Still to do: a licence record (Standard/Extended) in place of the unused `products.license` placeholder.
   - A ledger, an M-Pesa gateway adapter, a payout model; move engagement escrow and partial payments onto the ledger.
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
