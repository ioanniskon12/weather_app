<?php
/**
 * WPML Integration Class
 *
 * Handles WPML (WordPress Multilingual) compatibility
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WSC_WPML_Integration {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Only initialize if WPML is active
        if ($this->is_wpml_active()) {
            $this->init_hooks();
        }
    }

    /**
     * Check if WPML is active
     */
    private function is_wpml_active() {
        return defined('ICL_SITEPRESS_VERSION') || class_exists('SitePress');
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register plugin strings for translation
        add_action('init', array($this, 'register_strings'));

        // Filter products by current language
        add_filter('woocommerce_product_query_meta_query', array($this, 'filter_products_by_language'), 10, 2);

        // Add language column to products table
        add_action('manage_product_posts_custom_column', array($this, 'add_language_column'), 10, 2);

        // Add language switcher to CRM pages
        add_action('admin_notices', array($this, 'add_language_switcher'));
    }

    /**
     * Register strings for WPML translation
     */
    public function register_strings() {
        if (function_exists('icl_register_string')) {
            // Register plugin strings
            icl_register_string('woo-shop-crm', 'Dashboard', 'Dashboard');
            icl_register_string('woo-shop-crm', 'Products', 'Products');
            icl_register_string('woo-shop-crm', 'Orders', 'Orders');
            icl_register_string('woo-shop-crm', 'Offers & Discounts', 'Offers & Discounts');
            icl_register_string('woo-shop-crm', 'Image Settings', 'Image Settings');
            icl_register_string('woo-shop-crm', 'Shop CRM', 'Shop CRM');
        }
    }

    /**
     * Filter products by current language
     */
    public function filter_products_by_language($meta_query, $query) {
        if (is_admin() && function_exists('icl_object_id')) {
            global $sitepress;
            if ($sitepress) {
                $current_language = $sitepress->get_current_language();
                if ($current_language) {
                    // WPML handles this automatically for product queries
                    return $meta_query;
                }
            }
        }
        return $meta_query;
    }

    /**
     * Add language column to products table
     */
    public function add_language_column($column, $post_id) {
        if ($column === 'language' && function_exists('wpml_get_language_information')) {
            $lang_info = wpml_get_language_information(null, $post_id);
            if ($lang_info && !is_wp_error($lang_info)) {
                echo '<img src="' . esc_url($lang_info['language_flag_url']) . '" alt="' . esc_attr($lang_info['language_code']) . '" title="' . esc_attr($lang_info['native_name']) . '" />';
            }
        }
    }

    /**
     * Add language switcher to CRM pages
     */
    public function add_language_switcher() {
        $screen = get_current_screen();

        // Only show on CRM pages
        if (!$screen || strpos($screen->id, 'woo-shop-crm') === false) {
            return;
        }

        if (function_exists('icl_get_languages')) {
            global $sitepress;
            $current_language = $sitepress->get_current_language();
            $languages = icl_get_languages('skip_missing=0');

            if (!empty($languages)) {
                echo '<div class="wsc-wpml-language-switcher" style="background: #fff; padding: 10px 20px; border-left: 4px solid #2271b1; margin: 20px 0;">';
                echo '<strong>' . __('Language:', 'woo-shop-crm') . '</strong> ';

                foreach ($languages as $lang) {
                    $active_class = ($lang['language_code'] === $current_language) ? 'current' : '';
                    $url = admin_url('admin.php?page=' . $_GET['page'] . '&lang=' . $lang['language_code']);

                    echo '<a href="' . esc_url($url) . '" class="' . esc_attr($active_class) . '" style="margin: 0 10px; text-decoration: none;">';
                    echo '<img src="' . esc_url($lang['country_flag_url']) . '" alt="' . esc_attr($lang['language_code']) . '" style="vertical-align: middle; margin-right: 5px;" />';
                    echo esc_html($lang['native_name']);
                    if ($lang['language_code'] === $current_language) {
                        echo ' ✓';
                    }
                    echo '</a>';
                }

                echo '</div>';
            }
        }
    }

    /**
     * Get current language
     */
    public function get_current_language() {
        if (function_exists('icl_get_current_language')) {
            return icl_get_current_language();
        }
        return 'en'; // Default
    }

    /**
     * Get product in specific language
     */
    public function get_product_translation($product_id, $language_code) {
        if (function_exists('icl_object_id')) {
            return icl_object_id($product_id, 'product', false, $language_code);
        }
        return $product_id;
    }

    /**
     * Get all product translations
     */
    public function get_product_translations($product_id) {
        if (function_exists('wpml_get_language_information')) {
            global $sitepress;
            $trid = $sitepress->get_element_trid($product_id, 'post_product');
            return $sitepress->get_element_translations($trid, 'post_product');
        }
        return array();
    }

    /**
     * Sync product data across translations
     */
    public function sync_product_translations($product_id, $data_to_sync = array()) {
        $translations = $this->get_product_translations($product_id);

        foreach ($translations as $lang_code => $translation) {
            if ($translation->element_id != $product_id) {
                $translated_product = wc_get_product($translation->element_id);

                if ($translated_product) {
                    // Sync non-translatable fields (prices, SKU, stock)
                    if (isset($data_to_sync['price'])) {
                        $translated_product->set_regular_price($data_to_sync['price']);
                    }
                    if (isset($data_to_sync['sale_price'])) {
                        $translated_product->set_sale_price($data_to_sync['sale_price']);
                    }
                    if (isset($data_to_sync['stock'])) {
                        $translated_product->set_stock_quantity($data_to_sync['stock']);
                    }
                    if (isset($data_to_sync['sku'])) {
                        $translated_product->set_sku($data_to_sync['sku']);
                    }

                    $translated_product->save();
                }
            }
        }
    }

    /**
     * Filter products query by language
     */
    public function filter_products_query($args) {
        if ($this->is_wpml_active() && function_exists('icl_get_current_language')) {
            $current_lang = icl_get_current_language();

            // WPML automatically filters WooCommerce product queries
            // But we can add explicit language parameter if needed
            $args['wpml_language'] = $current_lang;
        }

        return $args;
    }

    /**
     * Get language flag HTML
     */
    public function get_language_flag($language_code) {
        if (function_exists('icl_get_languages')) {
            $languages = icl_get_languages('skip_missing=0');

            if (isset($languages[$language_code])) {
                return '<img src="' . esc_url($languages[$language_code]['country_flag_url']) . '"
                        alt="' . esc_attr($language_code) . '"
                        title="' . esc_attr($languages[$language_code]['native_name']) . '"
                        style="width: 18px; height: 12px; vertical-align: middle;" />';
            }
        }

        return '';
    }

    /**
     * Check if product has translations
     */
    public function has_translations($product_id) {
        $translations = $this->get_product_translations($product_id);
        return count($translations) > 1;
    }

    /**
     * Get translation status badge
     */
    public function get_translation_status($product_id) {
        if (!$this->is_wpml_active()) {
            return '';
        }

        $translations = $this->get_product_translations($product_id);
        $total_languages = count(icl_get_languages('skip_missing=0'));
        $translated_languages = count($translations);

        if ($translated_languages === $total_languages) {
            return '<span class="wsc-translation-complete" style="color: #00a32a;">✓ ' . __('All languages', 'woo-shop-crm') . '</span>';
        } elseif ($translated_languages > 1) {
            return '<span class="wsc-translation-partial" style="color: #f0b849;">⚠ ' . sprintf(__('%d of %d languages', 'woo-shop-crm'), $translated_languages, $total_languages) . '</span>';
        } else {
            return '<span class="wsc-translation-none" style="color: #d63638;">✗ ' . __('No translations', 'woo-shop-crm') . '</span>';
        }
    }
}

// Initialize WPML integration
function wsc_wpml_init() {
    return WSC_WPML_Integration::get_instance();
}

add_action('plugins_loaded', 'wsc_wpml_init', 20);
