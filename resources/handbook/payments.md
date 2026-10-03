# Payments handbook for staff

> **DRAFT for review, 2026-10-02.** Internal. Describes how money works on ModelHub and what to do in each situation, written from how the platform actually behaves. Items marked **[CONFIRM]** are decisions or rules for the team to settle. Read the member-facing guide (How payments and earnings work) too: it is what members have been told, and your decisions should be consistent with it.

---

## 1. The five rules

1. **The ledger is the truth, and it only ever grows.** Nothing in it is edited or deleted. A mistake is corrected by posting the opposite, never by changing history.
2. **Money is never lost.** Anything that arrives and cannot be matched is parked in *Payments waiting to be sorted out* and shows in **Payments → Needs review**. It stays there until a person decides.
3. **The platform records; you move the real money.** Refunds and returns to clients are **recorded** in the portal (which posts the books) and then **sent by hand** from the M-Pesa portal. Withdrawals are approved in the portal, and the system sends them. Until you have sent the money, the record is a promise, so **send it promptly**.
4. **Check before you click on anything that sends or settles money.** M-Pesa transfers cannot be recalled.
5. **Everything you do here is logged** (Activity log) with your name, what, and when. Phone numbers are masked unless you hold the permission that needs them.

---

## 2. Who can do what

| Role | Payments | Payouts | Escrow refunds | Ledger | Disputes |
|---|---|---|---|---|---|
| **Super admin** | all | all | all | all | all |
| **Finance** | view + **refund** | view + **approve** | view + **record returns** | view | no |
| **Auditor** | view | no | view (masked) | view | view |
| **Dispute manager** | no | no | no | no | view + **resolve** |
| Others | no | no | no | no | (Support/Platform manager: view only) |

Permissions behind this: *view payments*, *refund payments*, *view payouts*, *approve payouts*, *view ledger*, *view disputes*, *resolve disputes*. Roles are edited under **Access → Roles**.

**Recommended practice [CONFIRM]:** have a second person (not the one who approved a large withdrawal or recorded a large return) check the Ledger page weekly; agree a threshold above which a second person must approve.

---

## 3. How the money is held

Every movement is a balanced posting between **accounts**:

| Account | What it is |
|---|---|
| **Held at the payment gateway** | All the money we hold with M-Pesa. |
| **Member: in the hold** | A seller's share of a model sale, not yet withdrawable. |
| **Member: available** | What a member can withdraw (model sales after the hold, plus job earnings). |
| **Job escrow** (one per project) | What a client paid into that project and has not been released or returned. |
| **Payments waiting to be sorted out** | Money that arrived but could not be matched. |
| **Payouts on their way out** | Withdrawals asked for but not yet confirmed paid. |
| **Commission earned** | Our income: model commission and the job service fee. |
| **Payout fees charged** | The withdrawal fees we kept. |

**The reconciliation rule.** *What the gateway holds = what we owe members (hold + available + payouts on their way + unallocated + job escrow) + what we have earned.* The **Ledger** page shows this at the top, in green ("The books add up") or red ("do not add up", with the difference).

- **Red means stop and investigate.** Do not approve more money out until it is explained. Open the most recent postings, check for a recorded refund or return that has not been matched by a real transfer, and escalate to engineering with the difference shown.
- Money you have **sent by hand** (a refund or return) should bring the *real* M-Pesa balance down by the same amount. Comparing the M-Pesa portal balance with "Held at the gateway" is a good end-of-day check. **[CONFIRM: who does this and how often.]**

---

## 4. Daily routine

Check these in order; the menu badges and the dashboard "attention" list show what is waiting.

1. **Payments → Needs review** (badge): anything here is real money we cannot yet match. Clear it (section 5).
2. **Payouts → Waiting**: approve or turn down withdrawals (section 6). Oldest first.
3. **Payouts → Being sent**: anything still here after the alert threshold (6 hours by default) is unconfirmed. Settle it (section 7).
4. **Escrow refunds → Due back**: record and send the returns (section 8).
5. **Payment disputes**: check for new or unassigned disputes (section 9).
6. **Ledger**: make sure the banner is green.

---

## 5. Payments that need review

A payment lands in **Needs review** when money arrived but could not become what it was for:

- **Wrong amount:** the customer paid a different amount from the one asked for (M-Pesa lets people change it in some situations).
- **Already holds the licence:** the buyer got the licence another way while this payment was pending.
- **Job no longer waiting for funding:** the client paid after the project was cancelled, or it had already been funded.

