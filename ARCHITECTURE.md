# WooCommerce Shop CRM - Plugin Architecture

## Overview

This is a comprehensive WordPress plugin that provides a complete CRM (Customer Relationship Management) system for WooCommerce stores. The plugin allows shop owners to manage products, orders, offers, and view analytics - all from a centralized dashboard.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        WordPress Admin                           │
│                     (User Interface Layer)                       │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│                   Main Plugin File                               │
│              woocommerce-shop-crm.php                           │
│  - Plugin initialization                                         │
│  - Admin menu registration                                       │
│  - AJAX endpoint handlers                                        │
│  - Asset enqueuing (CSS/JS)                                     │
└────────────┬────────────────────────────────────────────────────┘
             │
             ├─────────────────┬──────────────┬──────────────┬────┐
             ↓                 ↓              ↓              ↓    ↓
┌──────────────────┐  ┌──────────────┐  ┌─────────────┐  ┌────────────┐
│ Product Manager  │  │Order Manager │  │Offer Manager│  │ Dashboard  │
│                  │  │              │  │             │  │            │
│ - CRUD products  │  │ - Get orders │  │ - Create    │  │ - Stats    │
│ - Categories     │  │ - Update     │  │   offers    │  │ - Reports  │
│ - Tags           │  │   status     │  │ - Coupons   │  │            │
│ - Attributes     │  │ - Stats      │  │             │  │            │
│ - Images         │  │              │  │             │  │            │
└────────┬─────────┘  └──────┬───────┘  └──────┬──────┘  └─────┬──────┘
         │                   │                  │               │
         └───────────────────┴──────────────────┴───────────────┘
                             │
                             ↓
┌─────────────────────────────────────────────────────────────────┐
│                     WooCommerce API                              │
│  - Product Objects (WC_Product)                                  │
│  - Order Objects (WC_Order)                                      │
│  - Coupon Objects (WC_Coupon)                                    │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│                    WordPress Database                            │
│  - wp_posts (products, orders)                                   │
│  - wp_postmeta (product data)                                    │
│  - wp_terms (categories, tags)                                   │
│  - wp_wsc_offers (custom table)                                  │
└─────────────────────────────────────────────────────────────────┘
```

## Directory Structure

```
woocommerce-shop-crm/
│
├── woocommerce-shop-crm.php      # Main plugin file
├── README.md                      # Installation & usage guide
├── ARCHITECTURE.md                # This file
│
├── includes/                      # PHP Classes (Business Logic)
│   ├── class-product-manager.php  # Product CRUD & image processing
│   ├── class-order-manager.php    # Order management
│   ├── class-offer-manager.php    # Offer/discount management
│   ├── class-dashboard.php        # Dashboard statistics
│   └── class-wpml-integration.php # WPML multilingual support
│
├── templates/                     # PHP View Templates
│   ├── dashboard.php              # Dashboard page
│   ├── products.php               # Products listing & form
│   ├── orders.php                 # Orders listing
│   ├── offers.php                 # Offers management
│   └── settings.php               # Plugin settings
│
└── assets/                        # Frontend Assets
    ├── css/
    │   └── admin.css              # Admin styles
    └── js/
        └── admin.js               # Admin JavaScript (AJAX, UI)
```

## Core Components

### 1. Main Plugin Class (`woocommerce-shop-crm.php`)

**Purpose**: Plugin initialization and orchestration

**Responsibilities**:
- Register activation/deactivation hooks
- Load required classes
- Register admin menus and submenus
- Enqueue CSS and JavaScript assets
- Register AJAX endpoints
- Render page templates

**Key Methods**:
```php
__construct()              # Initialize plugin
init()                     # Hook into WordPress
includes()                 # Load class files
add_admin_menu()          # Register admin pages
enqueue_admin_assets()    # Load CSS/JS
ajax_save_product()       # AJAX: Save product
ajax_get_product()        # AJAX: Load product for editing
ajax_delete_product()     # AJAX: Delete product
ajax_update_order_status() # AJAX: Update order
```

### 2. Product Manager (`includes/class-product-manager.php`)

**Purpose**: Complete product lifecycle management

**Responsibilities**:
- Create, read, update, delete products
- Handle product images (upload, resize, compress)
- Manage product categories and tags
- Handle product attributes (brand, color, size, material)
- Apply image overlays (badges, logos)
- Bulk image optimization

**Key Methods**:
```php
get_products($args)                    # Get product list with pagination
get_product($product_id)               # Get single product object
get_product_for_edit($product_id)      # Get all product data for form
save_product($data)                    # Create or update product
delete_product($product_id)            # Delete product
update_stock($product_id, $quantity)   # Update stock quantity
optimize_image_on_upload($file)        # Compress image on upload
add_badge_overlay($file_path)          # Add badge/logo to image
get_categories()                       # Get product categories
get_tags()                             # Get product tags
```

**Data Flow - Save Product**:
```
User Form → AJAX Request → Main Plugin → Product Manager
                                              ↓
                                    Sanitize & Validate
                                              ↓
                                    WC_Product_Simple Object
                                              ↓
                                    Set Properties (name, price, etc.)
                                              ↓
                                    Save to Database
                                              ↓
                                    Save Custom Meta (attributes)
                                              ↓
                                    Return Product ID
