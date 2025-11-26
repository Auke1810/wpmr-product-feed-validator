# GitHub Auto-Updates Investigation & Implementation Plan

**Task ID:** 004  
**Created:** 2025-11-26  
**Status:** 🔍 Investigation  
**Priority:** Medium  
**Type:** Feature Enhancement

---

## 1. Executive Summary

### Objective
Enable automatic plugin updates from GitHub repository, allowing WordPress admin to detect and install new versions directly from GitHub releases without requiring WordPress.org hosting.

### Key Findings
✅ **Feasible** - Multiple proven solutions exist  
✅ **No WordPress.org Required** - Can update from private GitHub repos  
✅ **Standard WordPress Hooks** - Uses built-in update mechanism  
⚠️ **Requires GitHub Token** - For private repos or API rate limits  
⚠️ **Manual Implementation** - Need to code the update checker

---

## 2. How WordPress Plugin Updates Work

### Standard WordPress.org Flow
1. WordPress checks `https://api.wordpress.org/plugins/update-check/` periodically
2. Compares installed version with latest version
3. Shows update notification in admin
4. Downloads ZIP from WordPress.org on update
5. Extracts and replaces plugin files

### Custom Update Flow (GitHub)
1. Hook into `pre_set_site_transient_update_plugins` filter
2. Check GitHub Releases API for latest version
3. Compare with installed version
4. Inject update info into WordPress transient
5. WordPress handles download and installation from GitHub release ZIP

---

## 3. Solution Options Analysis

### Option 1: Custom Update Checker (Recommended)
**Approach:** Implement custom update checker using WordPress hooks and GitHub API

**Pros:**
- ✅ Full control over update process
- ✅ No external dependencies
- ✅ Works with private repositories
- ✅ Lightweight (< 200 lines of code)
- ✅ Uses WordPress built-in update UI
- ✅ Can customize update notifications
- ✅ Free and open-source

**Cons:**
- ❌ Requires manual implementation
- ❌ Need to maintain update code
- ❌ Requires GitHub token for private repos
- ❌ Must create GitHub releases for each version

**Implementation Complexity:** Medium - Requires understanding of WordPress hooks and GitHub API  
**Risk Level:** Low - Well-documented pattern, many examples exist  
**WordPress Compatibility:** WordPress 5.0+ (tested up to 6.4)

**Code Size:** ~150-200 lines PHP  
**Maintenance:** Low - stable WordPress hooks

---

### Option 2: Git Updater Plugin
**Approach:** Use third-party "Git Updater" plugin to manage updates

**Pros:**
- ✅ No coding required
- ✅ Supports GitHub, GitLab, Bitbucket
- ✅ GUI for configuration
- ✅ Handles multiple plugins/themes
- ✅ Active development and support
- ✅ Works with private repos

**Cons:**
- ❌ External dependency (another plugin)
- ❌ Adds overhead for single plugin
- ❌ User must install Git Updater first
- ❌ Less control over update process
- ❌ Potential conflicts with other plugins

**Implementation Complexity:** Low - Just install and configure  
**Risk Level:** Medium - Dependency on third-party plugin  
**WordPress Compatibility:** WordPress 5.2+

**Reference:** https://github.com/afragen/git-updater

---

### Option 3: Update URI Header (WordPress 5.8+)
**Approach:** Add `Update URI` header to prevent WordPress.org checks

**Pros:**
- ✅ Simple one-line addition
- ✅ Prevents WordPress.org API calls
- ✅ Official WordPress feature
- ✅ No code required

**Cons:**
- ❌ Only prevents WP.org checks
- ❌ Doesn't provide update mechanism
- ❌ Still need Option 1 or 2 for actual updates
- ❌ WordPress 5.8+ only

**Implementation Complexity:** Very Low - Add one header line  
**Risk Level:** Very Low - Official WordPress feature  
**WordPress Compatibility:** WordPress 5.8+

**Note:** This should be used **in combination** with Option 1 or 2

---

## 4. Recommended Solution

### **Option 1 + Option 3: Custom Update Checker with Update URI**

**Rationale:**
1. **Full Control:** Own the update mechanism
2. **No Dependencies:** Self-contained solution
3. **Professional:** Standard practice for premium plugins
4. **Lightweight:** Minimal code footprint
5. **Flexible:** Easy to customize notifications and behavior

---

## 5. Technical Implementation Plan

### Phase 1: Add Update URI Header ✅ EASY
**Goal:** Prevent WordPress.org update checks

#### Task 1.1: Update Plugin Header
Add `Update URI` to main plugin file:

