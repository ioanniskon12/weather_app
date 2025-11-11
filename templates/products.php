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
        <div class="wsc-modal-content wsc-modal-large">
            <div class="wsc-modal-header">
                <h2 id="wsc-modal-title"><?php _e('Add New Product', 'woo-shop-crm'); ?></h2>
                <span class="wsc-modal-close">&times;</span>
            </div>
            <div class="wsc-modal-body">
                <form id="wsc-product-form">
                    <input type="hidden" name="product_id" id="product_id" value="">
                    <input type="hidden" name="action" value="wsc_save_product">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wsc_product_nonce'); ?>">

                    <div class="wsc-form-row">
                        <label for="product_name">
                            <?php _e('Product Name', 'woo-shop-crm'); ?> <span class="required">*</span>
                        </label>
                        <input type="text" name="product_name" id="product_name" required class="widefat" placeholder="<?php _e('Enter product name', 'woo-shop-crm'); ?>">
                    </div>

                    <div class="wsc-form-row">
                        <label for="product_description"><?php _e('Description', 'woo-shop-crm'); ?></label>
                        <?php
                        wp_editor('', 'product_description', array(
                            'textarea_name' => 'product_description',
                            'textarea_rows' => 10,
                            'teeny' => false,
                            'media_buttons' => true,
                            'tinymce' => array(
                                'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,blockquote',
                                'toolbar2' => 'alignleft,aligncenter,alignright,forecolor,backcolor,outdent,indent,undo,redo'
                            )
                        ));
                        ?>
                    </div>

                    <div class="wsc-form-row">
                        <label for="product_short_description"><?php _e('Short Description', 'woo-shop-crm'); ?></label>
                        <textarea name="product_short_description" id="product_short_description" rows="3" class="widefat" placeholder="<?php _e('Brief product summary', 'woo-shop-crm'); ?>"></textarea>
                    </div>

                    <h3><?php _e('Pricing', 'woo-shop-crm'); ?></h3>

                    <div class="wsc-form-row-grid">
                        <div class="wsc-form-col">
                            <label for="product_price">
                                <?php _e('Regular Price', 'woo-shop-crm'); ?> <span class="required">*</span>
                            </label>
                            <input type="number" step="0.01" name="product_price" id="product_price" required class="widefat" placeholder="0.00">
                        </div>

                        <div class="wsc-form-col">
                            <label for="product_sale_price"><?php _e('Sale Price', 'woo-shop-crm'); ?></label>
                            <input type="number" step="0.01" name="product_sale_price" id="product_sale_price" class="widefat" placeholder="0.00">
                        </div>
                    </div>

                    <div class="wsc-form-row-grid">
                        <div class="wsc-form-col">
                            <label for="product_sale_from"><?php _e('Sale Price From', 'woo-shop-crm'); ?></label>
                            <input type="date" name="product_sale_from" id="product_sale_from" class="widefat">
                        </div>

                        <div class="wsc-form-col">
                            <label for="product_sale_to"><?php _e('Sale Price To', 'woo-shop-crm'); ?></label>
                            <input type="date" name="product_sale_to" id="product_sale_to" class="widefat">
                        </div>
                    </div>

                    <h3><?php _e('Inventory', 'woo-shop-crm'); ?></h3>

                    <div class="wsc-form-row-grid">
                        <div class="wsc-form-col">
                            <label for="product_sku">
                                <?php _e('SKU', 'woo-shop-crm'); ?>
                            </label>
                            <input type="text" name="product_sku" id="product_sku" class="widefat" placeholder="<?php _e('Product SKU', 'woo-shop-crm'); ?>">
                        </div>

                        <div class="wsc-form-col">
                            <label for="product_stock_status"><?php _e('Stock Status', 'woo-shop-crm'); ?></label>
                            <select name="product_stock_status" id="product_stock_status" class="widefat">
                                <option value="instock"><?php _e('In Stock', 'woo-shop-crm'); ?></option>
                                <option value="outofstock"><?php _e('Out of Stock', 'woo-shop-crm'); ?></option>
                                <option value="onbackorder"><?php _e('On Backorder', 'woo-shop-crm'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="wsc-form-row">
                        <label>
                            <input type="checkbox" name="product_manage_stock" id="product_manage_stock" value="1" checked>
                            <?php _e('Manage Stock?', 'woo-shop-crm'); ?>
                        </label>
                    </div>

                    <div class="wsc-form-row" id="stock_quantity_field">
                        <label for="product_stock">
                            <?php _e('Stock Quantity', 'woo-shop-crm'); ?>
                        </label>
                        <input type="number" name="product_stock" id="product_stock" class="widefat" value="0">
                    </div>

                    <h3><?php _e('Product Categories', 'woo-shop-crm'); ?></h3>

                    <div class="wsc-form-row">
                        <div class="wsc-category-checklist">
                            <?php
                            $walker = new Walker_Category_Checklist();
                            wp_terms_checklist(0, array(
                                'taxonomy' => 'product_cat',
                                'walker' => $walker,
                                'selected_cats' => array(),
                                'popular_cats' => array(),
                                'checked_ontop' => false,
                            ));
                            ?>
                        </div>
                    </div>

                    <h3><?php _e('Product Tags', 'woo-shop-crm'); ?></h3>

                    <div class="wsc-form-row">
                        <input type="text" name="product_tags" id="product_tags" class="widefat" placeholder="<?php _e('Separate tags with commas', 'woo-shop-crm'); ?>">
                    </div>

                    <h3><?php _e('Product Attributes', 'woo-shop-crm'); ?></h3>

                    <div class="wsc-form-row">
                        <label for="product_brand"><?php _e('Brand', 'woo-shop-crm'); ?></label>
                        <input type="text" name="product_brand" id="product_brand" class="widefat" placeholder="<?php _e('Product brand', 'woo-shop-crm'); ?>">
                    </div>

                    <div class="wsc-form-row">
                        <label for="product_color"><?php _e('Color', 'woo-shop-crm'); ?></label>
                        <input type="text" name="product_color" id="product_color" class="widefat" placeholder="<?php _e('Product color', 'woo-shop-crm'); ?>">
                    </div>

                    <div class="wsc-form-row">
                        <label for="product_size"><?php _e('Size', 'woo-shop-crm'); ?></label>
                        <input type="text" name="product_size" id="product_size" class="widefat" placeholder="<?php _e('Product size', 'woo-shop-crm'); ?>">
                    </div>

                    <div class="wsc-form-row">
                        <label for="product_material"><?php _e('Material', 'woo-shop-crm'); ?></label>
                        <input type="text" name="product_material" id="product_material" class="widefat" placeholder="<?php _e('Product material', 'woo-shop-crm'); ?>">
                    </div>

                    <h3><?php _e('Product Images', 'woo-shop-crm'); ?></h3>

                    <div class="wsc-form-row">
                        <label><?php _e('Product Image', 'woo-shop-crm'); ?></label>
                        <div class="wsc-image-upload">
                            <button type="button" class="button wsc-upload-image-btn" data-target="product_image_id">
                                <?php _e('Set Product Image', 'woo-shop-crm'); ?>
                            </button>
                            <button type="button" class="button wsc-remove-image-btn" data-target="product_image_id" style="display:none;">
                                <?php _e('Remove Image', 'woo-shop-crm'); ?>
                            </button>
                            <input type="hidden" name="product_image_id" id="product_image_id" value="">
                            <div class="wsc-image-preview" id="product_image_preview"></div>
                        </div>
                    </div>

                    <div class="wsc-form-row">
                        <label><?php _e('Product Gallery', 'woo-shop-crm'); ?></label>
                        <div class="wsc-image-upload">
                            <button type="button" class="button wsc-upload-gallery-btn">
                                <?php _e('Add Gallery Images', 'woo-shop-crm'); ?>
                            </button>
                            <input type="hidden" name="product_gallery_ids" id="product_gallery_ids" value="">
                            <div class="wsc-gallery-preview" id="product_gallery_preview"></div>
                        </div>
                    </div>

                    <h3><?php _e('Energy Efficiency', 'woo-shop-crm'); ?></h3>

                    <div class="wsc-form-row">
                        <label for="product_energy_rating"><?php _e('Energy Efficiency Rating', 'woo-shop-crm'); ?></label>
                        <select name="product_energy_rating" id="product_energy_rating" class="widefat">
                            <option value=""><?php _e('-- No Rating --', 'woo-shop-crm'); ?></option>
                            <option value="A+++"><?php _e('A+++ (Most Efficient)', 'woo-shop-crm'); ?></option>
                            <option value="A++"><?php _e('A++', 'woo-shop-crm'); ?></option>
                            <option value="A+"><?php _e('A+', 'woo-shop-crm'); ?></option>
                            <option value="A"><?php _e('A', 'woo-shop-crm'); ?></option>
                            <option value="B"><?php _e('B', 'woo-shop-crm'); ?></option>
                            <option value="C"><?php _e('C', 'woo-shop-crm'); ?></option>
                            <option value="D"><?php _e('D', 'woo-shop-crm'); ?></option>
                            <option value="E"><?php _e('E', 'woo-shop-crm'); ?></option>
                            <option value="F"><?php _e('F', 'woo-shop-crm'); ?></option>
                            <option value="G"><?php _e('G (Least Efficient)', 'woo-shop-crm'); ?></option>
                        </select>
                        <p class="description"><?php _e('This will display an energy efficiency badge in your product description', 'woo-shop-crm'); ?></p>
                    </div>

                    <h3><?php _e('Shipping & Dimensions', 'woo-shop-crm'); ?></h3>

                    <div class="wsc-form-row-grid">
                        <div class="wsc-form-col">
                            <label for="product_weight"><?php _e('Weight (kg)', 'woo-shop-crm'); ?></label>
                            <input type="number" step="0.01" name="product_weight" id="product_weight" class="widefat" placeholder="0.00">
                        </div>

                        <div class="wsc-form-col">
                            <label for="product_length"><?php _e('Length (cm)', 'woo-shop-crm'); ?></label>
                            <input type="number" step="0.01" name="product_length" id="product_length" class="widefat" placeholder="0.00">
                        </div>
                    </div>

                    <div class="wsc-form-row-grid">
                        <div class="wsc-form-col">
                            <label for="product_width"><?php _e('Width (cm)', 'woo-shop-crm'); ?></label>
                            <input type="number" step="0.01" name="product_width" id="product_width" class="widefat" placeholder="0.00">
                        </div>

                        <div class="wsc-form-col">
                            <label for="product_height"><?php _e('Height (cm)', 'woo-shop-crm'); ?></label>
                            <input type="number" step="0.01" name="product_height" id="product_height" class="widefat" placeholder="0.00">
                        </div>
                    </div>

                    <h3><?php _e('Product Settings', 'woo-shop-crm'); ?></h3>

                    <div class="wsc-form-row">
                        <label>
                            <input type="checkbox" name="product_featured" id="product_featured" value="1">
                            <?php _e('Featured Product', 'woo-shop-crm'); ?>
                        </label>
                        <p class="description"><?php _e('Display this product in featured sections', 'woo-shop-crm'); ?></p>
                    </div>

                    <div class="wsc-form-row">
                        <label>
                            <input type="checkbox" name="product_virtual" id="product_virtual" value="1">
                            <?php _e('Virtual Product', 'woo-shop-crm'); ?>
                        </label>
                        <p class="description"><?php _e('No shipping required (e.g., services, digital products)', 'woo-shop-crm'); ?></p>
                    </div>

                    <div class="wsc-form-row">
                        <label for="product_catalog_visibility"><?php _e('Catalog Visibility', 'woo-shop-crm'); ?></label>
                        <select name="product_catalog_visibility" id="product_catalog_visibility" class="widefat">
                            <option value="visible"><?php _e('Shop and Search Results', 'woo-shop-crm'); ?></option>
                            <option value="catalog"><?php _e('Shop Only', 'woo-shop-crm'); ?></option>
                            <option value="search"><?php _e('Search Results Only', 'woo-shop-crm'); ?></option>
                            <option value="hidden"><?php _e('Hidden', 'woo-shop-crm'); ?></option>
                        </select>
                    </div>

                    <h3><?php _e('Advanced Inventory', 'woo-shop-crm'); ?></h3>

                    <div class="wsc-form-row">
                        <label for="product_low_stock_threshold"><?php _e('Low Stock Threshold', 'woo-shop-crm'); ?></label>
                        <input type="number" name="product_low_stock_threshold" id="product_low_stock_threshold" class="widefat" placeholder="<?php _e('Default: 5', 'woo-shop-crm'); ?>">
                        <p class="description"><?php _e('When stock reaches this amount, you will be notified', 'woo-shop-crm'); ?></p>
                    </div>

                    <div class="wsc-form-row">
                        <label>
                            <input type="checkbox" name="product_sold_individually" id="product_sold_individually" value="1">
                            <?php _e('Sold Individually', 'woo-shop-crm'); ?>
                        </label>
                        <p class="description"><?php _e('Limit purchase to 1 item per order', 'woo-shop-crm'); ?></p>
                    </div>

                    <div class="wsc-form-row">
                        <label for="product_backorders"><?php _e('Allow Backorders', 'woo-shop-crm'); ?></label>
                        <select name="product_backorders" id="product_backorders" class="widefat">
                            <option value="no"><?php _e('Do Not Allow', 'woo-shop-crm'); ?></option>
                            <option value="notify"><?php _e('Allow, but Notify Customer', 'woo-shop-crm'); ?></option>
                            <option value="yes"><?php _e('Allow', 'woo-shop-crm'); ?></option>
                        </select>
                    </div>

                    <h3><?php _e('Tax Settings', 'woo-shop-crm'); ?></h3>

                    <div class="wsc-form-row-grid">
                        <div class="wsc-form-col">
                            <label for="product_tax_status"><?php _e('Tax Status', 'woo-shop-crm'); ?></label>
                            <select name="product_tax_status" id="product_tax_status" class="widefat">
                                <option value="taxable"><?php _e('Taxable', 'woo-shop-crm'); ?></option>
                                <option value="shipping"><?php _e('Shipping Only', 'woo-shop-crm'); ?></option>
                                <option value="none"><?php _e('None', 'woo-shop-crm'); ?></option>
                            </select>
                        </div>

                        <div class="wsc-form-col">
                            <label for="product_tax_class"><?php _e('Tax Class', 'woo-shop-crm'); ?></label>
                            <select name="product_tax_class" id="product_tax_class" class="widefat">
                                <option value=""><?php _e('Standard', 'woo-shop-crm'); ?></option>
                                <option value="reduced-rate"><?php _e('Reduced Rate', 'woo-shop-crm'); ?></option>
                                <option value="zero-rate"><?php _e('Zero Rate', 'woo-shop-crm'); ?></option>
                            </select>
                        </div>
                    </div>

                    <h3><?php _e('Additional Information', 'woo-shop-crm'); ?></h3>

                    <div class="wsc-form-row-grid">
                        <div class="wsc-form-col">
                            <label for="product_manufacturer"><?php _e('Manufacturer', 'woo-shop-crm'); ?></label>
                            <input type="text" name="product_manufacturer" id="product_manufacturer" class="widefat" placeholder="<?php _e('Product manufacturer', 'woo-shop-crm'); ?>">
                        </div>

                        <div class="wsc-form-col">
                            <label for="product_warranty"><?php _e('Warranty Period', 'woo-shop-crm'); ?></label>
                            <input type="text" name="product_warranty" id="product_warranty" class="widefat" placeholder="<?php _e('e.g., 2 years', 'woo-shop-crm'); ?>">
                        </div>
                    </div>

                    <div class="wsc-form-row-grid">
                        <div class="wsc-form-col">
                            <label for="product_country_origin"><?php _e('Country of Origin', 'woo-shop-crm'); ?></label>
                            <input type="text" name="product_country_origin" id="product_country_origin" class="widefat" placeholder="<?php _e('e.g., Germany', 'woo-shop-crm'); ?>">
                        </div>

                        <div class="wsc-form-col">
                            <label for="product_barcode"><?php _e('Barcode / EAN', 'woo-shop-crm'); ?></label>
                            <input type="text" name="product_barcode" id="product_barcode" class="widefat" placeholder="<?php _e('Product barcode', 'woo-shop-crm'); ?>">
                        </div>
                    </div>

                    <div class="wsc-form-row">
                        <label for="product_purchase_note"><?php _e('Purchase Note', 'woo-shop-crm'); ?></label>
                        <textarea name="product_purchase_note" id="product_purchase_note" rows="3" class="widefat" placeholder="<?php _e('Note to customer after purchase', 'woo-shop-crm'); ?>"></textarea>
                    </div>

                    <div class="wsc-form-actions">
                        <button type="submit" class="button button-primary button-large">
                            <?php _e('Save Product', 'woo-shop-crm'); ?>
                        </button>
                        <button type="button" class="button button-large wsc-modal-close">
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
                        <?php if (defined('ICL_SITEPRESS_VERSION')): ?>
                            <th><?php _e('Language', 'woo-shop-crm'); ?></th>
                        <?php endif; ?>
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
                            <?php if (defined('ICL_SITEPRESS_VERSION')): ?>
                                <td>
                                    <?php
                                    if (class_exists('WSC_WPML_Integration')) {
                                        $wpml = WSC_WPML_Integration::get_instance();
                                        echo $wpml->get_language_flag(icl_get_current_language());
                                        echo '<br/><small>' . $wpml->get_translation_status($product->get_id()) . '</small>';
                                    }
                                    ?>
                                </td>
                            <?php endif; ?>
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
