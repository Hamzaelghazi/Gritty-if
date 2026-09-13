=== Nasm Order Tracking Pro ===
Contributors: nasminc
Tags: woocommerce, order tracking, shipping, tracking
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Custom, carrier-independent order tracking for WooCommerce stores that fulfill manually. Built for nasminc.com.

== Description ==

Nasm Order Tracking Pro replaces carrier-API tracking with a fully manual, admin-controlled tracking system:

* **Automatic WooCommerce sync** — new orders are turned into tracking records on their own, so you never have to add them by hand.
* Admins create and update tracking records from a dedicated "Nasm Tracking" dashboard.
* Customers look up their order using **Order Number + Last Name** on a branded `[nasm_order_tracking]` page.
* An Amazon-style checkpoint timeline shows shipment progress, with full status history retained forever.
* Optional branded email notifications on every status change.
* CSV import/export, activity logging, and a searchable/filterable orders table.

Data lives in dedicated custom database tables (not WooCommerce order meta) for performance at scale.

== Installation ==

1. Upload the `nasm-order-tracking-pro` folder to `/wp-content/plugins/`.
2. Activate the plugin. This creates the required database tables, seeds default statuses, and creates a "Track Your Order" page containing the `[nasm_order_tracking]` shortcode.
3. Go to **Nasm Tracking → Settings** and confirm your company info, warehouse address, and branding.
4. Go to **Nasm Tracking → Add Tracking** to create your first tracking record.

== Frequently Asked Questions ==

= Do I have to create every order by hand? =
No. Go to **Nasm Tracking → WooCommerce Sync** and turn on "Automatically create a tracking record when a WooCommerce order comes in". From then on, every new WooCommerce order that reaches one of the statuses you selected (Processing, On hold, Completed by default) gets its own tracking record automatically — order number, customer name, email, shipping address, and order date are filled in for you. You only add the tracking number and carrier when you ship. To bring in orders placed before you turned sync on, use the "Import Existing Orders Now" button on the same screen.

= Will the tracking status follow the WooCommerce status? =
If you leave "Update the tracking status when the WooCommerce order status changes" enabled, yes. You control exactly how each WooCommerce status maps to a tracking status on the WooCommerce Sync screen. You can still change any tracking status manually at any time.

= Does this integrate with FedEx/UPS/USPS/DHL? =
Not in this version — tracking numbers and carriers are entered manually by your team. The codebase is structured so carrier API integrations can be added later without a rewrite.

= Where is order data stored? =
In dedicated tables (`wp_nasm_orders`, `wp_nasm_order_status_history`, `wp_nasm_statuses`, `wp_nasm_activity_log`), not WooCommerce post meta.

= Does uninstalling the plugin delete my data? =
Only if you check "Delete all tracking data when this plugin is uninstalled" under Settings. Deactivating never deletes data.

== Changelog ==

= 1.1.0 =
* New: Automatic WooCommerce order syncing. New orders become tracking records automatically, with no manual entry.
* New: "WooCommerce Sync" admin screen to enable auto-import, choose which order statuses import, map WooCommerce statuses to tracking statuses, and optionally mirror status changes.
* New: One-click, batched "Import Existing Orders" catch-up for orders placed before syncing was turned on.
* Sync respects the original design: tracking data stays in the plugin's own tables and is matched to WooCommerce orders by order number, so imports are never duplicated.

= 1.0.0 =
* Initial release.
