# WordPress AI Task Template

> **Instructions:** This template helps you create comprehensive task documents for AI-driven WordPress development. Fill out each section thoroughly to ensure the AI agent has all necessary context and can execute the task systematically.

---

## 1. Task Overview

### Task Title
<!-- Provide a clear, specific title for this task -->
**Title:** [Brief, descriptive title of what you're building/fixing]

### Goal Statement
<!-- One paragraph describing the high-level objective -->
**Goal:** [Clear statement of what you want to achieve and why it matters]

---

## 2. Strategic Analysis & Solution Options

### When to Use Strategic Analysis
<!--
AI Agent: Use your judgement to determine when strategic analysis is needed vs direct implementation.

**✅ CONDUCT STRATEGIC ANALYSIS WHEN:**
- Multiple viable technical approaches exist (custom post types vs custom tables, REST API vs AJAX, etc.)
- Trade-offs between different solutions are significant
- User requirements could be met through different WordPress patterns
- Architectural decisions will impact future development
- Implementation approach affects performance, security, or maintainability significantly
- Change touches multiple WordPress subsystems (database, admin, frontend, REST API)
- Change affects plugin architecture or public API
- User has expressed uncertainty about the best approach

**❌ SKIP STRATEGIC ANALYSIS WHEN:**
- Only one obvious WordPress solution exists
- It's a straightforward bug fix or minor enhancement
- The implementation pattern is clearly established in the codebase
- Change is small and isolated with minimal impact
- User has already specified the exact approach they want

**DEFAULT BEHAVIOR:** When in doubt, provide strategic analysis. It's better to over-communicate than to assume.
-->

### Problem Context
<!-- Restate the problem and why it needs strategic consideration -->
[Explain the problem and why multiple solutions should be considered - what makes this decision important?]

### Solution Options Analysis

#### Option 1: [Solution Name]
**Approach:** [Brief description of this solution approach]

**Pros:**
- ✅ [Advantage 1 - specific benefit]
- ✅ [Advantage 2 - quantified when possible]
- ✅ [Advantage 3 - why this is better]

**Cons:**
- ❌ [Disadvantage 1 - specific limitation]
- ❌ [Disadvantage 2 - trade-off or cost]
- ❌ [Disadvantage 3 - risk or complexity]

**Implementation Complexity:** [Low/Medium/High] - [Brief justification]
**Risk Level:** [Low/Medium/High] - [Primary risk factors]
**WordPress Compatibility:** [Which versions supported, any known conflicts]

#### Option 2: [Solution Name]
**Approach:** [Brief description of this solution approach]

**Pros:**
- ✅ [Advantage 1]
- ✅ [Advantage 2]
- ✅ [Advantage 3]

**Cons:**
- ❌ [Disadvantage 1]
- ❌ [Disadvantage 2]
- ❌ [Disadvantage 3]

**Implementation Complexity:** [Low/Medium/High] - [Brief justification]
**Risk Level:** [Low/Medium/High] - [Primary risk factors]
**WordPress Compatibility:** [Which versions supported, any known conflicts]

#### Option 3: [Solution Name] (if applicable)
**Approach:** [Brief description of this solution approach]

**Pros:**
- ✅ [Advantage 1]
- ✅ [Advantage 2]

**Cons:**
- ❌ [Disadvantage 1]
- ❌ [Disadvantage 2]

**Implementation Complexity:** [Low/Medium/High] - [Brief justification]
**Risk Level:** [Low/Medium/High] - [Primary risk factors]
**WordPress Compatibility:** [Which versions supported, any known conflicts]

### Recommendation & Rationale

**🎯 RECOMMENDED SOLUTION:** Option [X] - [Solution Name]

**Why this is the best choice:**
1. **[Primary reason]** - [Specific justification]
2. **[Secondary reason]** - [Supporting evidence]
3. **[Additional reason]** - [Long-term considerations]

**Key Decision Factors:**
- **Performance Impact:** [How this affects site performance]
- **User Experience:** [How this affects end users and admin users]
- **Maintainability:** [How this affects future development and WordPress updates]
- **Scalability:** [How this handles growth and high traffic]
- **Security:** [Security implications and WordPress security best practices]
- **WordPress Compatibility:** [How well this works with WordPress core and common plugins]

**Alternative Consideration:**
[If there's a close second choice, explain why it wasn't selected and under what circumstances it might be preferred]

### Decision Request

**👤 USER DECISION REQUIRED:**
Based on this analysis, do you want to proceed with the recommended solution (Option [X]), or would you prefer a different approach?

**Questions for you to consider:**
- Does the recommended solution align with your priorities?
- Are there any constraints or preferences I should factor in?
- Do you have specific plugin compatibility requirements?
- What WordPress version are you targeting?

**Next Steps:**
Once you approve the strategic direction, I'll update the implementation plan and present you with next step options.

---

## 3. Project Analysis & Current State

### Technology & Architecture
<!--
AI Agent: Analyze the project to fill this out.
- Check main plugin file for plugin metadata (version, WordPress version requirements)
- Check `composer.json` for PHP dependencies
- Check `package.json` for build tools and asset dependencies
- Check database schema files or activation hooks for custom tables
- Check `includes/` directory structure for class organization
- Check for existing custom post types, taxonomies, shortcodes
- Check admin menu structure and settings pages
- Check REST API endpoints if any exist
- Check existing hooks and filters used
-->
- **Plugin Type:** [Single plugin / Multi-plugin setup / Theme with plugin]
- **WordPress Version:** [Minimum required version and tested up to version]
- **PHP Version:** [Minimum required PHP version]
- **Database:** [WordPress default tables / Custom tables via dbDelta]
- **Admin Interface:** [Settings API / Custom admin pages / Metaboxes / Block editor extensions]
- **Frontend Display:** [Shortcodes / Blocks / Template overrides / Widgets]
- **Build Tools:** [e.g., webpack, npm scripts, composer for autoloading]
- **CSS Framework:** [e.g., None / Tailwind / Bootstrap / Custom]
- **JavaScript:** [Vanilla JS / jQuery / React (Gutenberg blocks) / Vue]
- **Key Architectural Patterns:** [e.g., OOP with namespaces, singleton pattern, dependency injection]
- **Existing Custom Post Types:** [List any registered CPTs]
- **Existing Taxonomies:** [List any custom taxonomies]
- **REST API Endpoints:** [List any custom REST routes]
- **AJAX Handlers:** [List any wp_ajax actions]
- **Cron Jobs:** [List any wp_cron scheduled events]

### Current State
<!-- Describe what exists today based on actual analysis -->
[Describe the current situation, existing code, and what's working/not working - based on actual file analysis, not assumptions]

### Existing WordPress Hooks Analysis
<!--
AI Agent: MANDATORY - Analyze existing hooks before planning new functionality
- Check where actions are added (add_action calls)
- Check where filters are used (add_filter calls)
- Identify custom hooks that the plugin defines
- Map the hook execution order and priorities
- Check for dependencies on other plugins via hooks
-->
- **Core WordPress Hooks Used:** [List actions/filters the plugin hooks into]
- **Custom Hooks Defined:** [List any do_action/apply_filters the plugin creates]
- **Hook Priorities:** [Note any non-default priorities that might cause conflicts]
- **Plugin Dependencies:** [Other plugins this plugin expects or hooks into]

**🔍 Hook Coverage Analysis:**
- What WordPress core functionality is the plugin extending?
- Are there conflicts with common plugins (WooCommerce, ACF, Yoast, etc.)?
- Which hooks run on admin vs frontend vs both?
- Are nonces and capability checks properly implemented on admin actions?

## 4. Context & Problem Definition

### Problem Statement
<!-- What specific problem are you solving? -->
[Detailed explanation of the problem, including pain points, user impact, and why this needs to be solved now]

### Success Criteria
<!-- How will you know this is complete and successful? -->
- [ ] [Specific, measurable outcome 1]
- [ ] [Specific, measurable outcome 2]
- [ ] [Specific, measurable outcome 3]

---

## 5. Development Mode Context

### Development Mode Context
- **🚨 IMPORTANT: This is a plugin in active development**
- **No backwards compatibility concerns** - feel free to make breaking changes
- **Data loss acceptable** - existing data can be wiped/migrated aggressively
- **Users are developers/testers** - not production users requiring careful migration
- **Priority: Speed and simplicity** over data preservation
- **Aggressive refactoring allowed** - delete/recreate classes and functions as needed

---

## 6. Technical Requirements

### Functional Requirements
<!-- What should the plugin do? -->
- [Requirement 1: Admin user can...]
- [Requirement 2: Frontend user can...]
- [Requirement 3: When X happens, then Y...]

### Non-Functional Requirements
<!-- Performance, security, usability, etc. -->
- **Performance:** [Page load impact, database query limits, caching strategy]
- **Security:** [Nonce verification, capability checks, data sanitization, escaping output]
- **Usability:** [Admin UX requirements, user-facing interface]
- **Accessibility:** [WCAG compliance, keyboard navigation, screen reader support]
- **Responsive Design:** Must work on mobile, tablet, and desktop
- **WordPress Standards:** Must follow WordPress Coding Standards (WPCS)
- **Compatibility:** [WordPress version range, PHP version, plugin conflicts to avoid]
- **Multisite Support:** [Required / Not required / Partial support]
- **Internationalization:** [Translation-ready with text domain, RTL support if needed]

### Technical Constraints
<!-- What limitations exist? -->
- [Constraint 1: Must use WordPress core functions only (no external API dependencies)]
- [Constraint 2: Cannot modify core WordPress tables directly]
- [Constraint 3: Must be compatible with WordPress Multisite]
- [Constraint 4: Must follow WordPress Plugin Repository guidelines if submitting]

---

## 7. Data & Database Changes

### Database Schema Changes
<!-- If any database changes are needed -->
```sql
-- Use dbDelta for WordPress-compatible schema changes
-- Include table prefix variable handling: {$wpdb->prefix}
-- Follow WordPress table naming conventions
-- Example: New table creation
CREATE TABLE {$wpdb->prefix}plugin_table_name (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL,
  data_field varchar(255) NOT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY user_id (user_id)
) {$wpdb->get_charset_collate()};
```

### Data Model Updates
<!-- Changes to custom post types, taxonomies, options, user meta, etc. -->
```php
// Example: Register custom post type
register_post_type('custom_type', [
    'public' => true,
    'labels' => [...],
    'supports' => ['title', 'editor', 'thumbnail'],
    'has_archive' => true,
    'show_in_rest' => true, // For Gutenberg support
]);

// Example: Register custom taxonomy
register_taxonomy('custom_tax', 'custom_type', [
    'hierarchical' => true,
    'labels' => [...],
    'show_in_rest' => true,
]);

// Example: Options to add
add_option('plugin_setting_name', $default_value);

// Example: Custom table schema (use dbDelta in activation hook)
```

### Data Migration Plan
<!-- How to handle existing data -->
- [ ] [Migration step 1: Update option schema]
- [ ] [Migration step 2: Migrate post meta to new structure]
- [ ] [Migration step 3: Run dbDelta for table changes]
- [ ] [Data validation: Verify data integrity after migration]

### 🚨 MANDATORY: Database Update Version Control
**CRITICAL REQUIREMENT:** WordPress database changes must be version-controlled:

- [ ] **Step 1: Increment DB Version** - Update the plugin's database version constant
- [ ] **Step 2: Create Update Function** - Write `plugin_update_db_to_version_X()` function
- [ ] **Step 3: Use dbDelta** - Use WordPress `dbDelta()` function for schema changes (never raw ALTER TABLE)
- [ ] **Step 4: Version Check Hook** - Hook into `plugins_loaded` to check if update needed
- [ ] **Step 5: Test on Activation** - Ensure activation hook calls latest update function
- [ ] **Step 6: Create Rollback Plan** - Document manual SQL for reverting changes

**🛑 NEVER directly ALTER tables without version control and dbDelta!**

**Example Pattern:**
```php
// In main plugin file
define('PLUGIN_DB_VERSION', '1.2');

// Update check
add_action('plugins_loaded', 'plugin_check_db_version');
function plugin_check_db_version() {
    $current_version = get_option('plugin_db_version', '1.0');
    if (version_compare($current_version, PLUGIN_DB_VERSION, '<')) {
        plugin_update_database();
    }
}

// Update function
function plugin_update_database() {
    $current_version = get_option('plugin_db_version', '1.0');

    if (version_compare($current_version, '1.1', '<')) {
        plugin_update_db_to_1_1();
    }
    if (version_compare($current_version, '1.2', '<')) {
        plugin_update_db_to_1_2();
    }

    update_option('plugin_db_version', PLUGIN_DB_VERSION);
}
```

---

## 8. API & Backend Changes

### Data Access Pattern - CRITICAL ARCHITECTURE RULES

**🚨 MANDATORY: Follow WordPress patterns strictly:**

#### **DATABASE OPERATIONS** → Use `$wpdb` or WordPress APIs

**Direct Database Queries** → `$wpdb` global
- [ ] **Use $wpdb methods** - `$wpdb->get_results()`, `$wpdb->prepare()`, `$wpdb->insert()`
- [ ] **Always use prepare()** - Never concatenate SQL queries (SQL injection prevention)
- [ ] **Use placeholders** - `%s` for strings, `%d` for integers, `%f` for floats
- [ ] Example: `$wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}options WHERE option_name = %s", $option_name))`

**WordPress Data APIs** → Preferred over direct queries
- [ ] **Posts/Pages** - `get_posts()`, `wp_insert_post()`, `wp_update_post()`, `wp_delete_post()`
- [ ] **Post Meta** - `get_post_meta()`, `update_post_meta()`, `delete_post_meta()`
- [ ] **Options** - `get_option()`, `update_option()`, `delete_option()`
- [ ] **User Meta** - `get_user_meta()`, `update_user_meta()`, `delete_user_meta()`
- [ ] **Transients** - `get_transient()`, `set_transient()` (for caching)
- [ ] **Terms** - `wp_insert_term()`, `get_terms()`, `wp_set_post_terms()`

#### **ADMIN ACTIONS** → Use WordPress AJAX or Admin Post Handlers

**AJAX Handlers** → `wp_ajax_{action}` hooks
- [ ] **Admin AJAX** - `add_action('wp_ajax_my_action', 'my_ajax_handler')`
- [ ] **Frontend AJAX** - `add_action('wp_ajax_nopriv_my_action', 'my_ajax_handler')`
- [ ] **Security** - Always verify nonces: `check_ajax_referer('my_nonce_action')`
- [ ] **Capabilities** - Check user permissions: `current_user_can('manage_options')`
- [ ] **Response** - Use `wp_send_json_success()` and `wp_send_json_error()`

**Admin Post Handlers** → `admin_post_{action}` hooks
- [ ] **Admin Forms** - `add_action('admin_post_my_action', 'my_form_handler')`
- [ ] **Security** - Verify nonces: `check_admin_referer('my_action_nonce')`
- [ ] **Redirect** - Use `wp_safe_redirect()` after processing

#### **REST API** → `register_rest_route()` - For modern integrations

**REST API Endpoints** → Only for external integrations or Gutenberg blocks
- [ ] **Register Routes** - `register_rest_route('myplugin/v1', '/endpoint', [...])`
- [ ] **Permission Callbacks** - Always include `permission_callback`
- [ ] **Validation** - Use `validate_callback` and `sanitize_callback`
- [ ] Example: Gutenberg dynamic blocks, mobile apps, external integrations

❌ **DO NOT use REST API for:**
- [ ] ❌ Simple admin form submissions (use admin_post hooks)
- [ ] ❌ Traditional admin AJAX (use wp_ajax hooks)
- [ ] ❌ Internal plugin-to-plugin communication

#### **CRON JOBS** → `wp_schedule_event()` for background tasks
- [ ] **Schedule Events** - `wp_schedule_event(time(), 'hourly', 'my_cron_hook')`
- [ ] **Custom Intervals** - Use `cron_schedules` filter for custom intervals
- [ ] **Hook Action** - `add_action('my_cron_hook', 'my_cron_function')`
- [ ] **Cleanup** - Unschedule on plugin deactivation

#### **DECISION FLOWCHART - "Where should this code go?"**

```
📝 What are you building?
│
├─ 💾 Database Operations?
│  ├─ WordPress data (posts, users, options)?
│  │  └─ ✅ Use WordPress APIs: get_posts(), update_option(), etc.
│  └─ Custom table data?
│     └─ ✅ Use $wpdb with prepare(): $wpdb->get_results($wpdb->prepare(...))
│
├─ 🔄 Admin Form Submission?
│  ├─ Traditional page reload?
│  │  └─ ✅ Admin Post Hook: add_action('admin_post_my_action', ...)
│  └─ AJAX (no reload)?
│     └─ ✅ AJAX Hook: add_action('wp_ajax_my_action', ...)
│
├─ 🌐 External Integration / Gutenberg Block?
│  └─ ✅ REST API: register_rest_route('myplugin/v1', ...)
│
└─ ⏰ Background Processing?
   └─ ✅ WP Cron: wp_schedule_event() + add_action('my_cron_hook', ...)
```

### Admin Interface Changes
<!-- Settings pages, metaboxes, admin notices, etc. -->
- [ ] **Settings Pages** - Using Settings API or custom admin pages?
- [ ] **Metaboxes** - `add_meta_box()` for post edit screens
- [ ] **Admin Notices** - `add_action('admin_notices', ...)` for messages
- [ ] **Bulk Actions** - Custom bulk actions on list tables
- [ ] **Admin Columns** - Custom columns in post/user lists

### Frontend Changes
<!-- Shortcodes, blocks, template overrides, widgets -->
- [ ] **Shortcodes** - `add_shortcode('my_shortcode', 'my_shortcode_function')`
- [ ] **Gutenberg Blocks** - Block registration and React components
- [ ] **Template Overrides** - Loadable templates from theme
- [ ] **Widgets** - `register_widget()` for sidebar widgets
- [ ] **Enqueue Scripts/Styles** - `wp_enqueue_script()` / `wp_enqueue_style()`

### Security Checklist
<!-- WordPress-specific security measures -->
- [ ] **Nonce Verification** - All forms use `wp_nonce_field()` and `wp_verify_nonce()`
- [ ] **Capability Checks** - All admin actions check `current_user_can()`
- [ ] **Data Sanitization** - Use `sanitize_text_field()`, `sanitize_email()`, etc.
- [ ] **Output Escaping** - Use `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses()`
- [ ] **SQL Injection Prevention** - Always use `$wpdb->prepare()` with placeholders
- [ ] **CSRF Protection** - Nonces on all state-changing operations
- [ ] **XSS Prevention** - Escape all output, especially user-generated content

---

## 9. Frontend Changes

### Template Structure
<!-- WordPress template hierarchy and custom templates -->
- [ ] **Template Files** - `templates/` directory for loadable templates
- [ ] **Template Parts** - Reusable template parts via `get_template_part()`
- [ ] **Theme Overrides** - Templates that can be overridden in theme
- [ ] **Template Functions** - Helper functions for template rendering

**Template Organization Pattern:**
- Use `plugin-dir/templates/` for template files
- Allow theme overrides: Check theme folder first, fallback to plugin templates
- Example: `locate_template(['myplugin/template.php'], false) ?: plugin_dir_path(__FILE__) . 'templates/template.php'`

### Shortcode Requirements
<!-- Shortcodes to create -->
- [ ] **`[my_shortcode]`** - [Description and attributes]
- [ ] **Shortcode Security** - Sanitize attributes, escape output
- [ ] **Shortcode UI** - Consider using Shortcake for better UX

### Gutenberg Blocks (if applicable)
<!-- Block registration and components -->
- [ ] **Block Registration** - `register_block_type()` in PHP
- [ ] **Block Assets** - Compiled JS/CSS with `npm run build`
- [ ] **Block Attributes** - Define block schema
- [ ] **Dynamic Blocks** - Server-side rendering with `render_callback`
- [ ] **Block Patterns** - Reusable block patterns for users

### Asset Management
<!-- Scripts and styles -->
- [ ] **Admin Assets** - `admin_enqueue_scripts` hook
- [ ] **Frontend Assets** - `wp_enqueue_scripts` hook
- [ ] **Conditional Loading** - Only load assets on relevant pages
- [ ] **Script Dependencies** - Properly declare jQuery or other dependencies
- [ ] **Localization** - `wp_localize_script()` for AJAX URLs and nonces
- [ ] **Asset Versioning** - Use plugin version for cache busting

### 🚨 CRITICAL: Enqueue Assets Properly

**MANDATORY: Never hardcode script/style tags in templates**

#### Asset Enqueuing Pattern
- [ ] **✅ Use wp_enqueue_script():** Register and enqueue all JavaScript
- [ ] **✅ Use wp_enqueue_style():** Register and enqueue all CSS
- [ ] **✅ Declare Dependencies:** List jQuery, wp-element, etc. in dependency array
- [ ] **✅ Conditional Loading:** Check page/post type before enqueuing
- [ ] **✅ Localize Scripts:** Pass PHP data to JS via `wp_localize_script()`
- [ ] **❌ Never inline scripts/styles** unless absolutely necessary (use `wp_add_inline_script()` if needed)

#### Decision Flowchart - "How should I include this asset?"
```
📊 Do I need to add CSS/JS?
│
├─ 🎨 Stylesheet?
│  └─ ✅ Use wp_enqueue_style('handle', $url, $deps, $version)
│
├─ 📜 JavaScript?
│  ├─ Admin only?
│  │  └─ ✅ Hook: add_action('admin_enqueue_scripts', ...)
│  ├─ Frontend only?
│  │  └─ ✅ Hook: add_action('wp_enqueue_scripts', ...)
│  └─ Both?
│     └─ ✅ Hook both with conditional checks inside
│
└─ 🔄 Pass data from PHP to JS?
   └─ ✅ Use wp_localize_script('handle', 'objectName', $data)
```

---

## 10. Code Changes Overview

### 🚨 MANDATORY: Always Show High-Level Code Changes Before Implementation

**AI Agent Instructions:** Before presenting the task document for approval, you MUST provide a clear overview of the code changes you're about to make. This helps the user understand exactly what will be modified without having to approve first.

**Requirements:**
- [ ] **Always include this section** for any task that modifies existing code
- [ ] **Show actual code snippets** with before/after comparisons
- [ ] **Focus on key changes** - don't show every line, but show enough to understand the transformation
- [ ] **Use file paths and line counts** to give context about scope of changes
- [ ] **Explain the impact** of each major change

### Format to Follow:

#### 📂 **Current Implementation (Before)**
```php
// Show current code that will be changed
// Include file paths and key logic
// Focus on the parts that will be modified
// Example: includes/Admin/Settings.php
class Settings {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
    }
}
```

#### 📂 **After Refactor**
```php
// Show what the code will look like after changes
// Same files, but with new structure/logic
// Highlight the improvements
// Example: includes/Admin/Settings.php
class Settings {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings() {
        // New Settings API integration
    }
}
```

#### 🎯 **Key Changes Summary**
- [ ] **Change 1:** Brief description of what's being modified and why
- [ ] **Change 2:** Another major change with rationale
- [ ] **Files Modified:** List of files that will be changed
- [ ] **Impact:** How this affects the plugin behavior
- [ ] **WordPress Compatibility:** Any version-specific changes

**Note:** If no code changes are required (pure documentation/planning tasks), state "No code changes required" and explain what will be created or modified instead.

---

## 11. Implementation Plan

### Phase 1: Database Changes (If Required)
**Goal:** Prepare and apply database schema changes with safe rollback capability

- [ ] **Task 1.1:** Update Database Schema
  - Files: `includes/Database/Schema.php` or activation hook
  - Details: Define schema changes using dbDelta-compatible SQL
- [ ] **Task 1.2:** Increment Database Version
  - Files: Main plugin file (version constant)
  - Details: Update DB version constant and add version check hook
- [ ] **Task 1.3:** Create Update Function
  - Files: `includes/Database/Updates.php`
  - Details: Create `plugin_update_db_to_X_Y()` function with dbDelta
- [ ] **Task 1.4:** Test Migration
  - Details: Verify schema changes apply correctly on fresh install and update

### Phase 2: [Phase Name]
**Goal:** [What this phase accomplishes]

- [ ] **Task 2.1:** [Specific task with file paths]
  - Files: `includes/ClassName.php`, `templates/template-name.php`
  - Details: [Technical specifics]
- [ ] **Task 2.2:** [Another task]
  - Files: [Affected files]
  - Details: [Implementation notes]

### Phase 3: [Phase Name]
**Goal:** [What this phase accomplishes]

- [ ] **Task 3.1:** [Specific task]
- [ ] **Task 3.2:** [Another task]

### Phase 4: Basic Code Validation (AI-Only)
**Goal:** Run safe static analysis only - NEVER activate plugin or test in browser

- [ ] **Task 4.1:** Code Quality Verification
  - Files: All modified files
  - Details: Check WordPress Coding Standards (PHPCS with WPCS), syntax validation
- [ ] **Task 4.2:** Static Logic Review
  - Files: Modified business logic files
  - Details: Read code to verify logic correctness, security (nonces, escaping, sanitization)
- [ ] **Task 4.3:** File Content Verification (if applicable)
  - Files: Language files, configuration files, static data
  - Details: Verify file structure and format correctness (NO live WordPress testing)

🛑 **CRITICAL WORKFLOW CHECKPOINT**
After completing Phase 4, you MUST:
1. Present "Implementation Complete!" message (exact text from section 16)
2. Wait for user approval of code review
3. Execute comprehensive code review process
4. NEVER proceed to user testing without completing code review first

### Phase 5: Comprehensive Code Review (Mandatory)
**Goal:** Present implementation completion and request thorough code review

- [ ] **Task 5.1:** Present "Implementation Complete!" Message (MANDATORY)
  - Template: Use exact message from section 16, step 7
  - Details: STOP here and wait for user code review approval
- [ ] **Task 5.2:** Execute Comprehensive Code Review (If Approved)
  - Process: Follow step 8 comprehensive review checklist from section 16
  - Details: Read all files, verify requirements, check WordPress standards

### Phase 6: User WordPress Testing (Only After Code Review)
**Goal:** Request human testing in actual WordPress environment

- [ ] **Task 6.1:** Present AI Testing Results
  - Files: Summary of static analysis results
  - Details: Provide comprehensive results of all AI-verifiable checks
- [ ] **Task 6.2:** Request User WordPress Testing
  - Files: Specific WordPress testing checklist for user
  - Details:
    - Activate/deactivate plugin testing
    - Admin interface testing
    - Frontend display testing
    - Conflict testing with other plugins
    - Different user role testing
    - Edge case testing
- [ ] **Task 6.3:** Wait for User Confirmation
  - Details: Wait for user to complete WordPress testing and confirm results

...

---

## 12. Task Completion Tracking - MANDATORY WORKFLOW

### Task Completion Tracking - MANDATORY WORKFLOW
🚨 **CRITICAL: Real-time task completion tracking is mandatory**

- [ ] **🗓️ GET TODAY'S DATE FIRST** - Before adding any completion timestamps, use the `time` tool to get the correct current date (fallback to web search if time tool unavailable)
- [ ] **Update task document immediately** after each completed subtask
- [ ] **Mark checkboxes as [x]** with completion timestamp using ACTUAL current date (not assumed date)
- [ ] **Add brief completion notes** (file paths, key changes, etc.)
- [ ] **This serves multiple purposes:**
  - [ ] **Forces verification** - You must confirm you actually did what you said
  - [ ] **Provides user visibility** - Clear progress tracking throughout implementation
  - [ ] **Prevents skipped steps** - Systematic approach ensures nothing is missed
  - [ ] **Creates audit trail** - Documentation of what was actually completed
  - [ ] **Enables better debugging** - If issues arise, easy to see what was changed

### Example Task Completion Format
```
### Phase 1: Database Schema Update
**Goal:** Add custom table for storing validation results

- [x] **Task 1.1:** Create Schema Definition ✓ 2025-11-26
  - Files: `includes/Database/Schema.php` ✓
  - Details: Added feed_validations table with dbDelta-compatible SQL ✓
- [x] **Task 1.2:** Increment Database Version ✓ 2025-11-26
  - Files: `wpmr-product-feed-validator.php` (updated DB version to 1.1) ✓
  - Details: Added version check hook on plugins_loaded ✓
- [x] **Task 1.3:** Create Update Function ✓ 2025-11-26
  - Files: `includes/Database/Updates.php` ✓
  - Details: Created plugin_update_db_to_1_1() with dbDelta call ✓
```

---

## 13. File Structure & Organization

### New Files to Create
```
plugin-root/
├── plugin-name.php                      # Main plugin file (metadata, activation)
├── uninstall.php                        # Cleanup on plugin deletion
├── includes/
│   ├── Admin/
│   │   ├── Settings.php                 # Settings page(s)
│   │   └── MetaBoxes.php                # Post edit screen metaboxes
│   ├── Frontend/
│   │   ├── Shortcodes.php               # Shortcode handlers
│   │   └── Enqueue.php                  # Asset enqueuing
│   ├── Database/
│   │   ├── Schema.php                   # Database schema definitions
│   │   └── Updates.php                  # Version-based update functions
│   ├── API/
│   │   ├── AJAX.php                     # AJAX handlers
│   │   └── REST.php                     # REST API endpoints
│   └── Core/
│       ├── Activator.php                # Activation hooks
│       ├── Deactivator.php              # Deactivation hooks
│       └── Loader.php                   # Hook/filter loader
├── templates/
│   ├── admin/
│   │   └── settings-page.php            # Admin template
│   └── public/
│       └── display.php                  # Frontend template
├── assets/
│   ├── css/
│   │   ├── admin.css                    # Admin styles
│   │   └── public.css                   # Frontend styles
│   ├── js/
│   │   ├── admin.js                     # Admin scripts
│   │   └── public.js                    # Frontend scripts
│   └── images/
├── languages/
│   └── plugin-name.pot                  # Translation template
└── blocks/                              # Gutenberg blocks (if any)
    └── my-block/
        ├── block.json                   # Block metadata
        ├── index.js                     # Block editor JS
        └── style.css                    # Block styles
```

**File Organization Rules:**
- **Main Plugin File**: Metadata, constants, autoloader, activation/deactivation hooks
- **Includes**: All PHP classes organized by responsibility (Admin, Frontend, Database, etc.)
- **Templates**: All HTML/PHP templates, organized by context (admin vs public)
- **Assets**: All CSS, JS, images - organized by context
- **Languages**: Translation files (.pot, .po, .mo)
- **Blocks**: Gutenberg block assets (if using blocks)

#### **WordPress Coding Standards - CRITICAL RULES**

**🚨 MANDATORY: Follow WordPress Coding Standards (WPCS)**

**Naming Conventions:**
- [ ] **Functions**: `lowercase_with_underscores()` - Never camelCase for functions
- [ ] **Classes**: `ClassName` with underscores for namespacing: `Plugin_Name_Admin_Settings`
- [ ] **Constants**: `UPPERCASE_WITH_UNDERSCORES`
- [ ] **Files**: `lowercase-with-hyphens.php`
- [ ] **Hooks**: `prefix_hook_name` - Always prefix custom hooks

**File Headers:**
- [ ] **Plugin Main File**: Requires WordPress plugin header with metadata
- [ ] **All PHP Files**: Use `<?php` (no closing `?>` tag at end of file)
- [ ] **Docblocks**: PHPDoc style for all functions, classes, and file headers

**Security Patterns:**
```php
// ✅ GOOD: Nonce verification
if (!isset($_POST['my_nonce']) || !wp_verify_nonce($_POST['my_nonce'], 'my_action')) {
    wp_die('Security check failed');
}

// ✅ GOOD: Capability check
if (!current_user_can('manage_options')) {
    wp_die('Insufficient permissions');
}

// ✅ GOOD: Sanitize input
$user_input = sanitize_text_field($_POST['field_name']);

// ✅ GOOD: Escape output
echo esc_html($user_generated_content);
echo '<a href="' . esc_url($url) . '">' . esc_html($link_text) . '</a>';

// ✅ GOOD: Prepare SQL
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}table WHERE id = %d AND name = %s",
    $id,
    $name
));

// ❌ BAD: No escaping
echo $user_generated_content; // XSS vulnerability!

// ❌ BAD: SQL concatenation
$wpdb->query("SELECT * FROM {$wpdb->prefix}table WHERE id = $id"); // SQL injection!
```

### Files to Modify
- [ ] **`existing/file.php`** - [What changes to make]
- [ ] **`another/class.php`** - [Modifications needed]

### Dependencies to Add
```json
// composer.json (PHP dependencies)
{
  "require": {
    "php": ">=7.4",
    "composer/installers": "^1.0"
  },
  "require-dev": {
    "phpunit/phpunit": "^9.0",
    "wp-coding-standards/wpcs": "^2.3"
  }
}

// package.json (Build tools and asset dependencies)
{
  "devDependencies": {
    "@wordpress/scripts": "^26.0.0",
    "webpack": "^5.0.0"
  }
}
```

---

## 14. Potential Issues & Security Review

### Error Scenarios to Analyze
- [ ] **Error Scenario 1:** [What could go wrong when...]
  - **Code Review Focus:** [Which files/functions to examine for this issue]
  - **Potential Fix:** [If issue found, suggest this approach]
- [ ] **Error Scenario 2:** [Another potential failure point]
  - **Code Review Focus:** [Where to look for gaps in error handling]
  - **Potential Fix:** [Recommended solution if needed]

### WordPress-Specific Edge Cases
- [ ] **Plugin Conflicts:** Could this conflict with popular plugins? (WooCommerce, ACF, Yoast, etc.)
- [ ] **Theme Conflicts:** Are we using hooks that themes commonly override?
- [ ] **Multisite Issues:** Does this work correctly on WordPress Multisite?
- [ ] **User Role Edge Cases:** What happens with Subscriber, Contributor, Author, Editor, Admin roles?
- [ ] **Permalink Structure:** Does this work with default and custom permalink structures?
- [ ] **AJAX Failures:** What if AJAX requests fail or timeout?

### Security & Access Control Review
- [ ] **Nonce Verification:** Are all forms and AJAX requests using nonces?
  - **Check:** `wp_nonce_field()` in forms, `wp_verify_nonce()` in handlers
- [ ] **Capability Checks:** Are admin features restricted to appropriate user roles?
  - **Check:** `current_user_can('manage_options')` or appropriate capability
- [ ] **Data Sanitization:** Are all inputs sanitized before processing?
  - **Check:** `sanitize_text_field()`, `sanitize_email()`, `absint()`, etc.
- [ ] **Output Escaping:** Is all output properly escaped?
  - **Check:** `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses()` usage
- [ ] **SQL Injection Prevention:** Are all database queries using prepare()?
  - **Check:** All `$wpdb` queries use `$wpdb->prepare()` with placeholders
- [ ] **File Upload Security:** If handling uploads, are file types validated?
  - **Check:** `wp_check_filetype()`, proper mime type validation
- [ ] **Direct File Access:** Are all PHP files protected from direct access?
  - **Check:** `defined('ABSPATH') || exit;` at top of files

### AI Agent Analysis Approach
**Focus:** Review existing code to identify potential failure points and security gaps. When issues are found, provide specific recommendations with file paths and code examples. This is code analysis and gap identification - not writing tests or test procedures.

**Priority Order:**
1. **Critical:** Security and access control issues
2. **Important:** WordPress compatibility and plugin conflicts
3. **Nice-to-have:** UX improvements and enhanced error messaging

---

## 15. Deployment & Configuration

### Environment Variables / Constants
```php
// Add these to wp-config.php or define in plugin for configuration
define('PLUGIN_NAME_API_KEY', 'your_api_key_here');
define('PLUGIN_NAME_DEBUG', true); // Enable debug mode
```

### WordPress Version Requirements
- **Minimum WordPress Version:** [e.g., 5.8]
- **Tested Up To:** [e.g., 6.7]
- **Minimum PHP Version:** [e.g., 7.4]
- **Required PHP Extensions:** [e.g., json, mbstring]

### Plugin Dependencies
- **Required Plugins:** [None / WooCommerce / Advanced Custom Fields / etc.]
- **Optional Plugins:** [Plugins that enhance functionality if present]

### Server Requirements
- **PHP Memory Limit:** [e.g., 128M minimum]
- **Max Execution Time:** [e.g., 30s minimum for imports]
- **WordPress Multisite:** [Supported / Not supported / Network-only]

---

## 16. AI Agent Instructions

### Default Workflow - STRATEGIC ANALYSIS FIRST
🎯 **STANDARD OPERATING PROCEDURE:**
When a user requests any new feature, improvement, or significant change, your **DEFAULT BEHAVIOR** should be:

1. **EVALUATE STRATEGIC NEED** - Determine if multiple solutions exist or if it's straightforward
2. **STRATEGIC ANALYSIS** (if needed) - Present solution options with pros/cons and get user direction
3. **CREATE A TASK DOCUMENT** in `ai-docs/tasks/` using this template
4. **GET USER APPROVAL** of the task document
5. **IMPLEMENT THE FEATURE** only after approval

**DO NOT:** Present implementation plans in chat without creating a proper task document first.
**DO:** Always create comprehensive task documentation that can be referenced later.
**DO:** Present strategic options when multiple viable approaches exist.

### Communication Preferences
- [ ] Ask for clarification if requirements are unclear
- [ ] Provide regular progress updates
- [ ] Flag any blockers or concerns immediately
- [ ] Suggest improvements or alternatives when appropriate
- [ ] Alert user to potential plugin conflicts or WordPress compatibility issues

### Implementation Approach - CRITICAL WORKFLOW
🚨 **MANDATORY: Always follow this exact sequence:**

1. **EVALUATE STRATEGIC NEED FIRST (Required)**
   - [ ] **Assess complexity** - Is this a straightforward change or are there multiple viable approaches?
   - [ ] **Review the criteria** in "Strategic Analysis & Solution Options" section
   - [ ] **Decision point**: Skip to step 3 if straightforward, proceed to step 2 if strategic analysis needed

2. **STRATEGIC ANALYSIS SECOND (If needed)**
   - [ ] **Present solution options** with pros/cons analysis for each approach
   - [ ] **Include implementation complexity and risk levels** for each option
   - [ ] **Consider WordPress compatibility** and potential plugin conflicts
   - [ ] **Provide clear recommendation** with rationale
   - [ ] **Wait for user decision** on preferred approach before proceeding
   - [ ] **Document approved strategy** for inclusion in task document

3. **CREATE TASK DOCUMENT THIRD (Required)**
   - [ ] **Create a new task document** in the `ai-docs/tasks/` directory using this template
   - [ ] **Fill out all sections** with specific details for the requested feature
   - [ ] **Include strategic analysis** (if conducted) in the appropriate section
   - [ ] **🔢 FIND LATEST TASK NUMBER**: Use appropriate tool to examine ai-docs/tasks/ and find the highest numbered task file
   - [ ] **Name the file** using the pattern `XXX_feature_name.md` (where XXX is the next incremental number)
   - [ ] **🚨 MANDATORY: POPULATE CODE CHANGES OVERVIEW**: Always read existing files and show before/after code snippets in section 10
   - [ ] **Present a summary** of the task document to the user for review

4. **PRESENT IMPLEMENTATION OPTIONS (Required)**
   - [ ] **After incorporating user feedback**, present these 3 exact options:

   **👤 IMPLEMENTATION OPTIONS:**

   **A) Preview High-Level Code Changes**
   Would you like me to show you detailed code snippets and specific changes before implementing? I'll walk through exactly what files will be modified and show before/after code examples.

   **B) Proceed with Implementation**
   Ready to begin implementation? Say "Approved" or "Go ahead" and I'll start implementing phase by phase.

   **C) Provide More Feedback**
   Have questions or want to modify the approach? I can adjust the plan based on additional requirements or concerns.

   - [ ] **Wait for explicit user choice** (A, B, or C) - never assume or default
   - [ ] **If A chosen**: Provide detailed code snippets showing exact changes planned
   - [ ] **If B chosen**: Begin phase-by-phase implementation immediately
   - [ ] **If C chosen**: Address feedback and re-present options

