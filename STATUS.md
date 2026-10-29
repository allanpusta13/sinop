# Status

## G1 — Evidence & Acceptance-Criteria Reconciliation (2026-10-27 → 2026-10-28)

Report: [docs/01-issues/2026-10-28-g1-evidence-ac-reconciliation.md](docs/01-issues/2026-10-28-g1-evidence-ac-reconciliation.md)
Pinned revision: PR #238 head `9ac18567bb22ff293eeece503129fc1d0deefbed`, working tree clean.

- **No code or test change warranted for #173, #205 or #207.** Every surviving contradiction is
  stale wording in a report or in the GitHub issue body — not a defect.
- **DC-5 — OUT-OF-SCOPE FINDING, TRACKED SEPARATELY (owner-ruled).** Analysis complete; follow-up
  issue **filed as #240** (linked from the G1 report §3.4/§5/§9 and from the #205 scoping line). (a) `isWarehouseStaff()` **returns `false`**
  on a malformed role, it does not throw (`app/Models/User.php:106-113`, `return false` at `:111`),
  so the negation is genuinely reached and the fail-open is real — not a helper-contract
  inconsistency. (b) `UserPolicy.php:61` is **CLEARED**: it is a guard-clause negation
  (`if (! helper()) return false;`), which fails closed. **The deciding factor is polarity
  position, not the negated text** — `return ! helper()` fails open, `if (! helper()) return false;`
  fails closed. Repo-wide, exactly **one** role negation has the fail-open shape. The
  `InventoryService.php:215,:880` inconsistency is folded into the follow-up. Fix direction to be
  stated in the issue: positive-predicate composition or an explicit `hasValidRole()` guard —
  **not** "flip the `!`". **No authorization code changed. Not a G1 blocker.**
- **Original finding, retained for the record:**
- **⚠ NEW, NEEDS-DECISION (DC-5) — pre-existing fail-open in authorization.**
  `app/Policies/DirectTransferPolicy.php:26` returns `! $user->isWarehouseStaff()`. Every other
  policy composes positive predicates, where a malformed role makes the helper `false` and thus
  denies; this one **negates**, so a malformed role yields `! false` → **`true`** and grants
  `viewAny`. It is the only verified breach of #205's "malformed role must never grant"
  contract. Bounded: `view()` still requires Admin/Auditor/BranchManager, so the exposure is
  list/navigation visibility, not row access; and `users.role` is `NOT NULL` with a throwing
  cast, so it needs out-of-band data. **No authorization code was changed** — the fix is a
  behaviour change reserved for the owner. Same negated pattern also at `UserPolicy.php:61`
  (not analysed; flagged). See the G1 report §3.4.
- **#173 `+1` key pinpointed:** `validation.role.invalid`, added by `aa54af6` — 140 leaves vs 139
  at `aa54af6^`. Previously inferred, now observed. R-C closed.
- **#205 cast question closed:** native `UserRole::class` also throws `ValueError` on read *and*
  set (`HasAttributes.php:1326-1331`), so swapping casts is behaviour-neutral — the AC's
  expectation was never a hardening requirement.
- **Parallel investigation passes integrated.** The #173/#205/#207 agents reported after the
  classifier recovered; every claim they made was independently re-verified before being
  recorded here or in the G1 report. Two contributed new evidence (the `+1` key identity; the
  `DirectTransferPolicy` fail-open).
- **#173 — canonical count CLOSED at 996.** Loader re-derived at the pin by two independent paths:
  `php docs/02-research/i18n-count-reproduction.php` → 996, and `TranslationKeyParityTest`
  (green, 1 test / 5 assertions) writing `Canonical keys: 996` to `storage/logs/i18n-parity.md`
  (Missing 0 / Orphan 0 / Identity 0 / es 0 / tl 0 placeholders). 995 is a stale 2026-10-23
  snapshot, not a rule difference — the counting rule is byte-identical in both producers
  (`TranslationKeyParityTest.php:59-107`, `i18n-count-reproduction.php:9-40`), and `lang/en` was
  last changed by `aa54af6` (2026-10-24 04:51 UTC) after it. **Owner decision D3 and residual
  risk R-4 are DISCHARGED.** 258 retired orphans per locale re-confirmed (272 lines / 258 rows,
  self-attested tail). 847 stays retired as a criterion.
- **#205 — fail-closed confirmed, no change.** Cast throws (`ValueError` on read,
  `InvalidArgumentException` on set), model helpers catch and deny, resolver returns `[]` via
  `default => []`. Nothing widens on malformed data. Owner decision **D1** requires this and
  outranks the AC's "resolver throws" wording — see the existing D-G note below. Deliverable
  state: 112/112 targeted tests green (`UserTest`, `ScopesNavigationBadgesTest`, `BadgeScopeTest`,
  parity, completeness, exporter).
- **#207 — removal real, guard valid, no change.** `app/Filament/Exports/` absent (directory, not
  just file); zero executable references; the delivered test asserts non-existence plus a
  PHP-native five-root sweep — stronger than the AC's `grep`. 2/2 green.
- **Corrections applied (documentation only):** `173-847-provenance.md` §4.4 and
  `173-issue-text-amendment.md` §1/§3 no longer say the count is open or "pending Bash recovery".
