# WordPress AI Task Template

> **Task Document:** XML Feed Validation Enhancements - Declaration, Encoding, and Structure Checks

---

## 1. Task Overview

### Task Title
**Title:** Enhanced XML Feed Validation - Declaration, Encoding, and Structure Verification

### Goal Statement
**Goal:** Implement comprehensive XML validation checks to detect and report issues with XML declarations, file encoding, and XML structure before processing product feed data. This ensures feeds meet basic XML standards and provides clear diagnostic feedback to users when feeds are malformed, improving the reliability and user experience of the validator.

---

## 2. Strategic Analysis & Solution Options

### When to Use Strategic Analysis
<!-- This is a straightforward enhancement with a clear implementation path -->
**❌ SKIP STRATEGIC ANALYSIS** - This is a straightforward enhancement with established patterns in the codebase. The implementation approach is clear: extend the existing `Fetcher` and `Parser` services with additional validation checks.

---

## 3. Project Analysis & Current State

### Technology & Architecture
- **Plugin Type:** Single WordPress plugin
- **WordPress Version:** Minimum 5.8, tested up to 6.7
- **PHP Version:** 7.4+
- **Database:** WordPress default tables + custom options
- **Admin Interface:** Settings API + REST API endpoints
- **Frontend Display:** Shortcodes + embeddable validator
- **Build Tools:** None (pure PHP)
- **CSS Framework:** Custom CSS
- **JavaScript:** Vanilla JS for admin interface
- **Key Architectural Patterns:** OOP with namespaces (`WPMR\PFV`), service classes, REST API controllers
- **Existing Custom Post Types:** None
- **Existing Taxonomies:** None
- **REST API Endpoints:** 
  - `/wpmr-pfv/v1/validate` - Main validation endpoint
  - `/wpmr-pfv/v1/full-scan` - Full feed scan
  - `/wpmr-pfv/v1/rules/*` - Rules management
- **AJAX Handlers:** None (uses REST API)
- **Cron Jobs:** None

### Current State
The plugin currently performs basic XML validation:

**Fetcher.php (lines 101-107):**
- ✅ Checks for XML Content-Type header
- ✅ Checks for XML declaration (`<?xml`) presence
- ❌ Does NOT validate encoding attribute in declaration
- ❌ Does NOT validate XML version
- ❌ Does NOT check for BOM issues

**Parser.php (lines 26-29):**
- ✅ Uses XMLReader with `LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING`
- ✅ Detects if XML is not well-formed
- ❌ Does NOT extract or validate encoding from declaration
- ❌ Does NOT validate XML structure beyond well-formedness
- ❌ Does NOT check for namespace issues

### Existing WordPress Hooks Analysis
- **Core WordPress Hooks Used:** None (standalone service classes)
- **Custom Hooks Defined:** None in validation services
- **Hook Priorities:** N/A
- **Plugin Dependencies:** None

**🔍 Hook Coverage Analysis:**
- Services are called directly from REST API controllers
- No WordPress core functionality dependencies for XML validation
- No known conflicts with common plugins

---

## 4. Context & Problem Definition

### Problem Statement
Users are uploading XML product feeds that may have:
1. **Missing or invalid XML declarations** - Feeds without `<?xml version="1.0" encoding="UTF-8"?>` or with incorrect versions
2. **Encoding mismatches** - Declaration says UTF-8 but file is actually ISO-8859-1, or BOM markers present
3. **Structural issues** - Malformed XML that passes basic checks but has namespace problems or invalid root elements

Currently, these issues are either:
- Not detected until parsing fails (poor user experience)
- Detected but reported as generic "XML is not well-formed" errors (not actionable)
- Silently handled by XMLReader's lenient parsing (potential data corruption)

This leads to confusion and makes debugging difficult for users.

