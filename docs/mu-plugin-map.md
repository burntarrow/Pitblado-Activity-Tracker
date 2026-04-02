# MU Plugin Map (Pitblado Activity Tracker)

This repository is composed of standalone MU-plugin PHP files (loaded by WordPress from `mu-plugins`).

## CSS loading architecture

- **Current centralized CSS loader:** `associatedb-shared-ui-loader.php` enqueues:
  - `assets/css/associatedb-shared-ui.css`
  - `assets/css/associatedb-director.css`
  - `assets/css/associatedb-associate.css`
- **Legacy inline helper:** `associatedb-shared-ui.php` keeps `pitblado_get_director_shared_styles()` as a compatibility shim and now returns an empty string (styles are enqueued globally for `/director/*` and `/associate/*` logged-in routes).
- **Inline style leftovers:** A few non-portal files may still use local inline styles (e.g., auth branding), but director/associate dashboard shell styles are now centralized.

## Plugin responsibilities

| File | Responsibility | Shortcodes | Target slugs/pages | Key helpers/dependencies | GravityView usage | Shared CSS dependency |
|---|---|---|---|---|---|---|
| `associatedb-helpers-users.php` | Core associate/director relationship + access helpers; inactive associate auth block | _None_ | Shared backend logic used by director and associate pages | `pitblado_get_manageable_associate()`, `pitblado_get_active_associates_for_director()`, `pitblado_current_user_can_manage_associate()` | None direct | Indirect (all portal UI plugins) |
| `associatedb-shared-ui-loader.php` | Enqueues centralized shared/director/associate styles | _None_ | `/director/*`, `/associate/*` | `pitblado_is_portal_page_request()` | None | Loads all shared CSS bundles |
| `associatedb-shared-ui.php` | Backward-compatible style helper shim | _None_ | Shared | `pitblado_get_director_shared_styles()` | None | Compatibility-only |
| `associatedb-director-associates.php` | Director associates list, scope toggle, KPI tiles, links to overview | `director_my_associates_page` | `/director/associates/` | `pitblado_get_active_associates_for_director()`, `pitblado_get_all_active_associates()` | None direct | `associatedb-shared-ui.css`, `associatedb-director.css` |
| `associatedb-director-inactive-associates.php` | Director inactive associates list + reactivate flow | `director_inactive_associates_page` | `/director/associates/inactive/` | helpers from `associatedb-helpers-users.php` | None direct | `associatedb-shared-ui.css`, `associatedb-director.css` |
| `associatedb-director-associate-overview.php` | Associate overview header, KPIs, recent activity table, plan snapshot, deactivate action | `director_associate_dashboard`, `director_associate_recent_activity`, `director_associate_plan_snapshot` | `/director/associates/overview/` | `pitblado_get_requested_associate_id()`, permission helpers | None direct (manual GFAPI queries) | `associatedb-shared-ui.css`, `associatedb-director.css` |
| `associatedb-director-associate-activity.php` | Associate-scoped activity shell + GravityView wrapper + scoped back-link rewrite | `director_associate_activity_page` | `/director/associates/activity/` | `pitblado_get_manageable_associate()` | GravityView id **708** | `associatedb-shared-ui.css`, `associatedb-director.css` |
| `associatedb-director-associate-plan.php` | Associate-scoped plan shell + GravityView wrapper | `director_associate_plan_page` | `/director/associates/plan/` | `pitblado_get_manageable_associate()` | GravityView id **714** | `associatedb-shared-ui.css`, `associatedb-director.css` |
| `associatedb-director-assigned-views.php` | Filters director `/director/activity/` + `/director/plans/` to allowed associates; injects context panel | _None_ | `/director/activity/`, `/director/plans/` | path detectors + associate permission helpers | Filters view entries for IDs **101** and **102** | `associatedb-shared-ui.css`, `associatedb-director.css` |
| `associatedb-director-dashboard-cards.php` | Director dashboard utility shortcodes (range selector, relationship donut, alert cards, averages) | `director_dashboard_range_selector`, `director_relationship_type_chart`, `director_users_no_plan`, `director_users_no_activity_14_days`, `director_average_plan_progress`, `director_average_30_day_completion` | director dashboard sections | GFAPI + helpers in `associatedb-helpers-users.php` | None direct | `associatedb-director.css` (+ shared shell classes) |
| `total-registered-users.php` | Director metric card helper | `director_total_associates` | director dashboard card regions | user query helpers | None | shared/director CSS as used by host markup |
| `plans-submitted-metric.php` | Director metric card helper for submitted plans | `director_plan_submission_card` | director dashboard card regions | GFAPI helpers | None | shared/director CSS as used by host markup |
| `current-monthly-activity.php` | Director metric card helper for month-over-month logs | `director_logs_month_compare` | director dashboard card regions | GFAPI | None | shared/director CSS as used by host markup |
| `30-day-progress.php` | Plan progress propagation/update hooks | _None_ | Form processing layer | GF/GravityView update hooks | GravityView edit-entry hooks | None |
| `plan-progress-percent.php` | Computes and syncs plan progress percentages | _None_ | Form processing layer | GF/GravityView update hooks | GravityView edit-entry hooks | None |
| `current-quarter-objective-focus.php` | Associate current-quarter plan focus output | `associate_current_quarter_focus` | associate dashboard widgets | GFAPI | None | `associatedb-associate.css` (if host wrapper classes used) |
| `associate-activity-contact-sync.php` | Sync activity form contact fields and secure contact fetch endpoint | _None_ | Activity/contact integration | GF hooks + REST/AJAX behavior | None | None |
| `associate-db-appointments-zoom-production.php` | Associate Zoom appointments integration | `assoc_next_appointment`, `assoc_my_appointments` | `/associate/my-meetings/`, `/associate/book-a-meeting/` | Zoom APIs + WP HTTP/auth | None | `associatedb-associate.css` (host page-level styling) |
| `associatedb-registration-flows.php` | Associate registration + onboarding flow customizations | varies by hooks | registration/login pages | WP auth/user hooks | None | none/branding dependent |
| `associatedb-auth-branding.php` | Login/auth branding and style output | _None_ | auth pages | WP login hooks | None | separate inline branding styles |
| `health-check-troubleshooting-mode.php` | Operational troubleshooting toggles/checks | _None_ | admin/runtime diagnostics | WP hooks | None | None |

## Route ownership snapshot

- `/director/associates/` → `associatedb-director-associates.php` (`[director_my_associates_page]`)
- `/director/associates/overview/` → `associatedb-director-associate-overview.php`
- `/director/associates/activity/` → `associatedb-director-associate-activity.php`
- `/director/associates/plan/` → `associatedb-director-associate-plan.php`
- `/director/activity/` and `/director/plans/` context filtering → `associatedb-director-assigned-views.php`
- Associate dashboard/activity/contacts are primarily host page + GravityView driven with helper shortcodes from `current-quarter-objective-focus.php`, `associate-db-appointments-zoom-production.php`, and sync logic in `associate-activity-contact-sync.php`.