```

### 3. Order Manager (`includes/class-order-manager.php`)

**Purpose**: Order lifecycle and reporting

**Responsibilities**:
- Retrieve orders with filtering
- Update order status
- Calculate order statistics
- Get recent orders
- Revenue tracking

**Key Methods**:
```php
get_orders($args)                 # Get orders with filters
get_order($order_id)              # Get single order
update_order_status($id, $status) # Change order status
get_order_stats()                 # Calculate statistics
get_recent_orders($limit)         # Get latest orders
```

### 4. Offer Manager (`includes/class-offer-manager.php`)

**Purpose**: Discount and promotion management

**Responsibilities**:
- Create and manage custom offers
- Create WooCommerce coupons
- Track offer validity
- Sync with WooCommerce coupon system

**Key Methods**:
```php
get_offers($args)                 # Get offers with pagination
save_offer($data)                 # Create/update offer
delete_offer($offer_id)           # Delete offer
create_woocommerce_coupon($offer) # Create WC coupon from offer
```

**Database**:
Custom table `wp_wsc_offers`:
```sql
id              INT PRIMARY KEY
offer_name      VARCHAR(255)
offer_type      VARCHAR(50)    -- 'percentage' or 'fixed'
offer_value     DECIMAL(10,2)
min_purchase    DECIMAL(10,2)
start_date      DATETIME
end_date        DATETIME
status          VARCHAR(20)    -- 'active' or 'inactive'
created_at      TIMESTAMP
```

### 5. Dashboard (`includes/class-dashboard.php`)

**Purpose**: Aggregate statistics and overview

**Responsibilities**:
- Calculate total products, orders, revenue
- Get low stock alerts
- Provide quick stats for admin overview

**Key Methods**:
```php
get_stats()              # Get all dashboard statistics
get_total_revenue()      # Calculate total revenue
get_low_stock_products() # Products below threshold
```

### 6. WPML Integration (`includes/class-wpml-integration.php`)

**Purpose**: Multilingual support

**Responsibilities**:
- Detect WPML installation
- Show language switcher in CRM
- Display translation status
- Sync prices/stock across translations

**Key Methods**:
```php
is_wpml_active()              # Check if WPML installed
render_language_switcher()    # Display language selector
get_language_flag($lang)      # Get language flag HTML
get_translation_status($id)   # Check translation completeness
```

## Frontend Layer (JavaScript)

### Admin JS (`assets/js/admin.js`)

**Architecture Pattern**: Revealing Module Pattern

```javascript
const WSC_CRM = {
    // Properties
    mediaUploader: null,
    galleryUploader: null,

    // Initialization
    init()
    bindEvents()

    // Product Operations
    openProductModal()
    editProduct()           // Loads product via AJAX
    saveProduct()           // Submits via AJAX
    deleteProduct()         // Deletes via AJAX
    populateProductForm()   // Fills form with data
    resetProductForm()      // Clears form

    // Image Management
    uploadProductImage()    // WordPress Media Library
    removeProductImage()
    uploadGalleryImages()
    removeGalleryImage()

    // Utility
    showNotice()           // Display success/error
    closeModal()
}
```

**AJAX Flow - Edit Product**:
```
User clicks "Edit" → editProduct() → AJAX Request
                                          ↓
                            ajax_get_product (PHP)
                                          ↓
                          get_product_for_edit()
                                          ↓
                            Return JSON data
                                          ↓
                          populateProductForm()
                                          ↓
                            Show filled modal
```

**AJAX Flow - Save Product**:
```
User submits form → saveProduct() → Collect form data
                                          ↓
                              Update TinyMCE content
                                          ↓
                              Add category checkboxes
                                          ↓
                              FormData object
                                          ↓
                              AJAX POST request
                                          ↓
                          ajax_save_product (PHP)
                                          ↓
                            save_product()
                                          ↓
                            Return success/error
                                          ↓
                            Reload page
```

## Styling Layer (CSS)

### Admin CSS (`assets/css/admin.css`)

**Organization**:
```css
/* 1. General Styles */
- Stats grid layout
- Card components
- Status badges

/* 2. Modal System */
- Modal overlay
- Modal content (900px wide for product form)
- Modal animations
- Responsive scaling