5. **IMPLEMENT PHASE-BY-PHASE (Only after Option B approval)**

   **MANDATORY PHASE WORKFLOW:**

   For each phase, follow this exact pattern:

   a. **Execute Phase Completely** - Complete all tasks in current phase
   b. **Update Task Document** - Mark all completed tasks as [x] with timestamps
   c. **Provide Specific Phase Recap** using this format:

   ```
   ✅ **Phase [X] Complete - [Phase Name]**
   - Modified [X] files with [Y] total line changes
   - Key changes: [specific file paths and what was modified]
   - Files updated:
     • includes/Admin/Settings.php (+25 lines): Added Settings API integration
     • templates/admin/settings-page.php (-5 lines, +15 lines): Updated form structure
   - Commands executed: [list any commands run]
   - PHPCS status: ✅ All files pass WordPress Coding Standards / ❌ [specific issues]

   **🔄 Next: Phase [X+1] - [Phase Name]**
   - Will modify: [specific files]
   - Changes planned: [brief description]
   - Estimated scope: [number of files/changes expected]

   **Say "proceed" to continue to Phase [X+1]**
   ```

   d. **Wait for "proceed"** before starting next phase
   e. **Repeat for each phase** until all implementation complete
   f. **🚨 CRITICAL:** After final implementation phase, you MUST proceed to Phase 5 (Comprehensive Code Review) before any user testing

   **🚨 PHASE-SPECIFIC REQUIREMENTS:**
   - [ ] **Real-time task completion tracking** - Update task document immediately after each subtask
   - [ ] **Mark checkboxes as [x]** with completion timestamps
   - [ ] **Add specific completion notes** (file paths, line counts, key changes)
   - [ ] **Run PHPCS on modified files** (static analysis only - no WordPress activation)
   - [ ] **🚨 MANDATORY: For ANY database changes, increment DB version and create update function**
     - [ ] Update DB version constant in main plugin file
     - [ ] Create version-specific update function using dbDelta
     - [ ] Add version check hook on plugins_loaded
     - [ ] Document manual rollback SQL if needed
   - [ ] **Always add nonce verification and capability checks** for admin actions
   - [ ] **Always sanitize input and escape output** according to WordPress standards
   - [ ] **🚨 MANDATORY WORKFLOW SEQUENCE:** After implementation phases, follow this exact order:
     - [ ] **Phase 4 Complete** → Present "Implementation Complete!" message (section 16, step 7)
     - [ ] **Wait for user approval** → Execute comprehensive code review (section 16, step 8)
     - [ ] **Code review complete** → ONLY THEN request user WordPress testing
     - [ ] **NEVER skip comprehensive code review** - Phase 4 basic validation ≠ comprehensive review
   - [ ] **NEVER plan WordPress testing as AI task** - always mark as "👤 USER TESTING" and wait for user confirmation