### Success Criteria
- [x] **XML Declaration Validation:** Detect missing `<?xml` declaration and report as warning
- [x] **XML Version Check:** Validate version attribute is "1.0" or "1.1" and report if invalid
- [x] **Encoding Detection:** Extract and validate encoding attribute from declaration
- [x] **Encoding Verification:** Detect BOM markers and verify they match declared encoding
- [x] **Structure Validation:** Verify root element is `<rss>` or `<feed>` (Atom)
- [x] **Namespace Validation:** Check for required namespaces (g: namespace for Google feeds)
- [x] **Clear Diagnostics:** All validation failures reported with severity (error/warning) and actionable messages

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
- **FR1:** Validate XML declaration presence and report warning if missing
- **FR2:** Extract and validate XML version attribute (must be "1.0" or "1.1")
- **FR3:** Extract and validate encoding attribute from XML declaration
- **FR4:** Detect BOM (Byte Order Mark) in file and verify it matches declared encoding
- **FR5:** Validate root element is `<rss>` (RSS 2.0) or `<feed>` (Atom)
- **FR6:** Check for presence of required Google namespace (`xmlns:g="http://base.google.com/ns/1.0"`)
- **FR7:** Report all validation issues with appropriate severity (error vs warning)
- **FR8:** Include validation results in existing diagnostics array

### Non-Functional Requirements
- **Performance:** Validation must complete within existing timeout limits (no significant overhead)
- **Security:** All validation uses safe PHP functions (no eval, no external calls)
- **Usability:** Error messages must be clear and actionable for non-technical users
- **Accessibility:** N/A (backend validation only)
- **Responsive Design:** N/A (backend validation only)
- **WordPress Standards:** Must follow WordPress Coding Standards (WPCS)
- **Compatibility:** PHP 7.4+, WordPress 5.8+
- **Multisite Support:** Not required
- **Internationalization:** All error messages must be translatable with text domain

### Technical Constraints
- Must use PHP's built-in XML functions (XMLReader, SimpleXML)
- Cannot modify WordPress core
- Must maintain existing REST API response structure
- Must not break existing validation flow

---

## 7. Data & Database Changes

### Database Schema Changes
**N/A** - No database changes required

### Data Model Updates
**N/A** - No data model changes required

### Data Migration Plan
**N/A** - No migration required

---

## 8. API & Backend Changes

### Service Layer Changes

#### Fetcher.php Enhancements
**Current Implementation (lines 101-107):**
```php
$is_xml_ct = stripos( $ct, 'xml' ) !== false;
$has_xml_decl = strpos( ltrim( $body ), '<?xml' ) === 0;
if ( ! $is_xml_ct && ! $has_xml_decl ) {
    $diagnostics[] = [ 'severity' => 'error', 'code' => 'content_type', 'message' => __( 'Content-Type not XML and no XML declaration found.', 'wpmr-product-feed-validator' ) ];
} elseif ( ! $is_xml_ct ) {
    $diagnostics[] = [ 'severity' => 'warning', 'code' => 'content_type_warning', 'message' => __( 'Content-Type header does not indicate XML.', 'wpmr-product-feed-validator' ) ];
}
```

**After Enhancement:**
```php
// Enhanced XML declaration and encoding validation
$validation_result = self::validate_xml_declaration( $body );
if ( ! empty( $validation_result['diagnostics'] ) ) {
    $diagnostics = array_merge( $diagnostics, $validation_result['diagnostics'] );
}

// Existing Content-Type check
$is_xml_ct = stripos( $ct, 'xml' ) !== false;
if ( ! $is_xml_ct && ! $validation_result['has_declaration'] ) {
    $diagnostics[] = [ 'severity' => 'error', 'code' => 'content_type', 'message' => __( 'Content-Type not XML and no XML declaration found.', 'wpmr-product-feed-validator' ) ];
} elseif ( ! $is_xml_ct ) {
    $diagnostics[] = [ 'severity' => 'warning', 'code' => 'content_type_warning', 'message' => __( 'Content-Type header does not indicate XML.', 'wpmr-product-feed-validator' ) ];
}
```

