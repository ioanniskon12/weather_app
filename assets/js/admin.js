/**
 * WooCommerce Shop CRM - Admin Scripts
 */

(function($) {
    'use strict';

    // Global CRM object
    const WSC_CRM = {
        mediaUploader: null,
        galleryUploader: null,

        init: function() {
            this.bindEvents();
            this.initModals();
        },

        bindEvents: function() {
            // Product events
            $('.wsc-add-product-btn').on('click', this.openProductModal.bind(this));
            $(document).on('click', '.wsc-edit-product', this.editProduct.bind(this));
            $(document).on('click', '.wsc-delete-product', this.deleteProduct.bind(this));
            $('#wsc-product-form').on('submit', this.saveProduct.bind(this));

            // Image upload events
            $(document).on('click', '.wsc-upload-image-btn', this.uploadProductImage.bind(this));
            $(document).on('click', '.wsc-remove-image-btn', this.removeProductImage.bind(this));
            $(document).on('click', '.wsc-upload-gallery-btn', this.uploadGalleryImages.bind(this));
            $(document).on('click', '.wsc-remove-gallery-image', this.removeGalleryImage.bind(this));

            // Stock management toggle
            $('#product_manage_stock').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#stock_quantity_field').show();
                } else {
                    $('#stock_quantity_field').hide();
                }
            });

            // Offer events
            $('.wsc-add-offer-btn').on('click', this.openOfferModal.bind(this));
            $('.wsc-edit-offer').on('click', this.editOffer.bind(this));
            $('.wsc-delete-offer').on('click', this.deleteOffer.bind(this));
            $('#wsc-offer-form').on('submit', this.saveOffer.bind(this));

            // Order events
            $('.wsc-order-status-select').on('change', this.updateOrderStatus.bind(this));
            $('.wsc-view-order-details').on('click', this.viewOrderDetails.bind(this));

            // Modal events
            $('.wsc-modal-close').on('click', this.closeModal.bind(this));
            $(window).on('click', function(e) {
                if ($(e.target).hasClass('wsc-modal')) {
                    WSC_CRM.closeModal();
                }
            });
        },

        initModals: function() {
            // Initialize modals if needed
        },

        // Product Functions
        openProductModal: function(e) {
            e.preventDefault();
            $('#wsc-modal-title').text(wscCRM.strings.add_product || 'Add New Product');
            this.resetProductForm();
            $('#wsc-product-modal').fadeIn(300);
        },

        resetProductForm: function() {
            $('#wsc-product-form')[0].reset();
            $('#product_id').val('');

            // Reset TinyMCE editor
            if (typeof tinymce !== 'undefined' && tinymce.get('product_description')) {
                tinymce.get('product_description').setContent('');
            }

            // Reset images
            $('#product_image_id').val('');
            $('#product_image_preview').empty();
            $('.wsc-remove-image-btn').hide();
            $('#product_gallery_ids').val('');
            $('#product_gallery_preview').empty();

            // Uncheck all categories
            $('.wsc-category-checklist input[type="checkbox"]').prop('checked', false);

            // Show stock field by default
            $('#stock_quantity_field').show();
        },

        editProduct: function(e) {
            e.preventDefault();
            const productId = $(this).data('product-id');
            const self = this;

            $('#wsc-modal-title').text('Edit Product');

            // Show loading state
            $('#wsc-product-modal').fadeIn(300);
            $('#wsc-product-form').css('opacity', '0.5');

            // Fetch product data via AJAX
            $.ajax({
                url: wscCRM.ajax_url,
                type: 'POST',
                data: {
                    action: 'wsc_get_product',
                    nonce: wscCRM.nonce,
                    product_id: productId
                },
                success: function(response) {
                    if (response.success && response.data.product) {
                        self.populateProductForm(response.data.product);
                    } else {
                        self.showNotice('error', response.data.message || 'Failed to load product');
                        self.closeModal();
                    }
                    $('#wsc-product-form').css('opacity', '1');
                },
                error: function() {
                    self.showNotice('error', wscCRM.strings.error || 'An error occurred');
                    self.closeModal();
                }
            });
        },

        populateProductForm: function(product) {
            // Basic fields
            $('#product_id').val(product.id);
            $('#product_name').val(product.name);
            $('#product_short_description').val(product.short_description);
            $('#product_price').val(product.regular_price);
            $('#product_sale_price').val(product.sale_price);
            $('#product_sale_from').val(product.sale_from);
            $('#product_sale_to').val(product.sale_to);
            $('#product_sku').val(product.sku);
            $('#product_stock').val(product.stock_quantity);
            $('#product_stock_status').val(product.stock_status);

            // Manage stock checkbox
            $('#product_manage_stock').prop('checked', product.manage_stock);
            if (product.manage_stock) {
                $('#stock_quantity_field').show();
            } else {
                $('#stock_quantity_field').hide();
            }

            // Description with TinyMCE
            if (typeof tinymce !== 'undefined' && tinymce.get('product_description')) {
                tinymce.get('product_description').setContent(product.description || '');
            } else {
                $('#product_description').val(product.description);
            }

            // Categories
            $('.wsc-category-checklist input[type="checkbox"]').prop('checked', false);
            if (product.category_ids && product.category_ids.length > 0) {
                product.category_ids.forEach(function(catId) {
                    $('.wsc-category-checklist input[value="' + catId + '"]').prop('checked', true);
                });
            }

            // Tags
            $('#product_tags').val(product.tags);

            // Attributes
            $('#product_brand').val(product.brand || '');
            $('#product_color').val(product.color || '');
            $('#product_size').val(product.size || '');
            $('#product_material').val(product.material || '');

            // Product image
            if (product.image_id && product.image_url) {
                $('#product_image_id').val(product.image_id);
                $('#product_image_preview').html('<img src="' + product.image_url + '" style="max-width: 150px; margin-top: 10px;">');
                $('.wsc-remove-image-btn[data-target="product_image_id"]').show();
            }

            // Gallery images
            if (product.gallery_images && product.gallery_images.length > 0) {
                const galleryIds = product.gallery_images.map(img => img.id).join(',');
                $('#product_gallery_ids').val(galleryIds);

                let galleryHtml = '<div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">';
                product.gallery_images.forEach(function(img) {
                    galleryHtml += '<div class="wsc-gallery-item" style="position: relative;">' +
                        '<img src="' + img.url + '" style="max-width: 100px; height: auto;">' +
                        '<button type="button" class="wsc-remove-gallery-image" data-image-id="' + img.id + '" style="position: absolute; top: 0; right: 0; background: red; color: white; border: none; cursor: pointer; padding: 2px 6px;">×</button>' +
                        '</div>';
                });
                galleryHtml += '</div>';
                $('#product_gallery_preview').html(galleryHtml);
            }
        },

        deleteProduct: function(e) {
            e.preventDefault();

            if (!confirm(wscCRM.strings.confirm_delete || 'Are you sure you want to delete this product?')) {
                return;
            }

            const $button = $(this);
            const productId = $button.data('product-id');

            $button.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: wscCRM.ajax_url,
                type: 'POST',
                data: {
                    action: 'wsc_delete_product',
                    nonce: wscCRM.nonce,
                    product_id: productId
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                        WSC_CRM.showNotice('success', response.data.message || 'Product deleted successfully');
                    } else {
                        WSC_CRM.showNotice('error', response.data.message || 'Failed to delete product');
                        $button.prop('disabled', false).text('Delete');
                    }
                },
                error: function() {
                    WSC_CRM.showNotice('error', wscCRM.strings.error || 'An error occurred');
                    $button.prop('disabled', false).text('Delete');
                }
            });
        },

        saveProduct: function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');

            // Update TinyMCE content before serializing
            if (typeof tinymce !== 'undefined' && tinymce.get('product_description')) {
                tinymce.get('product_description').save();
            }

            // Get form data
            let formData = new FormData($form[0]);
            formData.append('action', 'wsc_save_product');
            formData.append('nonce', wscCRM.nonce);

            // Add checked categories
            $('.wsc-category-checklist input[type="checkbox"]:checked').each(function() {
                formData.append('tax_input[product_cat][]', $(this).val());
            });

            $submitBtn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: wscCRM.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        WSC_CRM.showNotice('success', response.data.message || 'Product saved successfully');
                        WSC_CRM.closeModal();
                        // Reload the page to show updated data
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        WSC_CRM.showNotice('error', response.data.message || 'Failed to save product');
                        $submitBtn.prop('disabled', false).text('Save Product');
                    }
                },
                error: function() {
                    WSC_CRM.showNotice('error', wscCRM.strings.error || 'An error occurred');
                    $submitBtn.prop('disabled', false).text('Save Product');
                }
            });
        },

        // Image Upload Functions
        uploadProductImage: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const target = $button.data('target');
            const self = this;

            // Create the media uploader if it doesn't exist
            if (!this.mediaUploader) {
                this.mediaUploader = wp.media({
                    title: 'Select Product Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false
                });

                // When an image is selected, update the preview
                this.mediaUploader.on('select', function() {
                    const attachment = self.mediaUploader.state().get('selection').first().toJSON();
                    $('#' + target).val(attachment.id);
                    $('#' + target.replace('_id', '_preview')).html(
                        '<img src="' + attachment.url + '" style="max-width: 150px; margin-top: 10px;">'
                    );
                    $('.wsc-remove-image-btn[data-target="' + target + '"]').show();
                });
            }

            // Open the uploader
            this.mediaUploader.open();
        },

        removeProductImage: function(e) {
            e.preventDefault();
            const target = $(e.currentTarget).data('target');
            $('#' + target).val('');
            $('#' + target.replace('_id', '_preview')).empty();
            $(e.currentTarget).hide();
        },

        uploadGalleryImages: function(e) {
            e.preventDefault();
            const self = this;

            // Create the gallery uploader if it doesn't exist
            if (!this.galleryUploader) {
                this.galleryUploader = wp.media({
                    title: 'Select Gallery Images',
                    button: {
                        text: 'Add to gallery'
                    },
                    multiple: true
                });

                // When images are selected, update the preview
                this.galleryUploader.on('select', function() {
                    const attachments = self.galleryUploader.state().get('selection').toJSON();
                    const existingIds = $('#product_gallery_ids').val();
                    const imageIds = existingIds ? existingIds.split(',') : [];

                    let galleryHtml = $('#product_gallery_preview').html();

                    attachments.forEach(function(attachment) {
                        if (!imageIds.includes(attachment.id.toString())) {
                            imageIds.push(attachment.id);
                            galleryHtml += '<div class="wsc-gallery-item" style="position: relative; display: inline-block; margin: 5px;">' +
                                '<img src="' + attachment.url + '" style="max-width: 100px; height: auto;">' +
                                '<button type="button" class="wsc-remove-gallery-image" data-image-id="' + attachment.id + '" style="position: absolute; top: 0; right: 0; background: red; color: white; border: none; cursor: pointer; padding: 2px 6px;">×</button>' +
                                '</div>';
                        }
                    });

                    $('#product_gallery_ids').val(imageIds.join(','));
                    $('#product_gallery_preview').html(galleryHtml);
                });
            }

            // Open the uploader
            this.galleryUploader.open();
        },

        removeGalleryImage: function(e) {
            e.preventDefault();
            const imageId = $(e.currentTarget).data('image-id').toString();
            const existingIds = $('#product_gallery_ids').val();
            const imageIds = existingIds ? existingIds.split(',') : [];

            // Remove the image ID
            const newIds = imageIds.filter(id => id !== imageId);
            $('#product_gallery_ids').val(newIds.join(','));

            // Remove the preview
            $(e.currentTarget).closest('.wsc-gallery-item').remove();
        },

        // Offer Functions
        openOfferModal: function(e) {
            e.preventDefault();
            $('#wsc-offer-modal-title').text('Add New Offer');
            $('#wsc-offer-form')[0].reset();
            $('#offer_id').val('');
            $('#wsc-offer-modal').fadeIn(300);
        },

        editOffer: function(e) {
            e.preventDefault();
            const offerData = $(this).data('offer');

            $('#wsc-offer-modal-title').text('Edit Offer');
            $('#offer_id').val(offerData.id);
            $('#offer_name').val(offerData.offer_name);
            $('#offer_type').val(offerData.offer_type);
            $('#offer_value').val(offerData.offer_value);
            $('#min_purchase').val(offerData.min_purchase);
            $('#status').val(offerData.status);

            // Handle dates
            if (offerData.start_date) {
                const startDate = new Date(offerData.start_date);
                $('#start_date').val(this.formatDateTimeLocal(startDate));
            }

            if (offerData.end_date) {
                const endDate = new Date(offerData.end_date);
                $('#end_date').val(this.formatDateTimeLocal(endDate));
            }

            $('#wsc-offer-modal').fadeIn(300);
        },

        deleteOffer: function(e) {
            e.preventDefault();

            if (!confirm(wscCRM.strings.confirm_delete || 'Are you sure you want to delete this offer?')) {
                return;
            }

            const $button = $(this);
            const offerId = $button.data('offer-id');

            $button.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: wscCRM.ajax_url,
                type: 'POST',
                data: {
                    action: 'wsc_delete_offer',
                    nonce: wscCRM.nonce,
                    offer_id: offerId
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                        WSC_CRM.showNotice('success', response.data.message || 'Offer deleted successfully');
                    } else {
                        WSC_CRM.showNotice('error', response.data.message || 'Failed to delete offer');
                        $button.prop('disabled', false).text('Delete');
                    }
                },
                error: function() {
                    WSC_CRM.showNotice('error', wscCRM.strings.error || 'An error occurred');
                    $button.prop('disabled', false).text('Delete');
                }
            });
        },

        saveOffer: function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const formData = $form.serialize();

            $submitBtn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: wscCRM.ajax_url,
                type: 'POST',
                data: formData + '&action=wsc_save_offer&nonce=' + wscCRM.nonce,
                success: function(response) {
                    if (response.success) {
                        WSC_CRM.showNotice('success', response.data.message || 'Offer saved successfully');
                        WSC_CRM.closeModal();
                        // Reload the page to show updated data
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        WSC_CRM.showNotice('error', response.data.message || 'Failed to save offer');
                        $submitBtn.prop('disabled', false).text('Save Offer');
                    }
                },
                error: function() {
                    WSC_CRM.showNotice('error', wscCRM.strings.error || 'An error occurred');
                    $submitBtn.prop('disabled', false).text('Save Offer');
                }
            });
        },

        // Order Functions
        updateOrderStatus: function(e) {
            const $select = $(this);
            const orderId = $select.data('order-id');
            const newStatus = $select.val();
            const originalStatus = $select.data('original-status') || $select.val();

            $select.prop('disabled', true);

            $.ajax({
                url: wscCRM.ajax_url,
                type: 'POST',
                data: {
                    action: 'wsc_update_order_status',
                    nonce: wscCRM.nonce,
                    order_id: orderId,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        WSC_CRM.showNotice('success', response.data.message || 'Order status updated');
                        $select.data('original-status', newStatus);
                    } else {
                        WSC_CRM.showNotice('error', response.data.message || 'Failed to update order status');
                        $select.val(originalStatus);
                    }
                    $select.prop('disabled', false);
                },
                error: function() {
                    WSC_CRM.showNotice('error', wscCRM.strings.error || 'An error occurred');
                    $select.val(originalStatus);
                    $select.prop('disabled', false);
                }
            });
        },

        viewOrderDetails: function(e) {
            e.preventDefault();
            const orderId = $(this).data('order-id');

            // In a real implementation, you would fetch order details via AJAX
            $('#wsc-order-details').html('<p>Loading order details...</p>');
            $('#wsc-order-modal').fadeIn(300);

            // TODO: Fetch and display order details
            console.log('Viewing order:', orderId);
        },

        // Modal Functions
        closeModal: function() {
            $('.wsc-modal').fadeOut(300);
        },

        // Utility Functions
        showNotice: function(type, message) {
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            const $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

            $('.wrap > h1').after($notice);

            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        },

        formatDateTimeLocal: function(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');

            return `${year}-${month}-${day}T${hours}:${minutes}`;
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        WSC_CRM.init();
    });

})(jQuery);
