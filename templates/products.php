<?php
/**
 * Products Management Template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$product_manager = new WSC_Product_Manager();

// Get current page
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

// Get products
$result = $product_manager->get_products(array(
    'page' => $current_page,
    'limit' => 20,
));

$products = $result['products'];
$total_pages = $result['pages'];

// Get categories and tags for the form
$categories = $product_manager->get_categories();
$tags = $product_manager->get_tags();
?>

<div class="wrap wsc-products">
    <h1>
        <?php _e('Products Management', 'woo-shop-crm'); ?>
        <button class="page-title-action wsc-add-product-btn">
            <?php _e('Add New Product', 'woo-shop-crm'); ?>
        </button>
    </h1>

    <!-- Add/Edit Product Modal -->
    <div id="wsc-product-modal" class="wsc-modal" style="display: none;">
        <div class="wsc-modal-content">
            <div class="wsc-modal-header">
                <h2 id="wsc-modal-title"><?php _e('Add New Product', 'woo-shop-crm'); ?></h2>
                <span class="wsc-modal-close">&times;</span>
            </div>
            <div class="wsc-modal-body">
                <form id="wsc-product-form">
                    <input type="hidden" name="product_id" id="product_id" value="">

                    <div class="wsc-form-row">
                        <label for="product_name">
                            <?php _e('Product Name', 'woo-shop-crm'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" name="product_name" id="product_name" required class="widefat">
                    </div>

                    <div class="wsc-form-row">
                        <label for="product_description"><?php _e('Description', 'woo-shop-crm'); ?></label>
                        <textarea name="product_description" id="product_description" rows="5" class="widefat"></textarea>
                    </div>

                    <div class="wsc-form-row">
                        <label for="product_short_description"><?php _e('Short Description', 'woo-shop-crm'); ?></label>
                        <textarea name="product_short_description" id="product_short_description" rows="3" class="widefat"></textarea>
                    </div>

                    <div class="wsc-form-row-grid">
                        <div class="wsc-form-col">
                            <label for="product_price">
                                <?php _e('Regular Price', 'woo-shop-crm'); ?> <span class="required">*</span>
                            </label>
                            <input type="number" step="0.01" name="product_price" id="product_price" required class="widefat">
                        </div>

                        <div class="wsc-form-col">
                            <label for="product_sale_price"><?php _e('Sale Price', 'woo-shop-crm'); ?></label>
                            <input type="number" step="0.01" name="product_sale_price" id="product_sale_price" class="widefat">
                        </div>
                    </div>

                    <div class="wsc-form-row-grid">
                        <div class="wsc-form-col">
                            <label for="product_sku">
                                <?php _e('SKU', 'woo-shop-crm'); ?> <span class="required">*</span>
                            </label>
                            <input type="text" name="product_sku" id="product_sku" required class="widefat">
                        </div>

                        <div class="wsc-form-col">
                            <label for="product_stock">
                                <?php _e('Stock Quantity', 'woo-shop-crm'); ?> <span class="required">*</span>
                            </label>
                            <input type="number" name="product_stock" id="product_stock" required class="widefat">
                        </div>
                    </div>

                    <div class="wsc-form-row">
                        <label for="product_categories"><?php _e('Categories', 'woo-shop-crm'); ?></label>
                        <select name="product_categories[]" id="product_categories" multiple class="widefat">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category->term_id); ?>">
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="wsc-form-actions">
                        <button type="submit" class="button button-primary">
                            <?php _e('Save Product', 'woo-shop-crm'); ?>
                        </button>
                        <button type="button" class="button wsc-modal-close">
                            <?php _e('Cancel', 'woo-shop-crm'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Products List -->
    <div class="wsc-products-list">
        <?php if (!empty($products)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Image', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Product Name', 'woo-shop-crm'); ?></th>
                        <th><?php _e('SKU', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Price', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Stock', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Status', 'woo-shop-crm'); ?></th>
                        <th><?php _e('Actions', 'woo-shop-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php
                                $image = $product->get_image('thumbnail');
                                echo $image ? $image : '<span class="dashicons dashicons-format-image"></span>';
                                ?>
                            </td>
                            <td>
                                <strong>
                                    <a href="<?php echo get_edit_post_link($product->get_id()); ?>">
                                        <?php echo esc_html($product->get_name()); ?>
                                    </a>
                                </strong>
                            </td>
                            <td><?php echo esc_html($product->get_sku()); ?></td>
                            <td>
                                <?php
                                if ($product->is_on_sale()) {
                                    echo '<del>' . wc_price($product->get_regular_price()) . '</del> ';
                                    echo '<ins>' . wc_price($product->get_sale_price()) . '</ins>';
                                } else {
                                    echo wc_price($product->get_regular_price());
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($product->managing_stock()): ?>
                                    <span class="<?php echo $product->get_stock_quantity() <= 5 ? 'wsc-low-stock' : ''; ?>">
                                        <?php echo esc_html($product->get_stock_quantity()); ?>
                                    </span>
                                <?php else: ?>
                                    <span><?php _e('Not managed', 'woo-shop-crm'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="wsc-status wsc-status-<?php echo esc_attr($product->get_status()); ?>">
                                    <?php echo esc_html(ucfirst($product->get_status())); ?>
                                </span>
                            </td>
                            <td>
                                <button class="button button-small wsc-edit-product"
                                        data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                                    <?php _e('Edit', 'woo-shop-crm'); ?>
                                </button>
                                <button class="button button-small wsc-delete-product"
                                        data-product-id="<?php echo esc_attr($product->get_id()); ?>">
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
                <p><?php _e('No products found.', 'woo-shop-crm'); ?></p>
                <button class="button button-primary wsc-add-product-btn">
                    <?php _e('Add Your First Product', 'woo-shop-crm'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>
