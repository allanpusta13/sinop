# Issue #207 — Issue-Text Amendment (owner paste sheet)

**Status:** prepared for manual paste. `gh` was unavailable this pass, so the issue
body was **not** written by this session. No GitHub write occurred. **Paste awaits
owner sign-off on the exact wording below.**

**Authority:** Phase 3 owner decision **D4 = (b)** — name the delivered test in the
*Test coverage* section. **Do NOT create the named classes. Do NOT modify the
delivered test. Code is NOT changed.**

**Target:** `allanpusta13/sinop` issue **#207**.

---

## 1. Authorized edit — the test-coverage section

**Current text** (as read earlier this session):

> - [ ] `RepositoryMapTest::*`
> - [ ] `RuntimeCompletenessTest::*`

**Replacement:**

> - [ ] `tests/Feature/Architecture/ProductExporterDeletionTest.php::product_exporter_does_not_exist`,
>       `::no_reference_to_product_exporter`

---

## 2. Why the named classes are not created

Both names exist **only as blueprint citations**, never as files on disk:

- `docs/00-project/blueprint.md:15128` — `RepositoryMapTest::declared_files_exist`
- `docs/00-project/blueprint.md:15234-15237` — `RuntimeCompletenessTest::direct_transfer_*`
- `docs/00-project/blueprint.md:15593` — a compliance-table row for `RepositoryMapTest::declared_files_exist`

A prior approved plan already adjudicated this:
`docs/00-project/ai/plans/approved/2026-10-16-layout-invariant-remediation.md:71` —
*"cites … `RuntimeCompletenessTest::*`. **None exist on disk.** → mark unbuilt.
(All five seats rejected revision 1's proposal to create a test to make the
citation true.)"*

Creating them now would reverse that approved decision and would add test files
outside the D4(b) authorization. Verified at the G1 pin: `php artisan test
--filter=RepositoryMapTest` → `No tests found`, exit 1; same for `RuntimeCompletenessTest`.

---

## 3. Delivered evidence (verified at PR #238 head `9ac1856`)

**Removal — real, and the directory is gone, not just the file:**

- `test -e app/Filament/Exports/ProductExporter.php` → absent
- `test -d app/Filament/Exports` → **absent** (`app/Filament/` holds only
  `Pages/ Resources/ Support/ Widgets/`)
- Glob `**/*ProductExporter*` → exactly one file, the guard test
- `vendor/composer/autoload_classmap.php` → no match; `composer.json` → no `Exports` entry
- `git log --all --full-history -- app/Filament/Exports/` → **no commits in any of the 65 refs in
  this repository**, after a fresh `git fetch --all --prune` at
  **2026-10-28 08:01:15 +0800** (remote `origin` = `https://github.com/allanpusta13/sinop.git`).
  Precise claim: the path is **absent from all fetched history**. `--all` sees only refs present
  in this clone, so it cannot speak for deleted unmerged branches; the wording is scoped
  accordingly rather than asserting a global "never committed".
- Commits matching `*ProductExporter*` across all refs are `74d9010`, `5a175c2`, `6c73653`,
  `512562a`, `fe0ba03` — every one touches **only the guard test**, so there is no deletion
  event either. `git log --diff-filter=D --all -- "*ProductExporter*"` → empty.
- Note: the issue is titled "Remove ProductExporter", but the verified outcome is **"absent, and
  not present in fetched history, with a guard test added"**. No deletion event occurred, so
  retained evidence must not describe one.

**Live-reference scan — zero executable references:**

- Sweep of `app/ resources/ database/ config/ routes/` → **0** hits.
- Every remaining hit is either **(b)** the guard test that asserts absence
  (`tests/Feature/Architecture/ProductExporterDeletionTest.php:8,11,12,27,71`) or
  **(c)** historical prose: `docs/00-project/blueprint.md` (`:33, :20051, :20103, :20251, :20369`),
  the `docs/01-issues/` reports, `.patches/RUNLOG.md`, and root-level `*.md`/`*.txt` reports.

**Guard test — protects the actual requirement, not stale naming:**

`it('product_exporter_does_not_exist')` asserts class *and* file non-existence.
`it('no_reference_to_product_exporter')` runs a **PHP-native recursive scan** across the five
roots the issue names (`app`, `tests`, `config`, `database`, `resources`), self-excluding by
filename. The shell-`grep` form was deliberately replaced because `2>/dev/null` broke under
`cmd.exe` and produced a **silent vacuous pass**. This is a stronger guard than the AC's literal
command. 2/2 green.

---

## 4. Proposed close comment (paste-ready)

> Verified against PR #238 head `9ac1856`.
>
> **Removal:** `app/Filament/Exports/ProductExporter.php` absent; the `app/Filament/Exports/`
> directory is absent too (`app/Filament/` contains only `Pages/ Resources/ Support/ Widgets/`).
> `git log --all --full-history -- app/Filament/Exports/` returns no commits in any of the 65
> refs in this repository after a fresh fetch on 2026-10-28; the path is absent from all
> fetched history. No deletion commit exists either (`--diff-filter=D` is empty), so the verified
> outcome is "absent and never present in fetched history", not a recorded deletion event.
>
> **References:** zero executable references. Sweep of `app/ resources/ database/ config/ routes/`
> returns 0 hits; all remaining matches are the guard test or historical documentation.
>
> **Coverage:** `tests/Feature/Architecture/ProductExporterDeletionTest.php`:
> `product_exporter_does_not_exist` and `no_reference_to_product_exporter`, both green. The
> second does a PHP-native recursive scan over the five roots named in this issue and
> self-excludes, replacing a shell-`grep` command that silently vacuous-passed on Windows.
>
> **Corrected:** the previously cited `RepositoryMapTest::*` and `RuntimeCompletenessTest::*` do
> not exist on disk and were never created; an approved plan had already rejected creating
> tests to make a blueprint citation true.
>
> Closing as satisfied.

---

## 5. RESOLVED — option (b), owner-approved. No reopening event exists.

The owner asked the paste to include the **reopening rationale**. **No such record exists in
this repository:** a repo-wide search for `reopen` across `docs/` returns exactly one unrelated
hit (`2026-10-26-issue-audit-173-218.md:168`, about #214). `docs/01-issues/207-summary.md`
records removal, the reference sweep, autoload verification and a green CI run — no reopening
event, no date, no reason.

**RESOLVED — option (b), owner-approved, 2026-10-28.** No reopening event exists in fetched
history; the close comment proceeds without a reopening paragraph because there is no event to
explain. If a reopening event later surfaces in an unfetched ref, that is a follow-up, not a G1
blocker.

Option (a) was unavailable: the rationale cannot be written from evidence without inventing it.
No reopening text has been drafted. Nothing was fabricated.