6. **VERIFY WORDPRESS STANDARDS COMPLIANCE (For all changes)**
   - [ ] **Check coding standards** using PHPCS with WPCS ruleset
   - [ ] **Verify security patterns** - nonces, capability checks, sanitization, escaping
   - [ ] **Check hook naming** - proper prefixing on custom hooks
   - [ ] **Verify file structure** - proper directory organization
   - [ ] **Check internationalization** - all strings wrapped in translation functions

7. **FINAL CODE REVIEW RECOMMENDATION (Mandatory after all phases)**
   - [ ] **Present this exact message** to user after all implementation complete:

   ```
   🎉 **Implementation Complete!**

   All phases have been implemented successfully. I've made changes to [X] files across [Y] phases.

   **📋 I recommend doing a thorough code review of all changes to ensure:**
   - No mistakes were introduced
   - All goals were achieved
   - WordPress coding standards followed
   - Security best practices implemented (nonces, escaping, sanitization)
   - Everything will work as expected in WordPress

   **Would you like me to proceed with the comprehensive code review?**

   This review will include:
   - Verifying all changes match the intended goals
   - Running PHPCS (WordPress Coding Standards) on all modified files
   - Checking for security issues (XSS, SQL injection, CSRF)
   - Confirming all requirements were met
   ```

   - [ ] **Wait for user approval** of code review
   - [ ] **If approved**: Execute comprehensive code review process below

