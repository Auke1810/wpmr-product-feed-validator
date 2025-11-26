# GitHub Auto-Updates Guide

**Feature:** Automatic Plugin Updates from GitHub  
**Version:** 0.3.0+  
**Status:** ✅ Implemented

---

## Overview

The WPMR Product Feed Validator plugin now supports automatic updates directly from GitHub releases. WordPress will automatically detect new versions and allow one-click updates from the admin panel.

---

## How It Works

### For Users

1. **Automatic Detection**
   - WordPress checks GitHub every 12 hours for new releases
   - Update notifications appear in WordPress admin (Plugins page)
   - Standard WordPress update UI - no difference from WordPress.org plugins

2. **One-Click Updates**
   - Click "Update Now" button
   - WordPress downloads from GitHub
   - Plugin updates automatically
   - No manual download/upload needed

3. **Update Information**
   - View version details
   - Read changelog
   - See requirements (WordPress/PHP versions)

### For Developers

1. **Creating Releases**
   - Push a new tag (e.g., `v0.3.0`)
   - GitHub Actions automatically creates release
   - ZIP file generated and attached
   - Changelog extracted from commits

2. **Update Flow**
   ```
   Push Tag → GitHub Actions → Create Release → WordPress Detects → User Updates
   ```

---

## Release Process

### Standard Release Steps

1. **Update Version Numbers**
   ```bash
   # Update version in main plugin file
   # Update version in README.txt
   # Update CHANGELOG.md
   ```

2. **Commit Changes**
   ```bash
   git add .
   git commit -m "release: version 0.3.0"
   git push origin main
   ```

3. **Create and Push Tag**
   ```bash
   git tag v0.3.0
   git push origin v0.3.0
   ```

4. **GitHub Actions Runs Automatically**
   - Creates plugin ZIP (excludes dev files)
   - Generates changelog from commits
   - Creates GitHub release
   - Uploads ZIP as asset

5. **WordPress Detects Update**
   - Within 12 hours (or immediately if cache cleared)
   - Update notification appears
   - Users can update with one click

---

## Technical Details

### Files Added

1. **`includes/GitHub_Updater.php`**
   - Main update checker class
   - Hooks into WordPress update system
   - Fetches release info from GitHub API
   - Caches responses for 12 hours

2. **`.github/workflows/release.yml`**
   - GitHub Actions workflow
   - Triggered on tag push
   - Creates ZIP and release automatically

3. **`.distignore`**
   - Lists files to exclude from release ZIP
   - Keeps ZIP clean (no dev files)

### Files Modified

1. **`wpmr-product-feed-validator.php`**
   - Added `Update URI` header
   - Initialize GitHub_Updater class

### WordPress Hooks Used

- `pre_set_site_transient_update_plugins` - Inject update info
- `plugins_api` - Provide plugin details for modal
- `admin_init` - Force update check (debugging)

### GitHub API

- **Endpoint:** `https://api.github.com/repos/Auke1810/wpmr-product-feed-validator/releases/latest`
- **Rate Limits:** 60/hour (unauthenticated), 5000/hour (authenticated)
- **Caching:** 12 hours (reduces to ~2 checks/day)

---

## Configuration

### Optional: GitHub Token

For private repositories or higher rate limits, add to `wp-config.php`:

```php
define('WPMR_PFV_GITHUB_TOKEN', 'ghp_xxxxxxxxxxxx');
```

**Token Permissions:** `repo` scope (for private repos)

### Force Update Check

For debugging, add query parameter:

```
/wp-admin/plugins.php?wpmr_pfv_force_update=1
```

This clears cache and forces immediate update check.

---

## Troubleshooting

### Update Not Showing

1. **Check GitHub Release**
   - Verify release exists on GitHub
   - Verify ZIP file is attached
   - Check tag format (`v0.3.0` not `0.3.0`)

2. **Clear Cache**
   - Visit: `/wp-admin/plugins.php?wpmr_pfv_force_update=1`
   - Or wait 12 hours for cache to expire

3. **Check Debug Log**
   - Enable `WP_DEBUG` and `WP_DEBUG_LOG`
   - Check `wp-content/debug.log` for errors
   - Look for "WPMR PFV GitHub Updater" messages

### Common Issues

**Issue:** "No update available"
- **Cause:** Version in tag ≤ installed version
- **Fix:** Ensure tag version > installed version

**Issue:** "Download failed"
- **Cause:** ZIP file not attached to release
- **Fix:** Check GitHub Actions workflow ran successfully

