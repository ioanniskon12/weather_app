<?php
/**
 * Offers & Discounts Management Template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$offer_manager = new WSC_Offer_Manager();

// Get current page
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

// Get offers
$result = $offer_manager->get_offers(array(
    'limit' => 20,
    'offset' => ($current_page - 1) * 20,
));

$offers = $result['offers'];
$total_pages = $result['pages'];

// Get offer statistics
$stats = $offer_manager->get_offer_stats();
?>

<div class="wrap wsc-offers">
    <h1>
        <?php _e('Offers & Discounts', 'woo-shop-crm'); ?>
        <button class="page-title-action wsc-add-offer-btn">
            <?php _e('Add New Offer', 'woo-shop-crm'); ?>
        </button>
    </h1>

    <!-- Offer Stats -->
    <div class="wsc-stats-grid">
        <div class="wsc-stat-card" style="border-left-color: #2271b1;">
            <div class="wsc-stat-icon">
                <span class="dashicons dashicons-tag" style="color: #2271b1;"></span>
            </div>
            <div class="wsc-stat-content">
                <h3><?php _e('Total Offers', 'woo-shop-crm'); ?></h3>
                <p class="wsc-stat-value"><?php echo $stats['total']; ?></p>
            </div>
        </div>

        <div class="wsc-stat-card" style="border-left-color: #00a32a;">
            <div class="wsc-stat-icon">
                <span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
            </div>
            <div class="wsc-stat-content">
                <h3><?php _e('Active Offers', 'woo-shop-crm'); ?></h3>
                <p class="wsc-stat-value"><?php echo $stats['active']; ?></p>
            </div>
        </div>

        <div class="wsc-stat-card" style="border-left-color: #d63638;">
            <div class="wsc-stat-icon">
                <span class="dashicons dashicons-dismiss" style="color: #d63638;"></span>
            </div>
            <div class="wsc-stat-content">
                <h3><?php _e('Inactive Offers', 'woo-shop-crm'); ?></h3>
                <p class="wsc-stat-value"><?php echo $stats['inactive']; ?></p>
            </div>
        </div>
    </div>

    <!-- Add/Edit Offer Modal -->
    <div id="wsc-offer-modal" class="wsc-modal" style="display: none;">
        <div class="wsc-modal-content">
            <div class="wsc-modal-header">
                <h2 id="wsc-offer-modal-title"><?php _e('Add New Offer', 'woo-shop-crm'); ?></h2>
                <span class="wsc-modal-close">&times;</span>
            </div>
            <div class="wsc-modal-body">
                <form id="wsc-offer-form">
                    <input type="hidden" name="offer_id" id="offer_id" value="">

                    <div class="wsc-form-row">
                        <label for="offer_name">
                            <?php _e('Offer Name', 'woo-shop-crm'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" name="offer_name" id="offer_name" required class="widefat"
                               placeholder="<?php _e('e.g., Summer Sale 2024', 'woo-shop-crm'); ?>">
                    </div>

                    <div class="wsc-form-row">
                        <label for="offer_type">
                            <?php _e('Discount Type', 'woo-shop-crm'); ?> <span class="required">*</span>
                        </label>
                        <select name="offer_type" id="offer_type" required class="widefat">
                            <option value="percentage"><?php _e('Percentage Discount', 'woo-shop-crm'); ?></option>
                            <option value="fixed"><?php _e('Fixed Amount Discount', 'woo-shop-crm'); ?></option>
                            <option value="free_shipping"><?php _e('Free Shipping', 'woo-shop-crm'); ?></option>
                        </select>
                    </div>

                    <div class="wsc-form-row">
                        <label for="offer_value">
                            <?php _e('Discount Value', 'woo-shop-crm'); ?> <span class="required">*</span>
                        </label>
                        <input type="number" step="0.01" name="offer_value" id="offer_value" required class="widefat"
                               placeholder="<?php _e('e.g., 10 for 10% or $10', 'woo-shop-crm'); ?>">
                        <p class="description"><?php _e('Enter percentage value or fixed amount', 'woo-shop-crm'); ?></p>
                    </div>

                    <div class="wsc-form-row">
                        <label for="min_purchase"><?php _e('Minimum Purchase Amount', 'woo-shop-crm'); ?></label>
                        <input type="number" step="0.01" name="min_purchase" id="min_purchase" class="widefat"
                               placeholder="0.00">
                        <p class="description"><?php _e('Leave 0 for no minimum', 'woo-shop-crm'); ?></p>
                    </div>

                    <div class="wsc-form-row-grid">
                        <div class="wsc-form-col">
                            <label for="start_date"><?php _e('Start Date', 'woo-shop-crm'); ?></label>
                            <input type="datetime-local" name="start_date" id="start_date" class="widefat">
                        </div>

                        <div class="wsc-form-col">
                            <label for="end_date"><?php _e('End Date', 'woo-shop-crm'); ?></label>
                            <input type="datetime-local" name="end_date" id="end_date" class="widefat">
                        </div>
                    </div>

                    <div class="wsc-form-row">
                        <label for="status"><?php _e('Status', 'woo-shop-crm'); ?></label>
                        <select name="status" id="status" class="widefat">
                            <option value="active"><?php _e('Active', 'woo-shop-crm'); ?></option>
                            <option value="inactive"><?php _e('Inactive', 'woo-shop-crm'); ?></option>
                        </select>
                    </div>

                    <div class="wsc-form-actions">
                        <button type="submit" class="button button-primary">
                            <?php _e('Save Offer', 'woo-shop-crm'); ?>
                        </button>
                        <button type="button" class="button wsc-modal-close">
                            <?php _e('Cancel', 'woo-shop-crm'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Offers List -->
    <div class="wsc-offers-list">
        <?php if (!empty($offers)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Offer Name', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Type', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Value', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Min Purchase', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Start Date', 'woo-shop-crm'); ?></th>
                        <th><?php _e('End Date', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Status', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Actions', 'woo-shop-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($offers as $offer): ?>
                        <tr>
                            <td><strong><?php echo esc_html($offer->offer_name); ?></strong></td>
                            <td>
                                <?php
                                $type_labels = array(
                                    'percentage' => __('Percentage', 'woo-shop-crm'),
                                    'fixed' => __('Fixed Amount', 'woo-shop-crm'),
                                    'free_shipping' => __('Free Shipping', 'woo-shop-crm'),
                                );
                                echo esc_html($type_labels[$offer->offer_type]);
                                ?>
                            </td>
                            <td>
                                <?php
                                if ($offer->offer_type === 'percentage') {
                                    echo esc_html($offer->offer_value) . '%';
                                } elseif ($offer->offer_type === 'fixed') {
                                    echo wc_price($offer->offer_value);
                                } else {
                                    echo __('N/A', 'woo-shop-crm');
                                }
                                ?>
                            </td>
                            <td><?php echo $offer->min_purchase > 0 ? wc_price($offer->min_purchase) : '-'; ?></td>
                            <td>
                                <?php
                                echo $offer->start_date
                                    ? date_i18n('M j, Y', strtotime($offer->start_date))
                                    : '-';
                                ?>
                            </td>
                            <td>
                                <?php
                                echo $offer->end_date
                                    ? date_i18n('M j, Y', strtotime($offer->end_date))
                                    : '-';
                                ?>
                            </td>
                            <td>
                                <span class="wsc-status wsc-status-<?php echo esc_attr($offer->status); ?>">
                                    <?php echo esc_html(ucfirst($offer->status)); ?>
                                </span>
                            </td>
                            <td>
                                <button class="button button-small wsc-edit-offer"
                                        data-offer-id="<?php echo esc_attr($offer->id); ?>"
                                        data-offer='<?php echo esc_attr(json_encode($offer)); ?>'>
                                    <?php _e('Edit', 'woo-shop-crm'); ?>
                                </button>
                                <button class="button button-small wsc-delete-offer"
                                        data-offer-id="<?php echo esc_attr($offer->id); ?>">
                                    <?php _e('Delete', 'woo-shop-crm'); ?>
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
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $current_page,
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="wsc-no-data">
                <p><?php _e('No offers found.', 'woo-shop-crm'); ?></p>
                <button class="button button-primary wsc-add-offer-btn">
                    <?php _e('Create Your First Offer', 'woo-shop-crm'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>