**New Method to Add:**
```php
/**
 * Validate XML declaration and encoding.
 *
 * @param string $xml XML content to validate
 * @return array{has_declaration:bool,version:?string,encoding:?string,diagnostics:array}
 */
protected static function validate_xml_declaration( string $xml ) {
    $diagnostics = [];
    $has_declaration = false;
    $version = null;
    $encoding = null;

    // Check for BOM
    $bom_detected = self::detect_bom( $xml );
    if ( $bom_detected ) {
        $diagnostics[] = [
            'severity' => 'warning',
            'code' => 'bom_detected',
            'message' => sprintf(
                __( 'BOM (Byte Order Mark) detected: %s. This may cause parsing issues.', 'wpmr-product-feed-validator' ),
                $bom_detected
            ),
        ];
    }

    // Extract XML declaration
    $trimmed = ltrim( $xml );
    if ( strpos( $trimmed, '<?xml' ) === 0 ) {
        $has_declaration = true;
        
        // Extract declaration attributes
        if ( preg_match( '/^<\?xml\s+([^?]*)\?>/i', $trimmed, $matches ) ) {
            $decl = $matches[1];
            
            // Extract version
            if ( preg_match( '/version\s*=\s*["\']([^"\']+)["\']/i', $decl, $ver_match ) ) {
                $version = $ver_match[1];
                if ( ! in_array( $version, [ '1.0', '1.1' ], true ) ) {
                    $diagnostics[] = [
                        'severity' => 'error',
                        'code' => 'invalid_xml_version',
                        'message' => sprintf(
                            __( 'Invalid XML version "%s". Must be "1.0" or "1.1".', 'wpmr-product-feed-validator' ),
                            esc_html( $version )
                        ),
                    ];
                }
            } else {
                $diagnostics[] = [
                    'severity' => 'warning',
                    'code' => 'missing_xml_version',
                    'message' => __( 'XML declaration missing version attribute.', 'wpmr-product-feed-validator' ),
                ];
            }
            
            // Extract encoding
            if ( preg_match( '/encoding\s*=\s*["\']([^"\']+)["\']/i', $decl, $enc_match ) ) {
                $encoding = strtoupper( $enc_match[1] );
                
                // Validate encoding value
                $valid_encodings = [ 'UTF-8', 'UTF-16', 'ISO-8859-1', 'US-ASCII' ];
                if ( ! in_array( $encoding, $valid_encodings, true ) ) {
                    $diagnostics[] = [
                        'severity' => 'warning',
                        'code' => 'uncommon_encoding',
                        'message' => sprintf(
                            __( 'Uncommon encoding "%s" declared. Recommended: UTF-8.', 'wpmr-product-feed-validator' ),
                            esc_html( $encoding )
                        ),
                    ];
                }
                
                // Check BOM vs declared encoding mismatch
                if ( $bom_detected && $bom_detected !== $encoding ) {
                    $diagnostics[] = [
                        'severity' => 'error',
                        'code' => 'encoding_mismatch',
                        'message' => sprintf(
                            __( 'Encoding mismatch: BOM indicates %s but declaration says %s.', 'wpmr-product-feed-validator' ),
                            $bom_detected,
                            esc_html( $encoding )
                        ),
                    ];
                }
            } else {
                $diagnostics[] = [
                    'severity' => 'warning',
                    'code' => 'missing_encoding',
                    'message' => __( 'XML declaration missing encoding attribute. UTF-8 assumed.', 'wpmr-product-feed-validator' ),
                ];
            }
        }
    } else {
        $diagnostics[] = [
            'severity' => 'warning',
            'code' => 'missing_xml_declaration',
            'message' => __( 'XML declaration (<?xml version="1.0" encoding="UTF-8"?>) is missing.', 'wpmr-product-feed-validator' ),
        ];
    }

    return [
        'has_declaration' => $has_declaration,
        'version' => $version,
        'encoding' => $encoding,
        'diagnostics' => $diagnostics,
    ];
}

/**
 * Detect BOM (Byte Order Mark) in XML content.
 *
 * @param string $xml XML content
 * @return string|null BOM encoding detected or null
 */
protected static function detect_bom( string $xml ) {
    // UTF-8 BOM: EF BB BF
    if ( substr( $xml, 0, 3 ) === "\xEF\xBB\xBF" ) {
        return 'UTF-8';
    }
    // UTF-16 BE BOM: FE FF
    if ( substr( $xml, 0, 2 ) === "\xFE\xFF" ) {
        return 'UTF-16BE';
    }
    // UTF-16 LE BOM: FF FE
    if ( substr( $xml, 0, 2 ) === "\xFF\xFE" ) {
        return 'UTF-16LE';
    }
    // UTF-32 BE BOM: 00 00 FE FF
    if ( substr( $xml, 0, 4 ) === "\x00\x00\xFE\xFF" ) {
        return 'UTF-32BE';
    }
    // UTF-32 LE BOM: FF FE 00 00
    if ( substr( $xml, 0, 4 ) === "\xFF\xFE\x00\x00" ) {
        return 'UTF-32LE';
    }
    return null;
}
```

