<?php
/**
 * Background Remover Class
 *
 * Pre-processes product images to remove backgrounds (admin-side batch processing)
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WSC_Background_Remover {

    public function __construct() {
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));

        // AJAX handlers for processing images
        add_action('wp_ajax_wsc_get_products_for_bg_removal', array($this, 'ajax_get_products'));
        add_action('wp_ajax_wsc_save_processed_image', array($this, 'ajax_save_processed_image'));
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('wsc_bg_remover_settings', 'wsc_abr_product_category');
    }

    /**
     * Settings page HTML
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>🎨 Background Remover</h1>
            <p>Process product images to remove backgrounds permanently.</p>

            <div class="card" style="max-width: 100%; margin-top: 20px;">
                <h2>Batch Process Products</h2>
                <p>Select which products to process and click "Start Processing" to remove backgrounds from all product images.</p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wsc_product_category">Product Category</label>
                        </th>
                        <td>
                            <select id="wsc_product_category" style="min-width: 300px;">
                                <option value="">All Products</option>
                                <?php
                                $categories = get_terms(array(
                                    'taxonomy' => 'product_cat',
                                    'hide_empty' => false,
                                ));
                                foreach ($categories as $category) {
                                    echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">Choose a category or process all products</p>
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="button" id="wsc-start-bg-removal" class="button button-primary button-large">
                        🎨 Start Processing
                    </button>
                    <button type="button" id="wsc-stop-bg-removal" class="button button-secondary" style="display:none;">
                        ⏸️ Stop
                    </button>
                </p>

                <div id="wsc-bg-removal-progress" style="display:none; margin-top: 20px;">
                    <h3>Processing...</h3>
                    <progress id="wsc-progress-bar" max="100" value="0" style="width: 100%; height: 30px;"></progress>
                    <p id="wsc-progress-text">Preparing...</p>

                    <div id="wsc-processed-list" style="margin-top: 20px; max-height: 400px; overflow-y: auto; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                        <h4>Processing Log:</h4>
                        <ul id="wsc-log-list" style="list-style: none; padding: 0; font-family: monospace; font-size: 12px;"></ul>
                    </div>
                </div>
            </div>
        </div>

        <script type="module">
            let processing = false;
            let shouldStop = false;

            document.getElementById('wsc-start-bg-removal').addEventListener('click', async function() {
                if (processing) return;

                processing = true;
                shouldStop = false;
                this.style.display = 'none';
                document.getElementById('wsc-stop-bg-removal').style.display = 'inline-block';
                document.getElementById('wsc-bg-removal-progress').style.display = 'block';

                const categoryId = document.getElementById('wsc_product_category').value;

                addLog('🔍 Fetching products...');

                // Get products to process
                const formData = new FormData();
                formData.append('action', 'wsc_get_products_for_bg_removal');
                formData.append('nonce', '<?php echo wp_create_nonce('wsc_crm_nonce'); ?>');
                formData.append('category_id', categoryId);

                const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!data.success) {
                    addLog('❌ Error: ' + data.data.message, 'error');
                    resetButtons();
                    return;
                }

                const products = data.data.products;
                addLog(`✅ Found ${products.length} products to process`);

                // Import background removal library
                addLog('📦 Loading background removal library...');
                const { default: removeBackground } = await import('https://esm.sh/@imgly/background-removal@1.4.5?bundle');
                addLog('✅ Library loaded');

                // Process each product
                let processed = 0;
                let failed = 0;

                for (let i = 0; i < products.length; i++) {
                    if (shouldStop) {
                        addLog('⏸️ Processing stopped by user');
                        break;
                    }

                    const product = products[i];
                    const progress = Math.round(((i + 1) / products.length) * 100);
                    document.getElementById('wsc-progress-bar').value = progress;
                    document.getElementById('wsc-progress-text').textContent =
                        `Processing ${i + 1} of ${products.length}: ${product.name}`;

                    addLog(`🖼️ Processing: ${product.name}`);

                    // Process each image in the product
                    for (const imageUrl of product.images) {
                        try {
                            addLog(`  → Removing background from: ${imageUrl.split('/').pop()}`);

                            const blob = await removeBackground(imageUrl);

                            // Convert blob to base64
                            const base64 = await blobToBase64(blob);

                            // Send to server to save
                            const saveData = new FormData();
                            saveData.append('action', 'wsc_save_processed_image');
                            saveData.append('nonce', '<?php echo wp_create_nonce('wsc_crm_nonce'); ?>');
                            saveData.append('product_id', product.id);
                            saveData.append('image_url', imageUrl);
                            saveData.append('image_data', base64);

                            const saveResponse = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                                method: 'POST',
                                body: saveData
                            });

                            const saveResult = await saveResponse.json();

                            if (saveResult.success) {
                                addLog(`  ✅ Saved: ${saveResult.data.filename}`, 'success');
                                processed++;
                            } else {
                                addLog(`  ❌ Failed to save: ${saveResult.data.message}`, 'error');
                                failed++;
                            }

                        } catch (error) {
                            addLog(`  ❌ Error: ${error.message}`, 'error');
                            failed++;
                        }

                        // Small delay to prevent browser freeze
                        await new Promise(resolve => setTimeout(resolve, 100));
                    }
                }

                addLog(`\n🎉 Processing complete!`);
                addLog(`✅ Successfully processed: ${processed} images`);
                if (failed > 0) {
                    addLog(`❌ Failed: ${failed} images`, 'error');
                }

                resetButtons();
            });

            document.getElementById('wsc-stop-bg-removal').addEventListener('click', function() {
                shouldStop = true;
                addLog('⏸️ Stopping after current image...');
            });

            function addLog(message, type = 'info') {
                const logList = document.getElementById('wsc-log-list');
                const li = document.createElement('li');
                li.textContent = new Date().toLocaleTimeString() + ' - ' + message;

                if (type === 'success') li.style.color = 'green';
                if (type === 'error') li.style.color = 'red';

                logList.appendChild(li);
                logList.scrollTop = logList.scrollHeight;
            }

            function resetButtons() {
                processing = false;
                document.getElementById('wsc-start-bg-removal').style.display = 'inline-block';
                document.getElementById('wsc-stop-bg-removal').style.display = 'none';
                document.getElementById('wsc-progress-bar').value = 100;
                document.getElementById('wsc-progress-text').textContent = 'Complete!';
            }

            async function blobToBase64(blob) {
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result);
                    reader.onerror = reject;
                    reader.readAsDataURL(blob);
                });
            }
        </script>

        <style>
            #wsc-log-list li {
                padding: 5px 0;
                border-bottom: 1px solid #ddd;
            }
            #wsc-progress-bar {
                -webkit-appearance: none;
                appearance: none;
            }
            #wsc-progress-bar::-webkit-progress-bar {
                background-color: #f3f3f3;
                border-radius: 3px;
            }
            #wsc-progress-bar::-webkit-progress-value {
                background-color: #2271b1;
                border-radius: 3px;
            }
        </style>
        <?php
    }

    /**
     * AJAX: Get products for background removal
     */
    public function ajax_get_products() {
        check_ajax_referer('wsc_crm_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );

        if ($category_id > 0) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ),
            );
        }

        $products = get_posts($args);
        $product_data = array();

        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);

            // Get all product images
            $images = array();

            // Main image
            if ($product->get_image_id()) {
                $image_url = wp_get_attachment_url($product->get_image_id());
                if ($image_url) {
                    $images[] = $image_url;
                }
            }

            // Gallery images
            $gallery_ids = $product->get_gallery_image_ids();
            foreach ($gallery_ids as $image_id) {
                $image_url = wp_get_attachment_url($image_id);
                if ($image_url) {
                    $images[] = $image_url;
                }
            }

            if (!empty($images)) {
                $product_data[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'images' => $images,
                );
            }
        }

        wp_send_json_success(array('products' => $product_data));
    }

    /**
     * AJAX: Save processed image
     */
    public function ajax_save_processed_image() {
        check_ajax_referer('wsc_crm_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $product_id = intval($_POST['product_id']);
        $image_url = sanitize_text_field($_POST['image_url']);
        $image_data = $_POST['image_data']; // Base64 data

        // Decode base64
        $image_parts = explode(';base64,', $image_data);
        if (count($image_parts) !== 2) {
            wp_send_json_error(array('message' => 'Invalid image data'));
        }

        $image_base64 = base64_decode($image_parts[1]);
        if ($image_base64 === false) {
            wp_send_json_error(array('message' => 'Failed to decode image'));
        }

        // Get original filename
        $original_filename = basename($image_url);
        $path_info = pathinfo($original_filename);

        // Create new filename with -no-bg suffix
        $new_filename = $path_info['filename'] . '-no-bg.png';

        // Upload to WordPress
        $upload = wp_upload_bits($new_filename, null, $image_base64);

        if ($upload['error']) {
            wp_send_json_error(array('message' => $upload['error']));
        }

        // Create attachment
        $attachment = array(
            'post_mime_type' => 'image/png',
            'post_title' => sanitize_file_name($new_filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $upload['file']);

        if (is_wp_error($attach_id)) {
            wp_send_json_error(array('message' => $attach_id->get_error_message()));
        }

        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Store metadata to link processed image with original
        // This allows us to restore originals later if needed
        $product = wc_get_product($product_id);

        // Find the original attachment ID
        $original_attachment_id = null;

        // Check if it's the main image
        $main_image_url = wp_get_attachment_url($product->get_image_id());
        if ($main_image_url === $image_url) {
            $original_attachment_id = $product->get_image_id();
        } else {
            // Check gallery images
            $gallery_ids = $product->get_gallery_image_ids();
            foreach ($gallery_ids as $gallery_id) {
                $gallery_url = wp_get_attachment_url($gallery_id);
                if ($gallery_url === $image_url) {
                    $original_attachment_id = $gallery_id;
                    break;
                }
            }
        }

        // Store link between original and processed image
        if ($original_attachment_id) {
            update_post_meta($attach_id, '_wsc_original_image_id', $original_attachment_id);
            update_post_meta($original_attachment_id, '_wsc_processed_image_id', $attach_id);
        }

        // Add processed image to gallery (don't replace original)
        $gallery_ids = $product->get_gallery_image_ids();

        // Add the new processed image to gallery if not already there
        if (!in_array($attach_id, $gallery_ids)) {
            $gallery_ids[] = $attach_id;
            $product->set_gallery_image_ids($gallery_ids);
            $product->save();
        }

        wp_send_json_success(array(
            'attachment_id' => $attach_id,
            'original_attachment_id' => $original_attachment_id,
            'filename' => $new_filename,
            'url' => $upload['url'],
            'message' => 'Transparent background version added to gallery (original kept)'
        ));
    }
}