8. **COMPREHENSIVE CODE REVIEW PROCESS (If user approves)**
   - [ ] **Read all modified files** and verify changes match task requirements exactly
   - [ ] **Run PHPCS with WPCS** on all modified files (if configured)
   - [ ] **Security audit** - verify nonces, capability checks, sanitization, escaping
   - [ ] **WordPress standards check** - naming conventions, file headers, hook prefixes
   - [ ] **Check for common issues** - SQL injection, XSS, CSRF vulnerabilities
   - [ ] **Verify all success criteria** from task document are met
   - [ ] **Provide detailed review summary** using this format:

   ```
   ✅ **Code Review Complete**

   **Files Reviewed:** [list all modified files with line counts]
   **PHPCS Status:** ✅ All files pass WordPress Coding Standards / ❌ [specific issues found]
   **Security Audit:**
     - ✅ Nonce verification implemented / ❌ [missing locations]
     - ✅ Capability checks present / ❌ [missing checks]
     - ✅ Input sanitization complete / ❌ [unsanitized inputs]
     - ✅ Output escaping applied / ❌ [unescaped output]
     - ✅ SQL queries use prepare() / ❌ [unsafe queries]
   **WordPress Standards:** ✅ Follows WPCS / ❌ [specific violations]
   **Requirements Met:** ✅ All success criteria achieved / ❌ [missing requirements]

   **Summary:** [brief summary of what was accomplished and verified]
   **Confidence Level:** High/Medium/Low - [specific reasoning]
   **Recommendations:** [any follow-up suggestions or improvements]
   ```

