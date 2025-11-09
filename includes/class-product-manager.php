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

        // Add badge/logo overlay if enabled
        $this->add_badge_overlay($file['tmp_name']);

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

        // Add badge/logo overlay if enabled
        $this->add_badge_overlay($file_path);

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

    /**
     * Add badge/logo overlay to image
     */
    public function add_badge_overlay($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }

        $badge_enabled = get_option('wsc_badge_enabled', false);
        $logo_enabled = get_option('wsc_logo_enabled', false);

        if (!$badge_enabled && !$logo_enabled) {
            return false;
        }

        // Get GD image resource
        $image_info = getimagesize($file_path);
        if (!$image_info) {
            return false;
        }

        $mime_type = $image_info['mime'];

        // Create image resource based on type
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($file_path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($file_path);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($file_path);
                break;
            default:
                return false;
        }

        if (!$image) {
            return false;
        }

        // Enable alpha blending for transparency
        imagealphablending($image, true);
        imagesavealpha($image, true);

        // Add text badge if enabled
        if ($badge_enabled) {
            $this->apply_text_badge($image);
        }

        // Add logo overlay if enabled
        if ($logo_enabled) {
            $this->apply_logo_overlay($image);
        }

        // Save the modified image
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($image, $file_path, self::IMAGE_QUALITY);
                break;
            case 'image/png':
                imagepng($image, $file_path, 9);
                break;
            case 'image/gif':
                imagegif($image, $file_path);
                break;
        }

        imagedestroy($image);

        return true;
    }

    /**
     * Apply text badge to image
     */
    private function apply_text_badge($image) {
        $badge_text = get_option('wsc_badge_text', 'SALE');
        $badge_position = get_option('wsc_badge_position', 'top-right');
        $badge_bg_color = get_option('wsc_badge_bg_color', '#FF0000');
        $badge_text_color = get_option('wsc_badge_text_color', '#FFFFFF');
        $badge_size = intval(get_option('wsc_badge_size', 60));

        $img_width = imagesx($image);
        $img_height = imagesy($image);

        // Badge dimensions
        $badge_width = $badge_size * 2;
        $badge_height = $badge_size;
        $font_size = $badge_size / 3;

        // Parse colors
        list($bg_r, $bg_g, $bg_b) = sscanf($badge_bg_color, "#%02x%02x%02x");
        list($text_r, $text_g, $text_b) = sscanf($badge_text_color, "#%02x%02x%02x");

        // Create colors
        $bg_color = imagecolorallocatealpha($image, $bg_r, $bg_g, $bg_b, 20); // Semi-transparent
        $text_color = imagecolorallocate($image, $text_r, $text_g, $text_b);
        $border_color = imagecolorallocate($image, 0, 0, 0);

        // Calculate position
        $positions = $this->calculate_badge_position($img_width, $img_height, $badge_width, $badge_height, $badge_position);
        $x = $positions['x'];
        $y = $positions['y'];

        // Draw rounded rectangle for badge background
        $this->draw_rounded_rectangle($image, $x, $y, $badge_width, $badge_height, 10, $bg_color, $border_color);

        // Add text (use built-in font if TTF not available)
        $text_x = $x + ($badge_width / 2);
        $text_y = $y + ($badge_height / 2);

        // Try to use TrueType font if available
        $font_path = $this->get_system_font();

        if ($font_path && function_exists('imagettftext')) {
            // Get text bounding box
            $bbox = imagettfbbox($font_size, 0, $font_path, $badge_text);
            $text_width = abs($bbox[4] - $bbox[0]);
            $text_height = abs($bbox[5] - $bbox[1]);

            $text_x = $x + (($badge_width - $text_width) / 2);
            $text_y = $y + (($badge_height + $text_height) / 2);

            imagettftext($image, $font_size, 0, $text_x, $text_y, $text_color, $font_path, $badge_text);
        } else {
            // Fallback to built-in font
            $font = 5; // Largest built-in font
            $text_width = imagefontwidth($font) * strlen($badge_text);
            $text_height = imagefontheight($font);

            $text_x = $x + (($badge_width - $text_width) / 2);
            $text_y = $y + (($badge_height - $text_height) / 2);

            imagestring($image, $font, $text_x, $text_y, $badge_text, $text_color);
        }
    }

    /**
     * Apply logo overlay to image
     */
    private function apply_logo_overlay($image) {
        $logo_id = get_option('wsc_logo_attachment_id', 0);
        $logo_position = get_option('wsc_logo_position', 'bottom-right');
        $logo_size = intval(get_option('wsc_logo_size', 80));

        if (!$logo_id) {
            return false;
        }

        $logo_path = get_attached_file($logo_id);
        if (!$logo_path || !file_exists($logo_path)) {
            return false;
        }

        // Load logo image
        $logo_info = getimagesize($logo_path);
        if (!$logo_info) {
            return false;
        }

        $logo_mime = $logo_info['mime'];

        switch ($logo_mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $logo = imagecreatefromjpeg($logo_path);
                break;
            case 'image/png':
                $logo = imagecreatefrompng($logo_path);
                break;
            case 'image/gif':
                $logo = imagecreatefromgif($logo_path);
                break;
            default:
                return false;
        }

        if (!$logo) {
            return false;
        }

        // Get dimensions
        $img_width = imagesx($image);
        $img_height = imagesy($image);
        $logo_width = imagesx($logo);
        $logo_height = imagesy($logo);

        // Resize logo to desired size maintaining aspect ratio
        $logo_aspect = $logo_width / $logo_height;
        $new_logo_width = $logo_size;
        $new_logo_height = $logo_size / $logo_aspect;

        if ($new_logo_height > $logo_size) {
            $new_logo_height = $logo_size;
            $new_logo_width = $logo_size * $logo_aspect;
        }

        // Create resized logo
        $logo_resized = imagecreatetruecolor($new_logo_width, $new_logo_height);

        // Preserve transparency for PNG
        if ($logo_mime == 'image/png') {
            imagealphablending($logo_resized, false);
            imagesavealpha($logo_resized, true);
            $transparent = imagecolorallocatealpha($logo_resized, 255, 255, 255, 127);
            imagefilledrectangle($logo_resized, 0, 0, $new_logo_width, $new_logo_height, $transparent);
        }

        imagecopyresampled($logo_resized, $logo, 0, 0, 0, 0, $new_logo_width, $new_logo_height, $logo_width, $logo_height);

        // Calculate position
        $positions = $this->calculate_badge_position($img_width, $img_height, $new_logo_width, $new_logo_height, $logo_position);

        // Copy logo onto image
        imagecopy($image, $logo_resized, $positions['x'], $positions['y'], 0, 0, $new_logo_width, $new_logo_height);

        imagedestroy($logo);
        imagedestroy($logo_resized);

        return true;
    }

    /**
     * Calculate position for badge/logo
     */
    private function calculate_badge_position($img_width, $img_height, $badge_width, $badge_height, $position) {
        $margin = 10; // Margin from edges

        switch ($position) {
            case 'top-left':
                return array('x' => $margin, 'y' => $margin);
            case 'top-right':
                return array('x' => $img_width - $badge_width - $margin, 'y' => $margin);
            case 'bottom-left':
                return array('x' => $margin, 'y' => $img_height - $badge_height - $margin);
            case 'bottom-right':
                return array('x' => $img_width - $badge_width - $margin, 'y' => $img_height - $badge_height - $margin);
            case 'center':
                return array('x' => ($img_width - $badge_width) / 2, 'y' => ($img_height - $badge_height) / 2);
            default:
                return array('x' => $img_width - $badge_width - $margin, 'y' => $margin);
        }
    }

    /**
     * Draw rounded rectangle
     */
    private function draw_rounded_rectangle($image, $x, $y, $width, $height, $radius, $bg_color, $border_color) {
        // Draw filled rectangle
        imagefilledrectangle($image, $x + $radius, $y, $x + $width - $radius, $y + $height, $bg_color);
        imagefilledrectangle($image, $x, $y + $radius, $x + $width, $y + $height - $radius, $bg_color);

        // Draw corners
        imagefilledellipse($image, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $bg_color);
        imagefilledellipse($image, $x + $width - $radius, $y + $radius, $radius * 2, $radius * 2, $bg_color);
        imagefilledellipse($image, $x + $radius, $y + $height - $radius, $radius * 2, $radius * 2, $bg_color);
        imagefilledellipse($image, $x + $width - $radius, $y + $height - $radius, $radius * 2, $radius * 2, $bg_color);

        // Draw border
        imagerectangle($image, $x, $y, $x + $width, $y + $height, $border_color);
    }

    /**
     * Get system font path
     */
    private function get_system_font() {
        $possible_fonts = array(
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/System/Library/Fonts/Helvetica.ttc',
            'C:\\Windows\\Fonts\\arial.ttf',
            'C:\\Windows\\Fonts\\arialbd.ttf',
        );

        foreach ($possible_fonts as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }

        return false;
    }

    /**
     * Get badge settings
     */
    public function get_badge_settings() {
        return array(
            'badge_enabled' => get_option('wsc_badge_enabled', false),
            'badge_text' => get_option('wsc_badge_text', 'SALE'),
            'badge_position' => get_option('wsc_badge_position', 'top-right'),
            'badge_bg_color' => get_option('wsc_badge_bg_color', '#FF0000'),
            'badge_text_color' => get_option('wsc_badge_text_color', '#FFFFFF'),
            'badge_size' => get_option('wsc_badge_size', 60),
            'logo_enabled' => get_option('wsc_logo_enabled', false),
            'logo_attachment_id' => get_option('wsc_logo_attachment_id', 0),
            'logo_position' => get_option('wsc_logo_position', 'bottom-right'),
            'logo_size' => get_option('wsc_logo_size', 80),
        );
    }

    /**
     * Update badge settings
     */
    public function update_badge_settings($settings) {
        update_option('wsc_badge_enabled', isset($settings['badge_enabled']) ? (bool)$settings['badge_enabled'] : false);
        update_option('wsc_badge_text', sanitize_text_field($settings['badge_text']));
        update_option('wsc_badge_position', sanitize_text_field($settings['badge_position']));
        update_option('wsc_badge_bg_color', sanitize_hex_color($settings['badge_bg_color']));
        update_option('wsc_badge_text_color', sanitize_hex_color($settings['badge_text_color']));
        update_option('wsc_badge_size', intval($settings['badge_size']));
        update_option('wsc_logo_enabled', isset($settings['logo_enabled']) ? (bool)$settings['logo_enabled'] : false);
        update_option('wsc_logo_attachment_id', intval($settings['logo_attachment_id']));
        update_option('wsc_logo_position', sanitize_text_field($settings['logo_position']));
        update_option('wsc_logo_size', intval($settings['logo_size']));

        return true;
    }
}
