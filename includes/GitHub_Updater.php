<?php
/**
 * GitHub Update Checker
 *
 * Checks GitHub releases for plugin updates and integrates with WordPress update system.
 *
 * @package WPMR_Product_Feed_Validator
 * @since 0.3.0
 */

namespace WPMR\PFV;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * GitHub_Updater class
 *
 * Handles automatic plugin updates from GitHub releases.
 */
class GitHub_Updater {

    /**
     * Plugin slug
     *
     * @var string
     */
    private $plugin_slug;

    /**
     * Plugin file path
     *
     * @var string
     */
    private $plugin_file;

    /**
     * GitHub repository (username/repo)
     *
     * @var string
     */
    private $github_repo;

    /**
     * Current plugin version
     *
     * @var string
     */
    private $version;

    /**
     * Plugin data
     *
     * @var array
     */
    private $plugin_data;

    /**
     * Cache key for transient
     *
     * @var string
     */
    private $cache_key = 'wpmr_pfv_github_release';

    /**
     * Cache duration in seconds (12 hours)
     *
     * @var int
     */
    private $cache_duration = 43200;

    /**
     * Constructor
     *
     * @param string $plugin_file Full path to main plugin file.
     * @param string $github_repo GitHub repository in format 'username/repo'.
     */
    public function __construct($plugin_file, $github_repo) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->github_repo = $github_repo;

        // Get plugin data
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $this->plugin_data = get_plugin_data($plugin_file);
        $this->version = $this->plugin_data['Version'];

        // Hook into WordPress update system
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
        
        // Optional: Add force update check
        add_action('admin_init', [$this, 'force_update_check']);
    }

    /**
     * Check for updates from GitHub
     *
     * @param object $transient Update transient.
     * @return object Modified transient.
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
                'icons' => [],
            ];

            // Log update available
            $this->log('Update available: ' . $release->version);
        }

        return $transient;
    }

    /**
     * Provide plugin information for update screen
     *
     * @param false|object|array $result The result object or array.
     * @param string             $action The type of information being requested.
     * @param object             $args   Plugin API arguments.
     * @return false|object Modified result.
     */
    public function plugin_info($result, $action, $args) {
        // Check if the action is 'plugin_information'
        if ($action !== 'plugin_information') {
            return $result;
        }

        // Check if the slug matches the plugin slug
        if (!isset($args->slug) || $args->slug !== dirname($this->plugin_slug)) {
            return $result;
        }

        // Get latest release
        $release = $this->get_latest_release();

        if (!$release) {
            return $result;
        }

        // Return plugin information
        return (object) [
            'name' => $this->plugin_data['Name'],
            'slug' => dirname($this->plugin_slug),
            'version' => $release->version,
            'author' => $this->plugin_data['Author'],
            'author_profile' => $this->plugin_data['AuthorURI'] ?? '',
            'homepage' => 'https://github.com/' . $this->github_repo,
            'requires' => '5.8',
            'tested' => '6.4',
            'requires_php' => '7.4',
            'download_link' => $release->download_url,
            'sections' => [
                'description' => $this->plugin_data['Description'],
                'changelog' => $this->format_changelog($release->changelog),
            ],
            'banners' => [],
            'icons' => [],
        ];
    }

    /**
     * Get latest release from GitHub API
     *
     * @return false|object Release object or false on failure.
     */
    private function get_latest_release() {
        // Check cache first
        $cached = get_transient($this->cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Fetch from GitHub API
        $api_url = "https://api.github.com/repos/{$this->github_repo}/releases/latest";

        $args = [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
            ],
            'timeout' => 10,
        ];

        // Add GitHub token if defined (for private repos or higher rate limits)
        if (defined('WPMR_PFV_GITHUB_TOKEN') && WPMR_PFV_GITHUB_TOKEN) {
            $args['headers']['Authorization'] = 'token ' . WPMR_PFV_GITHUB_TOKEN;
        }

        $response = wp_remote_get($api_url, $args);

        // Handle errors
        if (is_wp_error($response)) {
            $this->log('GitHub API error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $this->log('GitHub API returned status code: ' . $response_code);
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response));

        if (!isset($body->tag_name)) {
            $this->log('Invalid GitHub API response: missing tag_name');
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
            $this->log('No ZIP asset found, using zipball URL');
        }

        // Create release object
        $release = (object) [
            'version' => ltrim($body->tag_name, 'v'),
            'url' => $body->html_url,
            'download_url' => $download_url,
            'changelog' => isset($body->body) ? $body->body : '',
            'published_at' => isset($body->published_at) ? $body->published_at : '',
        ];

        // Cache for 12 hours
        set_transient($this->cache_key, $release, $this->cache_duration);

        $this->log('Fetched latest release: ' . $release->version);

        return $release;
    }

    /**
     * Format changelog for display
     *
     * @param string $changelog Raw changelog from GitHub.
     * @return string Formatted changelog HTML.
     */
    private function format_changelog($changelog) {
        if (empty($changelog)) {
            return '<p>No changelog available.</p>';
        }

        // Convert markdown to HTML (basic conversion)
        $changelog = wpautop($changelog);
        
        // Convert markdown headers
        $changelog = preg_replace('/^## (.+)$/m', '<h3>$1</h3>', $changelog);
        $changelog = preg_replace('/^### (.+)$/m', '<h4>$1</h4>', $changelog);
        
        // Convert markdown lists
        $changelog = preg_replace('/^\- (.+)$/m', '<li>$1</li>', $changelog);
        $changelog = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $changelog);

        return $changelog;
    }

    /**
     * Force update check (for debugging)
     *
     * Adds a query parameter to force WordPress to check for updates.
     * Usage: /wp-admin/plugins.php?wpmr_pfv_force_update=1
     */
    public function force_update_check() {
        if (isset($_GET['wpmr_pfv_force_update']) && current_user_can('update_plugins')) {
            delete_transient($this->cache_key);
            delete_site_transient('update_plugins');
            wp_redirect(admin_url('plugins.php'));
            exit;
        }
    }

    /**
     * Log debug messages
     *
     * @param string $message Log message.
     */
    private function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('WPMR PFV GitHub Updater: ' . $message);
        }
    }
}
