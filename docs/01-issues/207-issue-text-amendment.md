# Issue #207 — Issue-Text Amendment (owner paste sheet)

**Status:** prepared for manual paste. `gh` was unavailable this pass, so the issue
body was **not** written by this session. No GitHub write occurred.

**Authority:** Phase 3 owner decision **D4 = (b)** — name the delivered test in the
*Test coverage* section. **Do NOT create the named classes. Do NOT modify the
delivered test. Code is NOT changed.**

**Target:** `allanpusta13/sinop` issue **#207**, section *Test coverage*.

---

## 1. Authorized edit

**Current text** (as read earlier this session):

> - [ ] `RepositoryMapTest::*`
> - [ ] `RuntimeCompletenessTest::*`

**Replacement:**

> - [ ] `tests/Feature/Architecture/ProductExporterDeletionTest.php::
>       product_exporter_does_not_exist`,
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
outside the D4(b) authorization.

---

## 3. Delivered test — recorded as-is, unmodified

- `tests/Feature/Architecture/ProductExporterDeletionTest.php` — two tests:
  `it('product_exporter_does_not_exist', …)` (line 10) and
  `it('no_reference_to_product_exporter', …)` (line 18).
- Sweep roots (line 19-25): `app`, `tests`, `config`, `database`, `resources` — all
  five the issue names. PHP-native `RecursiveIteratorIterator` scan; self-excludes by
  filename.
