# Eko Plastics Test Task

WordPress/WooCommerce test task implementation for the Eko Plastics demo site.

Demo URL is provided separately with the GitHub repository link.

## Demo

URL: [https://emanon-sandbox.ct.ws/](https://emanon-sandbox.ct.ws/)

## Completed Tasks

### 1. Frontend та CMS

URLs: [Home](https://emanon-sandbox.ct.ws/) + [Contact](https://emanon-sandbox.ct.ws/contact/)

Implemented a custom WordPress theme based on the provided Figma layout.

What was done:

- Pixel-focused page layout with the Inter font.
- Responsive header and footer.
- Mobile burger menu with open/close animation.
- Hover scaling for cards.
- Hover effect for placeholder links with text `Page`: smooth text shadow without underline.
- Flexible content template using ACF Flexible Content.
- Flexible section templates are loaded from:

```text
wp-content/themes/eko-plastics/template-parts/flexible/
```

The main flexible page template is:

```text
wp-content/themes/eko-plastics/templates/template-flexible.php
```

### 3. API and system integration

URLs: [Form](https://emanon-sandbox.ct.ws/form-test/) + [Settings](https://emanon-sandbox.ct.ws/wp-admin/options-general.php?page=eko-plastics-crm-integrations)

Implemented a separate validation form page template with CRM integrations.

Form template:

```text
wp-content/themes/eko-plastics/templates/template-validation-form.php
```

Frontend phone mask:

```text
wp-content/themes/eko-plastics/assets/js/validation-form.js
```

CRM integration code:

```text
wp-content/themes/eko-plastics/inc/crm-integrations.php
```

What was done:

- Custom form with `Name` and `Phone` fields.
- Server-side validation:
  - name allows only letters, spaces, apostrophes, and hyphens;
  - phone is normalized to `+380XXXXXXXXX`;
  - phone validates Ukrainian mobile operator codes.
- Client-side Ukrainian phone mask.
- Spam protection without captcha:
  - WordPress nonce;
  - hidden honeypot field;
  - minimum submit time;
  - simple IP-based rate limit.
- Data is sent to SalesDrive and creates a lead/order.
- Contact data is sent further to Dilovod as a client in the selected client category.
- SalesDrive webhook endpoint is available through WordPress REST API.
- Telegram alerts are sent when SalesDrive or Dilovod API fails during integration requests.
- Additional WP-Cron health check checks CRM API availability every 15 minutes and sends Telegram alerts when an API stops responding.

Admin settings page:

```text
Settings > CRM Integrations
```

### 4. CMS module / WooCommerce Plugin

URLs: [Checkout](https://emanon-sandbox.ct.ws/checkout/) + [Settings](https://emanon-sandbox.ct.ws/wp-admin/admin.php?page=wc-settings&tab=checkout&section=woo_mono_int&from=WCADMIN_PAYMENT_SETTINGS)

Implemented a custom WooCommerce payment gateway plugin for monobank acquiring.

Plugin path:

```text
wp-content/plugins/woo-mono-int/
```

What was done:

- Custom WooCommerce payment method: `monobank`.
- Payment gateway settings page in WooCommerce.
- Merchant token validation.
- Gateway availability validation:
  - payment method must be enabled;
  - merchant token must be set;
  - order currency must be UAH.
- monobank invoice creation through API.
- Customer redirect to monobank payment page.
- Invoice/payment data saved in WooCommerce order meta.
- Webhook endpoint for monobank callbacks.
- Webhook signature verification with monobank public key.
- Optional WooCommerce logging.
- Sanitization and escaping in settings, output, requests, and order meta.
- WooCommerce core and WordPress core were not modified.

Plugin installation:

```text
wp-content/plugins/woo-mono-int/
```

After copying the plugin folder, activate it in:

```text
Plugins > Woo Mono Int
```

Then open:

```text
WooCommerce > Settings > Payments > monobank acquiring
```

Plugin settings:

- Enable/disable payment method.
- Test mode.
- Merchant token.
- Invoice validity.
- Webhook signature verification.
- Debug log.

Test mode:

- The plugin uses monobank acquiring API endpoint:

```text
https://api.monobank.ua/api/merchant/invoice/create
```

- For testing, use a monobank test token from:

```text
https://api.monobank.ua/
```

Webhook:

- The webhook URL is shown in the gateway settings.
- It looks like:

```text
https://your-site.test/?wc-api=woo_mono_int
```

- The plugin sends this URL to monobank as `webHookUrl`.
- Webhook payloads are verified with the `X-Sign` header.
- The monobank public key is fetched from `GET /api/merchant/pubkey` and cached in WordPress option `woo_mono_int_pubkey`.

Order meta saved by the plugin:

- `_woo_mono_int_invoice_id`
- `_woo_mono_int_page_url`
- `_woo_mono_int_invoice_status`
- `_woo_mono_int_reference`
- `_woo_mono_int_rrn`
- `_woo_mono_int_approval_code`
- `_woo_mono_int_modified_date`

Logs:

- Enable `Debug log` in the gateway settings.
- Logs are available in:

```text
WooCommerce > Status > Logs
```

- Log source: `woo-mono-int`.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- WooCommerce
- Advanced Custom Fields Pro
- Gravity Forms
- SalesDrive test account
- Dilovod test account
- Telegram bot for API failure alerts
- monobank test token for WooCommerce payment testing

### Recommended Deployment Flow

1. Install a clean WordPress instance on the target hosting or local server.

2. Install and activate the All-in-One WP Migration plugin.

3. Import the provided backup file:

[Download backup](https://drive.google.com/file/d/1yqIYzsfueyNc3Ca5D-sc-o6-MZmLjl5n/view?usp=drive_link)

```text
All-in-One WP Migration > Import
```

4. After the import is complete, log in to WordPress admin with the provided credentials.

5. Check that the custom theme is active:

```text
Appearance > Themes > Eko Plastics
```

6. Upload and activate the WooCommerce payment plugin archive:

[Download archive](https://drive.google.com/file/d/1q9mc6CLsBEyehPXNMXwR8WAMY7FvdHwr/view?usp=sharing)

```text
Plugins > Add New > Upload Plugin > woo-mono-int.zip
```

7. Check that the required plugins are active:

- WooCommerce
- Advanced Custom Fields Pro
- Gravity Forms
- Woo Mono Int

8. Re-save permalinks after migration:

```text
Settings > Permalinks > Save Changes
```

### Important Notes

- API keys, passwords, and CRM account access are not stored in the GitHub repository.
- Access to the SalesDrive and Dilovod test accounts is provided separately for review.
- The demo site is hosted on InfinityFree free hosting. This hosting may block server-to-server webhooks with an anti-bot JavaScript challenge and may restrict outgoing requests to Telegram API.
- For full webhook and Telegram testing, use hosting that allows external POST requests to WordPress REST API and outgoing HTTPS requests to `api.telegram.org`.