The page shows the buyer, what was asked, what arrived, the M-Pesa receipt and why it was parked.

**What you can do:** **Return the parked money** (*Record a refund*). The system posts the books (unallocated → gateway) and tells the buyer; **you then send the money** from the M-Pesa portal to the number shown (reverse the receipt if the portal offers it). The payment becomes *Refunded*.

> **Limitation [CONFIRM]:** there is no "allocate it anyway" action. A customer who paid the wrong amount for a model must be refunded and buy again; a client who paid the wrong amount for a job must be refunded and fund again. Say so when you contact them.

Before you refund, **check the receipt really exists in the M-Pesa portal** with the same amount and number.

---

## 6. Withdrawals (Payouts)

A member asks to withdraw; the amount **leaves their available balance immediately** (into *payouts on their way out*), and waits for you.

**To approve:**
1. Open **Payouts → Waiting**. Check the member, the amount and the number ("To").
2. Is it plausible? Look for: a brand-new account withdrawing a large amount, a number that differs from before, a sale or job that is itself under dispute or recently refunded. If unsure, ask a colleague or look at the member's page and ledger postings.
3. **Approve.** The system marks it *being sent* **before** calling M-Pesa (so a crash cannot send it twice) and then sends it.
4. The result arrives from M-Pesa by itself, normally within seconds or minutes. On success the member is told; on failure the money returns to their balance and they are told.

**To turn down:** give a clear reason (the member sees it). The money returns to their balance. Use this for mismatched numbers, suspected fraud, or a withdrawal you cannot verify.

**Members can cancel** a withdrawal while it is still waiting.

**Fees and limits** (set under Settings → Fees): minimum withdrawal KES 500; fee KES 30 **[CONFIRM: placeholder]**; at most KES 150,000 per transfer.

---

## 7. Unconfirmed withdrawals

M-Pesa has no way for us to ask where a payout is; we only hear back when it tells us. If no result arrives, the withdrawal stays in **Being sent**, and after the threshold you get a notification ("Withdrawal not confirmed"), repeated once a day.

**Never guess.** Open the **M-Pesa business portal** and find the transaction by the member's number, the amount and the time (the withdrawal's reference is the *originator conversation ID*).

- **It is in the portal as completed:** click **Settle → It was sent**, enter the **M-Pesa receipt** exactly. The books close as if the result had arrived and the member is told.
- **It is not in the portal at all (or shows failed):** click **Settle → It was not sent**, write what you found. The money returns to the member's balance and they are told.

> **Settling as "not sent" when it was sent pays the member twice** (once now, once from their returned balance if they withdraw again). Settling as "sent" when it was not means the member is never paid. If you cannot tell, **do not settle**: escalate.

A timeout notice from M-Pesa changes nothing by itself: the money may still go.

---

## 8. Escrow returns to clients

When a funded project ends early, what is left in escrow is the client's to have back. The **Escrow refunds** queue lists it:

| Tab | Meaning |
|---|---|
| **Due back** | It is now the client's. Act on it. |
| **Waiting** | The client's review window is open (they may still pay for unapproved work), or a payment or dispute is open. Nothing to do yet. |
| **Returned** | What has been recorded. |

**When it becomes due.** If the **freelancer** cancelled: at once. If the **client** cancelled: after the review window (7 days **[CONFIRM]**) with no partial payment opened. After a partial payment is accepted or a dispute resolved: at once (the amount paid goes to the freelancer; the rest is due back).

**To return it:**
1. Open the job from the row if you need context (parties, what was approved, the ledger postings).
2. Confirm the amount and the number it will go to: **the number the client paid from**, shown in the row.
3. **Record return** (add a note if you like). The books are posted and the client is told.
4. **Send the money** from the M-Pesa portal to that number. Put the portal's reference in the note when you record it (so record it after sending, or send straight after and keep the reference to hand). **[CONFIRM: preferred way to keep the portal reference.]**

If the number is wrong or the client asks for it elsewhere, handle it as an exception: verify the client's identity before sending anywhere else.

---

## 9. Disputes about job money

A dispute arises when a freelancer refuses a partial payment the client offered after cancelling.