### What Constitutes "Explicit User Approval"

#### For Strategic Analysis
**✅ STRATEGIC APPROVAL RESPONSES (Proceed to task document creation):**
- "Option 1 looks good"
- "Go with your recommendation"
- "I prefer Option 2"
- "Proceed with [specific option]"
- "That approach works"
- "Yes, use that strategy"

#### For Implementation Options (A/B/C Choice)
**✅ OPTION A RESPONSES (Show detailed code previews):**
- "A" or "Option A"
- "Preview the changes"
- "Show me the code changes"
- "Let me see what will be modified"
- "Walk me through the changes"

**✅ OPTION B RESPONSES (Start implementation immediately):**
- "B" or "Option B"
- "Proceed" or "Go ahead"
- "Approved" or "Start implementation"
- "Begin" or "Execute the plan"
- "Looks good, implement it"

**✅ OPTION C RESPONSES (Provide more feedback):**
- "C" or "Option C"
- "I have questions about..."
- "Can you modify..."
- "What about..." or "How will you handle..."
- "I'd like to change..."
- "Wait, let me think about..."

#### For Phase Continuation
**✅ PHASE CONTINUATION RESPONSES:**
- "proceed"
- "continue"
- "next phase"
- "go ahead"
- "looks good"

**❓ CLARIFICATION NEEDED (Do NOT continue to next phase):**
- Questions about the completed phase
- Requests for changes to completed work
- Concerns about the implementation
- No response or silence