/* 3. Form Components */
- Input fields with focus states
- Category checklist (hierarchical)
- Image upload/preview areas
- Stock management toggle

/* 4. Product Form Specific */
- Section headings (H3 with bottom border)
- Attribute fields with hover effects
- Gallery management
- TinyMCE container styling

/* 5. Responsive Design */
- Tablet breakpoints (1024px)
- Mobile breakpoints (782px)
- Touch-friendly buttons
```

## Data Flow Patterns

### 1. Product Creation Flow

```
┌──────────────┐
│ User fills   │
│ product form │
└──────┬───────┘
       │
       ↓
┌──────────────────────┐
│ JavaScript validates │
│ and collects data    │
└──────┬───────────────┘
       │
       ↓ (AJAX POST)
┌──────────────────────────┐
│ Main Plugin receives     │
│ ajax_save_product()      │
└──────┬───────────────────┘
       │
       ↓
┌──────────────────────────┐
│ Product Manager          │
│ save_product()           │
│ - Sanitize input         │
│ - Create WC_Product      │
│ - Set properties         │
│ - Handle categories      │
│ - Handle tags            │
│ - Handle images          │
│ - Save attributes        │
└──────┬───────────────────┘
       │
       ↓
┌──────────────────────────┐
│ WooCommerce saves to DB  │
│ - wp_posts               │
│ - wp_postmeta            │
│ - wp_terms relationships │
└──────┬───────────────────┘
       │
       ↓ (Success Response)
┌──────────────────────────┐
│ JavaScript reloads page  │
│ Shows success notice     │
└──────────────────────────┘
```

### 2. Product Edit Flow

```
┌──────────────┐
│ User clicks  │
│ "Edit"       │
└──────┬───────┘
       │
       ↓ (AJAX GET)
┌──────────────────────────┐
│ Main Plugin              │
│ ajax_get_product()       │
└──────┬───────────────────┘
       │
       ↓
┌──────────────────────────┐
│ Product Manager          │
│ get_product_for_edit()   │
│ - Get WC_Product         │
│ - Get categories         │
│ - Get tags               │
│ - Get attributes (meta)  │
│ - Get images             │
│ - Format dates           │
│ - Return array           │
└──────┬───────────────────┘
       │
       ↓ (JSON Response)
┌──────────────────────────┐
│ JavaScript               │
│ populateProductForm()    │
│ - Fill basic fields      │
│ - Set TinyMCE content    │
│ - Check categories       │
│ - Show images            │
│ - Handle stock toggle    │
└──────┬───────────────────┘
       │
       ↓
┌──────────────┐
│ Modal shown  │
│ with data    │
└──────────────┘
```

### 3. Image Processing Flow

```
┌──────────────────┐
│ User uploads     │
│ image            │
└────────┬─────────┘
         │
         ↓
┌────────────────────────────┐
│ WordPress handles upload   │
│ via Media Library          │
└────────┬───────────────────┘
         │
         ↓
┌────────────────────────────┐
│ optimize_image_on_upload() │
│ - Check file type          │
│ - Create WP_Image_Editor   │
│ - Calculate dimensions     │
│ - Resize to 800x800        │
│ - Set quality to 85%       │
└────────┬───────────────────┘
         │
         ↓
┌────────────────────────────┐
│ add_badge_overlay()        │
│ (if enabled)               │
│ - Load with GD             │
│ - Apply text badge         │
│ - Apply logo watermark     │
│ - Save                     │
└────────┬───────────────────┘
         │
         ↓
┌────────────────────────────┐
│ Return attachment ID       │
│ to form                    │
└────────────────────────────┘
```

## Security Architecture

### 1. AJAX Security

All AJAX requests use WordPress nonce verification:

```php
// PHP: Generate nonce
wp_create_nonce('wsc_crm_nonce')

// PHP: Verify nonce
check_ajax_referer('wsc_crm_nonce', 'nonce');

// JavaScript: Send nonce
data: {
    action: 'wsc_save_product',
    nonce: wscCRM.nonce,
    // ... other data
}
```

### 2. Capability Checks

Every operation checks user permissions:

```php
if (!current_user_can('manage_woocommerce')) {
    wp_send_json_error(array('message' => 'Unauthorized'));
}
```

### 3. Input Sanitization

All user input is sanitized:

```php
$product_name = sanitize_text_field($data['product_name']);
$product_description = wp_kses_post($data['product_description']);
$product_price = floatval($data['product_price']);
$product_sku = sanitize_text_field($data['product_sku']);
```

### 4. Database Security

Uses WordPress and WooCommerce APIs (prepared statements):

```php
// WooCommerce handles SQL injection prevention
$product = wc_get_product($product_id);
$product->save();