1. **Open the dispute** (Payment disputes). Read both sides and the evidence. If you hold the permission, read the conversation (this is logged).
2. **Assign** it to yourself so others do not duplicate work.
3. **Decide a final amount.**
   - **Funded job (paid through escrow):** the amount is **the extra paid to the freelancer for unapproved work**, on top of what approved deliverables already released. It cannot exceed **what is left in escrow for the freelancer**; the system refuses a larger amount and tells you the ceiling. After you resolve it, the amount is paid out of escrow and the **rest becomes due back to the client** (appears in Escrow refunds).
   - **Job that was never funded (older data):** the amount is recorded only; no money moves through the platform.
4. Write **resolution notes** (both sides see them). Be specific about what you relied on.
5. **Resolve.**

Be even-handed and consistent with the member guide. Decisions are final on the platform **[CONFIRM: appeal route]**.

---

## 10. Refunding a model sale

Policy: a refund is for a **broken file or one not as described**, asked for within **7 days** of purchase (the hold). Files that work and match the listing are not refundable.

1. Find the payment (**Payments**, search by reference, buyer or model). The page shows the licence, **whether any files were downloaded**, and the ledger postings.
2. Verify the complaint (ask for a screenshot, try the file if you can, look at the listing).
3. **Record a refund** with a clear reason (the buyer is shown it). The system: ends the licence; takes the seller's share back (from **in the hold**, or from **available** if the hold has passed) and the commission back; posts the full amount back toward the gateway; tells both sides.
4. **Send the money** to the buyer's number shown on the payment page, from the M-Pesa portal.

**If the seller has already withdrawn** the money, the system **refuses** ("the seller has already withdrawn this money") and changes nothing. The options are outside the system: ask the seller to return it, or decide whether the platform covers the refund. **[CONFIRM: policy.]**

A payment for a **job's escrow** is **not** refunded from the Payments screen: it is paid out or returned from the job (section 8).

---

## 11. Fees, limits and the settings

Under **Settings → Fees and payments** (Super admin):

| Setting | Default | Notes |
|---|---|---|
| Commission on jobs | 10% | Taken from the freelancer's side as work is approved. |
| Commission on model sales | 15% | A store can have its own rate. |
| Lowest price for a paid model | KES 100 | |
| Smallest withdrawal | KES 500 | |
| Withdrawal fee | KES 30 | **[CONFIRM: placeholder.]** |
| Hold on model sales | 7 days | Job earnings are not held. |

Changing a setting applies to **new** sales and projects only. Each sale and project keeps the rates it started with, so you cannot change what a member agreed to.

Other limits: a single M-Pesa payment is at most **KES 150,000** (so a job above that cannot be funded through the platform yet); a payment prompt lasts **5 minutes**.

---

## 12. What members were told

Keep your decisions consistent with these promises:
- Money is never lost; it is held and sorted out.
- Refunds for broken or not-as-described files within 7 days.
- Project money is released as each deliverable is approved; approvals are final.
- A client who cancels has 7 days to pay for unapproved work; otherwise the rest goes back to them.
- Withdrawals are approved by staff **[CONFIRM: target time]**; rejected ones come with a reason.

---

## 13. Reference

**Payment statuses:** *Waiting* (prompt sent), *Paid*, *Needs review* (arrived, could not be matched), *Refunded*, *Failed*, *Cancelled*, *Expired*.
**Withdrawal statuses:** *Waiting for approval*, *Being sent*, *Paid*, *Failed*, *Turned down*, *Cancelled*.
**Job money states:** *Not funded* (nothing paid), *Awaiting funding*, *In escrow*, *Part paid / paid out*, *Refund due*, *Returned to client*.

**Ledger posting types** (Ledger → tabs): *sale* (payment becomes a licence), *unallocated* (money parked), *release* (hold ended), *refund* / *refund of unallocated*, *withdrawal asked / sent / returned*, *escrow funded / released / extra / returned*.

**Where to look:**
- A payment's whole story: **Payments → the payment** (including its postings).
- A project's money: **Hires → the project** (Escrow panel and postings).
- A member's money: **Ledger → filter by member**.
- Who did what: **Activity log**.

---

## 14. Known gaps

- No automatic approval of work a client never reviews.
- A partial payment the freelancer never answers keeps the escrow frozen; escalate to engineering to settle it.
- No member-facing "request a refund" button; requests come through support.
- Refunds and returns to clients are sent by hand from the M-Pesa portal; there is no automatic reversal yet.
- Projects over KES 150,000 cannot be funded through the platform (one M-Pesa payment).
- Tax (VAT on fees, withholding) is not handled by the platform; see the member guide's note **[CONFIRM]**.
- Real M-Pesa has not yet been run against Safaricom's live service; the first live transactions should be small and checked end to end.