#### For Final Code Review
**✅ CODE REVIEW APPROVAL:**
- "proceed"
- "yes, review the code"
- "go ahead with review"
- "approved"

🛑 **NEVER start coding without explicit A/B/C choice from user!**
🛑 **NEVER continue to next phase without "proceed" confirmation!**
🛑 **NEVER skip comprehensive code review after implementation phases!**
🛑 **NEVER proceed to user testing without completing code review first!**
🛑 **NEVER run database updates without version control and dbDelta!**
🛑 **NEVER test in WordPress environment - user will activate and test manually!**

### 🚨 CRITICAL: Command Execution Rules
**NEVER run WordPress activation or testing commands - the user will test in their WordPress install!**

**❌ FORBIDDEN COMMANDS (User tests these manually):**
- Any WordPress CLI commands (`wp plugin activate`, `wp db migrate`, etc.)
- Any commands that require a running WordPress instance
- Any browser-based testing or UI verification
- Any database operations on live WordPress database

**✅ ALLOWED COMMANDS (Safe static analysis only):**
- `composer install` - Install PHP dependencies
- `npm install` / `npm run build` - Build assets
- `vendor/bin/phpcs` - WordPress Coding Standards check (if PHPCS configured)
- `vendor/bin/phpunit` - Run unit tests (if configured)
- File reading/analysis tools

