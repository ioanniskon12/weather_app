/**
 * WooCommerce Shop CRM - Admin Scripts
 */

(function($) {
    'use strict';

    // Global CRM object
    const WSC_CRM = {
        init: function() {
            this.bindEvents();
            this.initModals();
        },

        bindEvents: function() {
            // Product events
            $('.wsc-add-product-btn').on('click', this.openProductModal);
            $('.wsc-edit-product').on('click', this.editProduct);
            $('.wsc-delete-product').on('click', this.deleteProduct);
            $('#wsc-product-form').on('submit', this.saveProduct);

            // Offer events
            $('.wsc-add-offer-btn').on('click', this.openOfferModal);
            $('.wsc-edit-offer').on('click', this.editOffer);
            $('.wsc-delete-offer').on('click', this.deleteOffer);
            $('#wsc-offer-form').on('submit', this.saveOffer);

            // Order events
            $('.wsc-order-status-select').on('change', this.updateOrderStatus);
            $('.wsc-view-order-details').on('click', this.viewOrderDetails);

            // Modal events
            $('.wsc-modal-close').on('click', this.closeModal);
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
            $('#wsc-product-form')[0].reset();
            $('#product_id').val('');
            $('#wsc-product-modal').fadeIn(300);
        },

        editProduct: function(e) {
            e.preventDefault();
            const productId = $(this).data('product-id');

            // In a real implementation, you would fetch product data via AJAX
            // For now, we'll just open the modal
            $('#wsc-modal-title').text('Edit Product');
            $('#product_id').val(productId);
            $('#wsc-product-modal').fadeIn(300);

            // TODO: Fetch and populate product data
            console.log('Editing product:', productId);
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
            const formData = $form.serialize();

            $submitBtn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: wscCRM.ajax_url,
                type: 'POST',
                data: formData + '&action=wsc_save_product&nonce=' + wscCRM.nonce,
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
                $('#start_date').val(WSC_CRM.formatDateTimeLocal(startDate));
            }

            if (offerData.end_date) {
                const endDate = new Date(offerData.end_date);
                $('#end_date').val(WSC_CRM.formatDateTimeLocal(endDate));
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
