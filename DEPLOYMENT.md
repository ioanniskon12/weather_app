# Deployment Guide - Connect GitHub to WordPress

This guide shows you **3 methods** to deploy your plugin from GitHub to WordPress without manually downloading/uploading every time.

---

## Method 1: GitHub Webhook (Automatic) ⚡ **RECOMMENDED**

### Setup (5 minutes):

1. **Upload `deploy.php` to your WordPress root directory**
   ```
   yoursite.com/deploy.php
   ```

2. **Edit the configuration in `deploy.php`:**
   ```php
   define('DEPLOY_SECRET', 'change-to-your-secret-password-123');
   ```

3. **Set up GitHub Webhook:**
   - Go to: https://github.com/ioanniskon12/weather_app/settings/hooks
   - Click **"Add webhook"**
   - **Payload URL**: `https://yoursite.com/deploy.php?secret=change-to-your-secret-password-123`
   - **Content type**: `application/json`
   - **Events**: Select "Just the push event"
   - Click **"Add webhook"**

4. **Test it:**
   - Make any change to your plugin code
   - Commit and push to GitHub
   - Your site will automatically update! ✨

**Benefits:**
- ✅ Automatic updates on every push
- ✅ No manual work needed
- ✅ Fast deployment (seconds)

---

## Method 2: Manual Git Pull (SSH Access Required)

### Setup:

1. **SSH into your server:**
   ```bash
   ssh user@yourserver.com
   ```

2. **Navigate to your plugin directory:**
   ```bash
   cd /path/to/wordpress/wp-content/plugins/
   ```

3. **Clone the repository:**
   ```bash
   git clone https://github.com/ioanniskon12/weather_app.git woocommerce-shop-crm
   cd woocommerce-shop-crm
   git checkout claude/wordpress-plugin-creation-011CUx8gR5uvBA93cZiAZmjk
   ```

4. **To update later, just run:**
   ```bash
   cd /path/to/wordpress/wp-content/plugins/woocommerce-shop-crm
   git pull origin claude/wordpress-plugin-creation-011CUx8gR5uvBA93cZiAZmjk
   ```

**Benefits:**
- ✅ Full control
- ✅ Can see changes before applying
- ❌ Requires SSH access

---

## Method 3: FTP Sync (Easiest, No Code)

### Using FileZilla or similar FTP client:

1. **Download your plugin from GitHub:**
   - Go to: https://github.com/ioanniskon12/weather_app
   - Switch to branch: `claude/wordpress-plugin-creation-011CUx8gR5uvBA93cZiAZmjk`
   - Click **Code → Download ZIP**
   - Extract the ZIP

2. **Connect via FTP to your WordPress site**

3. **Navigate to:**
   ```
   /wp-content/plugins/woocommerce-shop-crm/
   ```

4. **Drag and drop only the changed files** (not the whole folder)

**Benefits:**
- ✅ Easy to understand
- ✅ No server configuration needed
- ❌ Manual work required each time

---

## Method 4: Use WP Pusher Plugin (WordPress Plugin)

### Setup:

1. **Install WP Pusher:**
   - Download from: https://wppusher.com/
   - Upload to WordPress: Plugins → Add New → Upload

2. **Connect to GitHub:**
   - Go to WP Pusher → GitHub
   - Click "Obtain a GitHub token"
   - Authorize WP Pusher

3. **Install plugin from GitHub:**
   - WP Pusher → Install Plugin
   - Repository: `ioanniskon12/weather_app`
   - Branch: `claude/wordpress-plugin-creation-011CUx8gR5uvBA93cZiAZmjk`
   - Subdirectory: leave empty
   - Click "Install Plugin"

4. **Enable auto-updates:**
   - Check "Push-to-Deploy" checkbox

**Benefits:**
- ✅ WordPress admin interface
- ✅ One-click updates
- ✅ Auto-deploy on push
- ❌ Requires external plugin

---

## Troubleshooting Edit Button Issue

If your Edit button isn't working, check these:

### 1. **Clear WordPress Cache**
```php
// Add to wp-config.php temporarily
define('WP_CACHE', false);
```

### 2. **Check JavaScript Console**
- Open browser DevTools (F12)
- Click Console tab
- Click "Edit" button
- Look for errors

### 3. **Verify AJAX is working**
Open browser console and run:
```javascript
console.log(wscCRM);
```
You should see the CRM object.

### 4. **Check if jQuery is loaded**
```javascript
console.log(jQuery);
```
Should show jQuery function.

### 5. **Test the modal manually**
```javascript
jQuery('#wsc-product-modal').fadeIn(300);
```
Modal should appear.

---

## Quick Fix for Edit Button

If the edit button still doesn't work, you can:

1. **Use browser console** to test:
```javascript
jQuery('.wsc-edit-product').first().click();
```

2. **Check if the modal HTML exists:**
```javascript
jQuery('#wsc-product-modal').length
```
Should return 1.

3. **Manually trigger the edit function:**
```javascript
jQuery(document).on('click', '.wsc-edit-product', function(e) {
    e.preventDefault();
    console.log('Edit clicked!', jQuery(this).data('product-id'));
});
```

---

## After Deployment

1. **Clear WordPress cache** (if using cache plugin)
2. **Hard refresh browser** (Ctrl+Shift+R or Cmd+Shift+R)
3. **Test the Edit button** on Products page

---

## Support

If you encounter issues:

1. Check `deploy.log` file in WordPress root
2. Check browser console for JavaScript errors
3. Verify file permissions: `chmod 755` on plugin directory
4. Make sure Git is installed on your server: `git --version`

---

## Recommended: Method 1 (Webhook)

**Best workflow:**
1. Make changes to code locally or in GitHub
2. Commit and push
3. GitHub automatically deploys to your site
4. Refresh WordPress admin to see changes

**No more manual downloads or uploads!** 🎉
