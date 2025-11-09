<?php
/**
 * Offer Manager Class
 *
 * Handles all offer and discount-related operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WSC_Offer_Manager {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wsc_offers';
    }

    /**
     * Get all offers
     */
    public function get_offers($args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => 'all',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $where = '';
        if ($args['status'] !== 'all') {
            $where = $wpdb->prepare(" WHERE status = %s", sanitize_text_field($args['status']));
        }

        $query = "SELECT * FROM {$this->table_name}
                  {$where}
                  ORDER BY {$args['orderby']} {$args['order']}
                  LIMIT {$args['limit']} OFFSET {$args['offset']}";

        $offers = $wpdb->get_results($query);

        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} {$where}");

        return array(
            'offers' => $offers,
            'total' => $total,
            'pages' => ceil($total / $args['limit'])
        );
    }

    /**
     * Get single offer
     */
    public function get_offer($offer_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $offer_id
        ));
    }

    /**
     * Save offer (create or update)
     */
    public function save_offer($data) {
        global $wpdb;

        try {
            // Sanitize data
            $offer_id = isset($data['offer_id']) ? intval($data['offer_id']) : 0;
            $offer_name = sanitize_text_field($data['offer_name']);
            $offer_type = sanitize_text_field($data['offer_type']); // percentage, fixed, free_shipping
            $offer_value = floatval($data['offer_value']);
            $min_purchase = isset($data['min_purchase']) ? floatval($data['min_purchase']) : 0;
            $start_date = isset($data['start_date']) ? sanitize_text_field($data['start_date']) : null;
            $end_date = isset($data['end_date']) ? sanitize_text_field($data['end_date']) : null;
            $status = isset($data['status']) ? sanitize_text_field($data['status']) : 'active';

            $offer_data = array(
                'offer_name' => $offer_name,
                'offer_type' => $offer_type,
                'offer_value' => $offer_value,
                'min_purchase' => $min_purchase,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => $status,
            );

            if ($offer_id > 0) {
                // Update existing offer
                $result = $wpdb->update(
                    $this->table_name,
                    $offer_data,
                    array('id' => $offer_id),
                    array('%s', '%s', '%f', '%f', '%s', '%s', '%s'),
                    array('%d')
                );

                return $result !== false ? $offer_id : false;
            } else {
                // Insert new offer
                $offer_data['created_at'] = current_time('mysql');

                $result = $wpdb->insert(
                    $this->table_name,
                    $offer_data,
                    array('%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s')
                );

                return $result ? $wpdb->insert_id : false;
            }

        } catch (Exception $e) {
            error_log('Offer save error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete offer
     */
    public function delete_offer($offer_id) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_name,
            array('id' => $offer_id),
            array('%d')
        );
    }

    /**
     * Get active offers
     */
    public function get_active_offers() {
        global $wpdb;

        $current_date = current_time('mysql');

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             WHERE status = 'active'
             AND (start_date IS NULL OR start_date <= %s)
             AND (end_date IS NULL OR end_date >= %s)
             ORDER BY created_at DESC",
            $current_date,
            $current_date
        );

        return $wpdb->get_results($query);
    }

    /**
     * Update offer status
     */
    public function update_status($offer_id, $status) {
        global $wpdb;

        return $wpdb->update(
            $this->table_name,
            array('status' => $status),
            array('id' => $offer_id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Get offer statistics
     */
    public function get_offer_stats() {
        global $wpdb;

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        $active = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'active'");
        $expired = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name}
             WHERE status = 'active' AND end_date < %s",
            current_time('mysql')
        ));

        return array(
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'inactive' => $total - $active
        );
    }

    /**
     * Apply offer to cart
     */
    public function apply_offer_to_cart($offer_id) {
        $offer = $this->get_offer($offer_id);

        if (!$offer || $offer->status !== 'active') {
            return false;
        }

        // Check if offer is valid
        if (!$this->is_offer_valid($offer)) {
            return false;
        }

        // Apply discount based on offer type
        switch ($offer->offer_type) {
            case 'percentage':
                WC()->cart->add_discount($offer->offer_name);
                break;
            case 'fixed':
                WC()->cart->add_discount($offer->offer_name);
                break;
            case 'free_shipping':
                // Free shipping is handled via coupons
                break;
        }

        return true;
    }

    /**
     * Check if offer is valid
     */
    private function is_offer_valid($offer) {
        $current_date = current_time('mysql');

        // Check start date
        if ($offer->start_date && $offer->start_date > $current_date) {
            return false;
        }

        // Check end date
        if ($offer->end_date && $offer->end_date < $current_date) {
            return false;
        }

        // Check minimum purchase
        if ($offer->min_purchase > 0) {
            $cart_total = WC()->cart->get_subtotal();
            if ($cart_total < $offer->min_purchase) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get WooCommerce coupons
     */
    public function get_coupons($args = array()) {
        $defaults = array(
            'posts_per_page' => 20,
            'post_type' => 'shop_coupon',
            'post_status' => 'publish',
        );

        $args = wp_parse_args($args, $defaults);

        $coupons = get_posts($args);

        return $coupons;
    }

    /**
     * Create WooCommerce coupon from offer
     */
    public function create_coupon_from_offer($offer_id) {
        $offer = $this->get_offer($offer_id);

        if (!$offer) {
            return false;
        }

        $coupon = new WC_Coupon();
        $coupon->set_code($offer->offer_name);
        $coupon->set_description('Auto-generated from Shop CRM offer');

        // Set discount type
        switch ($offer->offer_type) {
            case 'percentage':
                $coupon->set_discount_type('percent');
                $coupon->set_amount($offer->offer_value);
                break;
            case 'fixed':
                $coupon->set_discount_type('fixed_cart');
                $coupon->set_amount($offer->offer_value);
                break;
            case 'free_shipping':
                $coupon->set_free_shipping(true);
                break;
        }

        // Set minimum amount
        if ($offer->min_purchase > 0) {
            $coupon->set_minimum_amount($offer->min_purchase);
        }

        // Set dates
        if ($offer->start_date) {
            $coupon->set_date_created($offer->start_date);
        }

        if ($offer->end_date) {
            $coupon->set_date_expires($offer->end_date);
        }

        $coupon_id = $coupon->save();

        return $coupon_id;
    }
}