```php
/**
 * Plugin Name:       WPMR Product Feed Validator
 * Description:       Validate Google Shopping product feeds and email/share reports.
 * Version:           0.2.0
 * Author:            WP Marketing Robot
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpmr-product-feed-validator
 * Domain Path:       /languages
 * Update URI:        https://github.com/Auke1810/wpmr-product-feed-validator
 */
```

**Benefit:** WordPress will skip checking WordPress.org for this plugin

---

### Phase 2: Create GitHub Update Checker Class
**Goal:** Implement custom update mechanism

#### Task 2.1: Create Update Checker Class
**File:** `includes/GitHub_Updater.php`

**Key Components:**
1. **GitHub API Client** - Fetch release info
2. **Version Comparison** - Check if update available
3. **WordPress Integration** - Hook into update system
4. **Download Handler** - Provide ZIP URL to WordPress

**Hooks Used:**
- `pre_set_site_transient_update_plugins` - Inject update info
- `plugins_api` - Provide plugin details for update screen
- `http_request_args` - Add GitHub auth token if needed

---

### Phase 3: GitHub Releases Setup
**Goal:** Prepare repository for automated releases

#### Task 3.1: Create GitHub Release Workflow
**File:** `.github/workflows/release.yml`

**Workflow:**
1. Triggered on tag push (e.g., `v0.2.0`)
2. Generate changelog from commits
3. Create ZIP file (exclude dev files)
4. Upload ZIP as release asset
5. Create GitHub release with changelog

---

### Phase 4: Configuration & Testing
**Goal:** Configure and test update mechanism

#### Task 4.1: Add Configuration
- Optional: GitHub token for private repos
- Optional: Update check frequency
- Optional: Beta/stable channel selection

#### Task 4.2: Testing Checklist
- [ ] Update notification appears
- [ ] Plugin details show correctly
- [ ] Update downloads from GitHub
- [ ] Update installs successfully
- [ ] No errors in debug log

---

## 6. Implementation Code Examples

### Example 1: Update URI Header
```php
/**
 * Update URI: https://github.com/Auke1810/wpmr-product-feed-validator
 */
```

---

### Example 2: GitHub Update Checker (Simplified)
```php
<?php
/**
 * GitHub Update Checker
 * 
 * Checks GitHub releases for plugin updates
 */

namespace WPMR\PFV;

class GitHub_Updater {
    
    private $plugin_slug;
    private $plugin_file;
    private $github_repo;
    private $version;
    
    public function __construct($plugin_file, $github_repo) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->github_repo = $github_repo; // e.g., 'Auke1810/wpmr-product-feed-validator'
        
        // Get current version from plugin header
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_data = get_plugin_data($plugin_file);
        $this->version = $plugin_data['Version'];
        
        // Hook into WordPress update system
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
    }
    
    /**
     * Check for updates from GitHub
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get latest release from GitHub
        $release = $this->get_latest_release();
        
        if (!$release) {
            return $transient;
        }
        
        // Compare versions
        if (version_compare($this->version, $release->version, '<')) {
            $transient->response[$this->plugin_slug] = (object) [
                'slug' => dirname($this->plugin_slug),
                'plugin' => $this->plugin_slug,
                'new_version' => $release->version,
                'url' => $release->url,
                'package' => $release->download_url,
                'tested' => '6.4',
                'requires_php' => '7.4',
            ];
        }
        
        return $transient;
    }
    
    /**
     * Provide plugin information for update screen
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if (!isset($args->slug) || $args->slug !== dirname($this->plugin_slug)) {
            return $result;
        }
        
        $release = $this->get_latest_release();
        
        if (!$release) {
            return $result;
        }
        
        return (object) [
            'name' => 'WPMR Product Feed Validator',
            'slug' => dirname($this->plugin_slug),
            'version' => $release->version,
            'author' => '<a href="https://github.com/Auke1810">Auke1810</a>',
            'homepage' => 'https://github.com/Auke1810/wpmr-product-feed-validator',
            'requires' => '5.8',
            'tested' => '6.4',
            'requires_php' => '7.4',
            'download_link' => $release->download_url,
            'sections' => [
                'description' => 'Validate Google Shopping product feeds and email/share reports.',
                'changelog' => $release->changelog,
            ],
        ];
    }
    
    /**
     * Get latest release from GitHub API
     */
    private function get_latest_release() {
        $cache_key = 'wpmr_pfv_github_release';
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $api_url = "https://api.github.com/repos/{$this->github_repo}/releases/latest";
        
        $response = wp_remote_get($api_url, [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                // Optional: Add token for private repos or higher rate limits
                // 'Authorization' => 'token ' . GITHUB_TOKEN,
            ],
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response));
        
        if (!isset($body->tag_name)) {
            return false;
        }
        
        // Find ZIP asset
        $download_url = '';
        if (isset($body->assets) && is_array($body->assets)) {
            foreach ($body->assets as $asset) {
                if (strpos($asset->name, '.zip') !== false) {
                    $download_url = $asset->browser_download_url;
                    break;
                }
            }
        }
        
        // Fallback to zipball if no ZIP asset found
        if (empty($download_url)) {
            $download_url = $body->zipball_url;
        }
        
        $release = (object) [
            'version' => ltrim($body->tag_name, 'v'),
            'url' => $body->html_url,
            'download_url' => $download_url,
            'changelog' => isset($body->body) ? $body->body : '',
        ];
        
        // Cache for 12 hours
        set_transient($cache_key, $release, 12 * HOUR_IN_SECONDS);
        
        return $release;
    }
}

// Initialize updater
new GitHub_Updater(
    WPMR_PFV_PLUGIN_FILE,
    'Auke1810/wpmr-product-feed-validator'
);
```

