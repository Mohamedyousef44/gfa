<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists('Flights_Cart')) {

    class Flights_Cart
    {
        private static $instance;

        private function __construct()
        {
            add_action('wp_ajax_flights_ajax_add_to_cart', array($this, 'flights_ajax_add_to_cart'));
            add_action('wp_ajax_nopriv_flights_ajax_add_to_cart', array($this, 'flights_ajax_add_to_cart'));
            add_filter('woocommerce_add_to_cart_validation', array($this, 'prevent_adding_if_flight_in_cart'), 10, 3);
        }

        public function get_or_create_dummy_product()
        {
            $sku = 'flight-dummy-product';

            // Check if a product with this SKU already exists
            $existing_product_id = wc_get_product_id_by_sku($sku);

            if ($existing_product_id) {
                return $existing_product_id; // Product already exists, return the ID
            } else {
                // Product doesn't exist, create it
                $product = new WC_Product_Simple();
                $product->set_name('Flight Booking Product');
                $product->set_sku($sku);
                $product->set_regular_price(0.01); // Set a minimal price to make it purchasable
                $product->set_catalog_visibility('hidden'); // Hide from catalog
                $product->set_status('publish'); // Set as private if it's for internal use only
                $product->set_stock_status('instock'); // Ensure it's in stock
                $product->set_manage_stock(false); // Optionally, disable stock management for simplicity
                $product_id = $product->save();
                return $product_id; // Return the new product ID
            }
        }

        public function flights_ajax_add_to_cart()
        {
            // // Check if the cart already contains any items
            if (WC()->cart->get_cart_contents_count() > 0) {
                wp_send_json_error(['message' => __('Your cart already contains items. Please complete or clear your cart before adding a flight.', "GFA_HUB")]);
                return;
            }

            // Validate inputs.
            $trace_id = isset($_POST['trace_id']) ? sanitize_text_field($_POST['trace_id']) : '';
            $purchase_id = isset($_POST['purchase_id']) ? sanitize_text_field($_POST['purchase_id']) : '';
            $flight_details = isset($_POST['flight_details']) ? $_POST['flight_details'] : [];

            // Define custom item data.
            $cart_item_data = [
                'flight' => true, // Custom flag to identify flight items
                'trace_id' => $trace_id,
                'purchase_id' => $purchase_id,
                'flight_details' => $flight_details
            ];

            // Generate a unique ID for the cart item.
            $product_id = $this->get_or_create_dummy_product();

            // Add the custom item directly to cart_contents.
            $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);
            WC()->session->set("flight_currency", $flight_details['currency']);

            if ($cart_item_key) {
                // Return checkout URL
                wp_send_json_success(
                    [
                        'message' => 'Flight added to cart',
                        'checkout_url' => wc_get_checkout_url(),
                    ]
                );
            } else {
                wp_send_json_error(['message' => __('Could not add flight to cart', "GFA_HUB")]);
            }
        }

        function prevent_adding_if_flight_in_cart($passed, $product_id, $quantity)
        {
            // Check if the cart already contains a flight item
            $cart_contains_flight = false;

            // Iterate over the cart items to check if there's a flight product
            foreach (WC()->cart->get_cart() as $cart_item) {
                if (isset($cart_item['flight']) && $cart_item['flight']) {
                    $cart_contains_flight = true;
                    break;
                }
            }

            // If the cart contains a flight item, prevent adding any other product
            if ($cart_contains_flight) {
                // You can add a custom message or error here to inform the user
                wc_add_notice(__('You cannot add any other product to the cart while a flight is already in the cart.', "GFA_HUB"), 'error');
                return false;
            }

            // Allow the product to be added if no flight item exists in the cart
            return $passed;
        }

        /**
         * Flights_Cart instance
         *
         * @return object
         */
        public static function get_instance()
        {
            if (!isset(self::$instance) || is_null(self::$instance))
                self::$instance = new self();

            return self::$instance;
        }
    }

    Flights_Cart::get_instance();
}