**🎯 VALIDATION STRATEGY:**
- Use PHPCS for WordPress Coding Standards compliance
- Read files to verify logic, security patterns, and structure
- Check syntax and dependencies statically
- Let the user handle all WordPress activation and testing manually

### Code Quality Standards
- [ ] Follow WordPress Coding Standards (WPCS)
- [ ] **🚨 MANDATORY: Security First**
  - [ ] **All forms have nonces**: `wp_nonce_field()` and `wp_verify_nonce()`
  - [ ] **All admin actions check capabilities**: `current_user_can()`
  - [ ] **All input is sanitized**: `sanitize_text_field()`, `sanitize_email()`, etc.
  - [ ] **All output is escaped**: `esc_html()`, `esc_attr()`, `esc_url()`
  - [ ] **All SQL uses prepare()**: `$wpdb->prepare()` with placeholders
- [ ] **🚨 MANDATORY: WordPress Naming Conventions**
  - [ ] **Functions**: `lowercase_with_underscores()`
  - [ ] **Classes**: `Plugin_Name_Class_Name` (underscores, not camelCase)
  - [ ] **Hooks**: `prefix_hook_name` (always prefixed)
  - [ ] **Files**: `lowercase-with-hyphens.php`
- [ ] **🚨 MANDATORY: Write Professional Comments - Never Historical Comments**
  - [ ] **❌ NEVER write change history**: "Fixed this", "Removed function", "Updated to use new API"
  - [ ] **❌ NEVER write migration artifacts**: "Moved from X", "Previously was Y"
  - [ ] **✅ ALWAYS explain business logic**: "Calculate discount for premium users", "Validate permissions before deletion"
  - [ ] **✅ Write for future developers** - explain what/why the code does what it does, not what you changed
  - [ ] **Remove unused code completely** - don't leave comments explaining what was removed
- [ ] **🚨 MANDATORY: Use early returns to keep code clean and readable**
  - [ ] **Prioritize early returns** over nested if-else statements
  - [ ] **Validate inputs early** and return immediately for invalid cases
  - [ ] **Handle error conditions first** before proceeding with main logic
  - [ ] **Exit early for edge cases** to reduce nesting and improve readability
- [ ] **🚨 MANDATORY: Database version control for all schema changes**
  - [ ] Increment database version constant
  - [ ] Create version-specific update function
  - [ ] Use dbDelta for all schema changes
  - [ ] Add version check on plugins_loaded
- [ ] **Internationalization**: All strings use translation functions
  - [ ] `__('text', 'text-domain')` for translation
  - [ ] `_e('text', 'text-domain')` for echo translation
  - [ ] `esc_html__()`, `esc_html_e()` for escaped translations
- [ ] **Proper error handling** with WordPress patterns
- [ ] **Accessibility**: Proper ARIA labels, keyboard navigation, screen reader support

### Architecture Compliance
- [ ] **✅ VERIFY: Used correct WordPress patterns**
  - [ ] Database operations → WordPress APIs or $wpdb->prepare()
  - [ ] Admin actions → wp_ajax or admin_post hooks with nonces
  - [ ] Frontend → Shortcodes, blocks, or template overrides
  - [ ] Cron jobs → wp_schedule_event() and custom hooks
- [ ] **✅ VERIFY: Security implementation**
  - [ ] All forms have nonce verification
  - [ ] All admin actions have capability checks
  - [ ] All input is sanitized
  - [ ] All output is escaped
  - [ ] All SQL uses prepare() with placeholders
- [ ] **✅ VERIFY: WordPress Coding Standards compliance**
  - [ ] Naming conventions followed
  - [ ] File headers present with proper docblocks
  - [ ] Proper hook prefixing
  - [ ] Translation-ready strings
- [ ] **❌ AVOID: Common WordPress pitfalls**
  - [ ] Direct database table modifications without dbDelta
  - [ ] Hardcoded script/style tags instead of wp_enqueue
  - [ ] SQL concatenation instead of prepare()
  - [ ] Missing nonces on forms
  - [ ] Missing capability checks on admin actions
  - [ ] Unescaped output (XSS vulnerability)
- [ ] **🔍 DOUBLE-CHECK: Does this follow WordPress best practices?**

