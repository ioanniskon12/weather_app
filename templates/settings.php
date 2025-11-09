<?php
/**
 * Image Settings Template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$product_manager = new WSC_Product_Manager();
$settings = $product_manager->get_image_settings();

// Get current settings or defaults
$image_width = get_option('wsc_image_width', $settings['width']);
$image_height = get_option('wsc_image_height', $settings['height']);
$image_quality = get_option('wsc_image_quality', $settings['quality']);

// Get product count for optimization
$total_products = wp_count_posts('product');
?>

<div class="wrap wsc-settings">
    <h1><?php _e('Image Optimization Settings', 'woo-shop-crm'); ?></h1>

    <div class="wsc-settings-container">
        <!-- Image Settings Card -->
        <div class="wsc-settings-card">
            <div class="wsc-card-header">
                <h2><?php _e('Image Resize & Compression Settings', 'woo-shop-crm'); ?></h2>
                <p class="description">
                    <?php _e('Configure how product images should be resized and compressed. These settings will apply to all newly uploaded images automatically.', 'woo-shop-crm'); ?>
                </p>
            </div>

            <form id="wsc-image-settings-form" class="wsc-card-body">
                <div class="wsc-form-row">
                    <label for="image_width">
                        <?php _e('Maximum Image Width (px)', 'woo-shop-crm'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="number"
                           name="image_width"
                           id="image_width"
                           value="<?php echo esc_attr($image_width); ?>"
                           min="100"
                           max="5000"
                           required>
                    <p class="description">
                        <?php _e('Images wider than this will be resized. Range: 100-5000 pixels. Default: 800px', 'woo-shop-crm'); ?>
                    </p>
                </div>

                <div class="wsc-form-row">
                    <label for="image_height">
                        <?php _e('Maximum Image Height (px)', 'woo-shop-crm'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="number"
                           name="image_height"
                           id="image_height"
                           value="<?php echo esc_attr($image_height); ?>"
                           min="100"
                           max="5000"
                           required>
                    <p class="description">
                        <?php _e('Images taller than this will be resized. Range: 100-5000 pixels. Default: 800px', 'woo-shop-crm'); ?>
                    </p>
                </div>

                <div class="wsc-form-row">
                    <label for="image_quality">
                        <?php _e('Image Compression Quality (%)', 'woo-shop-crm'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="number"
                           name="image_quality"
                           id="image_quality"
                           value="<?php echo esc_attr($image_quality); ?>"
                           min="1"
                           max="100"
                           required>
                    <div class="wsc-quality-indicator">
                        <span class="quality-label">
                            <?php _e('Current:', 'woo-shop-crm'); ?>
                            <strong id="quality_display"><?php echo esc_html($image_quality); ?>%</strong>
                        </span>
                        <div class="quality-bar">
                            <div class="quality-fill" style="width: <?php echo esc_attr($image_quality); ?>%"></div>
                        </div>
                    </div>
                    <p class="description">
                        <?php _e('Higher quality = larger file size. Lower quality = smaller file size. Range: 1-100. Recommended: 80-90', 'woo-shop-crm'); ?>
                    </p>
                </div>

                <div class="wsc-info-box">
                    <span class="dashicons dashicons-info"></span>
                    <div>
                        <strong><?php _e('How it works:', 'woo-shop-crm'); ?></strong>
                        <ul>
                            <li><?php _e('Images maintain their aspect ratio when resized', 'woo-shop-crm'); ?></li>
                            <li><?php _e('Images smaller than max dimensions won\'t be upscaled', 'woo-shop-crm'); ?></li>
                            <li><?php _e('All images are compressed to reduce file size', 'woo-shop-crm'); ?></li>
                            <li><?php _e('New uploads are automatically optimized', 'woo-shop-crm'); ?></li>
                        </ul>
                    </div>
                </div>

                <div class="wsc-form-actions">
                    <button type="submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Save Settings', 'woo-shop-crm'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Bulk Optimization Card -->
        <div class="wsc-settings-card">
            <div class="wsc-card-header">
                <h2><?php _e('Optimize Existing Images', 'woo-shop-crm'); ?></h2>
                <p class="description">
                    <?php _e('Apply optimization to all existing product images in your store. This may take a few minutes depending on how many products you have.', 'woo-shop-crm'); ?>
                </p>
            </div>

            <div class="wsc-card-body">
                <div class="wsc-bulk-stats">
                    <div class="stat-item">
                        <span class="dashicons dashicons-products"></span>
                        <div>
                            <strong><?php echo esc_html($total_products->publish); ?></strong>
                            <p><?php _e('Total Products', 'woo-shop-crm'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="wsc-warning-box">
                    <span class="dashicons dashicons-warning"></span>
                    <div>
                        <strong><?php _e('Important:', 'woo-shop-crm'); ?></strong>
                        <p><?php _e('This will resize and compress all product images (main images and gallery images). Make sure you have backups before proceeding. This action cannot be undone.', 'woo-shop-crm'); ?></p>
                    </div>
                </div>

                <button id="wsc-optimize-images-btn" class="button button-secondary button-large">
                    <span class="dashicons dashicons-image-rotate"></span>
                    <?php _e('Optimize All Images Now', 'woo-shop-crm'); ?>
                </button>

                <div id="wsc-optimization-progress" style="display: none;">
                    <div class="wsc-progress-bar">
                        <div class="wsc-progress-fill"></div>
                    </div>
                    <p class="wsc-progress-text"><?php _e('Optimizing images...', 'woo-shop-crm'); ?></p>
                </div>

                <div id="wsc-optimization-result" style="display: none;"></div>
            </div>
        </div>

        <!-- Image Size Recommendations -->
        <div class="wsc-settings-card">
            <div class="wsc-card-header">
                <h2><?php _e('Recommended Settings', 'woo-shop-crm'); ?></h2>
            </div>

            <div class="wsc-card-body">
                <table class="wsc-recommendations-table">
                    <thead>
                        <tr>
                            <th><?php _e('Use Case', 'woo-shop-crm'); ?></th>
                            <th><?php _e('Width x Height', 'woo-shop-crm'); ?></th>
                            <th><?php _e('Quality', 'woo-shop-crm'); ?></th>
                            <th><?php _e('Description', 'woo-shop-crm'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php _e('Small Store', 'woo-shop-crm'); ?></strong></td>
                            <td>600 x 600</td>
                            <td>85%</td>
                            <td><?php _e('Good balance for small catalogs', 'woo-shop-crm'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Medium Store', 'woo-shop-crm'); ?></strong></td>
                            <td>800 x 800</td>
                            <td>85%</td>
                            <td><?php _e('Default - works for most stores', 'woo-shop-crm'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Large Store', 'woo-shop-crm'); ?></strong></td>
                            <td>1000 x 1000</td>
                            <td>80%</td>
                            <td><?php _e('Higher quality for large inventories', 'woo-shop-crm'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('High-End Products', 'woo-shop-crm'); ?></strong></td>
                            <td>1200 x 1200</td>
                            <td>90%</td>
                            <td><?php _e('Best quality for premium items', 'woo-shop-crm'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Performance Focus', 'woo-shop-crm'); ?></strong></td>
                            <td>600 x 600</td>
                            <td>75%</td>
                            <td><?php _e('Smallest file size for fast loading', 'woo-shop-crm'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Update quality display
    $('#image_quality').on('input', function() {
        var quality = $(this).val();
        $('#quality_display').text(quality + '%');
        $('.quality-fill').css('width', quality + '%');
    });

    // Save settings form
    $('#wsc-image-settings-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var originalText = $button.html();

        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Saving...');

        $.ajax({
            url: wscCRM.ajax_url,
            type: 'POST',
            data: {
                action: 'wsc_save_image_settings',
                nonce: wscCRM.nonce,
                image_width: $('#image_width').val(),
                image_height: $('#image_height').val(),
                image_quality: $('#image_quality').val()
            },
            success: function(response) {
                if (response.success) {
                    $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
                        .insertAfter('.wrap > h1');
                } else {
                    $('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>')
                        .insertAfter('.wrap > h1');
                }
                $button.prop('disabled', false).html(originalText);

                // Auto-hide notice after 3 seconds
                setTimeout(function() {
                    $('.notice').fadeOut();
                }, 3000);
            },
            error: function() {
                $('<div class="notice notice-error is-dismissible"><p>An error occurred</p></div>')
                    .insertAfter('.wrap > h1');
                $button.prop('disabled', false).html(originalText);
            }
        });
    });

    // Optimize images button
    $('#wsc-optimize-images-btn').on('click', function() {
        if (!confirm('Are you sure you want to optimize all product images? This may take several minutes. Make sure you have backups!')) {
            return;
        }

        var $button = $(this);
        var originalText = $button.html();

        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Optimizing...');
        $('#wsc-optimization-progress').show();
        $('#wsc-optimization-result').hide();

        $.ajax({
            url: wscCRM.ajax_url,
            type: 'POST',
            data: {
                action: 'wsc_optimize_images',
                nonce: wscCRM.nonce
            },
            success: function(response) {
                $('#wsc-optimization-progress').hide();

                if (response.success) {
                    $('#wsc-optimization-result')
                        .html('<div class="wsc-success-message"><span class="dashicons dashicons-yes"></span> ' + response.data.message + '</div>')
                        .show();
                } else {
                    $('#wsc-optimization-result')
                        .html('<div class="wsc-error-message"><span class="dashicons dashicons-no"></span> ' + response.data.message + '</div>')
                        .show();
                }

                $button.prop('disabled', false).html(originalText);
            },
            error: function() {
                $('#wsc-optimization-progress').hide();
                $('#wsc-optimization-result')
                    .html('<div class="wsc-error-message"><span class="dashicons dashicons-no"></span> An error occurred</div>')
                    .show();
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