- **GitHub writes — status.** **#173 paste APPROVED, but its §2 block re-enters sign-off** for the
  command correction recorded below (§2 only; §1 is unchanged in substance). **#207 paste APPROVED** (with the
  "never committed" wording softened to "absent from all fetched history"). **#205 is HELD**:
  owner review caught that the draft bullet *"never widens to another tier"* is false at the
  policy layer (#240), so it is now scoped to the cast/model/resolver boundary with the policy
  layer scoped out and linked to #240 — awaiting re-sign-off. **Nothing has been pasted yet**;
  all three land together after #205 sign-off, followed by one 7-path docs commit. **No push,
  no merge, no other issue field touched.** One GitHub write did occur: **#240 was filed**
  (separately authorized).
- **Known near-miss, recorded:** the #205 draft would have asserted "never widens" while this
  very workstream documents a policy-layer widening. Owner caught it before paste. The finding
  changed the wording of the issue that produced it — the strongest case for surface-don't-fix.
- **⚠ NEW FINDING (2026-10-28) — the #173 paste block carried a false attestation.** The command
  as written, `grep -c 'RETIRED' docs/01-issues/i18n-parity-orphans-es.txt`, prints **260**, not
  the **258** its comment claimed: two comment lines also contain the word (`:3` header, `:272`
  total line). The count **258 is correct and confirmed three independent ways** — the namespace
  headers `140+83+35`, the sidecar's own tail line `TOTAL AUDITED RETIRED ORPHANS IN ES: 258`, and
  a row-anchored scan. The **command** was the defect. Corrected to
  `grep -cE '\| es \| RETIRED \|'` (returns 258), with an inline NOTE so a future reader does not
  "simplify" it back to the over-counting form. Same class as the #205 near-miss: a verification
  step whose stated output does not match what it actually prints. **#173's §2 paste block
  therefore re-enters sign-off.**
- **Paste-block hygiene (2026-10-28).** The three amendment sheets' pasted payloads were
  normalized to pure ASCII — the em dash `—` rendered as a block glyph in owner review, and `→`
  in the #173 bash block likewise. Structural prose is unchanged. Asserted ASCII-clean: 173 §1/§2,
  205 §1/§2, 207 §1/§4. `205-issue-text-amendment.md` SHA256
  `9C94AA7D3FD24242204FE197D08B624E134DA8AF5CE0566CB89FA69A9971BDCA` pre-normalization; the
  garble was transport-only, never file corruption.

### 2026-10-28 11:50 — first live issue-body read; #205 held on a target conflict

- **Issue bodies fetched.** The first successful `gh issue view` of the session returned the live
  bodies of **#173, #205, #207 and #240**. Every target below was checked against those bytes, not
  against the sheets' own quotations.
- **⚠ #173 and #207 — targets verified, no ambiguity.**
  **#173:** both targets matched exactly once — the *Scope* AC bullet, and the *Verification* bash
  block, whose fence interior is **CRLF** (the replacement preserves that).
  **#207:** the live *Test coverage* bullets carry the **specific** class names
  (`RepositoryMapTest::product_exporter_does_not_exist`,
  `RuntimeCompletenessTest::no_reference_to_product_exporter`), **not** the sheet's wildcard
  shorthand `RepositoryMapTest::*`. The sheet's §2 prose names the exact classes, so the intent is
  unambiguous: both bullets collapse into one bullet naming the delivered test. No issue edit made.
- **⚠ NEW FINDING — #205 is HELD on a target conflict, not on wording.** The sheet's **§2 second
  "current text"**, `An exception from the resolver on an out-of-domain value`, **does not exist in
  the live body**. The live *Resolver behavior* bullet — `An out-of-domain role value causes the
  resolver to throw (fail-closed), NOT silently widen to the Admin/Auditor tier` — is the one **§1
  already replaces**. So **§1 and §2(ii) name the same line and supply two different replacements**:
  §1 an AC bullet, §2(ii) a struck-through "removed" record. Both cannot apply. **No issue edit made.**
  **Owner decision required** — most likely reading is *apply §1 only, treat §2(ii) as discharged by
  §1*, since §1's replacement already carries the substance ("the resolver returns an empty scope").
  Choosing that reading without approval would be exactly the silent interpretation the project
  forbids on a public surface.
- **⚠ #205 — two further contradictions the authorized edit set does not cover.** Reading the live
  body against the source, the authorized §1/§2 edits leave two false statements standing:
  1. **The *Context* paragraph still says the resolver fails closed *by throwing*** — *"The resolver
     reads `role` inside its comparison chain, so an out-of-domain value fails closed by throwing."*
     No authorized edit touches *Context*. After §1 lands, the issue would contradict itself in two
     adjacent sections.
  2. **The first *Resolver behavior* bullet is factually wrong.** It requires
     `ScopesNavigationBadges::badgeScopedWarehouseIds()` to read **`$user->role`** inside its
     `match (true)` chain. The source does **not**: `ScopesNavigationBadges.php:129-137` composes
     only the catching helpers — `$user->isAdmin() || $user->isAuditor()`,
     `$user->isWarehouseStaff() || $user->isBranchManager()`, `default => []`. No `$user->role`
     access exists in the method. The sheet's own §3 table asserts exactly this ("no `ValueError`
     reaches the resolver frame"), so the sheet and the live AC disagree.
  Neither is resolvable by applying §1/§2 as written. They belong in the same owner decision as the
  §2(ii) conflict, so #205 can be corrected in one pass instead of three.

### 2026-10-29 02:40 — writes recovered; two pastes already landed; #205 held on a source gap

- **Write class recovered.** `gh` writes and shell redirects execute again after two blocked sessions
  (`UPDATES.md:61`, `:68`).
- **⚠ STATE DIVERGENCE — #173 and #207 bodies were already pasted by an unrecorded actor.**
  - **#173** `updatedAt` `2026-10-28T09:42:19Z`. Live body carries the sheet §1 and §2 replacements
    verbatim; both targets absent (`grep -c '847 orphan keys'` → **0**; `records the orphan set the
    documented counting rule` → **1**; `disposition record*, not a to-be-emptied` → **1**).
  - **#207** `updatedAt` `2026-10-28T12:27:56Z`. Live body lines 20–21 carry the delivered names
    (`RuntimeCompletenessTest|RepositoryMapTest` → **0**; the two `ProductExporterDeletionTest::`
    names → **2**). Still `OPEN`; **no close comment posted** (comment list empty).
  - **No `UPDATES.md` entry records either paste.** Both landed 10-28 17:42 and 20:27 local — hours
    before this pass, not a live concurrent race.
  - **No rewrite performed, deliberately.** Re-writing either body from a `/tmp` capture would risk
    re-encoding damage (the #173 capture shows `â€”` in one link title) for zero gain, since the live
    text already matches the sheet.
- **⚠ #205 HELD — the amendment sheet is missing two of the four authorized edits.** The signed set
  is §1 (AC replacement) + Context (tightened, minimal diff) + resolver bullet 1 + scoping line C.
  The sheet supplies **only §1 and scoping C** — it has no Context section and no replacement for the
  line-11 bullet (`…reads \`$user->role\` inside its \`match (true)\` chain`). Repo-wide search: the
  tightened Context sentence exists **only** at `STATUS.md:135` (my own transcription from the
  conversation — circular, not independent), and the bullet-1 replacement exists **nowhere**.
  The owner's safeguard is explicit — *"paste from the repo amendment sheets … not from this
  conversation"* and *"if a sheet and your in-session signed text disagree, stop and report the
  diff — do not resolve it silently."* **#205 is therefore not written.**
  Confirmed by direct read: #205 `updatedAt` `2026-10-14T01:24:53Z`, body wholly unamended.
- **#240 DONE — one-line `Related` path swap applied.** `2026-10-27-…` → `2026-10-28-…`, `§3.4`
  and `§9` anchors preserved. Proof: `diff` of the edited body against the live fetch showed exactly
  one changed line, all other bytes identical (5241 = 5241) **before** the write. Read-back after:
  `grep -c '2026-10-27-g1'` → **0**; `2026-10-28-g1` → **1**; `§3.4 (and §9 for this draft)` → **1**.
  New `updatedAt` `2026-10-28T21:44:15Z`.
- **#207 DONE — close comment posted and issue CLOSED.** Comment from sheet §4 (no reopening
  paragraph, per §5 option b), posted as `issuecomment-6481423734` and read back byte-identical bar
  GitHub's appended trailing newline (1367 vs 1366). Then `gh issue close 207 --reason completed` →
  read-back `CLOSED | COMPLETED | 2026-10-28T21:44:32Z | comments=1`. Body paste was already in place.
- **Commit still withheld.** Gated on #205 completing. `HEAD` `9ac1856`; 7 paths unchanged in scope
  (STATUS.md and UPDATES.md updated in place).

### 2026-10-29 22:35 — #205 Test coverage block resolved; follow-up #241 filed; G1 closeable

- **Item 2 — RESOLVED, applied.** Owner signed a revised draft; the placeholder became a real
  issue number before paste.
  - **Investigation result:** the three cited tests are phantom by **name only** — out-of-domain
    *is* exercised, at **four of the six** cited lines. `UserTest.php:519` (invalid persisted →
    cast read `ValueError`, `canAccessPanel()` false, all four `is*()` false), `:552` (invalid
    persisted **with a pivot row** → `hasAccessToWarehouse()` false anyway), `:544`/`:581`
    (invalid supplied on the set path → `InvalidArgumentException`, nothing persisted), and
    `ScopesNavigationBadgesTest.php:233`/`:289` (out-of-domain reaching the resolver → `[]` /
    `hasBadgeScope()` false). The harness at `:52` does `use ScopesNavigationBadges`, so those
    two drive the shipped resolver, not a copy.
  - **The real gaps** are elsewhere and were declared in the issue text: badge-count level
    (`BadgeScopeTest.php` is valid-role only) and policy-ability level (no test supplies a
    malformed role to `authorize()`/`Gate`; `canAccessPanel()` is the panel gate, not an ability).
  - **Correction:** `BadgeScopeTest.php` has **19** declared `it()` cases, not 17 — earlier count
    was a miscount, fixed in the sheet.
  - **Write:** `gh issue edit 205`; server `updatedAt` **`2026-10-29T14:35:03Z`**. Live body
    5106 → 5464 bytes; replaced span 191 → 549; prefix and suffix byte-identical; read-back equal
    bar the same single GitHub-appended trailing newline. Transport-damage patterns zero hits;
    the three phantom names have 0 occurrences on the live body.
- **Follow-up filed — #241** — *#205 test coverage gaps: badge-count out-of-domain and
  policy-ability malformed-role cases* (created `2026-10-29T14:13:55Z`). Names both gaps, states
  expected assertions, and records in the issue text that the policy-ability deny-assertion is
  **blocked-by-#240** (the `DirectTransferPolicy.php:26` fail-open) rather than written and left
  red.
- **G1 closeable.** Every stale statement is now off the live issue body; the remaining gaps are
  tracked in a real issue, not a placeholder.
- **Commit trail:** `c82a8381` (7 files), `8cd9851` (3 files), third commit this pass. Docs only;
  no code or test file in any of them.
- **No push, no merge.**

### 2026-10-29 17:05 — #205 Cast contract bullet applied and read back; phantom test names held

- **Item 1 — DONE (owner-authorized).** The live #205 *Cast contract* bullet at body line 6 —
  `- [ ] \`User::$casts['role'] = UserRole::class\`` — was replaced in place with sheet §2 [B]'s
  **first** bullet (the cast-contract bullet), verbatim. This corrects §8's scoping error: [B] held
  two bullets, only the second was phantom.
  - Write: `gh issue edit 205 --body-file …`; server `updatedAt` **`2026-10-29T09:01:36Z`** (read
    back twice, body size stable).
  - Byte proof: 4468 → 5106 bytes; replaced span 47 → 684 bytes (delta +637); every byte outside the
    span identical. Single-line replacement, not a body rewrite.
  - Read-back: equal except a **trailing newline** — candidate 5105, live 5106, first 5105 identical,
    live carries one extra trailing `\n` (GitHub write path). Content-identical; **no byte-exact
    trailing-newline claim** made (same posture as the #173 fence line-ending item).
  - Transport-damage patterns (`theresult`, `raisesValueError`, `UserRoleenum`, `negation:return`,
    `docs 01-issues`, `$user.role` unhyphenated): **zero hits** on the written body. Old false line:
    0 occurrences.
- **Item 2 — HELD, investigated, nothing deleted.** The three cited tests are phantom by **name**
  only; the behaviour is covered. `tests/Feature/Architecture/BadgeScopeTest.php` is real (17 tests,
  no out-of-domain case); `tests/Unit/Models/UserModelTest.php` does not exist; the real tests live in
  `tests/Unit/Models/UserTest.php:519/:544/:552/:581` and
  `tests/Unit/Listeners/Filament/Support/Concerns/ScopesNavigationBadgesTest.php:233/:289` (all six
  verified by direct grep, exact `it('…')` strings). Owner's rule resolves this to path **(a)**:
  replace the AC bullets with the real names. **Not applied** — the replacement wording is new public
  text not sourced from the sheet, so it is recorded as a §10 draft and awaits the owner's call.
- **Sheet updated:** `docs/01-issues/205-issue-text-amendment.md` — Status block corrected (§8's "body
  not written by this session" is no longer true), new **§9** execution record, new **§10** held item
  with the draft replacement.
- **G1 remains OPEN.** The live body no longer contradicts the implementation on the cast contract, but
  the *Test coverage* block still names three non-existent tests. Closes only when that is resolved.
- **Commit:** additional docs-only commit made on `fix/173-205-207-g1-evidence-reconciliation`. No
  push, no merge.

### 2026-10-29 13:12 — paste provenance closed; #205 was already pasted; 7-path commit made

- **⚠ Problem 1 (owner) — #173 unrecorded write: ACTOR AND TIME IDENTIFIED.** The actor is the
  account **`allanpusta13`** — which is both the repository owner and the account this session's
  `gh` token authenticates as, so "owner hand-paste" and "a previous agent session on the same
  token" are **not distinguishable from the API**; the attribution stops at the account.
  - Source: the REST issue **timeline does not expose body edits at all** (all 18 events for #173
    predate 2026-10-26; no `edited` event exists in this API). The edit history came from
    GraphQL `Issue.userContentEdits`.
  - `editedAt` **`2026-10-28T09:42:19Z`** (= `updatedAt`, single edit node), editor `allanpusta13`.
- **#173 byte-level diff — byte-identical, correction NOT required.** Compared live body against the
  sheet's §1 and §2 rendered text, after stripping the AC `- [ ]` marker (which the sheet's body-only
  text does not carry) and flattening soft-wrap newlines:
  | Target | Live bytes | Sheet bytes | Verdict |
  |---|---|---|---|
  | §1 Scope bullet (marker stripped, flat) | 787 | 787 | **equal** |
  | §2 fenced bash interior (flat) | 680 | 680 | **equal** |
  Old text is gone: `847 orphan keys` / `Enumerate all 847` → **0** hits. Transport-damage pattern
  sweep (`theresult`, `raisesValueError`, `UserRoleenum`, `negation:return`, `docs 01-issues`,
  unhyphenated `$user.role`) → **0 hits** in both the sheet and the live body.
  **No corrective re-paste made** — a rewrite from a capture would risk re-encoding damage for no
  gain, since the live text already equals the source of record.
- **⚠ #173 — fence line endings cannot be asserted byte-exact; two reads disagree.** The fenced
  `bash` block's interior is **mixed**, and the two read paths give different distributions:
  `gh issue view` body → **1 CRLF / 8 LF**; GraphQL `userContentEdits.diff` → **4 CRLF / 5 LF**.
  The sheet stores the block with LF. The *content* is identical (680 = 680 flat) and every line of
  the block is a self-contained command, so nothing is affected behaviourally or at render time.
  Recorded as a cosmetic transport artifact rather than resolved in favour of either reading.
- **Problem 2 (owner) — #207: YES, the body paste landed and passes read-back.** Body edit
  `editedAt` **`2026-10-28T12:27:56Z`**, editor `allanpusta13`. Live body **lines 20–21** carry
  `` `tests/Feature/Architecture/ProductExporterDeletionTest.php::product_exporter_does_not_exist` ``
  and `::no_reference_to_product_exporter`; `RepositoryMapTest` / `RuntimeCompletenessTest` →
  **0 hits**. **Closure stands** (comment `issuecomment-6481423734`, `CLOSED | COMPLETED`). The issue
  is **not** closed carrying a stale body.
- **⚠ #205 was ALREADY PASTED — the sheet is no longer the blocker.** Edit `editedAt`
  **`2026-10-29T02:13:10Z`** (= `updatedAt`), editor `allanpusta13`. All four authorised edits are in
  the live body and each is **byte-equal** to its sheet counterpart:
  | Authorised edit | Live bytes | Sheet bytes | Verdict |
  |---|---|---|---|
  | Context paragraph (sheet §6) | 605 | 605 | **equal** |
  | Resolver bullet 1 (sheet §7) | 278 | 278 | **equal** |
  | §1 AC replacement (sheet §1) | 294 | 294 | **equal** |
  | Scoping line C (sheet §2) | present | — | present |
  The superseded wording is gone (``reads `$user->role` inside its``, `causes the resolver to throw`,
  `fails closed by throwing` → **0 hits**). Sheet §6/§7/§8 are **already appended**; the owner's
  supplied §3/§4 texts were therefore recorded and pasted before this pass. Bullet count in
  *Resolver behavior* is still three, so nothing was dropped or narrowed.
- **⚠ STILL FALSE IN #205 AFTER THE PASTE — outside the authorised set, deliberately untouched.**
  1. *Cast contract* bullet 1 still reads `User::$casts['role'] = UserRole::class`. The project binds
     `App\Casts\UserRoleCast` (`app/Models/User.php:61`). Sheet §2(i) supplied the correction;
     §8 records it as **not applied** under owner condition 1. Left as-is — not a silent narrowing.
  2. *Test coverage* still names three tests that do not exist on disk (§5): `BadgeScopeTest::…`,
     `UserModelTest::…`, `ScopesNavigationBadgesTest::…`. The authorised set does not cover that
     section. Left as-is.
  Both remain live contradictions inside a closed-out issue; the owner's "no phantom test names"
  condition constrains what gets **pasted**, and it was honoured.
- **Unattributed-write disclosure, consolidated (four surfaces).** #173 `2026-10-28T09:42:19Z`,
  #207 `2026-10-28T12:27:56Z`, #240 `2026-10-28T21:44:15Z`, #205 `2026-10-29T02:13:10Z` — every one
  by `allanpusta13`, and **none had a contemporaneous `UPDATES.md` entry**. Retroactive entries are
  now in `UPDATES.md`. This is a disclosure, not a defect finding: the content of all four writes
  matches the authorised sheets.
- **Log-clock discrepancy (observed).** `UPDATES.md` local times run **~3 h behind** the server's
  UTC+8 reading: the `02:45` entries describe writes stamped `21:44Z` = **`05:44 +0800`**; this pass
  is `05:12Z` = `13:12 +0800`. Event ordering must be taken from **server timestamps**, not log times.
- **Commit made — 7 paths, docs only.** Observed state before the commit: `HEAD`
  `9ac18567bb22ff293eeece503129fc1d0deefbed`, branch `fix/173-205-207-g1-evidence-reconciliation`,
  6 modified + 1 untracked path (the G1 report), `UPDATES.md` and `STATUS.md` edited in place.
  Local scratch (`.issue-tmp/g1r/`) is **gitignored** (`.gitignore:47`), so it cannot enter the set.
  **No push, no merge.** The commit message makes no closeout claim.

### 2026-10-28 13:05 — retry pass 2 after context break; block persists, nothing partial

- **Owner tightened the #205 Context edit (Approved, 2026-10-28).** The Context replacement is a
  **minimal diff**, not a whole-paragraph rewrite: keep the citation sentence
  `` `Blueprint §1B.1a, §27 v13.9 row 11. … (the cast throws).` `` and replace **only** the final
  false sentence with the tightened form — *"The fail-closed guarantee lives at the model boundary,
  where the cast's throw is caught and denied."* Owner's warning on write: byte-check
  `$user.role` → `$user->role` (transport-loss pattern) and the concatenation-damage patterns.
- **Retry pass this session (Observed).** Retried the remaining three live-body fetches to files
  (`207`, `205`, `240`) — **all refused**, same classifier-outage message, same as the 12 refused
  attempts last session. `/tmp/g1/m173.md` (3544 bytes, from the one fetch that got through) is
  still the only body on disk.
- **Nothing partial.** `git status --short` re-checked read-only at the end of the pass: **7 paths,
  unchanged** (5 modified + 2 doc paths + the untracked G1 report); `HEAD` still
  `9ac18567bb22ff293eeece503129fc1d0deefbed`. Zero issues edited, no comment posted, no issue
  closed, nothing committed, nothing pushed. No workaround attempted. Per the owner's stand-down
  rule, this is reported, not routed around.

### 2026-10-28 12:20 — owner signed the execution set; write class still refused

- **Signed set (owner, 2026-10-28).** #173 paste; #207 body paste + close comment + closure
  (`state_reason: completed`); #205 **§1 only** as the AC replacement with **§2(ii) discharged**
  (no removal record for a line that does not exist), **plus** a Context-paragraph replacement,
  **plus** a resolver-bullet-1 replacement, **plus** scoping line C; #240 one-line `Related` path
  fix preserving the `§3.4` anchor; `UPDATES.md:31` deliberately untouched. Commit withheld; the
  approved message drops the "closeout" claim. Push and merge not authorized.
- **Pre-write verification done read-only (Observed).**
  1. **#205 bullet 1 and §1's target are distinct live lines** — bullet 1 is
     `` - [ ] `ScopesNavigationBadges::badgeScopedWarehouseIds()` reads `$user->role` inside its `match (true)` chain ``,
     §1's target is the *following* bullet. Per the owner's condition 3, **both apply**; no
     double-edit risk.
  2. **#207 delivered test names confirmed on disk** —
     `tests/Feature/Architecture/ProductExporterDeletionTest.php:10` `it('product_exporter_does_not_exist')`
     and `:18` `it('no_reference_to_product_exporter')`. Both names the owner gave are real.
  3. **#240's live `Related` line** reads
     `` - Analysis: `docs/01-issues/2026-10-27-g1-evidence-ac-reconciliation.md` §3.4 (and §9 for this draft). ``
     — one-line fix target confirmed.
- **BLOCKED — write class still refused by the classifier.** Reads pass (plain `gh issue view`,
  `git status`, `Read`/`Grep`/`Glob`). Every write is refused: shell redirects, heredocs, the
  `Write` tool, and `gh` with output redirection. **Nothing partial applied** — per the owner's
  stand-down rule: **zero issues edited, no comment posted, no issue closed, nothing committed,
  nothing pushed.** No workaround attempted (no `gh api` alternate path, no state change outside
  the authorized order).
- **BLOCKED — every `gh` command is refused by the classifier.** `echo`, `git`, `php`, `date`, and
  `Read`/`Grep`/`Glob`/`Edit` execute normally; any command containing `gh` — including a plain
  read — returns the outage message. Consequence: **no issue body written, no close comment posted,
  no issue state changed.** This is the same intermittent outage recorded at `UPDATES.md:53`, now
  reproducible against `gh` specifically. Reported, not worked around.
- **⚠ STALE PATH IN A LIVE ISSUE — #240 cites the pre-rename report.** #240's *Related* section ends
  `Analysis: docs/01-issues/2026-10-27-g1-evidence-ac-reconciliation.md §3.4`. That file no longer
  exists: the report was renamed to `2026-10-28-…`, and a repo-wide search now finds the old string
  **only** at the deliberately-untouched `UPDATES.md:31`. #240 was filed before the rename.
  **Editing #240 needs separate authorization.** It is also the only issue body written this
  session, so it is the one place a stale self-reference persists.
- **Scope note — the #207 close comment was NOT posted, and #207 was NOT closed.** The sheet's §4 is
  a *comment*, and closure is an issue-field change. The authorization covered the three amendment
  **bodies**; a comment and a state change would each be a new public-surface write, so neither was
  attempted. The close comment ships once the paste is authorized and unblocked.
- **Commit withheld.** The 7-path docs change set is unchanged on
  `fix/173-205-207-g1-evidence-ac-reconciliation` at `9ac1856`. The owner's sequence gates the commit
  on the three pastes landing; since none did, and since the specified commit message would describe a
  G1 "closeout" that has not happened, **no commit was made**. This supersedes the earlier plan line
  "all three land together after #205 sign-off, followed by one 7-path docs commit" — the gates are
  now (i) the classifier and (ii) the #205 target conflict.
- **Errata recorded, not edited:** `docs/02-research/2026-10-23-i18n-count-verification.md`
  §2/§3 (995, and `es: total=865 placeholders` — self-contradicted by its own §6 which reports
  0 markers) and its citations of two scripts plus two reference docs that do **not** exist;
  `FINAL_EXECUTION_REPORT.md:67`. Dated artifacts stay historical, per the R-6/R-7/R-8 precedent.
- **Unresolved sandbox blocker:** the safety classifier intermittently rejected tool calls,
  which blocked pinpointing the per-namespace origin of the `+1` key. Attributed at *file*
  granularity (`lang/en/validation.php`, the only `lang/en` file `aa54af6` touched) and labelled
  **inferred**, not observed.
- **MCP:** `laravel-boost` and `ide` failed to connect this session; Laravel work used the CLI
  (`php artisan`) instead.

## Current workstream — issue audit CLOSED (2026-10-27)

Closure report: [docs/01-issues/2026-10-27-issue-audit-closure.md](docs/01-issues/2026-10-27-issue-audit-closure.md)
Audit report (read-only, unmodified): [docs/01-issues/2026-10-26-issue-audit-173-218.md](docs/01-issues/2026-10-26-issue-audit-173-218.md)

- **§Prerequisite discharged.** The classifier that blocked the 2026-10-26 run recovered. `git`,
  `php artisan test`, `vendor/bin/pest`, `vendor/bin/phpstan analyse` and `composer show` all
  executed. No HALT required; no verdict rests on inference.
- **Green tails (D-A satisfied):**
  - Pest full suite `passed` — baseline **2107 / 2103 passed / 4 skipped / 4813 assertions** →
    final **2108 / 2104 / 4 skipped / 4817 assertions**. +1 test, +1 passed, +4 assertions,
    **0 new failures**.
  - PHPStan level 7 — **365 → 365**, zero drift. Note: not zero in absolute terms; 365
    pre-existing errors are unbaselined project debt. "Green vs baseline" = non-regression.
- **Three authorised edits applied, each independently verified:**
  - **D-C** — `docs/01-issues/210-supplier-shipment-tracking.md:22` no longer recommends
    auto-backorder tracking; now defers to #214 (DO-NOT-IMPLEMENT). Prose only, 1 line.
  - **D-D** — `TranslationKeyParityTest` gained an additive zero-catalogue-orphans assertion.
    **Mutation-proven red→green**: injected a probe orphan into `lang/es/common.php` → RED naming
    `common.__dd_orphan_probe` (996 canonical / 0 missing / 1 orphan); reverted → GREEN. Probe
    never committed.
  - **D-G** — `UserTest.php` gained `it('rejects invalid role assignment through the model set
    path')`, guarding the model-level assign+save contract that was previously unguarded
    (only the cast-level `set()` was covered). Owner-authorised explicitly; the sole test edit
    besides D-D's assertion.
- **D-G's "resolver throw" target was NOT implemented — resolved in favour of D1.** D1
  (owner-approved) requires the resolver to fail closed with `[]`; the AC demanded a throw. D1
  wins under the authority order. The `[]` contract is already guarded by
  `ScopesNavigationBadgesTest.php:233`.
- **D-H — no fail-open regression.** `app/Models/User.php:81-85` catches `ValueError` and returns
  `false`, i.e. **denies**. The catch does not fall through to a default or widened role.
- **Verdicts:** #173 **`CLOSED` (owner-signed 2026-10-26)**; #205 `SATISFIED` — evidence gap
  closed; #207 `SATISFIED` — re-proved (sweep of `app`/`config`/`database`/`resources`/`routes`:
  zero refs; glob finds only the guard test); #214 `EXCLUDED`; #218 unchanged
  (`TRIGGER-NOT-MET` local / `UNABLE-TO-MEASURE` prod); #210–#213, #215–#217 **`BLOCKED-gap`** —
  no §0.3 owner decisions supplied, so the blocks persist.
- **Recorded, not changed:** D-E test-name mapping table (the three #205 cited test names do not
  exist on disk; mapped to the tests that do deliver each contract); D-F CD-5 amended to accept
  `UserRoleCast` as compliant (matches `User.php:61`).
- **R-1 RESOLVED (option A).** The 13 hand-maintained CI-provenance lines (Head SHA, CI Run URL,
  runner summary, fixed verification clause) were moved byte-for-byte out of the test-generated
  `storage/logs/i18n-parity.md` into `storage/logs/i18n-parity.header.md`, and
  `storage/logs/.gitignore` gained a negation so the header is tracked. The log is now purely a
  test artifact: `TranslationKeyParityTest` is untouched, a full-suite run no longer alters it,
  and rerunning leaves it byte-identical (verified by hash). The header lines were **not**
  contiguous in the original (2 after the generated title, 11 after the canonical-key block);
  extraction adapted accordingly.
- **R-7 RESOLVED (option A, default) — renumbering citation corrected; errata recorded for the
  read-only sites.** Removing the R-1 header renumbered the parity log: `Canonical keys` moved
  line 7 → 5, and Missing/Orphan/Identity moved 20–22 → 7–9. Four citation sites referenced the old
  numbers. The one editable site was fixed; the three inside read-only reports were **not** edited
  and are recorded here as errata instead (cited *content* unchanged and still correct — only the
  line numbers moved):
  | Site | Status | Cited | Correct now | Content |
  |---|---|---|---|---|
  | `docs/01-issues/173-847-provenance.md:86` | **fixed** | `i18n-parity.md:7` | `:5` | `Canonical keys: 996` |
  | `docs/01-issues/2026-10-26-issue-audit-173-218.md:52` | errata (READ-ONLY) | `i18n-parity.md:20-22` | `:7-9` | Missing / Orphan / Identity |
  | `docs/01-issues/2026-10-26-issue-audit-173-218.md:64` | errata (READ-ONLY) | `i18n-parity.md:20-22` | `:7-9` | Missing / Orphan / Identity |
  | `docs/01-issues/2026-10-27-issue-audit-closure.md:298` | errata (READ-ONLY) | `i18n-parity.md:7` | `:5` | `Canonical keys: 996` |
- **R-6 RESOLVED (option C, default) — header kept as historical; drift recorded here.**
  `storage/logs/i18n-parity.header.md` was **not** edited and still attests `6c736538`. It is
  historical, not current. Its fixed verification clause asserts `6c736538` "is the most recent
  commit that modified application code or tests" and that later commits are "documentation-only";
  D-D (`225a8df`) and D-G (`3b405b8`) both modified test files after it, so the clause is false for
  the current tree, and its cited runner counts (2,103 passed / 119.76s / 130.04s) no longer match
  the current 2,104 / 4 skipped / 4,817. Re-attestation needs a real CI run (head SHA, run URL,
  per-version timings) which this checkout cannot produce; the values were **not** invented.
  Verbatim drift note, as directed:
  > R-6: storage/logs/i18n-parity.header.md reflects CI generation at 6c736538. Drift since: D-D + D-G modified test files. Re-attestation pending next CI run. Values not invented locally.
- **R-8 DEFERRED (LOW, owner-directed deferral) — the errata table is a snapshot of a regenerated
  file.** Both the errata table above and every citation it corrects point into
  `storage/logs/i18n-parity.md`, which the parity test rewrites on every run. R-1 proved the file is
  byte-stable across runs today, but the citations are still **line numbers**: if the report format
  ever changes, they break again — which is exactly how R-7 arose. The durable fix is option D
  (cite stable anchors instead of line numbers, in the citing documents plus the test's report
  header). That is a **convention change**, so it is deferred, not actioned. No file changed for R-8.
- Lower-severity: R-2 `210-...md:25` still says received costs update a moving average, against
  the implemented snapshot/current-cost method (#212 territory, blocked); R-3 PHPStan 365
  pre-existing errors at level 7; R-4 **RESOLVED 2026-10-27** — #173 canonical count is 996,
  loader-verified at `9ac1856` by two independent paths; D3 discharged (see the G1 section at the
  top of this file);
  R-5 pre-existing uncommitted changes in the tree are not part of this change set.
- **Committed, not pushed.** Branch `issue-audit-closure/2026-10-27` (off the audit branch at
  `39f9a79`), six atomic commits: `9ca4535` D-C, `225a8df` D-D, `3b405b8` D-G, `7593140` R-1, the
  R-6/R-7 status records ("chore(R-6,R-7): record header staleness and parity-log citation errata"),
  then owner-authorized `docs(173): track corrected provenance citation (R-7)` — which newly tracks
  `docs/01-issues/173-847-provenance.md` and carries the R-8 deferral note (the branch tip).
  Non-regression re-proven on the committed tree: suite `passed`
  2108 / 2104 / 4 skipped / 4817 assertions (closure baseline unchanged), targeted 86 / 86 / 160, and
  PHPStan 365 → 365 — zero drift on all three. **No push, no PR** (CD-18); proved via `.git` refs —
  no remote ref for this branch, parent remote still `39f9a798`.
  Close-out report: [docs/01-issues/2026-10-27-issue-audit-closeout.md](docs/01-issues/2026-10-27-issue-audit-closeout.md)
  Final report: [docs/01-issues/2026-10-27-issue-audit-final.md](docs/01-issues/2026-10-27-issue-audit-final.md)

### Prior — audit run (2026-10-26)

- Blocked at the §Prerequisite: the permission classifier refused every new command all session,
  so the suite never ran and **no verdict was closed** in that run. Recorded in the audit's §5/§8.
- **Headline of that run:** the supplied acceptance criteria **contradicted four recorded owner
  decisions (D1–D4)** already applied on this branch. The recorded decision was applied in each
  case and the conflict flagged, not overwritten:
  - **D1** — owner rewrote #205 to require the resolver to fail closed with `[]`; the AC demanded
    it *throw*. Code NOT changed.
  - **D2/D3** — owner retired the 847 criterion; orphan sidecars are a *disposition record*, not
    to be emptied. Catalogue orphans 0; sidecar line count 272 by design. **D3 is now discharged:
    the canonical count is 996, loader-verified at `9ac1856` (G1, 2026-10-27).**
  - **D4** — owner directed naming the delivered test; the AC's `RepositoryMapTest` /
    `RuntimeCompletenessTest` are phantom citations.
- No regression found then either: #212's implemented methodology is snapshot/current-cost
  replacement (`PurchaseService::updateCurrentCostPrice()`), not an unapproved weighted average.
  #213 has no half-built return workflow. #217's presets match the blueprint verbatim.
- Open: #167 has no artifact on disk (parity inferred from the green §0A.15 test).

## Done
- Aligned `tests/Unit/Policies/*` with the updated (v13.10 role model) policies.
  - PurchaseOrderPolicyTest, SalesOrderPolicyTest, TransferRequisitionPolicyTest, DirectTransferPolicyTest, ProductVariantPolicyTest.
  - Operational lifecycle abilities now asserted for BranchManager; WarehouseStaff asserted receive-only / read-only; DirectTransfer viewAny asserted false for WarehouseStaff.
- Restored the standalone Playwright E2E suite to green: **24 passed / 24**.
  - Locator drift fixed against Filament v5.9's real a11y tree: enhanced (searchable) Select trigger is a `<button>`, not `combobox`; row actions in an ActionGroup dropdown are `menuitem` (including `->url()` link actions), not `button`/`link`.
  - Root-cause fixes for the suite not running at all: missing `playwright.config.ts` restored; `.env.testing` repointed off the dev SQLite file to the MySQL test DB; admin theme added to `vite.config.js` inputs (panel 500'd with "Unable to locate file in Vite manifest").
- Made the suite runnable by the owner with no env vars: `npx playwright test`.
  - Bundled Chromium revision 1248 was missing; installed headless shell first (`npx playwright install chromium --only-shell`).
  - `playwright.config.ts` `baseURL` had drifted to the dev vhost `http://sinop.test` (dev DB) — restored to `http://127.0.0.1:8123` (managed test server).
  - `SERVER_PORT` now derives from `BASE_URL`, so the managed server's port and the client URL can never disagree.
  - `reuseExistingServer` is now opt-in (`PLAYWRIGHT_REUSE_SERVER=1`); two stale servers were being silently reused, causing a split-brain (browser served dev DB while the specs' `tinker()` read the test DB).
- Rewrote `tests/Browser/purchase-and-sales.spec.ts` (9, 10, 19) to drive the real Filament UI instead of calling services via `tinker()`.
  - Each now opens the row ActionGroup, fills the action modal, clicks Confirm, and asserts the success toast + DB state. Zero service calls from the specs.
  - Corrected a wrong premise in the old file: `TextInput::maxValue()` registers BOTH the HTML5 `max` attribute and a server-side `max:` rule ([TextInput.php:111](vendor/filament/forms/src/Components/TextInput.php:111)), so over-quantity never reaches the service guard — the browser refuses to submit (verified: `validity.rangeOverflow === true`, no POST issued, DB unchanged). The UI *is* a valid oracle.
  - Service-level guard contract stays covered by Pest (`GuardsOutstandingQuantityTest`, `ConcurrencyTest`, `PurchaseServiceTest`, `SalesServiceTest`) — no coverage lost.
  - Added an optional `stockQty` param to `makeConfirmedSalesOrder` so test 19 can seed on-hand == reserved (5/5) without bypassing the UI.
- Fixed `npx playwright test --ui` showing no tests: UI mode launches a **headed** browser, and only the headless shell (build 1248) was installed, so no full `chromium-1248` existed.
  - Installed the full build: `npx playwright install chromium` → `chromium-1248/chrome-win64/chrome.exe` present.
  - Verified: headed run passes; `--ui --ui-port=8899` boots clean ("Listening on http://localhost:8899", no error). UI mode still needs port 8123 free (a leftover `php artisan serve` blocks it — the config's intentional loud failure, not a silent wrong-DB reuse).

- Ran a read-only Playwright layout audit of every admin surface (41 routes × 1440/1280/768/375; index tables, card-grid lists, 6 create forms, 2 edit forms, infolist view pages, action modals). **No code changed — audit and report only.**
  - Full report: [docs/01-issues/2026-10-16-layout-audit.md](docs/01-issues/2026-10-16-layout-audit.md)
  - **Defect 1 (MEDIUM):** 9 of 10 infolist view pages render cards at 349/163/536 px inside a 1096 px content area and waste ~248 px on the right. Root cause proven by measured ancestor chains: the infolist root grid sits in a 536 px `.fi-grid-col`, not the full width. `UserInfolist.php:22` has `->columnSpanFull()` on the root `Grid`; the other 9 do not. Fix = one line each.
  - **Defect 2 (LOW):** `/admin/users` index needs 159 px of horizontal scroll (table 1255 in a 1096 wrapper) because three labelled row actions widen the actions cell to 334 px. Every other index list fits.
  - **Defect 3 (MEDIUM):** dashboard quick-action cards are 125×276 px at 1440 and 78×276 px at 768 — taller than wide, labels wrapping vertical. `QuickActionsWidget` declares `xl => 4` against a dashboard that only has 2 columns.
  - **Defect 4 (LOW):** two dashboard charts render at 1048×699 px, producing a ~3300 px page.
  - **Verified clean:** zero document-level horizontal overflow and zero console errors on all audited surfaces; forms lay out in even columns at 1440 and collapse correctly at 768; all 6 card-grid lists use 3/2/1 tracks correctly; the action modal is centred and correct; dashboard rows are even once widgets finish loading.
  - **Retracted after re-measurement** (were mid-load or mis-read artifacts, not bugs): "uneven infolist/dashboard rows"; "dashboard widgets hang at 768/375" (they lazy-load on scroll — skeletons 3–4 go to 0 once scrolled); "users table clipped" (it scrolls, `overflow-x: auto`).
  - **Not the cause of Defect 1:** the array-vs-integer `Grid::make()` form. The nested grids do get the correct track count; only the parent width is wrong.
- Ran the 5-seat council on all 4 audit findings. Strict recommendations only; **no fix applied, no file modified**.
  - Governing authority the audit never consulted: `docs/00-project/blueprint.md` — §7M.7 rule 2 (`:12823`), §7O.5 (`:12944`), §10 (`:14071`, `:14085`), §7N, F31 (`:1292`).
  - **Finding 1 (infolist width):** unanimous FIX WITH CHANGES. Framing inverted — the 9 files conform to §7M.7 rule 2; the blueprint's own canonical sample (`:8107`) omits the root span. `UserInfolist` is the outlier (violates the rule, only infolist with raw untranslated strings). Fix = add `->columnSpanFull()` **keeping** the array form, amend §7M.7 rule 2 + §7O.4 + §7O.8 #5, normalise `UserInfolist`. **Owner approval — convention change.**
  - **Finding 2 (users scroll):** majority REJECT as defect. Blueprint F31 forbids collapsing `EditAction` into an `ActionGroup`; `§7L.2` renders Users flat by design; auto-scroll is §7N behaviour. Real buried finding: `->modalWidth(Width::Large)` is dead config in **8 tables** (13 sites), not one. Separate cleanup task.
  - **Finding 3 (quick-action cards):** real defect, wrong direction. Root cause is `Dashboard::getColumns()` returning `xl => 2` against §7O.5's mandated `xl => 4` — **not** the widget span, which matches §10 and is pinned by `DashboardWidgetsTest.php:93`. Fix the Dashboard, leave widget + test. Add the blueprint-cited `ResponsiveSpanTest::dashboard_get_columns_returns_breakpoint_array()` — cited at blueprint `:15263`, **does not exist on disk**. **Owner approval — reflows all 9 widgets.**
  - **Finding 4 (charts):** DROP. Height is aspect-derived (`1096/699 ≈ 1.57`); likely a symptom of Finding 3. No objective acceptance threshold. `ChartWidget::$maxHeight` **does** exist (`ChartWidget.php:36,101`) — blueprint changelog `:20276` is wrong.
  - **Cross-cutting:** audit never read the blueprint; both MEDIUMs are really LOW; harness trust low (3 retractions, `report.json` missing, no 1280px data, "~248px" doesn't derive); no regression guard exists for any fix.
  - **Security seat:** `QuickActionsWidget::actions()` emits all four `Resource::getUrl('create')` unconditionally (`canView()` = `user !== null`). No authz bypass (server policy 403 on the page) but advertises create routes to roles that cannot use them. Optional hardening, separate.
  - **PII:** `.issue-tmp/layout-audit/` PNGs contain employee names/emails + financial figures (RA 10173). Must stay untracked; verify seed provenance before any sharing.
  - Recommended order if approved: **3 → re-measure 4 → 1 → 2-cleanup.**
- Council #2 on the owner's 12-column proposal ("set the columns to 12, resize each accordingly on all screen sizes"): **REJECT** — Product REJECT, Security MODIFY, Architecture REJECT, QA MODIFY (hard stop on "all screen sizes"), Skeptic REJECT. **No seat endorsed it. No code changed.**
  - **Verified vendor mechanism (decisive):** `ComponentAttributeBag.php:158-176` emits spans as CSS custom properties (`--col-span-{bp}: span N / span N`), not compiled classes; `CanSpanColumns.php:26-40` accepts any integer, no whitelist. So 12 buys **zero CSS/cascade/browser benefit** — PHP churn only. And 3/2/4/1 all divide 12, so every existing layout already maps exactly: **no new layout is producible.**
  - **Skeptic's framing correction:** the defects are **parent/child mismatches**, not resolution limits. Rescaling children while parent counts stay put reproduces the defect at larger spread. Right question = "does every span match its container's column count, and does every root grid own full width?"
  - **Root cause identified:** `QuickActionsWidget` `xl=>4` matches §10; it is `Dashboard::getColumns()` (`Dashboard.php:29-38`, `xl=>2`) that deviates from §7O.5 (`:12944`, `xl=>4`). Widening the Dashboard is one line and already a recorded deviation.
  - **Regression net does not exist.** `DashboardWidgetsTest.php:78-94` is the repo's only executable layout spec; it does **not** pin `Dashboard::getColumns()`. `blueprint.md:15263` cites `ResponsiveSpanTest::dashboard_get_columns_returns_breakpoint_array()` — **that test does not exist on disk**; the blueprint believed it was guarded, and was not. Zero tests reference infolist grids.
  - **QA blocker:** correctness unverifiable at *either* end. Before: audit retracted 3×, `report.json` missing, 1024/1280 never measured, all modal probes errored. After: class-level only (Pest has no layout engine). Both ends unverified.
  - **Blueprint errors (verified):** `:20276` falsely claims `->maxHeight()` was removed as non-existent (`ChartWidget.php:36,101` declares it); `:15263` phantom test; `:15510` contradicts §7M.7 r2.
  - **Step-count correction (observed, supersedes the earlier "9"):** infolist root grids are **DirectTransfers:21, InTransits:22, LossLedgers:18, Products:22, PurchaseOrders:21, SalesOrders:21, StockMovements:21, TransferRequisitions:21, Warehouses:24**; `UserInfolist.php:21` is the sole holder of root `columnSpanFull()`. LossLedgers (5 blocks) and StockMovements (2) would take more than one call; scope is **9 files**, not "9 lines".
  - **Plan written to `docs/00-project/ai/plans/pending/2026-10-16-layout-invariant-remediation.md`** (PENDING — owner approval required). Priority order: **P0** layout regression net (additive, no gate) → **P1** Dashboard cols per §7O.5 (owner; reflows all 9 widgets) → **P2** infolist root span + blueprint §7M.7 r2 amendment (owner) → **P3** `UserInfolist` normalisation → **P4** blueprint corrections → **P5** dead `modalWidth` → **P6** re-measure charts → **P7** PII cleanup → **P8** artifact deletion (destructive gate).
  - **QA's key point:** P0's new assertions must **fail** against current code before P1/P2 — that failure is the baseline proof the audit could not produce.
  - **Unresolved (Inferred, not Observed):** whether an over-count span clamps to full width (Product) or to one track (Architecture). Audit measurement of 536 px = one track backs Architecture. Does not change any verdict.
- Council #3 on the *plan itself* (revision 1 critiqued by all 5 seats as 5 separate agents): **Product MODIFY, Security MODIFY, Architecture MODIFY, QA MODIFY, Skeptic MODIFY. No seat accepted it.** Plan rewritten to revision 2. **No production file touched.**
  - **Product:** value order inverted — owner-visible fixes sat at P3/P6 behind plumbing; "regression net" oversells a declaration-checker. Fixes moved first; net renamed.
  - **Security:** PII capture was sequenced *before* the gate constraining it; also 2C bundled layout with i18n, violating the "keep layout diffs separable" control. Phase 0 PII precondition added; commits split A (layout) / B (i18n) with a diff-grep assertion (`visible(`, `hidden(`, `canView`, `can(`, `authorize`, `->query(`) as mechanism, not prose.
  - **Architecture:** the "child span ≤ parent track count" invariant is **unimplementable by the cited precedent** — `FormFieldSpanTest` inspects self-contained chains on one node via regex and does no nesting/scope resolution. Removed from the plan; a mismatch guard would need runtime or a hand-maintained `(surface, breakpoint) → parent tracks` table, as a separate proposal.
  - **QA:** no acceptance criteria (the exact thing QA criticised the audit for). Criteria block added per phase; the declaration-only limit is now stated in-body and in the test docblock; 1024 coverage cut as gold-plating (`lg` inherits `md=2` identically before/after); pixel baseline demoted from Phase 0 blocker to a one-time evidence artifact at 1280 + 1440, requiring screenshot+JSON pair, `deviceScaleFactor: 1`, animations off, seeded data, wait on `networkidle` AND `.fi-loading-section` count 0.
  - **Skeptic:** plan was downstream of an artifact whose evidence is absent. Gate 0.5 added — reconfirm each finding by direct source read, **retract rather than remediate** if it does not reproduce.
  - **Phantom-test finding corrected upward (verified this session):** blueprint `:15258-15271` cites **14** `ResponsiveSpanTest::*` methods; **zero exist**. Adjacent catalogue blocks are fiction too — `TableArchitectureTest` `:15273-15279`, `AllResourcesTest`/`AllActionsTest`/`AllFormFieldsTest` `:15249-15252`, `NavigationBadgeTest` `:15243-15248`, `RuntimeCompletenessTest` `:15234-15237`. Revision 1 had recorded **1** phantom method. (`WarehouseScopeTest`, `NavigationBadgeRoleScopeTest` do exist; cited correctly.) All 5 seats rejected revision 1's proposal to *create* `ResponsiveSpanTest` to make `:15263` true — backwards. Mark the blocks unbuilt instead.
  - **Reflow count corrected (verified this session): 8 of 9 widgets, not 9.** Widget xl spans vs parent xl: `4`×1, `2`×4, `1`×4. At parent 2 all 9 are full-width; at parent 4, `xl=4` (QuickActions) stays full (**unchanged**), the four `xl=2` shrink to half, the four `xl=1` shrink to quarter. QuickActions is the one widget the fix does not visibly move.
  - Additions: audit-doc correction banner (it recommends the rejected fix at `:147`), gate for the optional shared infolist root-grid helper (10 callsites = justified DRY, but a new convention needing approval), F31/UsersTable deferral now explicit rather than silent.
- Council #4 (binary verdict on plan revision 3): **ACCEPT ×4, REJECT ×1.** Every seat found a real defect; all applied. Plan is now revision 3-final. **No production file touched.**
  - **Product ACCEPT** — internal count error: scope said "**two**" doc corrections, task listed **four**. Cut `:20276` (`maxHeight`) and `:15510` (`Grid::make(3)`) as unrelated hygiene belonging to the dropped chart work. Confirmed payload: **10 lines** touching the defect (9 × `->columnSpanFull()` + 1 `getColumns()` array).
  - **Security ACCEPT** — diff-grep was declared a control but had no owner and no implementation. Now: named command, owned by the releasing developer, run pre-commit, not wired to CI; limits stated (a literal grep misses `Model::where(`, `DB::raw`, `Gate::` authz — it holds because A1/A2's scope cannot physically express authz). Added check: commit A2 must not alter `getWidgets()` membership (**verified this session** — `Dashboard.php:47-60`, a separate method from `getColumns()` at `:29-38`).
  - **Architecture REJECT** — cleared by one edit. Steps 2 and 3 shared commit A, defeating the revert isolation that was the point of splitting commits. Now **A1** (9 infolist roots; additive, view-only, lands first) and **A2** (dashboard reflow; owner-visible, sign-off gates it).
  - **QA ACCEPT** — sign-off must be a **dated written record** naming viewports and what resized, not a verbal pass. The pixel reflow is stated in AC#3 as an **inferred consequence** of the vendor span mechanism, **not a measured result**.
  - **Skeptic ACCEPT — most material finding of the round:** the net asserts the *declared string* `columnSpanFull()`, never the *rendered reflow*; owner sign-off covers the dashboard only. So the **9 infolist fixes would have shipped with zero rendered verification** — call insufficient on any root = net green, ~248px gap persists, undetected. Added **step 7**: one Playwright width assertion on one infolist view page (root width == content width), no screenshots, no PII, FAIL before / PASS after. Added **step 8**: confirm §7O.5 is not itself stale — **owner states the dashboard should be 4-column at `xl`**; if the current 2-column dashboard was intended, §7O.5 is wrong and Task 3 must not proceed. (`:12944` verified literal — but a correct-looking spec is not proof of a correct spec; that is a product call.)
  - Skeptic's real-change count: **~21 code lines** total (9 span adds, 1 array 5→3 keys, `UserInfolist` form + 10 translations, 1 test file with 2 assertions, 2 doc marks). Prose ~130 lines. The change is not inflated; the *prose* is — 4 council rounds.
  - **Skeptic was asked directly** whether the plan had been calibrated to its auditors rather than the code. Answered ACCEPT, named its falsifier. Further rounds have poor return; flagged in the plan.

## In progress
- **Step 1 + commit A1 EXECUTED 2026-10-17** on branch `fix/layout-invariant-a1` (off `develop`). Not committed, not pushed.
  - Plan moved to `docs/00-project/ai/plans/approved/2026-10-16-layout-invariant-remediation.md`; approval metadata filled (**partial approval: step 1 + A1 only**); `Infofist` typo fixed.
  - New net: `tests/Feature/Architecture/LayoutInvariantTest.php` (2 assertions, line parser, no framework boot).
  - A1: `->columnSpanFull()` added to the 9 infolist root grids. Array form retained. View-only.
  - **RED captured before any production edit:** 9 infolist violations + dashboard drift, exact list recorded (Products root is `:31`, not `:22` — the plan's `:22` pointed at the docblock). Dashboard failure: `+sm, +lg, xl 4→2`.
  - **Mutation-tested both assertions** (not just "run and see green"): widened dashboard drift (`xl` 2→3) **FAILS**; dropping one root's span **FAILS**. Both guards demonstrably detect their violation.
  - Evidence: focused net green (1 passed / 1 skipped); `tests/Feature/Architecture` + `tests/Feature/Filament/Widgets` → **258 tests, 257 passed, 1 skipped**; full suite → **2089 tests, 2085 passed, 4 skipped**; Pint clean; A1 authz diff-grep → **0 matches**.

- **Full re-audit run 2026-10-17** (read-only, no production change): 41 routes × 10 viewports = **410 loads**; harness `tests/Browser/_layout-audit-full.spec.ts`, report `.issue-tmp/layout-audit-full/report.json`. 0 pages scroll horizontally at any width (`overflow=0` in all 410).
  - **RETRACTED — the audit's `rootGrid` metric is broken.** It reported 0.91–0.96 uniformly on all 39 non-404 routes *including index pages and the dashboard*, which have no infolist root. Its DOM fallback (first `.fi-sc`/`.fi-grid` under main) resolves to the **page content wrapper** (same class descriptor `div.fi-grid.fi-sc.fi-sc-has-gap`, same 680px/744px at 1024) everywhere. My earlier claim that "all 7 reachable infolists sit at 0.91–0.96, hence A1 works" **does not hold and is withdrawn**. Probe: `tests/Browser/_probe-infolist-root.spec.ts` re-pins the node structurally and *still* catches the wrapper — the metric is not salvageable by selector tweaks.
  - **A1 rendered effect PROVEN, but only on one page.** Toggling `->columnSpanFull()` off `ProductInfolist.php` and re-running `_probe-a1-verify.spec.ts`: infolist root grid (`div.fi-grid.md:fi-grid-cols.xl:fi-grid-cols.fi-sc`) = **536px / 0.46** without, **1096px / 0.94** with. Causality established, then restored and verified restored. This supersedes the retracted claim as the only valid A1 evidence.
  - **8 of the 9 A1 infolists remain rendered-unverified.** Only Products was toggled. `InTransit` and `LossLedger` have **zero** rendered coverage at all — no seeded records, so both `--view` routes 404 at every viewport (20 of 410 cells).
  - **5 detector false positives confirmed by ancestor-chain probes** (each walks computed `overflow-x` + `scrollWidth` and asks whether that level can actually scroll): `clippedTables` (9 cells) — the real scroller is `div.fi-ta-content-ctn` (`overflow-x:auto`, 1254>680, canScroll true), not the `.fi-ta-content` node the detector checked; wizard-header `bleeding` (12 cells) — `ol.fi-sc-wizard-header` is itself `overflow-x:auto` canScroll true (intended step scroll); tab `bleeding` at 375 (2 cells) — `div.fi-tabs.fi-contained` canScroll true; table `bleeding` on users/stock-movements/loss-ledgers — same real scroller; `squished` (6 cells) — every one is `div.fi-section.fi-loading-section`, an unloaded **skeleton**, not final layout.
  - **ONE genuine new defect (outside approved scope, not fixed):** `transfer-requisitions--view` — a `RepeatableEntry` `table()` renders **725px inside a 632px** `.fi-in-table-repeatable`, and **no** ancestor scrolls (`canScroll:false`, `overflow-x:visible` all 8 levels). Content spills past the card and to `right=1061` on a 1024 viewport. 2 cells (768, 1024). Same widget class as ProductInfolist's `unitConversions`.
  - `test-results/` is untracked but **not** in `.gitignore` (only `.issue-tmp/` is) — accidental `git add` risk for Playwright output.

## Blocked
- (none)

## Next
- **Commit A1 when the owner authorizes a commit.** Nothing is committed; `CLAUDE.md` forbids committing without an approved plan saying so. Work sits on `fix/layout-invariant-a1`.
- **Known-drift skip is a live obligation.** The dashboard assertion is `markTestSkipped` while `getColumns()` matches the pinned wrong map (`default 1, sm 1, md 2, lg 2, xl 2`). It flips to a real assertion the moment A2 lands. A skip that outlives A2 hides the violation it records.
- **A1 landed without step 7** (rendered width assertion) — the owner approved step 1 + A1 only. So the 9 infolist fixes currently have **no rendered verification**: the net proves the declared string is present, not that the reflow renders. Statement of the Skeptic gap, not a new finding. Step 7 remains the fix.
- **Owner must answer step 8 before A2:** should the dashboard be **4 columns at `xl`** (blueprint §7O.5) or is the current **2-column** layout the intended behaviour? If the latter, §7O.5 is the stale artifact and Task 3 must not run. Council cannot settle this — product decision.
- **Still needs an owner decision:** where permanent audit evidence lives (PII); whether the optional shared infolist root-grid helper (10 callsites) becomes a project convention; the separately-approved §7M.7 r2 amendment; deletion approval for the temporary probe specs and `.issue-tmp/layout-audit/`.
- Owner review of the updated test expectations.
- Owner decision on tracked-config changes made outside the strict "fix the tests" scope: `vite.config.js` and `.gitignore` (plus new `playwright.config.ts`, `.env.testing.example`, `package.json` scripts).
- Owner decision on the audit findings (council recommendations, all owner-gated):
  - **F3 (dashboard columns)** — approve restoring `Dashboard::getColumns()` to §7O.5's `['default'=>1,'md'=>2,'xl'=>4]` + adding the missing `ResponsiveSpanTest` case. Reflows all 9 widgets; needs visual sign-off. **Do this first** — likely resolves F4.
  - **F1 (infolist span)** — approve adding `->columnSpanFull()` to the 9 infolists **keeping** the array form, amending blueprint §7M.7 rule 2 + §7O.4 + §7O.8 #5, and normalising `UserInfolist` (array form + translate its 10 raw strings).
  - **F2 (users scroll)** — recommend **no code change**. Separate optional cleanup: remove dead `->modalWidth(Width::Large)` from 8 tables (13 sites).
  - **F4 (chart size)** — recommend **drop**. Re-measure after F3. If still needed, `ChartWidget::$maxHeight` is available but introducing it is a new widget convention needing sign-off.
  - Also unresolved: blueprint changelog `:20276` wrongly claims `->maxHeight()` was "removed as non-existent" — blueprint is wrong, code has it.
- Owner decision on where permanent audit evidence should live (`docs/00-project/screenshots/` vs keeping `.issue-tmp/layout-audit/` temporary). **PII note:** those PNGs contain employee names/emails and financial figures — must stay untracked (RA 10173).
- Owner approval needed before deleting any audit artifact: 8 temporary `tests/Browser/_*.spec.ts` probes plus `.issue-tmp/layout-audit/`.

## Notes
- Full-suite verification after the UI rewrite: `npx playwright test` → **24 passed (3.2m)** on the default port with bundled Chromium, no env vars.
- `PLAYWRIGHT_CHANNEL` is an optional escape hatch only; not required.
- Both browser builds for Chromium 1248 are now installed (headless shell + full). `--ui` and `--headed` require the full build.
- `tests/Browser/purchase-and-sales.spec.ts` is the reference pattern for driving a Filament row ActionGroup modal: wait on the modal's config field (`input[id$="received.<id>"]`), not on `.fi-modal` — the page always holds a hidden `database-notifications` dialog plus one lazily mounted dialog per action, so `.fi-modal` is present but invisible.
- Council finding (Security seat, separate from the 4 items): `QuickActionsWidget::actions()` emits all four `Resource::getUrl('create')` links unconditionally; its `canView()` only checks `auth()->user() !== null`. No authorization bypass — the destination pages are policy-gated server-side (403) — but it advertises create routes to roles that cannot use them. Optional hardening, not approved.
- Nothing committed or pushed.