**Issue:** "GitHub API rate limit"
- **Cause:** Too many requests (60/hour limit)
- **Fix:** Add GitHub token or wait for rate limit reset

---

## Security

### HTTPS
- All GitHub API calls use HTTPS
- Download URLs use HTTPS

### Token Storage
- Store in `wp-config.php` (not database)
- Never commit to repository
- Use minimal scope (`repo` only)

### Download Verification
- WordPress verifies ZIP integrity
- Extracts in secure temporary directory
- Creates backup before update

---

## Versioning

### Semantic Versioning

Follow [SemVer](https://semver.org/):

- **Major (X.0.0):** Breaking changes
- **Minor (0.X.0):** New features, backward compatible
- **Patch (0.0.X):** Bug fixes, backward compatible

### Tag Format

- **Correct:** `v0.3.0`, `v1.0.0`, `v1.2.3`
- **Incorrect:** `0.3.0`, `version-0.3.0`, `release-0.3.0`

---

## Testing

### Before Release

1. **Test Locally**
   - Update version numbers
   - Test plugin functionality
   - Check for errors

2. **Create Test Release**
   - Push to test branch
   - Create test tag
   - Verify workflow runs
   - Check ZIP contents

3. **Test Update**
   - Install previous version
   - Trigger update check
   - Verify update appears
   - Test update process

### After Release

1. **Monitor**
   - Check GitHub Actions status
   - Verify release created
   - Check ZIP file attached

2. **Test Update**
   - Install on staging site
   - Check for update notification
   - Test update process
   - Verify plugin works after update

---

## Advantages

### vs WordPress.org

✅ **Faster Releases**
- No review process
- Immediate availability
- Automated via GitHub Actions

✅ **Better Version Control**
- Git-based workflow
- No SVN required
- Familiar tools

✅ **Private Plugins**
- Can keep plugin private
- Control access
- No public listing

### vs Manual Updates

✅ **User-Friendly**
- Standard WordPress UI
- One-click updates
- No manual download/upload

✅ **Automated**
- GitHub Actions handles releases
- Consistent ZIP structure
- Automatic changelog

---

## Limitations

### Compared to WordPress.org

❌ **No Discovery**
- Users must install manually first time
- No WordPress.org plugin directory listing

❌ **No Reviews**
- No built-in review system
- No ratings/feedback

❌ **Requires GitHub**
- Depends on GitHub availability
- Rate limits (mitigated by caching)

---

## Future Enhancements

### Potential Features

1. **Beta Channel**
   - Allow users to opt into beta releases
   - Separate stable/beta channels

2. **Admin Settings**
   - Configure update check frequency
   - Enable/disable auto-updates
   - View update history

3. **Rollback**
   - One-click rollback to previous version
   - Keep backup of previous version

4. **Notifications**
   - Email notifications for new versions
   - Slack/Discord webhooks

---

## FAQ

### Q: Do I need a GitHub account?
**A:** No, users don't need GitHub accounts. Only developers need GitHub access.

### Q: Will this work with private repositories?
**A:** Yes, with a GitHub token configured in `wp-config.php`.

### Q: How often does WordPress check for updates?
**A:** Every 12 hours (cached). Can force check with query parameter.

### Q: Can I disable auto-updates?
**A:** Yes, remove the GitHub_Updater initialization from main plugin file.

### Q: What if GitHub is down?
**A:** Updates won't be available until GitHub is back. Plugin continues to work normally.

### Q: Can I use this for themes too?
**A:** Yes, with minor modifications to hook into theme update system instead.

---

## Support

### Debug Mode

Enable WordPress debug logging:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check `wp-content/debug.log` for messages starting with:
```
WPMR PFV GitHub Updater: ...
```

### Common Debug Messages

- `Update available: X.X.X` - New version detected
- `Fetched latest release: X.X.X` - Successfully fetched from GitHub
- `GitHub API error: ...` - API request failed
- `No ZIP asset found, using zipball URL` - Using fallback download

---

## References

### Documentation
- [WordPress Plugin Update API](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/)
- [GitHub Releases API](https://docs.github.com/en/rest/releases/releases)
- [WordPress Update URI](https://make.wordpress.org/core/2021/06/29/introducing-update-uri-plugin-header-in-wordpress-5-8/)

### Tools
- [GitHub Actions](https://github.com/features/actions)
- [Semantic Versioning](https://semver.org/)

---

**Last Updated:** 2025-11-26  
**Version:** 1.0  
**Author:** WPMR Development Team
