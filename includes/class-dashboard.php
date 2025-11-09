<?php
/**
 * Dashboard Class
 *
 * Handles dashboard statistics and data aggregation
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WSC_Dashboard {

    private $product_manager;
    private $order_manager;
    private $offer_manager;

    public function __construct() {
        $this->product_manager = new WSC_Product_Manager();
        $this->order_manager = new WSC_Order_Manager();
        $this->offer_manager = new WSC_Offer_Manager();
    }

    /**
     * Get dashboard overview data
     */
    public function get_overview_data($period = 'month') {
        return array(
            'products' => $this->product_manager->get_product_stats(),
            'orders' => $this->order_manager->get_order_stats($period),
            'offers' => $this->offer_manager->get_offer_stats(),
            'recent_orders' => $this->order_manager->get_recent_orders(5),
            'low_stock_products' => $this->product_manager->get_low_stock_products(5),
            'top_selling' => $this->order_manager->get_top_selling_products(5),
        );
    }

    /**
     * Get sales statistics
     */
    public function get_sales_stats($period = 'month') {
        $stats = $this->order_manager->get_order_stats($period);

        return array(
            'total_sales' => wc_price($stats['total_sales']),
            'total_orders' => $stats['total_orders'],
            'average_order_value' => $stats['total_orders'] > 0
                ? wc_price($stats['total_sales'] / $stats['total_orders'])
                : wc_price(0),
        );
    }

    /**
     * Get quick stats for dashboard cards
     */
    public function get_quick_stats() {
        $product_stats = $this->product_manager->get_product_stats();
        $order_stats = $this->order_manager->get_order_stats('today');
        $offer_stats = $this->offer_manager->get_offer_stats();

        return array(
            array(
                'title' => 'Total Products',
                'value' => $product_stats['total'],
                'icon' => 'dashicons-products',
                'color' => '#2271b1',
                'link' => admin_url('admin.php?page=woo-shop-crm-products'),
            ),
            array(
                'title' => 'Total Orders',
                'value' => $order_stats['total_orders'],
                'icon' => 'dashicons-cart',
                'color' => '#00a32a',
                'link' => admin_url('admin.php?page=woo-shop-crm-orders'),
            ),
            array(
                'title' => 'Today\'s Sales',
                'value' => wc_price($order_stats['total_sales']),
                'icon' => 'dashicons-money-alt',
                'color' => '#d63638',
                'link' => admin_url('admin.php?page=woo-shop-crm-orders'),
            ),
            array(
                'title' => 'Active Offers',
                'value' => $offer_stats['active'],
                'icon' => 'dashicons-tag',
                'color' => '#f0b849',
                'link' => admin_url('admin.php?page=woo-shop-crm-offers'),
            ),
            array(
                'title' => 'Low Stock Items',
                'value' => $product_stats['low_stock'],
                'icon' => 'dashicons-warning',
                'color' => '#f56e28',
                'link' => admin_url('admin.php?page=woo-shop-crm-products&filter=low_stock'),
            ),
            array(
                'title' => 'Pending Orders',
                'value' => $order_stats['pending_orders'],
                'icon' => 'dashicons-clock',
                'color' => '#a7aaad',
                'link' => admin_url('admin.php?page=woo-shop-crm-orders&status=pending'),
            ),
        );
    }
}
