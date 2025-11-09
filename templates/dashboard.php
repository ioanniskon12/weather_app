<?php
/**
 * Dashboard Template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$dashboard = new WSC_Dashboard();
$quick_stats = $dashboard->get_quick_stats();
$overview = $dashboard->get_overview_data('month');
?>

<div class="wrap wsc-dashboard">
    <h1><?php _e('Shop CRM Dashboard', 'woo-shop-crm'); ?></h1>

    <!-- Quick Stats -->
    <div class="wsc-stats-grid">
        <?php foreach ($quick_stats as $stat): ?>
            <div class="wsc-stat-card" style="border-left-color: <?php echo esc_attr($stat['color']); ?>">
                <div class="wsc-stat-icon">
                    <span class="dashicons <?php echo esc_attr($stat['icon']); ?>" style="color: <?php echo esc_attr($stat['color']); ?>"></span>
                </div>
                <div class="wsc-stat-content">
                    <h3><?php echo esc_html($stat['title']); ?></h3>
                    <p class="wsc-stat-value"><?php echo wp_kses_post($stat['value']); ?></p>
                    <a href="<?php echo esc_url($stat['link']); ?>" class="wsc-stat-link">
                        <?php _e('View Details', 'woo-shop-crm'); ?> &rarr;
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="wsc-dashboard-grid">
        <!-- Recent Orders -->
        <div class="wsc-dashboard-section">
            <div class="wsc-section-header">
                <h2><?php _e('Recent Orders', 'woo-shop-crm'); ?></h2>
                <a href="<?php echo admin_url('admin.php?page=woo-shop-crm-orders'); ?>" class="button">
                    <?php _e('View All Orders', 'woo-shop-crm'); ?>
                </a>
            </div>
            <div class="wsc-section-content">
                <?php if (!empty($overview['recent_orders'])): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Order', 'woo-shop-crm'); ?></th>
                                <th><?php _e('Customer', 'woo-shop-crm'); ?></th>
                                <th><?php _e('Total', 'woo-shop-crm'); ?></th>
                                <th><?php _e('Status', 'woo-shop-crm'); ?></th>
                                <th><?php _e('Date', 'woo-shop-crm'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overview['recent_orders'] as $order): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <a href="<?php echo esc_url($order->get_edit_order_url()); ?>">
                                                #<?php echo $order->get_order_number(); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td><?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?></td>
                                    <td><?php echo $order->get_formatted_order_total(); ?></td>
                                    <td>
                                        <span class="wsc-status wsc-status-<?php echo esc_attr($order->get_status()); ?>">
                                            <?php echo wc_get_order_status_name($order->get_status()); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $order->get_date_created()->date_i18n('M j, Y'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="wsc-no-data"><?php _e('No recent orders found.', 'woo-shop-crm'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Selling Products -->
        <div class="wsc-dashboard-section">
            <div class="wsc-section-header">
                <h2><?php _e('Top Selling Products', 'woo-shop-crm'); ?></h2>
                <a href="<?php echo admin_url('admin.php?page=woo-shop-crm-products'); ?>" class="button">
                    <?php _e('View All Products', 'woo-shop-crm'); ?>
                </a>
            </div>
            <div class="wsc-section-content">
                <?php if (!empty($overview['top_selling'])): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Product', 'woo-shop-crm'); ?></th>
                                <th><?php _e('Sales', 'woo-shop-crm'); ?></th>
                                <th><?php _e('Stock', 'woo-shop-crm'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overview['top_selling'] as $item): ?>
                                <?php $product = $item['product']; ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <a href="<?php echo get_edit_post_link($product->get_id()); ?>">
                                                <?php echo esc_html($product->get_name()); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td><?php echo esc_html($item['total_sales']); ?> <?php _e('units', 'woo-shop-crm'); ?></td>
                                    <td>
                                        <?php if ($product->managing_stock()): ?>
                                            <span class="<?php echo $product->get_stock_quantity() <= 5 ? 'wsc-low-stock' : ''; ?>">
                                                <?php echo esc_html($product->get_stock_quantity()); ?>
                                            </span>
                                        <?php else: ?>
                                            <span><?php _e('Not managed', 'woo-shop-crm'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="wsc-no-data"><?php _e('No sales data available.', 'woo-shop-crm'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Low Stock Products -->
        <div class="wsc-dashboard-section">
            <div class="wsc-section-header">
                <h2><?php _e('Low Stock Alert', 'woo-shop-crm'); ?></h2>
            </div>
            <div class="wsc-section-content">
                <?php if (!empty($overview['low_stock_products'])): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Product', 'woo-shop-crm'); ?></th>
                                <th><?php _e('Stock', 'woo-shop-crm'); ?></th>
                                <th><?php _e('Action', 'woo-shop-crm'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overview['low_stock_products'] as $product): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($product->get_name()); ?></strong>
                                    </td>
                                    <td>
                                        <span class="wsc-low-stock">
                                            <?php echo esc_html($product->get_stock_quantity()); ?> <?php _e('left', 'woo-shop-crm'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($product->get_id()); ?>" class="button button-small">
                                            <?php _e('Update Stock', 'woo-shop-crm'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="wsc-no-data"><?php _e('No low stock products.', 'woo-shop-crm'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
