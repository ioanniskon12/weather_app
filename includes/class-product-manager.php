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
}