#### Parser.php Enhancements
**Current Implementation (lines 26-29):**
```php
$reader = new \XMLReader();
$ok = @$reader->XML( $xml, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING );
if ( ! $ok ) {
    return new WP_Error( 'wpmr_pfv_xml_invalid', __( 'XML is not well-formed or could not be read.', 'wpmr-product-feed-validator' ) );
}
```

**After Enhancement:**
```php
$reader = new \XMLReader();
$ok = @$reader->XML( $xml, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING );
if ( ! $ok ) {
    return new WP_Error( 'wpmr_pfv_xml_invalid', __( 'XML is not well-formed or could not be read.', 'wpmr-product-feed-validator' ) );
}

// Validate XML structure
$structure_validation = self::validate_xml_structure( $reader );
if ( ! empty( $structure_validation ) ) {
    $diagnostics = array_merge( $diagnostics, $structure_validation );
}
```

**New Method to Add:**
```php
/**
 * Validate XML structure (root element and namespaces).
 *
 * @param \XMLReader $reader XMLReader instance positioned at start
 * @return array Diagnostics array
 */
protected static function validate_xml_structure( \XMLReader $reader ) {
    $diagnostics = [];
    $root_found = false;
    $has_google_namespace = false;

    // Find root element
    while ( $reader->read() ) {
        if ( $reader->nodeType === \XMLReader::ELEMENT ) {
            $root_name = $reader->localName;
            $root_found = true;

            // Validate root element
            if ( ! in_array( $root_name, [ 'rss', 'feed' ], true ) ) {
                $diagnostics[] = [
                    'severity' => 'error',
                    'code' => 'invalid_root_element',
                    'message' => sprintf(
                        __( 'Invalid root element "<%s>". Expected <rss> or <feed>.', 'wpmr-product-feed-validator' ),
                        esc_html( $root_name )
                    ),
                ];
            }

            // Check for Google namespace
            $reader->moveToFirstAttribute();
            do {
                if ( $reader->name === 'xmlns:g' || $reader->value === 'http://base.google.com/ns/1.0' ) {
                    $has_google_namespace = true;
                    break;
                }
            } while ( $reader->moveToNextAttribute() );

            if ( ! $has_google_namespace ) {
                $diagnostics[] = [
                    'severity' => 'warning',
                    'code' => 'missing_google_namespace',
                    'message' => __( 'Google namespace (xmlns:g="http://base.google.com/ns/1.0") not found. Required for Google Shopping feeds.', 'wpmr-product-feed-validator' ),
                ];
            }

            // Reset reader to element
            $reader->moveToElement();
            break;
        }
    }

    if ( ! $root_found ) {
        $diagnostics[] = [
            'severity' => 'error',
            'code' => 'no_root_element',
            'message' => __( 'No root element found in XML document.', 'wpmr-product-feed-validator' ),
        ];
    }

    // Reset reader to beginning for subsequent parsing
    // Note: XMLReader cannot be reset, so caller must handle this

    return $diagnostics;
}
```

### Security Checklist
- [x] **Nonce Verification** - N/A (REST API uses nonce in headers)
- [x] **Capability Checks** - Handled by REST API permission callbacks
- [x] **Data Sanitization** - All user input (XML content) is validated, not executed
- [x] **Output Escaping** - All error messages use `esc_html()` for user-provided values
- [x] **SQL Injection Prevention** - N/A (no database queries)
- [x] **CSRF Protection** - N/A (REST API)
- [x] **XSS Prevention** - All output escaped with `esc_html()`

---

## 9. Frontend Changes

### Template Structure
**N/A** - No template changes required

### Shortcode Requirements
**N/A** - No shortcode changes required

### Gutenberg Blocks
**N/A** - No block changes required

### Asset Management
**N/A** - No asset changes required

---

## 10. Code Changes Overview

### 📂 **Current Implementation (Before)**

**Fetcher.php (lines 96-107):**
```php
$diagnostics = [];
if ( $len <= 0 ) {
    $diagnostics[] = [ 'severity' => 'error', 'code' => 'empty_body', 'message' => __( 'Response body is empty.', 'wpmr-product-feed-validator' ) ];
}

$is_xml_ct = stripos( $ct, 'xml' ) !== false;
$has_xml_decl = strpos( ltrim( $body ), '<?xml' ) === 0;
if ( ! $is_xml_ct && ! $has_xml_decl ) {
    $diagnostics[] = [ 'severity' => 'error', 'code' => 'content_type', 'message' => __( 'Content-Type not XML and no XML declaration found.', 'wpmr-product-feed-validator' ) ];
} elseif ( ! $is_xml_ct ) {
    $diagnostics[] = [ 'severity' => 'warning', 'code' => 'content_type_warning', 'message' => __( 'Content-Type header does not indicate XML.', 'wpmr-product-feed-validator' ) ];
}
```

