<?php
/**
 * Background Remover Class
 *
 * Automatically removes backgrounds from product images on specific pages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WSC_Background_Remover {

    public function __construct() {
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));

        // Enqueue scripts on frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('wsc_bg_remover_settings', 'wsc_abr_target_urls');
        register_setting('wsc_bg_remover_settings', 'wsc_abr_image_selector');
        register_setting('wsc_bg_remover_settings', 'wsc_abr_enabled');
    }

    /**
     * Settings page HTML
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>🎨 Background Remover Settings</h1>
            <p>Configure which pages should have automatic background removal for product images.</p>

            <form method="post" action="options.php">
                <?php
                settings_fields('wsc_bg_remover_settings');
                do_settings_sections('wsc_bg_remover_settings');
                ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wsc_abr_enabled">Enable Background Removal</label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="wsc_abr_enabled"
                                   name="wsc_abr_enabled"
                                   value="1"
                                   <?php checked(1, get_option('wsc_abr_enabled', 0)); ?> />
                            <p class="description">Turn on/off the background removal feature.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wsc_abr_target_urls">Target URLs</label>
                        </th>
                        <td>
                            <textarea id="wsc_abr_target_urls"
                                      name="wsc_abr_target_urls"
                                      rows="5"
                                      class="large-text"><?php echo esc_textarea(get_option('wsc_abr_target_urls', "/black-friday\n/en/black-friday")); ?></textarea>
                            <p class="description">
                                Enter exact URL paths (one per line) where background removal should work.<br>
                                Examples: <code>/black-friday</code> or <code>/en/black-friday</code><br>
                                <strong>Note:</strong> Must match exactly (e.g., /black-friday will NOT match /black-friday/products)
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wsc_abr_image_selector">Image Selector (CSS)</label>
                        </th>
                        <td>
                            <input type="text"
                                   id="wsc_abr_image_selector"
                                   name="wsc_abr_image_selector"
                                   value="<?php echo esc_attr(get_option('wsc_abr_image_selector', '.woocommerce-product-gallery img, .product img, .products img, img.attachment-woocommerce_thumbnail')); ?>"
                                   class="large-text" />
                            <p class="description">
                                CSS selector for images to process. Default targets only WooCommerce product images.<br>
                                Current default: <code>.woocommerce-product-gallery img, .product img, .products img</code><br>
                                This excludes logos, banners, icons, and other non-product images.
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr>

            <h2>ℹ️ How It Works</h2>
            <ul>
                <li>✅ <strong>No API Key Required</strong> - Uses free client-side AI processing</li>
                <li>✅ <strong>Automatic Processing</strong> - Removes backgrounds when page loads</li>
                <li>✅ <strong>Smart Caching</strong> - Processed images are cached in browser</li>
                <li>✅ <strong>Product Images Only</strong> - Excludes logos, banners, and icons</li>
                <li>⚠️ <strong>Note:</strong> First load may take a few seconds per image</li>
            </ul>

            <h2>🎯 Current Configuration</h2>
            <p><strong>Status:</strong> <?php echo get_option('wsc_abr_enabled', 0) ? '✅ Enabled' : '❌ Disabled'; ?></p>
            <p><strong>Active on exact URLs:</strong></p>
            <ul>
                <?php
                $urls = explode("\n", get_option('wsc_abr_target_urls', "/black-friday\n/en/black-friday"));
                foreach ($urls as $url) {
                    $url = trim($url);
                    if (!empty($url)) {
                        echo '<li><code>' . esc_html($url) . '</code> → <code>https://www.kontopyrgos.com.cy' . esc_html($url) . '</code></li>';
                    }
                }
                ?>
            </ul>
        </div>

        <style>
            .wrap h1 { color: #2271b1; }
            .wrap h2 { margin-top: 20px; }
            .wrap ul { list-style: disc; margin-left: 20px; }
            .wrap code { background: #f0f0f1; padding: 2px 6px; border-radius: 3px; }
        </style>
        <?php
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        // Debug: Log to PHP error log
        error_log('WSC Background Remover: enqueue_frontend_scripts called');

        // Check if enabled
        $is_enabled = get_option('wsc_abr_enabled', 0);
        error_log('WSC Background Remover: Enabled = ' . ($is_enabled ? 'YES' : 'NO'));

        if (!$is_enabled) {
            return;
        }

        // Check if current page matches target URLs (exact match)
        $target_urls = explode("\n", get_option('wsc_abr_target_urls', "/black-friday\n/en/black-friday"));
        $current_url = rtrim($_SERVER['REQUEST_URI'], '/'); // Remove trailing slash
        error_log('WSC Background Remover: Current URL = ' . $current_url);

        $is_target_page = false;

        foreach ($target_urls as $url) {
            $url = rtrim(trim($url), '/'); // Remove trailing slash and whitespace
            error_log('WSC Background Remover: Checking URL = ' . $url);
            if (!empty($url) && $current_url === $url) {
                $is_target_page = true;
                error_log('WSC Background Remover: MATCH! Will load script');
                break;
            }
        }

        if (!$is_target_page) {
            error_log('WSC Background Remover: No match, exiting');
            return;
        }

        error_log('WSC Background Remover: Script will be loaded!');

        // Get image selector
        $image_selector = get_option('wsc_abr_image_selector', '.woocommerce-product-gallery img, .product img, .products img, img.attachment-woocommerce_thumbnail');

        // Add inline script
        add_action('wp_footer', function() use ($image_selector, $current_url) {
            ?>
            <script type="module">
                console.log('🎨 WooCommerce CRM Background Remover: Loading...');
                console.log('Current URL: <?php echo esc_js($current_url); ?>');
                console.log('Image selector: <?php echo esc_js($image_selector); ?>');

                // Show visible notification
                alert('🎨 Background Remover Active!\n\nThis page will process product images.\nCheck console (F12) for progress.');

                // Import the background removal library
                // Using jsdelivr with +esm for automatic module resolution
                import removeBackground from 'https://cdn.jsdelivr.net/npm/@imgly/background-removal@1.4.5/+esm';

                async function processImages() {
                    const images = document.querySelectorAll('<?php echo esc_js($image_selector); ?>');
                    console.log(`🎨 Found ${images.length} product images to process`);

                    if (images.length === 0) {
                        alert('⚠️ No product images found!\n\nSelector: <?php echo esc_js($image_selector); ?>');
                        return;
                    }

                    let processed = 0;
                    let failed = 0;

                    for (const img of images) {
                        // Skip if already processed or data URL
                        if (!img.src || img.src.startsWith('data:') || img.dataset.bgRemoved === 'true') {
                            continue;
                        }

                        // Skip very small images (likely icons/logos)
                        if (img.width < 50 || img.height < 50) {
                            continue;
                        }

                        try {
                            console.log('Processing:', img.src);

                            // Add loading state
                            img.style.opacity = '0.6';
                            img.style.transition = 'opacity 0.3s';

                            // Remove background
                            const blob = await removeBackground(img.src);
                            const url = URL.createObjectURL(blob);

                            // Update image
                            img.src = url;
                            img.style.opacity = '1';
                            img.dataset.bgRemoved = 'true';

                            processed++;
                            console.log('✅ Success!');

                        } catch (error) {
                            console.error('❌ Failed:', error);
                            img.style.opacity = '1';
                            failed++;
                        }

                        // Small delay between images to prevent browser freeze
                        await new Promise(resolve => setTimeout(resolve, 100));
                    }

                    console.log(`🎨 Background Remover Complete: ${processed} processed, ${failed} failed`);
                    alert(`✅ Background Removal Complete!\n\n${processed} images processed\n${failed} failed`);
                }

                // Start processing when page is fully loaded
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', processImages);
                } else {
                    processImages();
                }
            </script>

            <style>
                /* Add smooth transitions */
                img {
                    transition: opacity 0.3s ease;
                }
            </style>
            <?php
        }, 999);
    }
}
