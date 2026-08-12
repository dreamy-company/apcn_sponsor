# AGENTS.md

# AI Project Bootstrap

## Project Metadata

Project Name: APCN SPONSOR

Workspace: Gretiva

Project Type:
- Web Application

Primary Domain:
- Sponsorship deal management (internal SSOT for sponsor deals at APCN 2027 — "J4U Sponsorship Deal Management System")

Status:
- Development

---

# Purpose

This file serves as the bootstrap entry point for AI assistants working on this project.

Before performing any task, understand the project context, load the AI Knowledge Base, and analyze the existing implementation before making changes.

---

# AI Knowledge Base

Always load the AI Knowledge Base in the following order:

1. AI/Global
2. AI/Workspaces/<Gretiva>
3. AI/Projects/<APCN SPONSOR>
4. AI/Playbooks

The AI Knowledge Base is the primary source of truth.

---

# Project Documentation

Source of truth for product requirements (read before any feature work):

- `.context/srs.md` — PRD v1.0 (approved): modules, RBAC, DB schema, logic hooks, acceptance criteria
- `.context/template.md` — this bootstrap template

---

# Priority Order

When information conflicts, follow this priority:

1. Source Code (Current Implementation)
2. Project Documentation
3. Workspace Documentation
4. Global Documentation
5. Relevant Playbooks

Always report inconsistencies before making assumptions.

---

# Standard Workflow

Requirement

↓

Discussion

↓

Planning

↓

Implementation

↓

Testing

↓

Documentation

---

# Before Starting Any Task

Always identify:

- Goal
- Scope
- Affected modules
- Dependencies
- Business rules
- Database impact
- API impact
- Security impact
- Performance impact
- Regression risks

---

# Working Principles

Always:

- Understand the existing implementation first.
- Follow existing architecture and coding standards.
- Preserve consistency throughout the codebase.
- Keep documentation synchronized with implementation.
- Verify assumptions using the source code.
- Explain important technical decisions.

Never:

- Invent business rules.
- Invent APIs.
- Invent database structures.
- Introduce breaking changes without explanation.
- Rewrite code unnecessarily.
- Ignore existing project conventions.

---

# Development Standards

Prefer:

- Small incremental changes
- Readable code
- Modular architecture
- Reusable components
- Consistent naming
- Framework best practices

Architecture pattern (per workspace playbook):

- **Enums** (`app/Enums/`) — PHP backed enums for roles/statuses
- **Models** (`app/Models/`) — Eloquent models, relations, `#[ObservedBy]` observers
- **DTOs** (`app/DTOs/<Area>/`) — plain data carriers with `fromRequest()`, no Model dependencies
- **Actions** (`app/Actions/<Area>/`) — single-purpose `VerbNounAction`, one `execute()`, wrapped in `DB::transaction`
- **Services** (`app/Services/`) — orchestrate multiple Actions for multi-step flows
- **Livewire components** (`app/Livewire/`) — for reactive UI (no page reloads), with Flux + Tailwind CSS

Stack (actual, from source code — newer than PRD recommendation):

- PHP 8.3 / Laravel 13 (PRD said 11 — report, follow source)
- Livewire 4 (PRD said 3 — report, follow source) + Flux 2 + Tailwind
- Fortify (auth) + Passkeys + 2FA
- SQLite locally (tests run on in-memory SQLite); PRD targets PostgreSQL/MySQL in production

---

# Business Rules (from PRD — do not invent new ones)

- RBAC: `j4u` = full CRUD; `doctor` = read-only viewer of deals they initiated
- Deal customization: select base package, add/remove items (add-ons via `is_addon`), custom final price, arbitrary payment terms
- Material checklist auto-generated when a deal becomes `finalized` (from items where `requires_material`)
- Every update to Deal / PaymentTerm / MaterialDeadline is recorded in `activity_logs` (observers, `getDirty()`)
- Doctor access: index/show routes only; no edit/delete

---

# Documentation

Read the relevant project documentation before making changes.

Typical project documentation includes:

- README.md
- Tech Stack.md
- Architecture.md
- Business Rules.md
- Modules.md
- API.md
- Database.md
- Roadmap.md
- Known Issues.md

If documentation is outdated, report it and suggest updates.

---

# Testing

Before completing a task, consider:

- Happy Path
- Validation
- Authorization
- Edge Cases
- Security
- Performance
- Regression Risks

Suggest automated tests when appropriate.

---

# Communication Style

Responses should be:

- Clear
- Structured
- Concise
- Evidence-based
- Actionable

State assumptions explicitly.

---

# Goal

Maintain a clean, scalable, maintainable, and well-documented project while following the standards defined in the AI Knowledge Base.
