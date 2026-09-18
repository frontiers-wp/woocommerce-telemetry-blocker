<?php
/**
 * @package       WooCommerce Telemetry Blocker
 * @author        Edwin Bekedam
 * @license       gplv2
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name: WooCommerce Telemetry Blocker
 * Plugin URI:  https://github.com/frontiers-wp/woocommerce-telemetry-blocker
 * Description: Hard-blocks WooCommerce tracker telemetry and wipes tracking options on activation.
 * Version:     1.0.0
 * Author:      Edwin Bekedam
 * Author URI:  https://github.com/frontiers-wp/
 * Text Domain: woocommerce-telemetry-blocker
 * Domain Path: /languages
 * License:     GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with NoDoss. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

/**
 * Define constant with current version.
 */
if (! defined( 'WOOCOMMERCE_TELEMETRY_BLOCKER_VERSION' ) ) {
    define( 'WOOCOMMERCE_TELEMETRY_BLOCKER_VERSION', '1.0.0' );
}

define( 'WOOCOMMERCE_TELEMETRY_BLOCKER_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOCOMMERCE_TELEMETRY_BLOCKER_PLUGIN_FILE', __FILE__ );
define( 'WOOCOMMERCE_TELEMETRY_BLOCKER_PLUGIN_BASE_NAME', basename(__DIR__));

/**
 * Complete Opt-Out for WooCommerce Tracker Telemetry.
 * Ensures that even if tracking is accidentally enabled in settings,
 * no store data (orders, revenue, user counts) is compiled or transmitted.
 *
 * @WC_Tracker_return_false
 * @param array $data The tracked data array.
 * @return array Empty array to block data compilation.
 */
class WooCommerceTelemetryBlocker
{
    /**
     * Create an instance which will be used to register the hooks with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */

    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'inc/init/http.php';
    }
    
    /**
     * Initialize hooks.
     */
    public function __construct()
    {
        // Completely strip out any compiled tracker data if it attempts to generate
        add_filter( 'woocommerce_tracker_data', [ $this, 'wtb_block_tracker_telemetry' ], 999 );
        add_filter( 'woocommerce_apply_user_tracking', '__return_false' );
        add_filter( 'woocommerce_tracker_send_override', '__return_false' );

        // Force the tracking option to always return 'no' regardless of DB state
        add_filter( 'pre_option_woocommerce_allow_tracking', [ $this, 'wtb_force_tracking_disabled' ] );
        add_filter( 'pre_option_woocommerce_allow_tracking', fn() => 'no', 1 );

        // Clean up database options periodically on admin initialization
        add_action( 'admin_init', [ $this, 'wtb_purge_tracking_options' ] );
    }

    /**
     * Overrides and strips out all compiled store insights.
     */
    public function wtb_block_tracker_telemetry( array $data ): array
    {
        return [];
    }

    /**
     * Hard-stops the tracking check from returning 'yes'.
     */
    public function wtb_force_tracking_disabled(): string
    {
        return 'no';
    }

    /**
     * Deletes telemetry schedules and sync records from the database safely.
     * Uses a transient to ensure it runs only once a day instead of every page load.
     */
    public function wtb_purge_tracking_options()
    {
        // Only run if WooCommerce is active to avoid unnecessary DB queries
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // Check if we already purged the database today
        if ( get_transient( 'woo_privacy_telemetry_purged' ) ) {
            return;
        }

        self::execute_database_purge();

        // Set a 24-hour transient to skip this check on subsequent page loads
        set_transient( 'woo_privacy_telemetry_purged', true, DAY_IN_SECONDS );
    }

    /**
     * Shared database wiping logic usable by both cron cycles and activation hooks.
     */
    public static function execute_database_purge()
    {
        // Only clear these if they actually exist to prevent redundant DB writes
        if ( get_option( 'woocommerce_tracker_last_send' ) !== false ) {
            delete_option( 'woocommerce_tracker_last_send' );
        }
        if ( get_option( 'woocommerce_allow_tracking_first_optin' ) !== false ) {
            delete_option( 'woocommerce_allow_tracking_first_optin' );
        }

        // Explicitly set to 'no' in the database as a fallback
        if ( get_option( 'woocommerce_allow_tracking' ) !== 'no' ) {
            update_option( 'woocommerce_allow_tracking', 'no' );
        }

        // Clear out scheduled Action Scheduler tasks for tracking if they exist
        if ( function_exists( 'as_unschedule_all_actions' ) ) {
            as_unschedule_all_actions( 'woocommerce_tracker_send' );
        }
    }

    /**
     * Clears the 24-hour transient when the plugin is deactivated.
     * This ensures that if the plugin is reactivated later, it wipes the DB immediately.
     */
    public static function clear_transient_on_deactivation()
    {
        delete_transient( 'woo_privacy_telemetry_purged' );
    }
}

// Instantiate the class safely
new WooCommerceTelemetryBlocker();

// Register deactivation hook to clean up our transient upon plugin deactivation
register_deactivation_hook( __FILE__, [ 'WooCommerceTelemetryBlocker', 'clear_transient_on_deactivation' ] );

// Call the shared static function directly on activation to bypass instantiation restrictions
register_activation_hook( __FILE__, [ 'WooCommerceTelemetryBlocker', 'execute_database_purge' ] );