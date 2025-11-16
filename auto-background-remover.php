<?php
/**
 * Plugin Name: Auto Background Remover
 * Plugin URI: https://kontopyrgos.com.cy
 * Description: Automatically removes backgrounds from images on specific pages (Black Friday pages)
 * Version: 1.0.1
 * Author: Giannis
 * Author URI: https://kontopyrgos.com.cy
 * License: GPL v2 or later
 * Text Domain: auto-bg-remover
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Auto_Background_Remover {

    private $version = '1.0.1';

    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Enqueue scripts on frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_options_page(
            'Background Remover Settings',
            'Background Remover',
            'manage_options',
            'auto-bg-remover',
            array($this, 'settings_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('auto_bg_remover_settings', 'abr_target_urls');
        register_setting('auto_bg_remover_settings', 'abr_image_selector');
        register_setting('auto_bg_remover_settings', 'abr_enabled');
    }

    /**
     * Settings page HTML
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>🎨 Auto Background Remover Settings</h1>
            <p>Configure which pages should have automatic background removal.</p>

            <form method="post" action="options.php">
                <?php
                settings_fields('auto_bg_remover_settings');
                do_settings_sections('auto_bg_remover_settings');
                ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="abr_enabled">Enable Background Removal</label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="abr_enabled"
                                   name="abr_enabled"
                                   value="1"
                                   <?php checked(1, get_option('abr_enabled', 1)); ?> />
                            <p class="description">Turn on/off the background removal feature.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="abr_target_urls">Target URLs</label>
                        </th>
                        <td>
                            <textarea id="abr_target_urls"
                                      name="abr_target_urls"
                                      rows="5"
                                      class="large-text"><?php echo esc_textarea(get_option('abr_target_urls', "/black-friday\n/en/black-friday")); ?></textarea>
                            <p class="description">
                                Enter exact URL paths (one per line) where background removal should work.<br>
                                Examples: /black-friday or /en/black-friday<br>
                                <strong>Note:</strong> Must match exactly (e.g., /black-friday will NOT match /black-friday/products)
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="abr_image_selector">Image Selector (CSS)</label>
                        </th>
                        <td>
                            <input type="text"
                                   id="abr_image_selector"
                                   name="abr_image_selector"
                                   value="<?php echo esc_attr(get_option('abr_image_selector', '.product-image img, .product img, img')); ?>"
                                   class="large-text" />
                            <p class="description">
                                CSS selector for images to process. Default processes all images.<br>
                                Examples: <code>.product img</code> or <code>.woocommerce-product-gallery img</code>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr>

            <h2>ℹ️ How It Works</h2>
            <ul>
                <li>✅ <strong>No API Key Required</strong> - Uses free client-side processing</li>
                <li>✅ <strong>Automatic Processing</strong> - Removes backgrounds when page loads</li>
                <li>✅ <strong>Smart Caching</strong> - Processed images are cached in browser</li>
                <li>⚠️ <strong>Note:</strong> First load may take a few seconds per image</li>
            </ul>

            <h2>🎯 Current Configuration</h2>
            <p><strong>Status:</strong> <?php echo get_option('abr_enabled', 1) ? '✅ Enabled' : '❌ Disabled'; ?></p>
            <p><strong>Active on pages containing:</strong></p>
            <ul>
                <?php
                $urls = explode("\n", get_option('abr_target_urls', "/black-friday\n/en/black-friday"));
                foreach ($urls as $url) {
                    $url = trim($url);
                    if (!empty($url)) {
                        echo '<li><code>' . esc_html($url) . '</code></li>';
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
        // Check if enabled
        if (!get_option('abr_enabled', 1)) {
            return;
        }

        // Check if current page matches target URLs (exact match)
        $target_urls = explode("\n", get_option('abr_target_urls', "/black-friday\n/en/black-friday"));
        $current_url = rtrim($_SERVER['REQUEST_URI'], '/'); // Remove trailing slash
        $is_target_page = false;

        foreach ($target_urls as $url) {
            $url = rtrim(trim($url), '/'); // Remove trailing slash and whitespace
            if (!empty($url) && $current_url === $url) {
                $is_target_page = true;
                break;
            }
        }

        if (!$is_target_page) {
            return;
        }

        // Get image selector
        $image_selector = get_option('abr_image_selector', '.product-image img, .product img, img');

        // Add inline script
        add_action('wp_footer', function() use ($image_selector) {
            ?>
            <script type="module">
                console.log('🎨 Auto Background Remover: Loading...');

                // Import the background removal library
                import removeBackground from 'https://cdn.jsdelivr.net/npm/@imgly/background-removal@1.4.5/dist/browser.min.js';

                async function processImages() {
                    const images = document.querySelectorAll('<?php echo esc_js($image_selector); ?>');
                    console.log(`🎨 Found ${images.length} images to process`);

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

// Initialize plugin
new Auto_Background_Remover();
