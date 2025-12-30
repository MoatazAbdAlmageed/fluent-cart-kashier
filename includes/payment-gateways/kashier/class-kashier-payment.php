<?php

namespace FluentCartKashier\Includes\PaymentGateways\Kashier;

use FluentCart\App\Modules\PaymentMethods\Core\AbstractPaymentGateway;
use FluentCart\App\Models\OrderTransaction;
use FluentCart\App\Models\Order;
use FluentCart\App\Helpers\StatusHelper;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Kashier Payment Gateway Class
 */
class Kashier_Payment extends AbstractPaymentGateway
{

    public function __construct()
    {
        $settings = new Kashier_Settings();
        // Debugging the fatal error where string is passed instead of object
        if (!is_object($settings)) {
            error_log('Kashier Fatal Error Debug: Kashier_Settings instantiation failed or returned non-object. Type: ' . gettype($settings));
        }
        parent::__construct($settings);
        $this->supportedFeatures = ['payment', 'webhook'];

        add_action('fluent_cart/receipt/thank_you/before_order_items', [$this, 'thank_you_content'], 10);
        add_action('fluent_cart/thankyou_content', [$this, 'thank_you_content'], 10);

        // Shortcode for manual placement
        add_shortcode('kashier_payment_details', [$this, 'thank_you_content_shortcode']);

        // Debugging: Log to console on footer
        add_action('wp_footer', [$this, 'footer_debug']);
    }

    public function footer_debug()
    {
        if (isset($_GET['fluent_cart_order_received'])) {
            echo '<script>console.log("Kashier: Order Received Page Loaded");</script>';
        }
    }

    public function thank_you_content_shortcode($atts)
    {
        ob_start();
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if ($order_id) {
            $order = Order::find($order_id);
            if ($order) {
                $this->thank_you_content($order);
            }
        }
        return ob_get_clean();
    }

    /**
     * Thank You Page Content
     * @param array|object $args
     */
    public function thank_you_content($args)
    {
        error_log('Kashier: thank_you_content hook fired.');

        if (is_object($args)) {
            $order = $args;
        } elseif (is_array($args) && isset($args['order'])) {
            $order = $args['order'];
        } else {
            error_log('Kashier: thank_you_content - No order found in args.');
            return;
        }

        if ($order->payment_method != 'kashier') {
            return;
        }

        $transaction = OrderTransaction::where('order_id', $order->id)
            ->where('payment_method', 'kashier')
            ->orderBy('id', 'DESC')
            ->first();

        if (!$transaction) {
            error_log('Kashier: thank_you_content - No transaction found.');
            return;
        }

?>
        <div class="kashier-thank-you-details" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
            <h3><?php _e('Payment Details', 'fluent-cart-kashier'); ?></h3>
            <p><strong><?php _e('Payment Method:', 'fluent-cart-kashier'); ?></strong> Kashier</p>
            <p><strong><?php _e('Transaction ID:', 'fluent-cart-kashier'); ?></strong> <?php echo esc_html($transaction->vendor_charge_id); ?></p>
            <p><strong><?php _e('Amount:', 'fluent-cart-kashier'); ?></strong> <?php echo esc_html($order->currency . ' ' . $transaction->total); ?></p>
        </div>
<?php
    }

    /**
     * Get Gateway Metadata
     * @return array
     */
    public function meta(): array
    {
        return [
            'title'       => 'Kashier',
            'status'       => 'active',
            'description' => 'Pay securely with Credit Card via Kashier.',
            'icon'        => FLUENT_CART_KASHIER_URL . 'assets/images/kashier.png',
            'logo'        => FLUENT_CART_KASHIER_URL . 'assets/images/kashier.png',
            'route'       => 'kashier',
            'slug'        => 'kashier',
            'settings_key' => 'fluent_cart_payment_settings_kashier',
            'brand_color' => '#D81B60'
        ];
    }

    /**
     * Get Gateway Settings Fields
     * @return array
     */
    public function fields(): array
    {
        return $this->getSettings()->fields();
    }



