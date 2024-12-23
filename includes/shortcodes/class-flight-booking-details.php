<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists('Flight_Booking_Details_Shortcode')) {

    class Flight_Booking_Details_Shortcode
    {
        private static $instance;

        private function __construct()
        {
            add_shortcode('gfa-hub-flight-booking-details', array($this, 'render'));
            add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        }

        public function register_scripts()
        {
            global $post;

            // Check if it's a valid WP_Post object and the specific shortcode is present
            if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'gfa-hub-flight-booking-details')) {

                // Enqueue Bootstrap styles and scripts
                wp_enqueue_style('bootstrap-style', GFA_HUB_FLIGHTS_ASSETS_CSS . 'bootstrap.min.css', array(), null);
                wp_enqueue_script('bootstrap-script', GFA_HUB_FLIGHTS_ASSETS_JS . 'bootstrap.min.js', array('jquery'), null, true);

                // Enqueue custom flights list styles and scripts
                wp_enqueue_style('flight-details-style', GFA_HUB_FLIGHTS_ASSETS_CSS . 'flight-details.css', array('select2', 'bootstrap-style'), '1.0.0');
                wp_enqueue_script('flight-details-script', GFA_HUB_FLIGHTS_ASSETS_JS . 'flight-details.js', array('jquery', 'select2'), '2.0.0', true);

                // Localize script with AJAX URL
                wp_localize_script('flight-details-script', 'flights_details', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                ));
            }
        }

        public function render()
        {
            $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
            $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

            if (!$order_id) {
                return __('Not Found', TEXT_DOMAIN);
            }

            $order = wc_get_order($order_id);

            if (!$order) {
                return __('Not Found.', TEXT_DOMAIN);
            }

            // Check ownership for logged-in users
            if (is_user_logged_in() && $order->get_user_id() === get_current_user_id()) {
                // The logged-in user is the owner
                $can_view = true;
            }
            // Check token for guest users
            elseif (!$order->get_user_id() && $token === get_post_meta($order->get_id(), 'order_guest_token', true)) {
                // Valid token matches for the guest order
                $can_view = true;
            } else {
                return __('You are not authorized to view this page.', TEXT_DOMAIN);
            }

            if ($can_view) {
                try {
                    // get the booking details using the api
                    $booking_id = get_post_meta($order_id, 'flight_orderId', true);
                    if ($booking_id) {
                        $api = Flights_API::get_instance();
                        $response = $api->get_booking($booking_id);
                        if ($response['code'] == 200) {
                            $flight = $response['data']['flights'][0];
                            $ticket_status = $flight['currentStatus'];
                            $currency = $flight['currency'];
                            $seg_groups = $flight['segGroups'];
                            $passengers = $flight['passengers'];
                            $flight_fares = $flight['flightFares'];
                            $baggages = $flight['baggages'];
                            $total_price = $order->get_total();
                            $passenger_counts = array(
                                "ADT" => $flight['adultCount'] ?? 0,
                                "CHD" => $flight['childCount'] ?? 0,
                                "INF" => $flight['infantCount'] ?? 0,
                            );

                            $ticket_time_limit = $flight['tktTimeLimit'] ?? null;
                            $current_ticket_time_limit = get_post_meta($order_id, 'ticket_time_limit', true);

                            if ($ticket_time_limit && (!$current_ticket_time_limit || $current_ticket_time_limit !== $ticket_time_limit)) {
                                update_post_meta($order_id, 'ticket_time_limit', $ticket_time_limit);
                            }
                        } else {
                            return __('Not Found', TEXT_DOMAIN);
                        }
                    } else {
                        return __('Fot Found', TEXT_DOMAIN);
                    }
                } catch (Exception $e) {
                    return __("Not Found", TEXT_DOMAIN);
                }

                ob_start();
                include GFA_HUB_FLIGHTS_TEMPLATES_FRONTEND . '/flight-booking-details.php';
                return ob_get_clean();
            }
        }

        /**
         * Flight_Booking_Details_Shortcode instance
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

    Flight_Booking_Details_Shortcode::get_instance();
}
