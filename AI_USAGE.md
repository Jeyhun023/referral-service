# AI Usage

## Tools Used

- **Claude Code (CLI)** — primary coding assistant for scaffolding, implementation, and iterating on code

## Where AI Was Helpful

- **Scaffolding speed** — generating the initial file structure in a single pass saved significant setup time
- **Boilerplate reduction** — form request rules, resource transformation, factory states, and test setup are repetitive patterns where AI output was accurate and consistent
- **Test coverage** — generating 25 tests covering auth, validation, CRUD, cancellation rules, triage flow, idempotency, and audit logging provided a solid starting point

## What It Got Wrong or Incomplete

- **Spatie + UUID incompatibility** — AI generated the Spatie activity log integration without accounting for the fact that the default migration uses integer `morphs` columns, which breaks with UUID primary keys. This caused a runtime SQL error (`Incorrect integer value` for `subject_id`). I had to identify the root cause from the error trace and manually change `nullableMorphs` to `nullableUuidMorphs` in the published migration
- **Cancellation test status codes** — the cancel guard uses `abort(422)`, but tests initially asserted `500`. The mismatch was caught on the first test run and corrected
- **Queue container entrypoint conflict** — the Dockerfile's `ENTRYPOINT` directive (running php-fpm) overrode the `command` in docker-compose for the queue worker. The container started php-fpm instead of the queue worker. Required adding `entrypoint: []` to the compose service

## What I Manually Verified or Changed

- **Created docker structure** whole docker infrastructure created by myself. AI is not helpful in this side.
- **Tested every endpoint** via curl inside the Docker network before considering it done
- **Verified audit log entries** via `tinker` to confirm Spatie was recording the correct events, descriptions, and old/new property diffs
- **Verified idempotency replay** — confirmed the same `Idempotency-Key` returns the cached response with `X-Idempotency-Replayed: true` header and does not create a duplicate record
- **End-to-end queue test** — created a referral via the API, waited for the queue worker to process it, then verified the status transitioned from `received` to `accepted` with `triaged_at` populated
- **Reviewed all generated code** for security concerns (SQL injection via search filter, timing-safe token comparison, proper status guard on cancellation)
- **Linter fixes** — removed unused imports (`Log`, `Queue`), reformatted constructor signatures, added null-safe operators on enum access in the resource
