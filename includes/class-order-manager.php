<?php
/**
 * Order Manager Class
 *
 * Handles all order-related operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WSC_Order_Manager {

    /**
     * Get all orders with pagination
     */
    public function get_orders($args = array()) {
        $defaults = array(
            'limit' => 20,
            'page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'status' => 'any',
        );

        $args = wp_parse_args($args, $defaults);

        // Get orders
        $orders = wc_get_orders($args);

        // Get total count
        $total_args = $args;
        $total_args['limit'] = -1;
        $total_args['return'] = 'ids';
        $total_orders = wc_get_orders($total_args);

        return array(
            'orders' => $orders,
            'total' => count($total_orders),
            'pages' => ceil(count($total_orders) / $args['limit'])
        );
    }

    /**
     * Get single order
     */
    public function get_order($order_id) {
        return wc_get_order($order_id);
    }

    /**
     * Update order status
     */
    public function update_order_status($order_id, $new_status) {
        $order = wc_get_order($order_id);

        if ($order) {
            $order->update_status($new_status);
            return true;
        }

        return false;
    }

    /**
     * Get order statistics
     */
    public function get_order_stats($period = 'month') {
        $stats = array(
            'total_orders' => 0,
            'total_sales' => 0,
            'pending_orders' => 0,
            'processing_orders' => 0,
            'completed_orders' => 0,
            'cancelled_orders' => 0,
        );

        // Get date range
        $date_range = $this->get_date_range($period);

        // Total orders in period
        $orders = wc_get_orders(array(
            'limit' => -1,
            'date_created' => $date_range,
        ));

        $stats['total_orders'] = count($orders);

        // Calculate total sales and status counts
        foreach ($orders as $order) {
            $stats['total_sales'] += $order->get_total();

            $status = $order->get_status();
            switch ($status) {
                case 'pending':
                    $stats['pending_orders']++;
                    break;
                case 'processing':
                    $stats['processing_orders']++;
                    break;
                case 'completed':
                    $stats['completed_orders']++;
                    break;
                case 'cancelled':
                case 'failed':
                    $stats['cancelled_orders']++;
                    break;
            }
        }

        return $stats;
    }

    /**
     * Get recent orders
     */
    public function get_recent_orders($limit = 10) {
        return wc_get_orders(array(
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
    }

    /**
     * Get top selling products
     */
    public function get_top_selling_products($limit = 10) {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT
                items.product_id,
                SUM(items.product_qty) as total_sales
            FROM
                {$wpdb->prefix}woocommerce_order_items as orders
            INNER JOIN
                {$wpdb->prefix}woocommerce_order_itemmeta as items
                ON orders.order_item_id = items.order_item_id
            WHERE
                items.meta_key = '_product_id'
            GROUP BY
                items.product_id
            ORDER BY
                total_sales DESC
            LIMIT %d
        ", $limit);

        $results = $wpdb->get_results($query);

        $products = array();
        foreach ($results as $result) {
            $product = wc_get_product($result->product_id);
            if ($product) {
                $products[] = array(
                    'product' => $product,
                    'total_sales' => $result->total_sales
                );
            }
        }

        return $products;
    }

    /**
     * Get order by status
     */
    public function get_orders_by_status($status) {
        return wc_get_orders(array(
            'limit' => -1,
            'status' => $status,
        ));
    }

    /**
     * Get date range for statistics
     */
    private function get_date_range($period) {
        $end_date = current_time('timestamp');

        switch ($period) {
            case 'today':
                $start_date = strtotime('today', $end_date);
                break;
            case 'week':
                $start_date = strtotime('-7 days', $end_date);
                break;
            case 'month':
                $start_date = strtotime('-30 days', $end_date);
                break;
            case 'year':
                $start_date = strtotime('-365 days', $end_date);
                break;
            default:
                $start_date = strtotime('-30 days', $end_date);
        }

        return $start_date . '...' . $end_date;
    }

    /**
     * Get order totals by status
     */
    public function get_status_totals() {
        $statuses = wc_get_order_statuses();
        $totals = array();

        foreach ($statuses as $status => $label) {
            $count = wc_orders_count($status);
            $totals[$status] = array(
                'label' => $label,
                'count' => $count
            );
        }

        return $totals;
    }

    /**
     * Search orders
     */
    public function search_orders($search_term) {
        return wc_get_orders(array(
            's' => $search_term,
            'limit' => -1,
        ));
    }

    /**
     * Add note to order
     */
    public function add_order_note($order_id, $note, $is_customer_note = false) {
        $order = wc_get_order($order_id);

        if ($order) {
            $order->add_order_note($note, $is_customer_note);
            return true;
        }

        return false;
    }

    /**
     * Get order notes
     */
    public function get_order_notes($order_id) {
        $notes = wc_get_order_notes(array(
            'order_id' => $order_id,
        ));

        return $notes;
    }
}
