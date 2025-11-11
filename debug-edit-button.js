/**
 * Debug Script for Edit Button
 *
 * Copy and paste this into your browser console (F12) on the Products page
 * to diagnose why the edit button might not be working.
 */

console.log("=== WooCommerce Shop CRM Debug ===");

// 1. Check if jQuery is loaded
if (typeof jQuery !== 'undefined') {
    console.log("✅ jQuery is loaded");
    console.log("jQuery version:", jQuery.fn.jquery);
} else {
    console.error("❌ jQuery is NOT loaded!");
}

// 2. Check if wscCRM object exists
if (typeof wscCRM !== 'undefined') {
    console.log("✅ wscCRM object exists");
    console.log("wscCRM:", wscCRM);
} else {
    console.error("❌ wscCRM object does NOT exist!");
}

// 3. Check if admin.js is loaded
if (typeof WSC_CRM !== 'undefined') {
    console.log("✅ WSC_CRM object exists (admin.js loaded)");
} else {
    console.error("❌ WSC_CRM object does NOT exist (admin.js not loaded)!");
}

// 4. Check if edit buttons exist
const editButtons = jQuery('.wsc-edit-product');
console.log("Number of edit buttons found:", editButtons.length);
if (editButtons.length > 0) {
    console.log("✅ Edit buttons exist on page");
    console.log("First edit button:", editButtons.first());
} else {
    console.error("❌ No edit buttons found on page!");
}

// 5. Check if modal exists
const modal = jQuery('#wsc-product-modal');
console.log("Modal exists:", modal.length > 0);
if (modal.length > 0) {
    console.log("✅ Product modal exists");
    console.log("Modal display:", modal.css('display'));
} else {
    console.error("❌ Product modal does NOT exist!");
}

// 6. Check if TinyMCE is loaded
if (typeof tinymce !== 'undefined') {
    console.log("✅ TinyMCE is loaded");
} else {
    console.warn("⚠️  TinyMCE is not loaded (might be OK)");
}

// 7. Test click handler manually
console.log("\n=== Testing Click Handler ===");
console.log("Try clicking an edit button now and watch for messages...");

jQuery(document).off('click', '.wsc-edit-product-debug');
jQuery(document).on('click', '.wsc-edit-product-debug', function(e) {
    e.preventDefault();
    const productId = jQuery(this).data('product-id');
    console.log("✅ Edit button clicked! Product ID:", productId);

    // Test AJAX call
    console.log("Testing AJAX call...");
    jQuery.ajax({
        url: wscCRM.ajax_url,
        type: 'POST',
        data: {
            action: 'wsc_get_product',
            nonce: wscCRM.nonce,
            product_id: productId
        },
        success: function(response) {
            console.log("✅ AJAX successful!");
            console.log("Response:", response);
        },
        error: function(xhr, status, error) {
            console.error("❌ AJAX failed!");
            console.error("Status:", status);
            console.error("Error:", error);
            console.error("Response:", xhr.responseText);
        }
    });
});

// Add debug class to first edit button
if (editButtons.length > 0) {
    editButtons.first().addClass('wsc-edit-product-debug');
    console.log("\n✅ Added debug handler to first edit button");
    console.log("Click the first 'Edit' button to test AJAX");
}

// 8. Check admin.css is loaded
const modalBody = jQuery('.wsc-modal-body');
if (modalBody.length > 0) {
    console.log("✅ Modal body exists");
    console.log("Modal body CSS:", {
        padding: modalBody.css('padding'),
        background: modalBody.css('background-color')
    });
} else {
    console.warn("⚠️  Modal body not found");
}

// 9. Summary
console.log("\n=== Summary ===");
const checks = {
    "jQuery loaded": typeof jQuery !== 'undefined',
    "wscCRM object": typeof wscCRM !== 'undefined',
    "WSC_CRM object": typeof WSC_CRM !== 'undefined',
    "Edit buttons exist": jQuery('.wsc-edit-product').length > 0,
    "Modal exists": jQuery('#wsc-product-modal').length > 0
};

let allPassed = true;
for (const [check, passed] of Object.entries(checks)) {
    console.log(passed ? `✅ ${check}` : `❌ ${check}`);
    if (!passed) allPassed = false;
}

if (allPassed) {
    console.log("\n✅ All checks passed! Edit button should work.");
    console.log("If it still doesn't work, try:");
    console.log("1. Hard refresh: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)");
    console.log("2. Clear browser cache");
    console.log("3. Clear WordPress cache if using cache plugin");
} else {
    console.log("\n❌ Some checks failed. See errors above.");
    console.log("Try:");
    console.log("1. Re-upload the plugin files");
    console.log("2. Check file permissions on server");
    console.log("3. Deactivate and reactivate the plugin");
}