// Custom table uses $wpdb->prepare()
$wpdb->insert($table, $data, $format);
```

## Extension Points

### 1. Hooks (for future extensions)

```php
// Before product save
do_action('wsc_before_product_save', $product_data);

// After product save
do_action('wsc_after_product_save', $product_id);

// Filter product data
$product_data = apply_filters('wsc_product_data', $product_data);
```

### 2. Template System

Templates can be overridden in theme:

```php
// Plugin: templates/products.php
// Theme override: wp-content/themes/your-theme/woocommerce-shop-crm/products.php
```

### 3. Custom Attributes

Easy to add more attributes:

```php
// In save_product()
update_post_meta($product_id, '_product_custom_field', $value);

// In get_product_for_edit()
$custom_field = get_post_meta($product_id, '_product_custom_field', true);
```

## Performance Considerations

### 1. Pagination

All listings use pagination to avoid loading too much data:

```php
$args = array(
    'limit' => 20,
    'page' => $current_page
);
```

### 2. Image Optimization

- Automatic resize to 800x800px
- Compression at 85% quality
- Processed on upload (not on-demand)

### 3. Caching Opportunities

Current caching:
- WooCommerce object cache for products/orders
- WordPress transients (can be added for stats)

Future improvements:
```php
// Cache dashboard stats for 5 minutes
$stats = get_transient('wsc_dashboard_stats');
if ($stats === false) {
    $stats = $this->calculate_stats();
    set_transient('wsc_dashboard_stats', $stats, 300);
}
```

### 4. Database Queries

- Uses WooCommerce APIs (optimized queries)
- Indexes on custom table (id, status, dates)
- Limits result sets with pagination

## Scalability

### Current Limits

- **Products**: Handles thousands (WooCommerce tested to 100k+)
- **Orders**: Handles thousands (paginated views)
- **Images**: Limited by server disk space
- **Concurrent users**: WordPress standard (PHP-FPM pool size)

### Scaling Strategies

1. **Database**: Use MySQL replication for read-heavy operations
2. **Images**: Offload to CDN (CloudFlare, AWS S3)
3. **Caching**: Implement Redis/Memcached for object cache
4. **Load Balancing**: Multiple PHP-FPM servers behind load balancer

## Testing Strategy

### Unit Tests (Future)

```php
class Test_Product_Manager extends WP_UnitTestCase {
    public function test_save_product() {
        // Test product creation
    }

    public function test_image_optimization() {
        // Test image resize/compress
    }
}
```

### Integration Tests

- Test AJAX endpoints with real WordPress environment
- Test WooCommerce API integration
- Test image upload and processing

### Manual Testing Checklist

- [ ] Create product with all fields
- [ ] Edit existing product
- [ ] Delete product
- [ ] Upload images
- [ ] Set sale dates
- [ ] Assign categories
- [ ] Add tags
- [ ] Update stock
- [ ] Test on mobile

## Deployment

### Plugin Installation

1. Upload ZIP via WordPress admin
2. Activate plugin
3. Configure settings (image optimization, badges)
4. Start managing products

### Requirements

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.2+ with GD library
- MySQL 5.6+
- Optional: WPML for multilingual

### Server Requirements

- PHP memory: 128MB minimum (256MB recommended)
- PHP max_execution_time: 30s+ (for image processing)
- PHP upload_max_filesize: 10MB+ (for images)

## Future Enhancements

### Planned Features

1. **Analytics Dashboard**
   - Sales charts (Chart.js)
   - Revenue trends
   - Top products

2. **Product Variations**
   - Size/Color variants
   - Variable product support

3. **Bulk Operations**
   - Bulk edit products
   - CSV import/export
   - Bulk image optimization

4. **Customer Management**
   - Customer list
   - Purchase history
   - Customer notes

5. **Email Notifications**
   - Low stock alerts
   - Order notifications
   - Custom email templates

6. **REST API**
   - External integrations
   - Mobile app support

7. **Advanced Reporting**
   - Profit margins
   - Tax reports
   - Inventory forecasting

## Troubleshooting

### Common Issues

**Issue**: Images not uploading
- Check PHP upload limits
- Verify GD library installed
- Check file permissions

**Issue**: Products not saving
- Check WooCommerce installed
- Verify database permissions
- Check PHP error logs

**Issue**: AJAX requests failing
- Check nonce in browser console
- Verify user has `manage_woocommerce` capability
- Check WordPress AJAX endpoint

## Conclusion

This plugin follows WordPress and WooCommerce best practices:

- ✅ Object-oriented PHP architecture
- ✅ Separation of concerns (MVC-like pattern)
- ✅ Security-first approach (nonces, capabilities, sanitization)
- ✅ Extensible design (hooks, filters)
- ✅ Responsive UI
- ✅ Well-documented code
- ✅ Scalable database design

The architecture is modular, maintainable, and ready for future enhancements.
