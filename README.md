# FluentCart Kashier Payment Gateway

Accept payments via Kashier in FluentCart. This plugin integrates the Kashier payment gateway with FluentCart, allowing you to securely accept credit card payments on your WordPress site.

## Features

*   **Secure Payments:** Process credit card payments securely through Kashier's payment gateway.
*   **Test & Live Modes:** Easily switch between test and live environments for development and production.
*   **Webhook/IPN Support:** Automatically updates order status via Kashier's Instant Payment Notification (IPN) / Webhooks.
*   **Order Synchronization:** Syncs transaction status with FluentCart orders (e.g., marks orders as "Paid" and "Completed" upon successful payment).
*   **Custom Thank You Page:** Displays payment details (Transaction ID, Amount) on the order received page.

## Requirements

*   WordPress 6.0 or higher
*   PHP 7.4 or higher
*   [FluentCart](https://fluentcart.com/) plugin installed and active
*   A Kashier Merchant Account

## Installation

1.  Download the plugin zip file.
2.  Go to your WordPress Dashboard -> Plugins -> Add New.
3.  Click **Upload Plugin** and select the zip file.
4.  Click **Install Now** and then **Activate**.
5.  Ensure that **FluentCart** is also installed and active.

## Configuration

1.  Go to **FluentCart** -> **Settings** -> **Payment Settings**.
2.  Enable the **Kashier** payment gateway.
3.  Click on **Kashier** to configure the settings.
4.  Enter your Kashier credentials:
    *   **Merchant ID:** Your Kashier Merchant ID.
    *   **Iframe API Key:** Your Kashier Payment API Key (often used for the iframe/checkout).
    *   **API Key:** Your Kashier Secret API Key (used for backend authentication).
5.  Select the **Payment Mode**:
    *   **Test:** For testing purposes (uses `test-api.kashier.io`).
    *   **Live:** For processing real payments (uses `api.kashier.io`).
6.  Save changes.

## Webhook Setup (Optional but Recommended)

To ensure your orders are updated even if the user closes the browser before being redirected back, configure the webhook in your Kashier dashboard:

1.  Log in to your Kashier Dashboard.
2.  Navigate to the Webhook/Notification settings.
3.  Set the **Webhook URL** to: `https://your-site.com/?fluent_cart_payment_listener=kashier`
    *(Replace `https://your-site.com` with your actual website URL)*

## License

This plugin is licensed under the GPLv3.
