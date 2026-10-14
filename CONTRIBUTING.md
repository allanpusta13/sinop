# Contributing to Sinop

## Development Workflow

1. Identify an existing GitHub issue before starting development.
2. Confirm the issue's scope and acceptance criteria.
3. Create a development branch following the required naming convention.
4. Keep changes focused on the issue being addressed.
5. Run the relevant checks before opening a pull request.
6. Open a pull request and reference the associated issue.
7. Do not merge until the required reviews and checks have passed.

## Branch Naming Convention

All development branches must follow this format:

`{issue-type}/{issue-number}-{branch-name}`

### Approved issue types

- `feature` — new functionality
- `fix` — bug fixes
- `refactor` — restructuring without intended behavior changes
- `chore` — maintenance and tooling
- `docs` — documentation changes
- `test` — test-related changes
- `perf` — performance improvements
- `security` — security-related fixes or improvements

### Examples

- `feature/205-branch-manager-role`
- `fix/206-stock-balance-error`
- `chore/207-update-dependencies`
- `refactor/208-stock-ledger-service`
- `docs/209-branch-naming-guidelines`
- `test/210-warehouse-access-rules`

### Naming requirements

- Use an existing GitHub issue number that matches the work being performed.
- Use an approved issue type.
- Use lowercase words separated by hyphens in the branch description.
- Keep branch names concise and specific.
- Do not use an issue number merely to satisfy the naming format when the issue is unrelated.

## Pull Requests

Every pull request should:

- Explain what changed and why.
- Reference the relevant GitHub issue.
- Describe relevant tests or checks performed.
- Identify known limitations or follow-up work.
- Follow the repository's review and approval requirements.

## Scope and Safety

- Do not include unrelated changes in a pull request.
- Do not bypass required checks or repository protections.
- Ask for clarification when requirements or the intended scope are ambiguous.
- Do not perform changes that require explicit approval until that approval is given.