**Parser.php (lines 26-40):**
```php
$reader = new \XMLReader();
$ok = @$reader->XML( $xml, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING );
if ( ! $ok ) {
    return new WP_Error( 'wpmr_pfv_xml_invalid', __( 'XML is not well-formed or could not be read.', 'wpmr-product-feed-validator' ) );
}

$format = null; // rss|atom|null
$diagnostics = [];
// ... continues with parsing logic
```

### 📂 **After Enhancement**

**Fetcher.php (enhanced validation section):**
```php
$diagnostics = [];
if ( $len <= 0 ) {
    $diagnostics[] = [ 'severity' => 'error', 'code' => 'empty_body', 'message' => __( 'Response body is empty.', 'wpmr-product-feed-validator' ) ];
}

// NEW: Enhanced XML declaration and encoding validation
$validation_result = self::validate_xml_declaration( $body );
if ( ! empty( $validation_result['diagnostics'] ) ) {
    $diagnostics = array_merge( $diagnostics, $validation_result['diagnostics'] );
}

// Enhanced Content-Type check
$is_xml_ct = stripos( $ct, 'xml' ) !== false;
if ( ! $is_xml_ct && ! $validation_result['has_declaration'] ) {
    $diagnostics[] = [ 'severity' => 'error', 'code' => 'content_type', 'message' => __( 'Content-Type not XML and no XML declaration found.', 'wpmr-product-feed-validator' ) ];
} elseif ( ! $is_xml_ct ) {
    $diagnostics[] = [ 'severity' => 'warning', 'code' => 'content_type_warning', 'message' => __( 'Content-Type header does not indicate XML.', 'wpmr-product-feed-validator' ) ];
}

// NEW: Add two new protected methods at end of class
// - validate_xml_declaration()
// - detect_bom()
```

**Parser.php (enhanced structure validation):**
```php
$reader = new \XMLReader();
$ok = @$reader->XML( $xml, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING );
if ( ! $ok ) {
    return new WP_Error( 'wpmr_pfv_xml_invalid', __( 'XML is not well-formed or could not be read.', 'wpmr-product-feed-validator' ) );
}

$format = null; // rss|atom|null
$diagnostics = [];

// NEW: Validate XML structure before parsing items
$structure_diags = self::validate_xml_structure_early( $xml );
if ( ! empty( $structure_diags ) ) {
    $diagnostics = array_merge( $diagnostics, $structure_diags );
}

// ... continues with existing parsing logic

// NEW: Add new protected method at end of class
// - validate_xml_structure_early()
```

### 🎯 **Key Changes Summary**
- **Change 1:** Add `validate_xml_declaration()` method to Fetcher.php - validates XML version, encoding, and detects BOM
- **Change 2:** Add `detect_bom()` method to Fetcher.php - detects UTF-8/16/32 byte order marks
- **Change 3:** Add `validate_xml_structure_early()` method to Parser.php - validates root element and Google namespace
- **Change 4:** Integrate new validation methods into existing validation flow in both services
- **Files Modified:** 
  - `includes/Services/Fetcher.php` (~100 new lines)
  - `includes/Services/Parser.php` (~60 new lines)
- **Impact:** More comprehensive validation with better error messages; no breaking changes to API responses
- **WordPress Compatibility:** PHP 7.4+, uses only built-in XML functions

---

## 11. Implementation Plan

### Phase 1: Fetcher.php - XML Declaration and Encoding Validation
**Goal:** Add comprehensive XML declaration and encoding validation to Fetcher service

- [ ] **Task 1.1:** Add `detect_bom()` method
  - Files: `includes/Services/Fetcher.php`
  - Details: Create protected static method to detect UTF-8, UTF-16, UTF-32 BOMs
  
- [ ] **Task 1.2:** Add `validate_xml_declaration()` method
  - Files: `includes/Services/Fetcher.php`
  - Details: Extract and validate XML version, encoding; check BOM mismatches
  
