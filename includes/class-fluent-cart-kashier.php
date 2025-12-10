<?php

namespace FluentCartKashier\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Fluent_Cart_Kashier {

	/**
	 * The single instance of the class.
	 *
	 * @var Fluent_Cart_Kashier
	 */
	protected static $_instance = null;

	/**
	 * Main Fluent_Cart_Kashier Instance.
	 *
	 * Ensures only one instance of Fluent_Cart_Kashier is loaded or can be loaded.
	 *
	 * @return Fluent_Cart_Kashier - Main instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * Include required files.
	 */
	public function includes() {
        // We will load the gateway class when registering
	}

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_action( 'fluent_cart/register_payment_methods', array( $this, 'register_payment_gateway' ) );
        add_action( 'template_redirect', array( $this, 'handle_kashier_return' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

    /**
     * Enqueue Scripts
     */
    public function enqueue_scripts() {
        if ( function_exists( 'is_checkout' ) && is_checkout() || is_page() ) {
            wp_enqueue_script( 
                'fluent-cart-kashier-checkout', 
                FLUENT_CART_KASHIER_URL . 'assets/js/kashier-checkout.js', 
                array( 'jquery' ), 
                FLUENT_CART_KASHIER_VERSION, 
                true 
            );
        }
    }

	/**
	 * Register the payment gateway.
     * 
     * @param \FluentCart\App\Services\PaymentMethods $paymentMethods
	 */
	public function register_payment_gateway( $paymentMethods ) {
		require_once FLUENT_CART_KASHIER_DIR . 'includes/payment-gateways/kashier/class-kashier-settings.php';
		require_once FLUENT_CART_KASHIER_DIR . 'includes/payment-gateways/kashier/class-kashier-payment.php';
        
        if ( function_exists( 'fluent_cart_api' ) ) {
            fluent_cart_api()->registerCustomPaymentMethod( 'kashier', new \FluentCartKashier\Includes\PaymentGateways\Kashier\Kashier_Payment() );
        }
	}

    /**
     * Handle Kashier Return Redirect
     */
    public function handle_kashier_return() {
        if (isset($_GET['fluent_cart_payment_return']) && $_GET['fluent_cart_payment_return'] == 1 && isset($_GET['payment_method']) && $_GET['payment_method'] == 'kashier') {
            
            error_log('Kashier: Return URL detected in Fluent_Cart_Kashier::handle_kashier_return');

            if (!class_exists('\FluentCartKashier\Includes\PaymentGateways\Kashier\Kashier_Payment')) {
                require_once FLUENT_CART_KASHIER_DIR . 'includes/payment-gateways/kashier/class-kashier-settings.php';
                require_once FLUENT_CART_KASHIER_DIR . 'includes/payment-gateways/kashier/class-kashier-payment.php';
            }
            
            try {
                $gateway = new \FluentCartKashier\Includes\PaymentGateways\Kashier\Kashier_Payment();
                $gateway->handleIPN();
                exit; // Should be handled by handleIPN, but just in case
            } catch (\Exception $e) {
                error_log('Kashier Error in handle_kashier_return: ' . $e->getMessage());
                wp_die('Kashier Error: ' . $e->getMessage());
            }
        }
    }
}
