<?php
/**
 * Background Remover Class
 *
 * Simple upload-based background remover interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WSC_Background_Remover {

    public function __construct() {
        // AJAX handler for saving processed image
        add_action('wp_ajax_wsc_save_processed_image', array($this, 'ajax_save_processed_image'));
    }

    /**
     * Settings page HTML
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>🎨 Background Remover</h1>
            <p>Upload images or select from media library to remove backgrounds</p>

            <div class="wsc-bg-remover-container">
                <!-- Upload Area -->
                <div class="wsc-upload-section">
                    <div class="wsc-upload-box" id="wsc-upload-box">
                        <div class="wsc-upload-content">
                            <span class="dashicons dashicons-cloud-upload" style="font-size: 60px; color: #0073aa;"></span>
                            <h2>Upload Image</h2>
                            <p>Drag & drop image here or click to browse</p>
                            <p class="wsc-formats">Supports: JPG, PNG, WEBP</p>
                            <input type="file" id="wsc-file-input" accept="image/*" style="display: none;">
                            <button type="button" class="button button-primary button-large" id="wsc-browse-btn">
                                📁 Browse Files
                            </button>
                            <button type="button" class="button button-secondary button-large" id="wsc-media-btn" style="margin-left: 10px;">
                                🖼️ Choose from Media Library
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Processing Area -->
                <div class="wsc-processing-section" id="wsc-processing-section" style="display: none;">
                    <h2>Processing...</h2>
                    <div class="wsc-spinner">
                        <div class="spinner is-active" style="float: none; margin: 20px auto;"></div>
                    </div>
                    <p id="wsc-processing-status">Removing background...</p>
                </div>

                <!-- Result Area -->
                <div class="wsc-result-section" id="wsc-result-section" style="display: none;">
                    <h2>Result</h2>
                    <div class="wsc-comparison">
                        <div class="wsc-image-box">
                            <h3>Original</h3>
                            <img id="wsc-original-preview" src="" alt="Original">
                        </div>
                        <div class="wsc-image-box">
                            <h3>Background Removed</h3>
                            <div class="wsc-transparent-bg">
                                <img id="wsc-processed-preview" src="" alt="Processed">
                            </div>
                        </div>
                    </div>
                    <div class="wsc-actions">
                        <button type="button" class="button button-primary button-large" id="wsc-save-to-media">
                            💾 Save to Media Library
                        </button>
                        <button type="button" class="button button-secondary button-large" id="wsc-download-btn">
                            ⬇️ Download
                        </button>
                        <button type="button" class="button button-large" id="wsc-new-image">
                            🔄 Process Another Image
                        </button>
                    </div>
                    <div id="wsc-save-success" style="display: none; margin-top: 20px; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724;">
                        <strong>✅ Saved successfully!</strong> Image added to Media Library.
                    </div>
                </div>
            </div>
        </div>

        <script type="module">
            let currentProcessedBlob = null;
            let currentFilename = null;

            // File input handling
            document.getElementById('wsc-browse-btn').addEventListener('click', () => {
                document.getElementById('wsc-file-input').click();
            });

            document.getElementById('wsc-file-input').addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    processImage(e.target.files[0]);
                }
            });

            // Drag & drop
            const uploadBox = document.getElementById('wsc-upload-box');
            uploadBox.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadBox.style.borderColor = '#0073aa';
                uploadBox.style.background = '#f0f8ff';
            });

            uploadBox.addEventListener('dragleave', () => {
                uploadBox.style.borderColor = '#ddd';
                uploadBox.style.background = '#fff';
            });

            uploadBox.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadBox.style.borderColor = '#ddd';
                uploadBox.style.background = '#fff';

                if (e.dataTransfer.files.length > 0) {
                    processImage(e.dataTransfer.files[0]);
                }
            });

            // Media library button
            document.getElementById('wsc-media-btn').addEventListener('click', () => {
                const mediaFrame = wp.media({
                    title: 'Select Image',
                    button: { text: 'Use this image' },
                    multiple: false,
                    library: { type: 'image' }
                });

                mediaFrame.on('select', () => {
                    const attachment = mediaFrame.state().get('selection').first().toJSON();
                    fetch(attachment.url)
                        .then(res => res.blob())
                        .then(blob => {
                            const file = new File([blob], attachment.filename, { type: blob.type });
                            processImage(file);
                        });
                });

                mediaFrame.open();
            });

            // Process image
            async function processImage(file) {
                currentFilename = file.name;

                // Show processing section
                document.querySelector('.wsc-upload-section').style.display = 'none';
                document.getElementById('wsc-processing-section').style.display = 'block';
                document.getElementById('wsc-result-section').style.display = 'none';

                // Show original preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    document.getElementById('wsc-original-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);

                try {
                    // Load library
                    document.getElementById('wsc-processing-status').textContent = 'Loading AI model...';
                    const { default: removeBackground } = await import('https://esm.sh/@imgly/background-removal@1.4.5?bundle');

                    // Process - creates PNG with transparent background, preserves quality
                    document.getElementById('wsc-processing-status').textContent = 'Removing background...';
                    const blob = await removeBackground(file);

                    currentProcessedBlob = blob;

                    // Show result
                    const processedUrl = URL.createObjectURL(blob);
                    document.getElementById('wsc-processed-preview').src = processedUrl;

                    document.getElementById('wsc-processing-section').style.display = 'none';
                    document.getElementById('wsc-result-section').style.display = 'block';

                } catch (error) {
                    console.error('Background removal error:', error);
                    alert('Error processing image: ' + error.message);
                    resetToUpload();
                }
            }

            // Download button
            document.getElementById('wsc-download-btn').addEventListener('click', () => {
                if (!currentProcessedBlob) return;

                const url = URL.createObjectURL(currentProcessedBlob);
                const a = document.createElement('a');
                a.href = url;
                a.download = currentFilename.replace(/\.[^/.]+$/, '') + '-no-bg.png';
                a.click();
                URL.revokeObjectURL(url);
            });

            // Save to media library
            document.getElementById('wsc-save-to-media').addEventListener('click', async () => {
                if (!currentProcessedBlob) return;

                document.getElementById('wsc-save-to-media').disabled = true;
                document.getElementById('wsc-save-to-media').textContent = 'Saving...';

                try {
                    // Convert blob to base64
                    const base64 = await blobToBase64(currentProcessedBlob);

                    // Save to WordPress
                    const formData = new FormData();
                    formData.append('action', 'wsc_save_processed_image');
                    formData.append('nonce', '<?php echo wp_create_nonce('wsc_crm_nonce'); ?>');
                    formData.append('image_data', base64);
                    formData.append('filename', currentFilename.replace(/\.[^/.]+$/, '') + '-no-bg.png');

                    const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        document.getElementById('wsc-save-success').style.display = 'block';
                        document.getElementById('wsc-save-to-media').textContent = '✅ Saved!';
                    } else {
                        alert('Error: ' + result.data.message);
                        document.getElementById('wsc-save-to-media').disabled = false;
                        document.getElementById('wsc-save-to-media').textContent = '💾 Save to Media Library';
                    }
                } catch (error) {
                    alert('Error saving: ' + error.message);
                    document.getElementById('wsc-save-to-media').disabled = false;
                    document.getElementById('wsc-save-to-media').textContent = '💾 Save to Media Library';
                }
            });

            // Process another image
            document.getElementById('wsc-new-image').addEventListener('click', resetToUpload);

            function resetToUpload() {
                document.querySelector('.wsc-upload-section').style.display = 'block';
                document.getElementById('wsc-processing-section').style.display = 'none';
                document.getElementById('wsc-result-section').style.display = 'none';
                document.getElementById('wsc-save-success').style.display = 'none';
                document.getElementById('wsc-save-to-media').disabled = false;
                document.getElementById('wsc-save-to-media').textContent = '💾 Save to Media Library';
                document.getElementById('wsc-file-input').value = '';
                currentProcessedBlob = null;
                currentFilename = null;
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
            .wsc-bg-remover-container {
                max-width: 1200px;
                margin: 20px 0;
            }

            .wsc-upload-box {
                border: 3px dashed #ddd;
                border-radius: 10px;
                padding: 60px 40px;
                text-align: center;
                background: #fff;
                transition: all 0.3s;
            }

            .wsc-upload-box:hover {
                border-color: #0073aa;
                background: #f9f9f9;
            }

            .wsc-upload-content h2 {
                margin: 15px 0 10px;
                color: #333;
            }

            .wsc-formats {
                color: #666;
                font-size: 13px;
                margin-bottom: 20px !important;
            }

            .wsc-comparison {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 30px;
                margin: 20px 0;
            }

            .wsc-image-box {
                text-align: center;
            }

            .wsc-image-box h3 {
                margin: 0 0 15px;
                font-size: 16px;
            }

            .wsc-image-box img {
                max-width: 100%;
                height: auto;
                border: 1px solid #ddd;
                border-radius: 5px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }

            .wsc-transparent-bg {
                background:
                    linear-gradient(45deg, #f0f0f0 25%, transparent 25%),
                    linear-gradient(-45deg, #f0f0f0 25%, transparent 25%),
                    linear-gradient(45deg, transparent 75%, #f0f0f0 75%),
                    linear-gradient(-45deg, transparent 75%, #f0f0f0 75%);
                background-size: 20px 20px;
                background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
                padding: 10px;
                border-radius: 5px;
                display: inline-block;
            }

            .wsc-actions {
                text-align: center;
                margin: 30px 0;
            }

            .wsc-actions button {
                margin: 0 5px;
            }

            .wsc-processing-section {
                text-align: center;
                padding: 60px 20px;
            }

            @media (max-width: 768px) {
                .wsc-comparison {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        <?php
    }

    /**
     * AJAX: Save processed image to media library
     */
    public function ajax_save_processed_image() {
        check_ajax_referer('wsc_crm_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $image_data = $_POST['image_data'];
        $filename = sanitize_file_name($_POST['filename']);

        // Decode base64
        $image_parts = explode(';base64,', $image_data);
        if (count($image_parts) !== 2) {
            wp_send_json_error(array('message' => 'Invalid image data'));
        }

        $image_base64 = base64_decode($image_parts[1]);
        if ($image_base64 === false) {
            wp_send_json_error(array('message' => 'Failed to decode image'));
        }

        // Upload to WordPress
        $upload = wp_upload_bits($filename, null, $image_base64);

        if ($upload['error']) {
            wp_send_json_error(array('message' => $upload['error']));
        }

        // Create attachment
        $attachment = array(
            'post_mime_type' => 'image/png',
            'post_title' => sanitize_file_name($filename),
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

        wp_send_json_success(array(
            'attachment_id' => $attach_id,
            'filename' => $filename,
            'url' => $upload['url']
        ));
    }
}