- [ ] **Task 1.3:** Integrate declaration validation into fetch() method
  - Files: `includes/Services/Fetcher.php`
  - Details: Call validation method and merge diagnostics into existing flow

### Phase 2: Parser.php - XML Structure Validation
**Goal:** Add root element and namespace validation to Parser service

- [ ] **Task 2.1:** Add `validate_xml_structure_early()` method
  - Files: `includes/Services/Parser.php`
  - Details: Create method to validate root element and check for Google namespace using SimpleXML
  
- [ ] **Task 2.2:** Integrate structure validation into parse_sample() method
  - Files: `includes/Services/Parser.php`
  - Details: Call validation early in parsing flow and merge diagnostics

### Phase 3: Translation Strings
**Goal:** Ensure all new error messages are translatable

- [ ] **Task 3.1:** Verify all strings use text domain
  - Files: `includes/Services/Fetcher.php`, `includes/Services/Parser.php`
  - Details: Confirm all `__()` calls include 'wpmr-product-feed-validator' text domain
  
- [ ] **Task 3.2:** Update .pot file (if build process exists)
  - Files: `languages/wpmr-product-feed-validator.pot`
  - Details: Regenerate translation template with new strings

### Phase 4: Basic Code Validation (AI-Only)
**Goal:** Run safe static analysis only - NEVER activate plugin or test in browser

- [ ] **Task 4.1:** Code Quality Verification
  - Files: All modified files
  - Details: Check WordPress Coding Standards (PHPCS with WPCS), syntax validation
  
- [ ] **Task 4.2:** Static Logic Review
  - Files: Modified business logic files
  - Details: Read code to verify logic correctness, security (escaping, sanitization)
  
- [ ] **Task 4.3:** Verify Integration Points
  - Files: Fetcher.php, Parser.php
  - Details: Confirm new methods integrate correctly with existing flow

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
  - Details: Provide specific test cases:
    - ✅ Valid XML with proper declaration and UTF-8 encoding
    - ❌ XML missing declaration
    - ❌ XML with invalid version (e.g., "2.0")
    - ❌ XML with uncommon encoding (e.g., "ISO-8859-1")
    - ❌ XML with BOM mismatch (UTF-8 BOM but UTF-16 declared)
    - ❌ XML with invalid root element (e.g., `<products>`)
    - ❌ XML missing Google namespace
    - ✅ Valid RSS feed with Google namespace
    - ✅ Valid Atom feed
  
- [ ] **Task 6.3:** Wait for User Confirmation
  - Details: Wait for user to complete WordPress testing and confirm results

---

## 12. Task Completion Tracking - MANDATORY WORKFLOW

### Phase 1: Fetcher.php - XML Declaration and Encoding Validation
**Goal:** Add comprehensive XML declaration and encoding validation to Fetcher service

- [ ] **Task 1.1:** Add `detect_bom()` method
  - Files: `includes/Services/Fetcher.php`
  - Details: Create protected static method to detect UTF-8, UTF-16, UTF-32 BOMs
  
- [ ] **Task 1.2:** Add `validate_xml_declaration()` method
  - Files: `includes/Services/Fetcher.php`
  - Details: Extract and validate XML version, encoding; check BOM mismatches
  
- [ ] **Task 1.3:** Integrate declaration validation into fetch() method
  - Files: `includes/Services/Fetcher.php`
  - Details: Call validation method and merge diagnostics into existing flow

### Phase 2: Parser.php - XML Structure Validation
**Goal:** Add root element and namespace validation to Parser service

- [ ] **Task 2.1:** Add `validate_xml_structure_early()` method
  - Files: `includes/Services/Parser.php`
  - Details: Create method to validate root element and check for Google namespace
  
- [ ] **Task 2.2:** Integrate structure validation into parse_sample() method
  - Files: `includes/Services/Parser.php`
  - Details: Call validation early in parsing flow and merge diagnostics

### Phase 3: Translation Strings
**Goal:** Ensure all new error messages are translatable

- [ ] **Task 3.1:** Verify all strings use text domain
  - Files: `includes/Services/Fetcher.php`, `includes/Services/Parser.php`
  - Details: Confirm all `__()` calls include 'wpmr-product-feed-validator' text domain
  
- [ ] **Task 3.2:** Update .pot file (if build process exists)
  - Files: `languages/wpmr-product-feed-validator.pot`
  - Details: Regenerate translation template with new strings

