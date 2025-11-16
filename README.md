# WooCommerce Shop CRM

**✅ Automatic Deployment Active & Working!**

A comprehensive CRM plugin for WooCommerce shop owners to manage products, orders, and offers all in one centralized dashboard.

## Features

### 📦 Product Management
- Add, edit, and delete products
- Manage product details (name, description, price, SKU, stock)
- Set regular and sale prices
- Organize products with categories and tags
- Monitor low stock alerts
- Quick product overview with images

### 🛒 Order Management
- View all orders in one place
- Filter orders by status (pending, processing, completed, etc.)
- Quick order status updates
- View order details including customer information
- Order statistics and analytics
- Track total sales and order counts

### 🏷️ Offers & Discounts
- Create custom offers and discounts
- Support for percentage discounts
- Support for fixed amount discounts
- Free shipping offers
- Set minimum purchase requirements
- Schedule offers with start and end dates
- Activate/deactivate offers easily

### 📊 Dashboard
- Overview of shop statistics
- Quick stats cards for products, orders, and sales
- Recent orders at a glance
- Top selling products
- Low stock alerts
- Active offers monitoring

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Installation

### Manual Installation

1. Download the plugin files
2. Upload the entire `woocommerce-shop-crm` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Make sure WooCommerce is installed and activated

### Installation from ZIP

1. Download the plugin as a ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" button
4. Choose the ZIP file and click "Install Now"
5. After installation, click "Activate Plugin"

### Creating a ZIP File for Upload

To create a ZIP file of this plugin for uploading to WordPress:

```bash
# Navigate to the parent directory
cd /home/user

# Create a ZIP file (exclude git and development files)
zip -r woocommerce-shop-crm.zip weather_app/ \
  -x "*.git*" \
  -x "*node_modules*" \
  -x "*.DS_Store"
```

Or simply compress the `weather_app` folder into a ZIP file using your preferred compression tool, then upload it to WordPress.

## Usage

### Accessing the CRM

After activation, you'll find a new "Shop CRM" menu item in your WordPress admin sidebar with the store icon.

### Dashboard

Navigate to **Shop CRM → Dashboard** to see:
- Total products, orders, and sales
- Active offers count
- Low stock alerts
- Recent orders
- Top selling products

### Managing Products

1. Go to **Shop CRM → Products**
2. Click "Add New Product" to create a product
3. Fill in the product details:
   - Product name (required)
   - Description and short description
   - Regular price and optional sale price
   - SKU (required)
   - Stock quantity (required)
   - Categories and tags
4. Click "Save Product"

**Editing Products:**
- Click the "Edit" button next to any product
- Modify the details in the modal
- Click "Save Product"

**Deleting Products:**
- Click the "Delete" button next to any product
- Confirm the deletion

### Managing Orders

1. Go to **Shop CRM → Orders**
2. View all orders in a table format
3. Filter orders by status using the top filters
4. Change order status directly from the dropdown
5. Click "View" to see full order details in WooCommerce
6. Click "Quick View" for a fast overview (modal)

**Order Statistics:**
- View monthly sales totals
- Track order counts by status
- Monitor pending orders

### Managing Offers

1. Go to **Shop CRM → Offers & Discounts**
2. Click "Add New Offer"
3. Fill in offer details:
   - Offer name (required)
   - Discount type (percentage, fixed amount, or free shipping)
   - Discount value (required)
   - Minimum purchase amount (optional)
   - Start and end dates (optional)
   - Status (active/inactive)
4. Click "Save Offer"

**Offer Types:**
- **Percentage Discount**: e.g., 10% off
- **Fixed Amount**: e.g., $10 off
- **Free Shipping**: Offer free shipping

## File Structure

```
woocommerce-shop-crm/
├── assets/
│   ├── css/
│   │   └── admin.css          # Admin interface styles
│   └── js/
│       └── admin.js           # Admin interface scripts
├── includes/
│   ├── class-product-manager.php   # Product management logic
│   ├── class-order-manager.php     # Order management logic
│   ├── class-offer-manager.php     # Offer management logic
│   └── class-dashboard.php         # Dashboard data aggregation
├── templates/
│   ├── dashboard.php          # Dashboard template
│   ├── products.php           # Products page template
│   ├── orders.php             # Orders page template
│   └── offers.php             # Offers page template
├── woocommerce-shop-crm.php   # Main plugin file
└── README.md                   # This file
```

## Database Tables

The plugin creates the following custom table:

- `wp_wsc_offers` - Stores custom offers and discounts

All other data is stored using WordPress/WooCommerce standard tables.

## Security Features

- AJAX nonce verification for all AJAX requests
- Capability checks (requires `manage_woocommerce` permission)
- Input sanitization and validation
- SQL injection protection using prepared statements
- XSS protection using WordPress escaping functions

## Hooks and Filters

The plugin is built with WordPress best practices and can be extended using standard WordPress hooks.

## Support

For issues, questions, or feature requests:
- GitHub: [https://github.com/ioanniskon12/weather_app](https://github.com/ioanniskon12/weather_app)

## Changelog

### Version 1.0.0
- Initial release
- Product management functionality
- Order management functionality
- Offer/discount management functionality
- Dashboard with statistics
- Responsive admin interface

## Credits

Developed by: Your Name
Plugin URI: [https://github.com/ioanniskon12/weather_app](https://github.com/ioanniskon12/weather_app)

## License

GPL v2 or later

Copyright (C) 2024

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
