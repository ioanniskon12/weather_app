<?php
/**
 * Plugin Name: WooCommerce Shop CRM
 * Plugin URI: https://github.com/ioanniskon12/weather_app
 * Description: A comprehensive CRM system for WooCommerce shop owners to manage products, orders, and offers all in one place.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://github.com/ioanniskon12
 * Text Domain: woo-shop-crm
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WOO_SHOP_CRM_VERSION', '1.0.0');
define('WOO_SHOP_CRM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOO_SHOP_CRM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WOO_SHOP_CRM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main WooCommerce Shop CRM Class
 */
class WooCommerce_Shop_CRM {

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Get instance of the class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        $this->init_hooks();
        $this->includes();
    }

    /**
     * Check if WooCommerce is active
     */
    private function is_woocommerce_active() {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('WooCommerce Shop CRM requires WooCommerce to be installed and active.', 'woo-shop-crm'); ?></p>
        </div>
        <?php
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // AJAX handlers
        add_action('wp_ajax_wsc_save_product', array($this, 'ajax_save_product'));
        add_action('wp_ajax_wsc_delete_product', array($this, 'ajax_delete_product'));
        add_action('wp_ajax_wsc_update_order_status', array($this, 'ajax_update_order_status'));
        add_action('wp_ajax_wsc_save_offer', array($this, 'ajax_save_offer'));
        add_action('wp_ajax_wsc_delete_offer', array($this, 'ajax_delete_offer'));
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once WOO_SHOP_CRM_PLUGIN_DIR . 'includes/class-product-manager.php';
        require_once WOO_SHOP_CRM_PLUGIN_DIR . 'includes/class-order-manager.php';
        require_once WOO_SHOP_CRM_PLUGIN_DIR . 'includes/class-offer-manager.php';
        require_once WOO_SHOP_CRM_PLUGIN_DIR . 'includes/class-dashboard.php';
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create custom tables if needed
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Table for offers/discounts
        $table_name = $wpdb->prefix . 'wsc_offers';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            offer_name varchar(255) NOT NULL,
            offer_type varchar(50) NOT NULL,
            offer_value decimal(10,2) NOT NULL,
            min_purchase decimal(10,2) DEFAULT 0,
            start_date datetime DEFAULT NULL,
            end_date datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Set default options
        add_option('woo_shop_crm_version', WOO_SHOP_CRM_VERSION);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Cleanup if needed
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Shop CRM', 'woo-shop-crm'),
            __('Shop CRM', 'woo-shop-crm'),
            'manage_woocommerce',
            'woo-shop-crm',
            array($this, 'render_dashboard'),
            'dashicons-store',
            56
        );

        // Dashboard submenu
        add_submenu_page(
            'woo-shop-crm',
            __('Dashboard', 'woo-shop-crm'),
            __('Dashboard', 'woo-shop-crm'),
            'manage_woocommerce',
            'woo-shop-crm',
            array($this, 'render_dashboard')
        );

        // Products submenu
        add_submenu_page(
            'woo-shop-crm',
            __('Products', 'woo-shop-crm'),
            __('Products', 'woo-shop-crm'),
            'manage_woocommerce',
            'woo-shop-crm-products',
            array($this, 'render_products')
        );

        // Orders submenu
        add_submenu_page(
            'woo-shop-crm',
            __('Orders', 'woo-shop-crm'),
            __('Orders', 'woo-shop-crm'),
            'manage_woocommerce',
            'woo-shop-crm-orders',
            array($this, 'render_orders')
        );

        // Offers submenu
        add_submenu_page(
            'woo-shop-crm',
            __('Offers & Discounts', 'woo-shop-crm'),
            __('Offers & Discounts', 'woo-shop-crm'),
            'manage_woocommerce',
            'woo-shop-crm-offers',
            array($this, 'render_offers')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'woo-shop-crm') === false) {
            return;
        }

        wp_enqueue_style(
            'woo-shop-crm-admin',
            WOO_SHOP_CRM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WOO_SHOP_CRM_VERSION
        );

        wp_enqueue_script(
            'woo-shop-crm-admin',
            WOO_SHOP_CRM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WOO_SHOP_CRM_VERSION,
            true
        );

        wp_localize_script('woo-shop-crm-admin', 'wscCRM', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wsc_crm_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'woo-shop-crm'),
                'error' => __('An error occurred. Please try again.', 'woo-shop-crm'),
                'success' => __('Action completed successfully.', 'woo-shop-crm'),
            )
        ));
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        include WOO_SHOP_CRM_PLUGIN_DIR . 'templates/dashboard.php';
    }

    /**
     * Render products page
     */
    public function render_products() {
        include WOO_SHOP_CRM_PLUGIN_DIR . 'templates/products.php';
    }

    /**
     * Render orders page
     */
    public function render_orders() {
        include WOO_SHOP_CRM_PLUGIN_DIR . 'templates/orders.php';
    }

    /**
     * Render offers page
     */
    public function render_offers() {
        include WOO_SHOP_CRM_PLUGIN_DIR . 'templates/offers.php';
    }

    /**
     * AJAX: Save product
     */
    public function ajax_save_product() {
        check_ajax_referer('wsc_crm_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $product_manager = new WSC_Product_Manager();
        $result = $product_manager->save_product($_POST);

        if ($result) {
            wp_send_json_success(array('message' => 'Product saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save product'));
        }
    }

    /**
     * AJAX: Delete product
     */
    public function ajax_delete_product() {
        check_ajax_referer('wsc_crm_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $product_id = intval($_POST['product_id']);
        $product = wc_get_product($product_id);

        if ($product && $product->delete(true)) {
            wp_send_json_success(array('message' => 'Product deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete product'));
        }
    }

    /**
     * AJAX: Update order status
     */
    public function ajax_update_order_status() {
        check_ajax_referer('wsc_crm_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $order_manager = new WSC_Order_Manager();
        $result = $order_manager->update_order_status($_POST['order_id'], $_POST['status']);

        if ($result) {
            wp_send_json_success(array('message' => 'Order status updated'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update order'));
        }
    }

    /**
     * AJAX: Save offer
     */
    public function ajax_save_offer() {
        check_ajax_referer('wsc_crm_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $offer_manager = new WSC_Offer_Manager();
        $result = $offer_manager->save_offer($_POST);

        if ($result) {
            wp_send_json_success(array('message' => 'Offer saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save offer'));
        }
    }

    /**
     * AJAX: Delete offer
     */
    public function ajax_delete_offer() {
        check_ajax_referer('wsc_crm_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $offer_manager = new WSC_Offer_Manager();
        $result = $offer_manager->delete_offer($_POST['offer_id']);

        if ($result) {
            wp_send_json_success(array('message' => 'Offer deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete offer'));
        }
    }
}

// Initialize the plugin
function woo_shop_crm_init() {
    return WooCommerce_Shop_CRM::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'woo_shop_crm_init');