---

## 17. Notes & Additional Context

### Research Links
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [Plugin Security Best Practices](https://developer.wordpress.org/plugins/security/)
- [WordPress Data Validation](https://developer.wordpress.org/apis/security/data-validation/)

### **⚠️ Common WordPress Security Pitfalls to Avoid**

**❌ NEVER DO:**
- Process form data without nonce verification
- Execute admin actions without capability checks
- Output user data without escaping
- Concatenate SQL queries instead of using prepare()
- Trust user input without sanitization
- Include files based on user input without validation

**✅ ALWAYS DO:**
- Use `wp_verify_nonce()` on all form submissions
- Check `current_user_can()` before admin operations
- Escape all output with `esc_html()`, `esc_attr()`, `esc_url()`
- Use `$wpdb->prepare()` for all database queries
- Sanitize all input with appropriate sanitize functions
- Validate and whitelist any file includes

---

## 18. Second-Order Consequences & Impact Analysis

### AI Analysis Instructions
🔍 **MANDATORY: The AI agent must analyze this section thoroughly before implementation**

Before implementing any changes, the AI must systematically analyze potential second-order consequences and alert the user to any significant impacts. This analysis should identify ripple effects that might not be immediately obvious but could cause problems later.

### Impact Assessment Framework

#### 1. **Breaking Changes Analysis**
- [ ] **Database Schema Changes:** Will this break existing installations on update?
- [ ] **Function Signature Changes:** Will this break other plugins that hook into this plugin?
- [ ] **Hook Name Changes:** Are we removing or renaming any action/filter hooks?
- [ ] **Template Changes:** Will theme overrides break with new template structure?
- [ ] **Shortcode Changes:** Will existing shortcode usage break?

#### 2. **WordPress Compatibility Assessment**
- [ ] **WordPress Version Compatibility:** Does this work with stated minimum WordPress version?
- [ ] **PHP Version Compatibility:** Does this work with stated minimum PHP version?
- [ ] **Multisite Compatibility:** Will this work on WordPress Multisite?
- [ ] **Plugin Conflicts:** Could this conflict with popular plugins?
- [ ] **Theme Conflicts:** Could this conflict with popular themes?

#### 3. **Performance Implications**
- [ ] **Database Query Impact:** Will new queries slow down page loads?
- [ ] **Asset Size:** Are we significantly increasing JS/CSS payload?
- [ ] **Cron Jobs:** Will scheduled tasks impact server performance?
- [ ] **Memory Usage:** Could this hit PHP memory limits on shared hosting?
- [ ] **Caching Impact:** Does this work with popular caching plugins?

#### 4. **Security Considerations**
- [ ] **New Attack Surfaces:** Does this introduce new input points that need validation?
- [ ] **Data Exposure:** Are there risks of exposing sensitive data?
- [ ] **Permission Escalation:** Could users access features they shouldn't?
- [ ] **File Upload Risks:** If handling uploads, are security measures adequate?
- [ ] **AJAX Security:** Are AJAX handlers properly protected with nonces and caps?

#### 5. **User Experience Impacts**
- [ ] **Admin Interface Changes:** Will changes confuse existing users?
- [ ] **Data Migration:** Do users need to migrate existing data?
- [ ] **Feature Removal:** Are any existing features being removed?
- [ ] **Workflow Changes:** Will this disrupt familiar user workflows?
- [ ] **Mobile Usability:** Does this work well on mobile devices?

#### 6. **Maintenance Burden**
- [ ] **Code Complexity:** Are we making the codebase harder to maintain?
- [ ] **WordPress Update Compatibility:** Will future WordPress updates break this?
- [ ] **Third-Party Dependencies:** Are we adding external dependencies?
- [ ] **Documentation Needs:** What new documentation is required?

### Critical Issues Identification

#### 🚨 **RED FLAGS - Alert User Immediately**
These issues must be brought to the user's attention before implementation:
- [ ] **Database Migration Required:** Changes requiring data migration on update
- [ ] **Breaking Hook Changes:** Modifications that break other plugins depending on this plugin
- [ ] **WordPress Version Requirement Change:** Minimum WordPress version increase
- [ ] **Security Vulnerabilities:** New attack vectors or data exposure risks
- [ ] **Data Loss Risk:** Risk of losing or corrupting existing user data
- [ ] **Multisite Breaking Changes:** Changes that break on WordPress Multisite

#### ⚠️ **YELLOW FLAGS - Discuss with User**
These issues should be discussed but may not block implementation:
- [ ] **Increased Complexity:** Changes making codebase harder to understand
- [ ] **Plugin Conflicts:** Potential conflicts with popular plugins
- [ ] **Performance Impact:** Changes that may slow down site
- [ ] **Admin UI Changes:** Modifications changing familiar admin workflows

### Mitigation Strategies

#### Database Changes
- [ ] **Version Control:** Use database version constant and update functions
- [ ] **Backward Compatibility:** Use dbDelta to safely modify existing tables
- [ ] **Rollback Documentation:** Document manual SQL for reverting changes
- [ ] **Migration Testing:** Test migration from previous version

#### Hook Changes
- [ ] **Deprecation Period:** Keep old hooks with _deprecated_hook() notices
- [ ] **Documentation:** Document all hook changes in changelog
- [ ] **Version Bump:** Increment major version for breaking changes

#### WordPress Compatibility
- [ ] **Version Testing:** Test with minimum and maximum stated WordPress versions
- [ ] **Fallback Code:** Use function_exists() / class_exists() checks for newer WP functions
- [ ] **Multisite Testing:** Test both single-site and multisite installations

### AI Agent Checklist

Before presenting the task document to the user, the AI agent must:
- [ ] **Complete Impact Analysis:** Fill out all sections of the impact assessment
- [ ] **Identify Critical Issues:** Flag any red or yellow flag items
- [ ] **Propose Mitigation:** Suggest specific mitigation strategies for identified risks
- [ ] **Alert User:** Clearly communicate any significant second-order impacts
- [ ] **Recommend Alternatives:** If high-risk impacts are identified, suggest alternative approaches

### Example Analysis Template

```
🔍 **SECOND-ORDER IMPACT ANALYSIS:**

**Breaking Changes Identified:**
- Database schema change adds new table (requires activation on update)
- Hook name changed from `old_hook` to `new_hook` (breaks plugins depending on old hook)

**WordPress Compatibility:**
- Minimum WordPress version increasing from 5.0 to 5.8 (drops support for older installs)
- Uses wp_timezone() which requires WordPress 5.3+

**Performance Implications:**
- New admin query adds JOIN that may slow down on large sites (10,000+ posts)
- Asset bundle increased by 25KB (consider lazy loading)

**Security Considerations:**
- New AJAX endpoint needs nonce and capability check (already planned)
- File upload feature requires mime type validation (already planned)

**User Experience Impacts:**
- Admin menu structure reorganized (existing users need to relearn navigation)
- Shortcode attribute names changed (existing shortcodes break without migration)

**Mitigation Recommendations:**
- Keep old hook with _deprecated_hook() for backward compatibility
- Add database version check and migration function
- Document breaking changes clearly in changelog
- Consider admin notice explaining menu changes
- Provide shortcode migration tool or backward compatibility

**🚨 USER ATTENTION REQUIRED:**
This update includes breaking changes that will require existing users to:
1. Re-save plugin settings after update
2. Update any shortcodes using old attribute names
3. Other plugins hooking into `old_hook` will need updates

Should we implement backward compatibility or is a breaking change acceptable?
```

---

*Template Version: 1.0*
*Last Updated: 2025-11-26*
*Created By: AI Assistant*
*Based on: Next.js Task Template by Brandon Hancock*