---

### Example 3: GitHub Release Workflow
**File:** `.github/workflows/release.yml`

```yaml
name: Create Release

on:
  push:
    tags:
      - 'v*'

jobs:
  release:
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v3
      
      - name: Get version from tag
        id: get_version
        run: echo "VERSION=${GITHUB_REF#refs/tags/v}" >> $GITHUB_OUTPUT
      
      - name: Create plugin ZIP
        run: |
          mkdir -p build
          rsync -av --exclude-from='.distignore' . build/wpmr-product-feed-validator/
          cd build
          zip -r wpmr-product-feed-validator-${{ steps.get_version.outputs.VERSION }}.zip wpmr-product-feed-validator/
      
      - name: Generate changelog
        id: changelog
        run: |
          CHANGELOG=$(git log --pretty=format:"- %s" $(git describe --tags --abbrev=0 HEAD^)..HEAD)
          echo "CHANGELOG<<EOF" >> $GITHUB_OUTPUT
          echo "$CHANGELOG" >> $GITHUB_OUTPUT
          echo "EOF" >> $GITHUB_OUTPUT
      
      - name: Create GitHub Release
        uses: softprops/action-gh-release@v1
        with:
          name: Version ${{ steps.get_version.outputs.VERSION }}
          body: |
            ## Changes
            ${{ steps.changelog.outputs.CHANGELOG }}
          files: build/wpmr-product-feed-validator-${{ steps.get_version.outputs.VERSION }}.zip
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
```

---

### Example 4: .distignore File
**File:** `.distignore`

```
.git
.github
.gitignore
.DS_Store
node_modules
vendor
composer.json
composer.lock
package.json
package-lock.json
phpunit.xml
tests
ai-docs
.distignore
README.md
```

---

## 7. GitHub API Considerations

### Rate Limits
- **Unauthenticated:** 60 requests/hour
- **Authenticated:** 5,000 requests/hour

**Solution:** Cache release info for 12 hours (reduces to ~2 checks/day)

### Private Repositories
**Requires:** GitHub Personal Access Token with `repo` scope

**Configuration:**
```php
// In wp-config.php
define('WPMR_PFV_GITHUB_TOKEN', 'ghp_xxxxxxxxxxxx');
```

**Usage in updater:**
```php
'headers' => [
    'Authorization' => 'token ' . WPMR_PFV_GITHUB_TOKEN,
],
```

---

## 8. Release Process

### Manual Release Steps
1. Update version in `wpmr-product-feed-validator.php`
2. Update version in `README.txt`
3. Update `CHANGELOG.md`
4. Commit changes: `git commit -m "release: version X.X.X"`
5. Create tag: `git tag vX.X.X`
6. Push tag: `git push origin vX.X.X`
7. GitHub Actions creates release automatically

### Automated Release (GitHub Actions)
1. Push tag → Workflow triggers
2. Creates ZIP (excludes dev files)
3. Generates changelog from commits
4. Creates GitHub release
5. Uploads ZIP as asset
6. WordPress detects update within 12 hours

---

## 9. Testing Plan

### Unit Tests
- [ ] Version comparison logic
- [ ] GitHub API response parsing
- [ ] Cache mechanism
- [ ] Error handling

### Integration Tests
- [ ] Update notification appears
- [ ] Plugin details modal works
- [ ] Download from GitHub succeeds
- [ ] Installation completes
- [ ] Plugin activates after update

### Edge Cases
- [ ] No internet connection
- [ ] GitHub API down
- [ ] Invalid release format
- [ ] Missing ZIP asset
- [ ] Rate limit exceeded

---

## 10. Security Considerations

### GitHub Token Security
- ✅ Store in `wp-config.php` (not in database)
- ✅ Use environment variables in production
- ✅ Never commit token to repository
- ✅ Use minimal scope (only `repo` for private)