    /**
     * Process Payment
     * @param \FluentCart\App\Services\Payments\PaymentInstance $paymentInstance
     * @return array
     */
    public function makePaymentFromPaymentInstance(\FluentCart\App\Services\Payments\PaymentInstance $paymentInstance): array
    {
        $settings = $this->getSettings();
        $mode = $settings->get('payment_mode', 'test');

        $merchant_id = $settings->get('merchant_id');
        $payment_api_key = $settings->get('iframe_api_key');

        $order = $paymentInstance->order;

        $amount = $order->total_amount / 100;
        $currency = $order->currency;
        $order_id = $order->id;

        // Ensure amount is formatted correctly
        if (empty($amount)) {
            error_log('Kashier Error: Order total is empty.');
        }

        $mid = $merchant_id;
        $oid = $order_id . '_' . time();
        $amt = $amount;
        $cur = $currency;

        $hash = $this->generate_kashier_hash($mid, $oid, $amt, $cur, $mode, $payment_api_key);

        // Create Pending Transaction
        OrderTransaction::create([
            'order_id'         => $order->id,
            'payment_method'   => 'kashier',
            // 'charge_id'        => '', // Column does not exist
            'vendor_charge_id' => $oid,
            'amount'           => $amount,
            'total'            => $amount,
            'status'           => 'pending',
            'payment_mode'     => $mode,
            'created_at'       => current_time('mysql'),
            'updated_at'       => current_time('mysql')
        ]);

        $baseUrl = ($mode === 'live') ? 'https://api.kashier.io/v3/payment/sessions' : 'https://test-api.kashier.io/v3/payment/sessions';

        $payload = [
            'merchantId' => $mid,
            'merchantOrderId' => $oid,
            'amount' =>  (string)$amt,
            'currency' => $cur,
            'merchantRedirect' => site_url('?fluent_cart_payment_return=1&order_id=' . $order->id . '&payment_method=kashier'),
            'serverWebhook' => site_url('?fluent_cart_payment_listener=kashier'),
            'allowedMethods' => 'card',
            'defaultMethod' => 'card',
            'brandColor' => '#000000',
            'display' => 'en',
            'customer' => [
                'name' => $order->billing_full_name ?? 'Customer',
                'email' => $order->billing_email ?? '',
                'reference' => (string)$hash,
            ],
            'maxFailureAttempts' => 3,
            'paymentType'     => 'credit',
            'type'            => 'one-time',
            'allowedMethods'  => 'card,wallet',
            'display'         => 'en',
        ];

        // Use the Secret API Key for Basic Auth
        $secretKey = trim($settings->get('api_key'));
        $auth = trim($settings->get('iframe_api_key'));
        $mid = trim($mid);
        // $auth = base64_encode($mid . ':' . $secretKey);

        // DEBUG: Log Request
        error_log('Kashier Request URL: ' . $baseUrl);
        error_log('secretKey ' . $secretKey);
        error_log('auth ' . $auth);
        error_log('Kashier Payload: ' . print_r($payload, true));
        error_log('Kashier order: ' . print_r($order, true));
        error_log('Kashier amount: ' . print_r($amount, true));

        $response = wp_remote_post($baseUrl, [
            'headers' => [
                'Authorization' => $secretKey,
                'api-key'       => $auth,
                'Content-Type'  => 'application/json',
            ],
            'body'    => json_encode($payload),
            'timeout' => 45,
        ]);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['sessionUrl'])) {
            // Log error for debugging
            error_log('Kashier API Error: ' . print_r($data, true));
            throw new \Exception(__('Payment session creation failed.', 'fluent-cart-kashier'));
        }

        return [
            'status'   => 'success',
            'message'     => __('Payment verified successfully.', 'kashier-for-fluent-cart'),
            'redirect_to' => $data['sessionUrl'],
        ];
    }

    /**
     * Handle IPN and Return
     * @return void
     */
    public function handleIPN()
    {
        // Handle Return from Payment (Redirect)
        if (isset($_GET['fluent_cart_payment_return']) && $_GET['fluent_cart_payment_return'] == 1 && isset($_GET['payment_method']) && $_GET['payment_method'] == 'kashier') {
            error_log('Kashier: handleIPN triggered for return. GET: ' . print_r($_GET, true));
            $this->handleReturn();
            return;
        }

        // Handle Webhook (IPN)
        $data = $_GET;
        array_walk($data, 'sanitize_text_field');

        if (empty($data['signature']) || empty($data['merchantOrderId'])) {
            return;
        }

        $this->processIPN($data);
    }

    private function handleReturn()
    {
        error_log('Kashier: handleReturn started.');
        $data = $_GET;
        $order_id = intval($data['order_id']);
        $order = Order::find($order_id);

        if (!$order) {
            error_log('Kashier Error: Order not found in handleReturn. ID: ' . $order_id);
            return;
        }

        error_log('Kashier: Order found. ID: ' . $order->id);

        if (isset($data['paymentStatus']) && $data['paymentStatus'] === 'SUCCESS') {
            error_log('Kashier: Payment Status is SUCCESS.');
            // Find Transaction
            $transaction = OrderTransaction::where('order_id', $order->id)
                ->where('payment_method', 'kashier')
                ->orderBy('id', 'DESC')
                ->first();

            if ($transaction) {
                error_log('Kashier: Transaction found. ID: ' . $transaction->id);
                $transaction->status = 'succeeded';
                // $transaction->charge_id = $data['transactionId'] ?? ''; // Column does not exist
                $transaction->save();
                error_log('Kashier: Transaction updated to succeeded. Transaction ID: ' . ($data['transactionId'] ?? 'N/A'));

                try {
                    // Sync Order Status
                    (new StatusHelper($order))->syncOrderStatuses($transaction);
                    error_log('Kashier: Order status synced via StatusHelper.');
                } catch (\Exception $e) {
                    error_log('Kashier Warning: StatusHelper failed: ' . $e->getMessage());
                }
            } else {
                error_log('Kashier: Transaction NOT found. Creating new transaction.');
                // Fallback if transaction not found - Create one
                $transaction = OrderTransaction::create([
                    'order_id'         => $order->id,
                    'payment_method'   => 'kashier',
                    'vendor_charge_id' => $data['merchantOrderId'] ?? '',
                    'amount'           => $data['amount'] / 100 ?? $order->total_amount,
                    'total'            => $data['amount'] / 100 ?? $order->total_amount,
                    'status'           => 'succeeded',
                    'payment_mode'     => $data['mode'] ?? 'test',
                    'created_at'       => current_time('mysql'),
                    'updated_at'       => current_time('mysql')
                ]);
                error_log('Kashier: New transaction created. ID: ' . $transaction->id);

                $order->status = 'completed';
                $order->payment_status = 'paid';
                $order->payment_method = 'kashier';
                $order->save();
                error_log('Kashier: Order manually updated to completed/paid.');
            }

            try {
                do_action('fluent_cart_payment_completed', 'kashier', $order, $transaction);
                error_log('Kashier: fluent_cart_payment_completed action fired.');
            } catch (\Exception $e) {
                error_log('Kashier Warning: fluent_cart_payment_completed action failed: ' . $e->getMessage());
            }

            // Redirect to Order Received Page
            // Redirect to Order Received Page
            $returnUrl = '';
            try {
                if (method_exists($order, 'getThankyouPageUrl')) {
                    $returnUrl = $order->getThankyouPageUrl();
                }
            } catch (\Exception $e) {
                error_log('Kashier Warning: Could not get thank you page URL: ' . $e->getMessage());
            }

            if (empty($returnUrl)) {
                // Redirect to Purchase History
                $returnUrl = site_url('/account/purchase-history/');
                error_log('Kashier: Redirecting to Purchase History: ' . $returnUrl);
            }

            error_log('Kashier: Redirecting to ' . $returnUrl);

            if (!headers_sent()) {
                wp_redirect($returnUrl);
            } else {
                echo "<script>window.location.href = '" . $returnUrl . "';</script>";
                echo "Redirecting to " . $returnUrl . "...";
            }
            exit;
        } else {
            error_log('Kashier: Payment Status is NOT SUCCESS. Status: ' . ($data['paymentStatus'] ?? 'Unknown'));
            // Redirect to Checkout with error
            $checkoutUrl = fluent_cart_get_checkout_url();
            wp_redirect(add_query_arg(['payment_error' => 'failed'], $checkoutUrl));
            exit;
        }
    }

    private function processIPN($data)
    {
        $settings = $this->getSettings();
        $secret = $settings->get('iframe_api_key');

        // Validate Signature
        if (! $this->validate_signature($data, $secret)) {
            error_log('Kashier IPN Error: Invalid Signature');
            exit('Invalid Signature');
        }

        // Get transaction based on merchantOrderId (vendor_charge_id)
        $transaction = OrderTransaction::where('payment_method', 'kashier')
            ->where('vendor_charge_id', $data['merchantOrderId'])
            ->orderBy('id', 'DESC')
            ->first();

        if (! $transaction) {
            error_log('Kashier IPN Error: Transaction not found for ' . $data['merchantOrderId']);
            exit('Transaction not found');
        }

        // Check if the transaction is to be processed or not
        if ($transaction->status !== 'pending') {
            error_log('Kashier IPN Warning: Transaction already processed. ID: ' . $transaction->id);
            exit('Transaction already processed');
        }

        $order = $transaction->order;
        if (!$order) {
            error_log('Kashier IPN Error: Order not found for transaction ' . $transaction->id);
            exit('Order not found');
        }

        if ($data['paymentStatus'] === 'SUCCESS') {
            $transaction->status = 'succeeded';
            // $transaction->charge_id = $data['transactionId'] ?? '';
            $transaction->save();

            // $order->transaction_id = $data['transactionId'] ?? '';
            // $order->save();

            (new StatusHelper($order))->syncOrderStatuses($transaction);

            do_action('fluent_cart_payment_completed', 'kashier', $order, $transaction);
            exit('Success');
        } else {
            $transaction->status = 'failed';
            $transaction->save();
            (new StatusHelper($order))->syncOrderStatuses($transaction);
            exit('Payment Failed');
        }
    }

    /**
     * Get Order Info
     * @param array $data
     * @return array
     */
    public function getOrderInfo(array $data): array
    {
        return [
            'transaction_id' => $data['transactionId'] ?? '',
            'amount'         => $data['amount'] / 100 ?? 0,
            'currency'       => $data['currency'] ?? 'EGP',
            'status'         => $data['paymentStatus'] ?? 'pending',
        ];
    }

    /**
     * Generate Kashier Hash.
     * 
     * @param string $mid Merchant ID
     * @param string $oid Order ID
     * @param string $amt Amount
     * @param string $cur Currency
     * @param string $mode Mode
     * @param string $secret Payment API Key
     * @return string
     */
    private function generate_kashier_hash($mid, $oid, $amt, $cur, $mode, $secret)
    {
        $path = "/?merchantId={$mid}&orderId={$oid}&amount={$amt}&currency={$cur}&mode={$mode}";
        return hash_hmac('sha256', $path, $secret);
    }

    /**
     * Validate Signature.
     * 
     * @param array $query
     * @param string $secret
     * @return bool
     */
    private function validate_signature($query, $secret)
    {
        $queryString = "";
        foreach ($query as $key => $value) {
            if ($key === "signature" || $key === "mode") {
                continue;
            }
            $queryString .= "&" . $key . "=" . $value;
        }
        $queryString = ltrim($queryString, '&');
        $signature = hash_hmac('sha256', $queryString, $secret, false);
        return hash_equals($signature, $query['signature']);
    }
}
