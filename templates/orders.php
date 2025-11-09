<?php
/**
 * Orders Management Template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$order_manager = new WSC_Order_Manager();

// Get current page and status filter
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'any';

// Get orders
$result = $order_manager->get_orders(array(
    'page' => $current_page,
    'limit' => 20,
    'status' => $status_filter,
));

$orders = $result['orders'];
$total_pages = $result['pages'];

// Get status totals
$status_totals = $order_manager->get_status_totals();

// Get order statuses
$order_statuses = wc_get_order_statuses();
?>

<div class="wrap wsc-orders">
    <h1><?php _e('Orders Management', 'woo-shop-crm'); ?></h1>

    <!-- Status Filters -->
    <ul class="subsubsub">
        <li>
            <a href="<?php echo admin_url('admin.php?page=woo-shop-crm-orders'); ?>"
               class="<?php echo $status_filter === 'any' ? 'current' : ''; ?>">
                <?php _e('All', 'woo-shop-crm'); ?>
                <span class="count">(<?php echo array_sum(array_column($status_totals, 'count')); ?>)</span>
            </a> |
        </li>
        <?php foreach ($status_totals as $status => $data): ?>
            <?php if ($data['count'] > 0): ?>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=woo-shop-crm-orders&status=' . $status); ?>"
                       class="<?php echo $status_filter === $status ? 'current' : ''; ?>">
                        <?php echo esc_html($data['label']); ?>
                        <span class="count">(<?php echo $data['count']; ?>)</span>
                    </a>
                    <?php echo next($status_totals) ? ' | ' : ''; ?>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>

    <!-- Order View Modal -->
    <div id="wsc-order-modal" class="wsc-modal" style="display: none;">
        <div class="wsc-modal-content wsc-modal-large">
            <div class="wsc-modal-header">
                <h2 id="wsc-order-modal-title"><?php _e('Order Details', 'woo-shop-crm'); ?></h2>
                <span class="wsc-modal-close">&times;</span>
            </div>
            <div class="wsc-modal-body" id="wsc-order-details">
                <!-- Order details will be loaded here via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Orders List -->
    <div class="wsc-orders-list">
        <?php if (!empty($orders)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Order', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Date', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Customer', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Items', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Total', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Status', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Actions', 'woo-shop-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="<?php echo esc_url($order->get_edit_order_url()); ?>">
                                        #<?php echo $order->get_order_number(); ?>
                                    </a>
                                </strong>
                            </td>
                            <td><?php echo $order->get_date_created()->date_i18n('M j, Y g:i A'); ?></td>
                            <td>
                                <?php
                                $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                                echo esc_html($customer_name);
                                ?>
                                <br>
                                <small><?php echo esc_html($order->get_billing_email()); ?></small>
                            </td>
                            <td><?php echo $order->get_item_count(); ?> <?php _e('items', 'woo-shop-crm'); ?></td>
                            <td><?php echo $order->get_formatted_order_total(); ?></td>
                            <td>
                                <select class="wsc-order-status-select"
                                        data-order-id="<?php echo esc_attr($order->get_id()); ?>">
                                    <?php foreach ($order_statuses as $status_key => $status_label): ?>
                                        <option value="<?php echo esc_attr($status_key); ?>"
                                                <?php selected($order->get_status(), str_replace('wc-', '', $status_key)); ?>>
                                            <?php echo esc_html($status_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($order->get_edit_order_url()); ?>"
                                   class="button button-small">
                                    <?php _e('View', 'woo-shop-crm'); ?>
                                </a>
                                <button class="button button-small wsc-view-order-details"
                                        data-order-id="<?php echo esc_attr($order->get_id()); ?>">
                                    <?php _e('Quick View', 'woo-shop-crm'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        $pagination_args = array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $current_page,
                        );

                        if ($status_filter !== 'any') {
                            $pagination_args['add_args'] = array('status' => $status_filter);
                        }

                        echo paginate_links($pagination_args);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="wsc-no-data">
                <p><?php _e('No orders found.', 'woo-shop-crm'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Statistics -->
    <div class="wsc-order-stats">
        <h2><?php _e('Order Statistics', 'woo-shop-crm'); ?></h2>
        <?php
        $stats = $order_manager->get_order_stats('month');
        ?>
        <div class="wsc-stats-grid">
            <div class="wsc-stat-box">
                <h3><?php _e('Total Sales (This Month)', 'woo-shop-crm'); ?></h3>
                <p class="wsc-stat-value"><?php echo wc_price($stats['total_sales']); ?></p>
            </div>
            <div class="wsc-stat-box">
                <h3><?php _e('Total Orders', 'woo-shop-crm'); ?></h3>
                <p class="wsc-stat-value"><?php echo $stats['total_orders']; ?></p>
            </div>
            <div class="wsc-stat-box">
                <h3><?php _e('Pending Orders', 'woo-shop-crm'); ?></h3>
                <p class="wsc-stat-value"><?php echo $stats['pending_orders']; ?></p>
            </div>
            <div class="wsc-stat-box">
                <h3><?php _e('Completed Orders', 'woo-shop-crm'); ?></h3>
                <p class="wsc-stat-value"><?php echo $stats['completed_orders']; ?></p>
            </div>
        </div>
    </div>
</div>