### Download Verification
- ✅ Use HTTPS for all GitHub API calls
- ✅ Verify ZIP integrity (optional: checksum)
- ✅ WordPress handles file extraction securely

### Update Safety
- ✅ WordPress creates backup before update
- ✅ Can rollback via FTP if needed
- ✅ Test updates on staging first

---

## 11. User Experience

### Update Notification
WordPress admin will show:
- "There is a new version of WPMR Product Feed Validator available"
- "View version X.X.X details" link
- "Update Now" button

### Update Details Modal
Shows:
- Version number
- Changelog
- Author
- Last updated
- Requires WordPress/PHP version

### Update Process
1. User clicks "Update Now"
2. WordPress downloads ZIP from GitHub
3. Deactivates plugin
4. Extracts new files
5. Reactivates plugin
6. Shows success message

---

## 12. Advantages Over WordPress.org

### For Development
- ✅ No SVN required (use Git)
- ✅ Faster release process
- ✅ Automated via GitHub Actions
- ✅ Better version control

### For Users
- ✅ Get updates immediately (no WP.org review delay)
- ✅ Beta/alpha channel support (if implemented)
- ✅ Direct from source

### For Private Plugins
- ✅ Can keep plugin private
- ✅ Control who has access
- ✅ No public listing required

---

## 13. Disadvantages & Limitations

### Compared to WordPress.org
- ❌ No automatic plugin discovery
- ❌ Users must install manually first time
- ❌ No WordPress.org reviews/ratings
- ❌ No built-in support forum
- ❌ Requires GitHub account for private repos

### Technical Limitations
- ❌ Depends on GitHub availability
- ❌ Rate limits (mitigated by caching)
- ❌ Requires maintenance of update code

---

## 14. Deployment Checklist

### Pre-Implementation
- [ ] Review code examples
- [ ] Understand WordPress update hooks
- [ ] Set up GitHub repository structure
- [ ] Plan release versioning strategy

### Implementation
- [ ] Add `Update URI` header
- [ ] Create `GitHub_Updater` class
- [ ] Add updater initialization
- [ ] Create `.distignore` file
- [ ] Create GitHub Actions workflow
- [ ] Test locally with mock releases

### Testing
- [ ] Create test release on GitHub
- [ ] Verify update notification
- [ ] Test update process
- [ ] Check for errors
- [ ] Test rollback if needed

### Production
- [ ] Deploy to production
- [ ] Create first official release
- [ ] Monitor for issues
- [ ] Document release process

---

## 15. Estimated Effort

### Development Time
- **Phase 1 (Update URI):** 5 minutes
- **Phase 2 (Update Checker):** 2-3 hours
- **Phase 3 (GitHub Workflow):** 1 hour
- **Phase 4 (Testing):** 2 hours
- **Total:** ~5-6 hours

### Ongoing Maintenance
- **Per Release:** 5-10 minutes (mostly automated)
- **Update Code:** Minimal (stable WordPress hooks)

---

## 16. Recommendation

### ✅ **Proceed with Implementation**

**Reasons:**
1. **Professional Standard:** Used by premium plugins (ACF, Gravity Forms, etc.)
2. **Full Control:** Own the update mechanism
3. **Automation:** GitHub Actions handles releases
4. **User-Friendly:** Uses standard WordPress update UI
5. **Low Maintenance:** Stable WordPress hooks
6. **Proven Solution:** Many successful implementations

**Next Steps:**
1. Review and approve this investigation
2. Create implementation task document
3. Implement Phase 1 (Update URI) - 5 minutes
4. Implement Phase 2 (Update Checker) - 2-3 hours
5. Implement Phase 3 (GitHub Workflow) - 1 hour
6. Test with beta release
7. Deploy to production

---

## 17. References

### Documentation
- WordPress Plugin Update API: https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/
- GitHub Releases API: https://docs.github.com/en/rest/releases/releases
- WordPress Update URI: https://make.wordpress.org/core/2021/06/29/introducing-update-uri-plugin-header-in-wordpress-5-8/

### Example Implementations
- Git Updater Test Plugin: https://github.com/fabrikage/git-updater-test-plugin
- WordPress Plugin Boilerplate: https://github.com/DevinVinson/WordPress-Plugin-Boilerplate
- YahnisElsts Plugin Update Checker: https://github.com/YahnisElsts/plugin-update-checker

### Tools
- GitHub Actions: https://github.com/features/actions
- Semantic Versioning: https://semver.org/

---

**Investigation Status:** ✅ **Complete**  
**Recommendation:** ✅ **Proceed with Custom Update Checker**  
**Next Task:** Create implementation task document (005)  
**Estimated ROI:** High - Professional feature with low maintenance

---

**Created:** 2025-11-26  
**Investigator:** AI Assistant (Cascade)  
**Approved By:** Pending user review
