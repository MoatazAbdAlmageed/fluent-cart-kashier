<?php

namespace FluentCartKashier\Includes\PaymentGateways\Kashier;

use FluentCart\App\Modules\PaymentMethods\Core\BaseGatewaySettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kashier Payment Gateway Settings Class
 */
class Kashier_Settings extends BaseGatewaySettings {

    /**
     * FluentCart Method handler.
     *
     * @var string
     */
    public $methodHandler = 'fluent_cart_payment_settings_kashier';

    /**
     * Gateway settings.
     *
     * @var array
     */
    public $settings;

    public function __construct()
    {
        parent::__construct();
        
        // Get current settings and defaults
        $settings = $this->getCachedSettings();
        $defaults = $this->getDefaults();
        
        if ( ! $settings || ! is_array( $settings ) || empty( $settings ) ) {
            $settings = $defaults;
        } else {
            $settings = wp_parse_args( $settings, $defaults );
        }
        $this->settings = $settings;
    }

    /**
     * Get Setting Value
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key = '', $default = '')
    {
        if (empty($key)) {
            return $this->settings;
        }
        return $this->settings[$key] ?? $default;
    }
    public function getMode()
    {
        return $this->get('payment_mode', 'test');
    }

    /**
     * Check if Gateway is Active
     * @return bool
     */
    public function isActive()
    {
        return $this->get('enabled') === 'yes';
    }

    /**
     * Get Default Settings
     * @return array
     */
    public function getDefaults()
    {
        return [
            'enabled'        => 'no',
            'title'          => 'Kashier',
            'description'    => 'Pay securely with Credit Card via Kashier.',
            'merchant_id'    => '',
            'api_key'        => '',
            'iframe_api_key' => '',
            'payment_mode'   => 'test',
        ];
    }

    /**
     * Get Gateway Settings Fields
     * @return array
     */
    public function fields(): array
    {
        return [
            'enabled' => [
                'type'        => 'yes-no',
                'label'       => __( 'Enable Kashier', 'fluent-cart-kashier' ),
                'description' => __( 'Enable or disable Kashier payment gateway.', 'fluent-cart-kashier' ),
                'default'     => 'no',
            ],
            'title' => [
                'type'        => 'text',
                'label'       => __( 'Title', 'fluent-cart-kashier' ),
                'description' => __( 'This controls the title which the user sees during checkout.', 'fluent-cart-kashier' ),
                'default'     => __( 'Kashier', 'fluent-cart-kashier' ),
            ],
            'description' => [
                'type'        => 'textarea',
                'label'       => __( 'Description', 'fluent-cart-kashier' ),
                'description' => __( 'This controls the description which the user sees during checkout.', 'fluent-cart-kashier' ),
                'default'     => __( 'Pay securely with Credit Card via Kashier.', 'fluent-cart-kashier' ),
            ],
            'merchant_id' => [
                'type'        => 'text',
                'label'       => __( 'Merchant ID', 'fluent-cart-kashier' ),
                'description' => __( 'Your Kashier Merchant ID.', 'fluent-cart-kashier' ),
                'required'    => true,
            ],
            'api_key' => [
                'type'        => 'password',
                'label'       => __( 'API Key (Secret)', 'fluent-cart-kashier' ),
                'description' => __( 'Your Kashier API Secret Key.', 'fluent-cart-kashier' ),
                'required'    => true,
            ],
            'iframe_api_key' => [
                'type'        => 'password',
                'label'       => __( 'Payment API Key', 'fluent-cart-kashier' ),
                'description' => __( 'Your Kashier Payment API Key (for hashing).', 'fluent-cart-kashier' ),
                'required'    => true,
            ],
            'payment_mode' => [
                'type'        => 'select',
                'label'       => __( 'Payment Mode', 'fluent-cart-kashier' ),
                'description' => __( 'Select Payment Mode.', 'fluent-cart-kashier' ),
                'options'     => [
                    [
                        'label' => 'Test Mode',
                        'value' => 'test'
                    ],
                    [
                        'label' => 'Live Mode',
                        'value' => 'live'
                    ]
                ],
                'default'     => 'test',
            ],
        ];
    }
}
