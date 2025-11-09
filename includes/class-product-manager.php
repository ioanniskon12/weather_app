<?php
/**
 * Product Manager Class
 *
 * Handles all product-related operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WSC_Product_Manager {

    // Standard image dimensions
    const IMAGE_WIDTH = 800;
    const IMAGE_HEIGHT = 800;
    const IMAGE_QUALITY = 85; // Compression quality (1-100)

    public function __construct() {
        // Hook into image upload to process images
        add_filter('wp_handle_upload_prefilter', array($this, 'optimize_image_on_upload'));
        add_filter('wp_generate_attachment_metadata', array($this, 'resize_product_images'), 10, 2);
    }

    /**
     * Get all products with pagination
     */
    public function get_products($args = array()) {
        $defaults = array(
            'status' => 'publish',
            'limit' => 20,
            'page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $products = wc_get_products($args);
        $total = wc_get_products(array_merge($args, array('limit' => -1, 'return' => 'ids')));

        return array(
            'products' => $products,
            'total' => count($total),
            'pages' => ceil(count($total) / $args['limit'])
        );
    }

    /**
     * Get single product
     */
    public function get_product($product_id) {
        return wc_get_product($product_id);
    }

    /**
     * Save product (create or update)
     */
    public function save_product($data) {
        try {
            // Sanitize data
            $product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
            $product_name = sanitize_text_field($data['product_name']);
            $product_description = wp_kses_post($data['product_description']);
            $product_short_description = wp_kses_post($data['product_short_description']);
            $product_price = floatval($data['product_price']);
            $product_sale_price = isset($data['product_sale_price']) ? floatval($data['product_sale_price']) : '';
            $product_sku = sanitize_text_field($data['product_sku']);
            $product_stock = intval($data['product_stock']);
            $product_type = sanitize_text_field($data['product_type']);

            // Create or update product
            if ($product_id > 0) {
                $product = wc_get_product($product_id);
            } else {
                $product = new WC_Product_Simple();
            }

            // Set product properties
            $product->set_name($product_name);
            $product->set_description($product_description);
            $product->set_short_description($product_short_description);
            $product->set_regular_price($product_price);

            if ($product_sale_price) {
                $product->set_sale_price($product_sale_price);
            }

            $product->set_sku($product_sku);
            $product->set_stock_quantity($product_stock);
            $product->set_manage_stock(true);
            $product->set_status('publish');

            // Handle categories
            if (isset($data['product_categories'])) {
                $categories = array_map('intval', (array)$data['product_categories']);
                $product->set_category_ids($categories);
            }

            // Handle tags
            if (isset($data['product_tags'])) {
                $tags = array_map('intval', (array)$data['product_tags']);
                $product->set_tag_ids($tags);
            }

            // Handle images
            if (isset($data['product_image_id'])) {
                $product->set_image_id(intval($data['product_image_id']));
            }

            if (isset($data['product_gallery_ids'])) {
                $gallery_ids = array_map('intval', (array)$data['product_gallery_ids']);
                $product->set_gallery_image_ids($gallery_ids);
            }

            // Save the product
            $saved_id = $product->save();

            return $saved_id;

        } catch (Exception $e) {
            error_log('Product save error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete product
     */
    public function delete_product($product_id) {
        $product = wc_get_product($product_id);

        if ($product) {
            return $product->delete(true);
        }

        return false;
    }

    /**
     * Update product stock
     */
    public function update_stock($product_id, $quantity) {
        $product = wc_get_product($product_id);

        if ($product) {
            $product->set_stock_quantity($quantity);
            return $product->save();
        }

        return false;
    }

    /**
     * Get product statistics
     */
    public function get_product_stats() {
        $total_products = wp_count_posts('product');
        $low_stock_products = $this->get_low_stock_products(5);

        return array(
            'total' => $total_products->publish,
            'drafts' => $total_products->draft,
            'low_stock' => count($low_stock_products),
            'out_of_stock' => $this->get_out_of_stock_count()
        );
    }

    /**
     * Get low stock products
     */
    public function get_low_stock_products($threshold = 5) {
        $args = array(
            'status' => 'publish',
            'limit' => -1,
            'stock_status' => 'instock',
        );

        $products = wc_get_products($args);
        $low_stock = array();

        foreach ($products as $product) {
            if ($product->managing_stock() && $product->get_stock_quantity() <= $threshold) {
                $low_stock[] = $product;
            }
        }

        return $low_stock;
    }

    /**
     * Get out of stock count
     */
    private function get_out_of_stock_count() {
        $args = array(
            'status' => 'publish',
            'stock_status' => 'outofstock',
            'limit' => -1,
            'return' => 'ids',
        );

        $products = wc_get_products($args);
        return count($products);
    }

    /**
     * Get product categories
     */
    public function get_categories() {
        $terms = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));

        return $terms;
    }

    /**
     * Get product tags
     */
    public function get_tags() {
        $terms = get_terms(array(
            'taxonomy' => 'product_tag',
            'hide_empty' => false,
        ));

        return $terms;
    }

    /**
     * Optimize image on upload - compress file size
     */
    public function optimize_image_on_upload($file) {
        // Check if file is an image
        $filetype = wp_check_filetype($file['name']);
        $valid_image_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');

        if (!in_array($file['type'], $valid_image_types)) {
            return $file;
        }

        // Get image editor
        $image_editor = wp_get_image_editor($file['tmp_name']);

        if (is_wp_error($image_editor)) {
            return $file;
        }

        // Get current image size
        $size = $image_editor->get_size();
        $original_width = $size['width'];
        $original_height = $size['height'];

        // Calculate new dimensions (maintain aspect ratio)
        $new_dimensions = $this->calculate_resize_dimensions(
            $original_width,
            $original_height,
            self::IMAGE_WIDTH,
            self::IMAGE_HEIGHT
        );

        // Resize image if needed
        if ($original_width > self::IMAGE_WIDTH || $original_height > self::IMAGE_HEIGHT) {
            $image_editor->resize($new_dimensions['width'], $new_dimensions['height'], false);
        }

        // Set compression quality
        $image_editor->set_quality(self::IMAGE_QUALITY);

        // Save the optimized image
        $saved = $image_editor->save($file['tmp_name']);

        if (is_wp_error($saved)) {
            return $file;
        }

        // Update file size
        $file['size'] = filesize($file['tmp_name']);

        return $file;
    }

    /**
     * Resize product images after upload
     */
    public function resize_product_images($metadata, $attachment_id) {
        // Check if this is an image
        if (!isset($metadata['width']) || !isset($metadata['height'])) {
            return $metadata;
        }

        // Get the uploaded file path
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/' . $metadata['file'];

        if (!file_exists($file_path)) {
            return $metadata;
        }

        // Get image editor
        $image_editor = wp_get_image_editor($file_path);

        if (is_wp_error($image_editor)) {
            return $metadata;
        }

        // Get original dimensions
        $original_width = $metadata['width'];
        $original_height = $metadata['height'];

        // Calculate new dimensions
        $new_dimensions = $this->calculate_resize_dimensions(
            $original_width,
            $original_height,
            self::IMAGE_WIDTH,
            self::IMAGE_HEIGHT
        );

        // Only resize if dimensions changed
        if ($original_width != $new_dimensions['width'] || $original_height != $new_dimensions['height']) {
            $image_editor->resize($new_dimensions['width'], $new_dimensions['height'], false);
            $image_editor->set_quality(self::IMAGE_QUALITY);

            // Save the resized image
            $saved = $image_editor->save($file_path);

            if (!is_wp_error($saved)) {
                // Update metadata
                $metadata['width'] = $new_dimensions['width'];
                $metadata['height'] = $new_dimensions['height'];
                $metadata['file'] = $saved['file'];
            }
        }

        return $metadata;
    }

    /**
     * Calculate resize dimensions maintaining aspect ratio
     */
    private function calculate_resize_dimensions($original_width, $original_height, $max_width, $max_height) {
        // Calculate aspect ratio
        $aspect_ratio = $original_width / $original_height;
        $target_ratio = $max_width / $max_height;

        // Calculate new dimensions maintaining aspect ratio
        if ($aspect_ratio > $target_ratio) {
            // Image is wider
            $new_width = $max_width;
            $new_height = round($max_width / $aspect_ratio);
        } else {
            // Image is taller or square
            $new_height = $max_height;
            $new_width = round($max_height * $aspect_ratio);
        }

        // Don't upscale images
        if ($new_width > $original_width || $new_height > $original_height) {
            $new_width = $original_width;
            $new_height = $original_height;
        }

        return array(
            'width' => (int)$new_width,
            'height' => (int)$new_height
        );
    }

    /**
     * Process existing product images (bulk optimization)
     */
    public function optimize_existing_product_images($product_id = null) {
        $args = array(
            'status' => 'publish',
            'limit' => -1,
        );

        if ($product_id) {
            $args['include'] = array($product_id);
        }

        $products = wc_get_products($args);
        $optimized_count = 0;

        foreach ($products as $product) {
            // Get product image
            $image_id = $product->get_image_id();

            if ($image_id) {
                if ($this->optimize_single_image($image_id)) {
                    $optimized_count++;
                }
            }

            // Get gallery images
            $gallery_ids = $product->get_gallery_image_ids();

            foreach ($gallery_ids as $gallery_id) {
                if ($this->optimize_single_image($gallery_id)) {
                    $optimized_count++;
                }
            }
        }

        return $optimized_count;
    }

    /**
     * Optimize a single image by attachment ID
     */
    private function optimize_single_image($attachment_id) {
        $file_path = get_attached_file($attachment_id);

        if (!file_exists($file_path)) {
            return false;
        }

        // Get image editor
        $image_editor = wp_get_image_editor($file_path);

        if (is_wp_error($image_editor)) {
            return false;
        }

        // Get current size
        $size = $image_editor->get_size();

        // Calculate new dimensions
        $new_dimensions = $this->calculate_resize_dimensions(
            $size['width'],
            $size['height'],
            self::IMAGE_WIDTH,
            self::IMAGE_HEIGHT
        );

        // Resize if needed
        if ($size['width'] != $new_dimensions['width'] || $size['height'] != $new_dimensions['height']) {
            $image_editor->resize($new_dimensions['width'], $new_dimensions['height'], false);
        }

        // Set compression quality
        $image_editor->set_quality(self::IMAGE_QUALITY);

        // Save
        $saved = $image_editor->save($file_path);

        if (is_wp_error($saved)) {
            return false;
        }

        // Update metadata
        wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $file_path));

        return true;
    }

    /**
     * Get image optimization settings
     */
    public function get_image_settings() {
        return array(
            'width' => self::IMAGE_WIDTH,
            'height' => self::IMAGE_HEIGHT,
            'quality' => self::IMAGE_QUALITY,
        );
    }

    /**
     * Update image optimization settings
     */
    public function update_image_settings($width, $height, $quality) {
        // Note: To make these settings truly dynamic, you would need to store them
        // in WordPress options and modify the class constants to read from options
        update_option('wsc_image_width', intval($width));
        update_option('wsc_image_height', intval($height));
        update_option('wsc_image_quality', intval($quality));

        return true;
    }
}