---

## 13. File Structure & Organization

### Files to Modify
- [ ] **`includes/Services/Fetcher.php`** - Add XML declaration and encoding validation methods
- [ ] **`includes/Services/Parser.php`** - Add XML structure validation method
- [ ] **`languages/wpmr-product-feed-validator.pot`** - Update translation template (if applicable)

### No New Files Required
All changes are enhancements to existing service classes.

---

## 14. Potential Issues & Security Review

### Error Scenarios to Analyze
- [ ] **Error Scenario 1:** Large XML files with BOM detection
  - **Code Review Focus:** Ensure `detect_bom()` only checks first 4 bytes (performance)
  - **Potential Fix:** Already optimized with `substr()` checks
  
- [ ] **Error Scenario 2:** Malformed XML declaration with special characters
  - **Code Review Focus:** Regex patterns in `validate_xml_declaration()`
  - **Potential Fix:** Use safe regex patterns, escape output with `esc_html()`
  
- [ ] **Error Scenario 3:** XMLReader state after structure validation
  - **Code Review Focus:** Parser.php structure validation method
  - **Potential Fix:** Document that XMLReader cannot be reset; validation must use separate SimpleXML instance

### WordPress-Specific Edge Cases
- [ ] **Plugin Conflicts:** No conflicts expected (standalone validation)
- [ ] **Theme Conflicts:** N/A (backend only)
- [ ] **Multisite Issues:** N/A (no multisite-specific code)
- [ ] **User Role Edge Cases:** Handled by REST API permission callbacks
- [ ] **Permalink Structure:** N/A (REST API)
- [ ] **AJAX Failures:** N/A (uses REST API)

### Security & Access Control Review
- [x] **Nonce Verification:** N/A (REST API handles this)
- [x] **Capability Checks:** Handled by REST API permission callbacks
- [x] **Data Sanitization:** XML content is validated, not executed
- [x] **Output Escaping:** All user-provided values in error messages use `esc_html()`
- [x] **SQL Injection Prevention:** N/A (no database queries)
- [x] **File Upload Security:** N/A (URL-based fetching only)
- [x] **Direct File Access:** Already protected with `defined('ABSPATH') || exit;`

---

## 15. Deployment & Configuration

### Environment Variables / Constants
**N/A** - No new constants required

### WordPress Version Requirements
- **Minimum WordPress Version:** 5.8
- **Tested Up To:** 6.7
- **Minimum PHP Version:** 7.4
- **Required PHP Extensions:** xml, xmlreader (standard in PHP)

### Plugin Dependencies
- **Required Plugins:** None
- **Optional Plugins:** None

### Server Requirements
- **PHP Memory Limit:** 128M (existing requirement)
- **Max Execution Time:** 30s (existing requirement)
- **WordPress Multisite:** Not specifically supported or tested

---

## 16. AI Agent Instructions

### Implementation Approach - CRITICAL WORKFLOW
🚨 **MANDATORY: Always follow this exact sequence:**

1. **✅ STRATEGIC ANALYSIS SKIPPED** - Straightforward enhancement with clear implementation path

2. **✅ TASK DOCUMENT CREATED** - This document in `ai-docs/tasks/001_xml_validation_enhancements.md`

3. **PRESENT IMPLEMENTATION OPTIONS (Required)**

   **👤 IMPLEMENTATION OPTIONS:**

   **A) Preview High-Level Code Changes**
   Would you like me to show you detailed code snippets and specific changes before implementing? I'll walk through exactly what files will be modified and show before/after code examples.

   **B) Proceed with Implementation**
   Ready to begin implementation? Say "Approved" or "Go ahead" and I'll start implementing phase by phase.

   **C) Provide More Feedback**
   Have questions or want to modify the approach? I can adjust the plan based on additional requirements or concerns.

---

## Summary

This task adds three critical XML validation checks to the product feed validator:

1. **XML Declaration Validation** - Checks for presence, validates version and encoding attributes
2. **File Encoding Verification** - Detects BOM markers and verifies encoding consistency
3. **XML Structure Validation** - Validates root element and required namespaces

**Scope:** 2 files modified, ~160 lines added, no database changes, no breaking changes to API

**Testing Required:** User must test with various XML feed scenarios (valid, missing declaration, encoding mismatches, etc.)
