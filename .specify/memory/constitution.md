<!--
SYNC IMPACT REPORT
Version Change: 0.0.0 → 1.0.0
Modified Principles: N/A (initial creation)
Added Sections: Core Principles (5), Development Workflow, Governance
Removed Sections: N/A (initial creation)
Templates Updated: N/A (no template updates required for initial constitution)
Deferred Items: TODO(RATIFICATION_DATE): Set to project kickoff date if known earlier than today.
-->

# Region & Land Price Tracker Constitution

## Core Principles

### I. YAGNI — You Aren't Gonna Need It

Build ONLY what is described in `plan.md`. No extra fields, no "just in case" options, no export/import CSV, no general settings tab, and no pagination or search/filter unless explicitly planned. Every feature must be complete (front-end + back-end) before moving to the next. Sub-agents own exactly one phase at a time and deliver it fully before hand-off.

**Rationale**: Scope creep is the primary risk in WordPress plugin development. Enforcing strict adherence to the specification document keeps the codebase lean, maintainable, and focused on actual user value.

### II. Zero Build Tooling

Plain `.css` and `.js` files only. No SCSS, no Gulp, no webpack, no TypeScript, and no bundlers. All assets are enqueued via `wp_enqueue_style()` and `wp_enqueue_script()`. External libraries (Chart.js, Select2, SweetAlert2) are loaded from CDN and localized with `wp_localize_script()`.

**Rationale**: Simplifies the development environment, reduces setup time, and ensures the plugin is deployable to any standard WordPress installation without requiring Node.js or build pipelines.

### III. Sub-Agent Delegation Architecture

The Main Agent reads `plan.md` and `AGENTS.md`, then creates sub-agents, assigning each exactly one phase. Sub-agents write code, test it, and fix it before returning. Main Agents NEVER write plugin PHP or JS code directly. Sub-agents do NOT skip phases, reorder them, or modify files owned by a previous phase without flagging it to the Main Agent.

**Rationale**: A clear hierarchy of responsibility prevents conflicts, ensures each feature is built completely before moving on, and enforces the horizontal (feature-by-feature) construction strategy required by this project.

### IV. Security-First Development

Every database query MUST use `$wpdb->prepare()`. All AJAX endpoints MUST verify nonces with `check_ajax_referer()` and check capabilities with `current_user_can('rlpt_access')`. All input MUST be sanitized (`sanitize_text_field`, `intval`, `floatval`). All output MUST be escaped (`esc_html`, `esc_attr`, `wp_json_encode`). PHP files MUST start with `if (!defined('ABSPATH')) exit;`. `$_REQUEST` is prohibited in favor of `$_POST` or `$_GET` explicitly.

**Rationale**: WordPress is a frequent target for attacks. Enforcing strict security patterns at the code level is non-negotiable to protect user data and site integrity.

### V. WordPress & PHP 7.4+ Conformance

Follow PSR-4 autoloading with the `RLPTNamespace\` prefix. Use WordPress coding standards for PHP, JS, and CSS. AJAX responses MUST use `wp_send_json_success()` and `wp_send_json_error()`. Shortcode output MUST be returned via `ob_start()` / `ob_get_clean()`, never echoed directly. JavaScript variables are declared with `const` and `let`; `var` is prohibited.

**Rationale**: Compliance with WordPress and PHP standards ensures long-term maintainability, compatibility with the WordPress ecosystem, and a consistent development experience for all agents.

## Development Workflow

### Phase-Based Horizontal Delivery

1. **Setup Phase**: Initialize shared infrastructure (database schema, base classes, asset registration).
2. **Feature Phases**: Implement one complete feature at a time (e.g., Regions CRUD, Materials CRUD). Each feature includes its admin tab, AJAX handlers, frontend shortcode, and associated assets.
3. **Polish Phase**: Cross-cutting concerns like performance optimization, security hardening, and final code review.

### Sub-Agent Handoff Checklist

Before a sub-agent marks a phase as complete, the Main Agent MUST verify:
- [ ] All files listed in the phase exist and are non-empty.
- [ ] No PHP fatal errors (check error log).
- [ ] No JS console errors (check browser dev tools).
- [ ] AJAX actions return the correct JSON structure as described in `plan.md §8`.
- [ ] Nonces are verified and capabilities are checked.
- [ ] All inputs are sanitized and all outputs are escaped.
- [ ] No hardcoded table names, URLs, or nonces in JS files.
- [ ] Any ambiguity decisions are documented at the bottom of `AGENTS.md` using the required format.

### Testing & Validation

- Manually verify every AJAX action works before marking a phase as done.
- Check the browser console for JS errors after every phase.
- Check the PHP error log after every phase.
- If a test or validation fails, the sub-agent MUST fix the issue before moving to the next phase. No exceptions.

## Governance

### Supremacy & Compliance

This Constitution supersedes all other guides, templates, or ad-hoc instructions. Any conflict between this document and another document (e.g., an agent's default behavior) MUST be resolved in favor of this Constitution.

### Amendment Procedure

1. **Proposal**: Any agent may propose an amendment by documenting the change, the rationale, and the impact on existing principles.
2. **Review**: The Main Agent reviews the proposal against the YAGNI principle and existing architecture. If the change adds scope not in `plan.md`, it is rejected.
3. **Approval & Propagation**: Once approved, the constitution is updated, the version is bumped, and the change is propagated to all dependent artifacts (templates, `AGENTS.md`, etc.).

### Versioning Policy

`CONSTITUTION_VERSION` follows semantic versioning:
- **MAJOR**: Backward-incompatible governance or principle redefinitions.
- **MINOR**: New principle or section added.
- **PATCH**: Clarifications, wording improvements, or typo fixes.

### Compliance Review

All PRs and sub-agent handoffs MUST verify compliance with this Constitution. The Main Agent acts as the final arbiter for any compliance disputes. Complexity that violates the YAGNI principle must be justified in writing in the `AGENTS.md` documented decisions section.

**Version**: 1.0.0 | **Ratified**: 2026-06-23 | **Last Amended**: 2026-06-23
