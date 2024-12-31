<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists('Book_Flight')) {

    class Book_Flight
    {
        private static $instance;

        private function __construct()
        {
            add_action('wp_enqueue_scripts', array($this, 'register_scripts'));

            add_action('woocommerce_before_calculate_totals', array($this, 'set_custom_price_for_flight_items'));

            add_action("woocommerce_checkout_after_customer_details", array($this, 'render_passengers_details_form'));

            add_filter('woocommerce_currency', array($this, "change_flight_currency"));

            add_action("woocommerce_after_checkout_validation", array($this, 'validate_passengers_data'), 10, 2);

            add_action("woocommerce_checkout_update_order_meta", array($this, "add_flight_data_to_order_meta"), 10, 2);

            add_filter("woocommerce_payment_successful_result", array($this, "maybe_book_flight"), 10, 2);

            add_action('woocommerce_after_pay_action', array($this, 'maybe_confirm_hold_flights'));

            add_action('woocommerce_checkout_order_created', array($this, 'update_order_currency_on_creation'));

            add_action('woocommerce_before_thankyou', array($this, "render_before_thankyou"));

            add_action('init', array($this, 'schedule_ticket_time_limit_cron'));

            add_action('check_ticket_time_limit_event', array($this, 'check_ticket_time_limit'));

            // add_filter('woocommerce_get_checkout_order_received_url', array($this, "redirect_to_flight_details"), 10, 2);
        }

        public function register_scripts()
        {
            // Check if it's a valid WP_Post object and the specific shortcode is present
            if (is_checkout()) {

                // Enqueue Bootstrap styles and scripts
                wp_enqueue_style('bootstrap-style', GFA_HUB_FLIGHTS_ASSETS_CSS . 'bootstrap.min.css', array(), null);
                wp_enqueue_script('bootstrap-script', GFA_HUB_FLIGHTS_ASSETS_JS . 'bootstrap.min.js', array('jquery'), null, true);

                // Enqueue custom flights list styles and 
                wp_enqueue_style('book-flight-style', GFA_HUB_FLIGHTS_ASSETS_CSS . 'book-flight.css', array('select2', 'bootstrap-style'), '1.0.0');
                wp_enqueue_script('book-flight-script', GFA_HUB_FLIGHTS_ASSETS_JS . 'book-flight.js', array('jquery'), '1.0.0', true);

                // Localize script with AJAX URL
                wp_localize_script('book-flight-script', 'book_flight', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                ));
            }
        }

        public function change_flight_currency($currency)
        {
            // Only change the currency if we are on the checkout or cart pages
            if (is_cart() || is_checkout()) {
                $c = WC()->session->get("flight_currency");
                if (!empty($currency)) {
                    $currency = $c;
                }
            }
            return $currency;
        }

        public function render_passengers_details_form()
        {
            // Check WooCommerce cart items for flight details
            $flight_details = null;

            foreach (WC()->cart->get_cart() as $cart_item) {
                if (!empty($cart_item['flight']) && $cart_item['flight']) {
                    $flight_details = $cart_item['flight_details'] ?? null;
                    break;
                }
            }

            // If flight details exist, render passenger forms
            if ($flight_details) {
                $adtNum = $flight_details['adtNum'] ?? 0;
                $chdNum = $flight_details['chdNum'] ?? 0;
                $infNum = $flight_details['infNum'] ?? 0;

                echo '<div class="passengers-container">';
                echo '<h3>' . __("Complete Passengers Information", "GFA_HUB") . '</h3>';
                // Render adult passenger details forms
                for ($i = 1; $i <= $adtNum; $i++) {
                    $type = 'adt';
                    include GFA_HUB_FLIGHTS_TEMPLATES_FRONTEND . '/passenger-data.php';
                }

                // Render child passenger details forms
                for ($i = 1; $i <= $chdNum; $i++) {
                    $type = 'chd';
                    include GFA_HUB_FLIGHTS_TEMPLATES_FRONTEND . '/passenger-data.php';
                }

                // Render infant passenger details forms
                for ($i = 1; $i <= $infNum; $i++) {
                    $type = 'inf';
                    include GFA_HUB_FLIGHTS_TEMPLATES_FRONTEND . '/passenger-data.php';
                }
                echo '</div>';
            }
        }

        public function set_custom_price_for_flight_items($cart)
        {
            // Only adjust prices in the frontend cart (not admin/backend).
            if (is_admin() && !defined('DOING_AJAX')) {
                return;
            }

            // Loop through each cart item.
            foreach ($cart->get_cart() as $cart_item) {
                // Check for our custom flight flag.
                if (isset($cart_item['flight']) && $cart_item['flight']) {
                    // Override the price with our custom price.
                    if (isset($cart_item['flight_details'])) {
                        // $pprice = $this->get_flight_total_price($cart_item['flight_details']['fareGroup'], $cart_item['flight_details']['passengersCount']);
                        if ($cart_item['flight_details']['price']) {
                            $price = $cart_item['flight_details']['price'];
                            $is_paid_bags = $cart_item['flight_details']['isPaidBags'];
                            if ($is_paid_bags == "true") {
                                $extra_bag = $cart_item['flight_details']['selectedBags'];

                                if (!empty($extra_bag)) {
                                    $bags_total = 0;
                                    foreach ($extra_bag as $bag) {
                                        $bags_total += $bag["amount"];
                                    }
                                    $price = floatval($price) + floatval($bags_total);
                                }
                            }
                            $cart_item['data']->set_price($price);
                        }
                    }
                }
            }
        }

        public function validate_passengers_data($data, $errors)
        {
            // Retrieve the flight details from WooCommerce cart
            $flight_details = null;
            $revalidation_data = [];
            $cart_item_key_to_update = null;

            // Loop through cart items to find the flight item and prepare revalidation data
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                if (!empty($cart_item['flight']) && $cart_item['flight']) {
                    $flight_details = $cart_item['flight_details'] ?? null;
                    $revalidation_data["traceId"] = $cart_item["trace_id"];
                    $revalidation_data["purchaseIds"] = [$cart_item["purchase_id"]];
                    $cart_item_key_to_update = $cart_item_key;
                    break;
                }
            }

            if ($cart_item_key_to_update) {
                // Perform revalidation and update traceId if successful
                $this->revalidate_flight($revalidation_data, $errors);

                if (empty($errors->get_error_messages())) {

                    // Update traceId in revalidation data and cart item
                    $new_trace_id =  $revalidation_data["traceId"];

                    // Update the traceId in the cart item data so it's accessible during booking
                    if ($cart_item_key_to_update) {
                        WC()->cart->cart_contents[$cart_item_key_to_update]['trace_id'] = $new_trace_id;
                        WC()->cart->set_session(); // Save changes to the session
                    }

                    // Proceed only if flight details exist
                    if ($flight_details) {
                        $adtNum = $flight_details['adtNum'] ?? 0;
                        $chdNum = $flight_details['chdNum'] ?? 0;
                        $infNum = $flight_details['infNum'] ?? 0;

                        // Validate fields for each passenger type
                        $this->validate_passenger_fields('adt', $adtNum, $errors);
                        $this->validate_passenger_fields('chd', $chdNum, $errors);
                        $this->validate_passenger_fields('inf', $infNum, $errors);
                        $this->validate_passport_numbers($_POST, $errors);
                    }
                }
            }
        }

        public function add_flight_data_to_order_meta($order_id, $data)
        {
            foreach (WC()->cart->get_cart() as $cart_item) {
                if (!empty($cart_item['flight']) && $cart_item['flight']) {
                    $flight_details = $cart_item['flight_details'] ?? [];
                    $trace_id = $cart_item["trace_id"];
                    $purchase_id = $cart_item["purchase_id"];

                    // Initialize the passengers array
                    $flight_details["passengers"] = [];

                    $adtNum = $flight_details['adtNum'] ?? 0;
                    $chdNum = $flight_details['chdNum'] ?? 0;
                    $infNum = $flight_details['infNum'] ?? 0;
                    $extra_bags = $flight_details['selectedBags'] ?? [];

                    // Populate passenger data
                    $this->add_passenger_data($flight_details["passengers"], 'adt', $adtNum, $extra_bags);
                    $this->add_passenger_data($flight_details["passengers"], 'chd', $chdNum, $extra_bags);
                    $this->add_passenger_data($flight_details["passengers"], 'inf', $infNum, $extra_bags);

                    // Save the flight details to the order's meta data
                    update_post_meta($order_id, 'is_flight_order', true);
                    update_post_meta($order_id, 'flight_trace_id', $trace_id);
                    update_post_meta($order_id, 'flight_purchase_id', $purchase_id);
                    update_post_meta($order_id, 'flight_details', $flight_details);
                    break;
                }
            }
        }

        public function maybe_book_flight($result, $order_id)
        {
            if ($result) {
                $is_flight_order = get_post_meta($order_id, "is_flight_order", true);

                if ($is_flight_order) {
                    // Prepare booking data
                    $passengers = [];
                    $trace_id = get_post_meta($order_id, 'flight_trace_id', true);
                    $purchase_id = get_post_meta($order_id, 'flight_purchase_id', true);
                    $flight_details = get_post_meta($order_id, 'flight_details', true);
                    if (isset($flight_details['passengers'])) {
                        $passengers = $flight_details['passengers'];
                    }

                    if ($trace_id && $purchase_id && !empty($passengers)) {
                        // Attempt to book the flight
                        $data = array(
                            "traceId" => $trace_id,
                            "purchaseIds" => [$purchase_id],
                            "isHold" => true,
                            "passengers" => $passengers
                        );
                        $this->book_flight($data, $order_id);
                        $this->maybe_confirm_hold_flights($order_id);
                    }
                }
            }
            return $result;
        }

        public function maybe_confirm_hold_flights($order_id)
        {
            $is_booked = get_post_meta($order_id, 'is_flight_booked', true);
            $flight_order_id = get_post_meta($order_id, 'flight_orderId', true);
            $flight_details = get_post_meta($order_id, 'flight_details', true);

            if ($is_booked  == "1" && $flight_order_id) {
                $is_hold = isset($flight_details['isHold']) && $flight_details['isHold'] == "true";
                $hold_action = isset($flight_details['holdAction']) && $flight_details['holdAction'] == "true";
                $this->maybe_confirm_flight($is_hold, $hold_action, $flight_order_id);
            }
        }

        public function update_order_currency_on_creation($order)
        {
            $flight_details = get_post_meta($order->get_id(), "flight_details", true);
            if ($flight_details) {
                $new_currency = $flight_details['currency'];
                // Check if the new currency is different from the current currency
                if ($order->get_currency() !== $new_currency) {
                    // Update the order currency
                    $order->set_currency($new_currency);
                    // Optionally, recalculate the order to reflect the new currency
                    $order->calculate_totals();
                    // Save the order with updated currency
                    $order->save();
                }
            }
        }

        public function render_before_thankyou($order_id)
        {
            $is_flight_order = get_post_meta($order_id, "is_flight_order", true);
            if ($is_flight_order) {
                echo "<p class='flight-notice'>" . __("Please check your email to confirm your flight.", "GFA_HUB") . "</p>";
                remove_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);
            }
        }

        public function schedule_ticket_time_limit_cron()
        {
            // Check if the event is already scheduled
            if (!wp_next_scheduled('check_ticket_time_limit_event')) {
                // Schedule the cron event
                wp_schedule_event(time(), 'hourly', 'check_ticket_time_limit_event');
            }
        }

        public function check_ticket_time_limit()
        {
            global $wpdb;

            // Query orders where the ticket_time_limit has passed, and the flight is not cancelled
            $orders = $wpdb->get_results("
                SELECT post_id
                FROM {$wpdb->postmeta}
                WHERE meta_key = 'ticket_time_limit'
                AND meta_value < NOW()
                AND post_id IN (
                    SELECT post_id FROM {$wpdb->postmeta}
                    WHERE meta_key = 'flight_status' AND meta_value != 'cancelled'
                )
            ");

            foreach ($orders as $order) {
                $order_id = $order->post_id;
                $this->process_refund($order_id, "Ticket limit time is reached, ticket cancelled automatically");
                update_post_meta($order_id, 'flight_status', 'cancelled');
            }
        }

        public function redirect_to_flight_details($url, $order)
        {
            $is_flight_order = get_post_meta($order->get_id(), '', true);
            if ($is_flight_order) {
                $guest_token = get_post_meta($order->get_id(), 'order_guest_token', true);
                $url = esc_url(home_url('booking-details') . '/?order_id=' . $order->get_id() . (!empty($guest_token) ? '&token=' . $guest_token : ''));
            }
            return $url;
        }

        private function revalidate_flight(&$data, $errors)
        {
            try {
                $api = Flights_API::get_instance();
                $response = $api->flight_revalidation($data);
                if ($response['code'] == 200) {
                    // error_log(json_encode($response));
                    $data["traceId"] = $response["data"]["traceId"];
                } else {
                    // Other errors - Raise an exception with a user-friendly message
                    $errors->add("flight-revalidation-error", __('Flight you about to book is now unavailable.', "GFA_HUB"));
                }
            } catch (Exception $e) {
                $errors->add("flight-revalidation-error", __('Flight you about to book is now unavailable.', "GFA_HUB"));
            }
        }

        private function book_flight($data, $order_id)
        {
            try {
                $api = Flights_API::get_instance();
                $response = $api->book_flight($data);

                if ($response["code"] != 200) {
                    error_log(json_encode($response));
                    $this->process_refund($order_id, 'Flight booking failed. Refunding the order.');
                } else {
                    error_log(json_encode($response));
                    // success case
                    if (isset($response["data"]["orderId"])) {
                        $flight_order_id = $response["data"]["orderId"];
                        update_post_meta($order_id, "flight_orderId", $flight_order_id);
                        update_post_meta($order_id, "is_flight_booked", true);
                        // guest user
                        if (!is_user_logged_in()) {
                            $guest_token = wp_generate_uuid4();
                            update_post_meta($order_id, "order_guest_token", $guest_token);
                        }
                        // send email with the flight details and link to access it.
                        $flight_details = get_post_meta($order_id, 'flight_details', true);

                        $order = wc_get_order($order_id);
                        $data = [
                            'order' => $order,
                            'isHold' => isset($flight_details["holdAction"]) && $flight_details["holdAction"] == "true"
                        ];
                        $emails = Omdr_Email_Manager::get_instance();
                        $subject = $data["isHold"] ? __('Booking Confirmation', "GFA_HUB") : __('Flight Booked', "GFA_HUB");
                        $emails->send(
                            $order->get_billing_email(),
                            $subject,
                            'customer-booked-flight',
                            $data
                        );
                    } else {
                        error_log("orderrefid not returned form the response");
                        error_log(json_encode($response));
                        $this->process_refund($order_id, 'Flight booking failed. Refunding the order.');
                    }
                }
            } catch (Exception $e) {
                // Log exception details and attempt refund
                error_log("Flight booking error for order #{$order_id}: " . $e->getMessage());
                $this->process_refund($order_id, 'An error occurred during flight booking. Refunding the order.');
            }
        }

        private function process_refund($order_id, $reason)
        {
            $order = wc_get_order($order_id);

            // Ensure the order exists and has been paid for
            if ($order && $order->is_paid()) {
                $refund = wc_create_refund([
                    'amount' => $order->get_total(),
                    'reason' => $reason,
                    'order_id' => $order_id,
                    'refund_payment' => true,
                ]);

                if (is_wp_error($refund)) {
                    error_log("Refund failed for order #{$order_id}: " . $refund->get_error_message());
                } else {
                    error_log("Refund successful for order #{$order_id}. Reason: $reason");
                }
            }
        }

        private function validate_passenger_fields($type, $num, $errors)
        {
            for ($i = 1; $i <= $num; $i++) {

                if (empty($_POST["passenger_title_{$type}_{$i}"])) {
                    $errors->add("passenger_title_{$type}_{$i}", __('Title is required for all passengers.', "GFA_HUB"));
                }

                if (empty($_POST["passenger_first_name_{$type}_{$i}"])) {
                    $errors->add("passenger_first_name_{$type}_{$i}", __('First Name is required for all passengers.', "GFA_HUB"));
                }

                if (empty($_POST["passenger_last_name_{$type}_{$i}"])) {
                    $errors->add("passenger_last_name_{$type}_{$i}", __('Last Name is required for all passengers.', "GFA_HUB"));
                }

                if (empty($_POST["passenger_email_{$type}_{$i}"]) || !filter_var($_POST["passenger_email_{$type}_{$i}"], FILTER_VALIDATE_EMAIL)) {
                    $errors->add("passenger_email_{$type}_{$i}", __('A valid Email is required for all passengers.', "GFA_HUB"));
                }

                if (empty($_POST["passenger_gender_{$type}_{$i}"])) {
                    $errors->add("passenger_gender_{$type}_{$i}", __('Gender is required for all passengers.', "GFA_HUB"));
                }

                if (empty($_POST["passenger_dob_{$type}_{$i}"])) {
                    $errors->add("passenger_dob_{$type}_{$i}", __('Birth Date is required for all passengers.', "GFA_HUB"));
                }

                if (empty($_POST["passenger_pax_{$type}_{$i}"])) {
                    $errors->add("passenger_pax_{$type}_{$i}", __('Pax type is required for all passengers.', "GFA_HUB"));
                }

                if (empty($_POST["passenger_area_code_{$type}_{$i}"])) {
                    $errors->add("passenger_area_code_{$type}_{$i}", __('Area Code type is required for all passengers.', "GFA_HUB"));
                }

                if (empty($_POST["passenger_mobile_{$type}_{$i}"])) {
                    $errors->add("passenger_mobile_{$type}_{$i}", __('Mobile is required for all passengers.', "GFA_HUB"));
                }

                if (empty($_POST["passenger_nationality_{$type}_{$i}"])) {
                    $errors->add("passenger_nationality_{$type}_{$i}", __('Nationality is required for all passengers.', "GFA_HUB"));
                }

                if (empty($_POST["passenger_passport_number_{$type}_{$i}"])) {
                    $errors->add("passenger_passport_number_{$type}_{$i}", __('Passport Number is required for all passengers.', "GFA_HUB"));
                }

                if (empty($_POST["passenger_passport_doi_{$type}_{$i}"])) {
                    $errors->add("passenger_passport_doi_{$type}_{$i}", __('Passport Issue Date is required for all passengers.', "GFA_HUB"));
                }

                if (empty($_POST["passenger_passport_doe_{$type}_{$i}"])) {
                    $errors->add("passenger_passport_doe_{$type}_{$i}", __('Passport Expiry Date is required for all passengers.', "GFA_HUB"));
                }

                if (empty($_POST["passenger_passport_ic_{$type}_{$i}"])) {
                    $errors->add("passenger_passport_ic_{$type}_{$i}", __('Passport Issue Country is required for all passengers.', "GFA_HUB"));
                }
            }
        }

        private function validate_passport_numbers($post_data, $errors)
        {
            $passport_numbers = [];

            // Collect all passport numbers across all types
            foreach ($post_data as $key => $value) {
                if (preg_match("/^passenger_passport_number_(adt|chd|inf)_(\d+)$/", $key)) {
                    $passport_numbers[] = $value;
                }
            }

            // Check for duplicates
            $unique_numbers = array_unique($passport_numbers);
            if (count($unique_numbers) < count($passport_numbers)) {
                $errors->add("passport_number_duplication", __('Duplicate passport numbers found.', "GFA_HUB"));
            }
        }

        // Helper function to add passenger data
        private function add_passenger_data(&$passenger_array, $type, $num, $selected_bags)
        {
            for ($i = 1; $i <= $num; $i++) {
                $extra_bags_id = $type . "_" . $num;
                $passenger_data = [
                    "title" => $_POST["passenger_title_{$type}_{$i}"] ?? '',
                    "firstName" => $_POST["passenger_first_name_{$type}_{$i}"] ?? '',
                    "lastName" => $_POST["passenger_last_name_{$type}_{$i}"] ?? '',
                    "email" => $_POST["passenger_email_{$type}_{$i}"] ?? '',
                    "genderType" => $_POST["passenger_gender_{$type}_{$i}"] ?? '',
                    "dob" => $_POST["passenger_dob_{$type}_{$i}"] ?? '',
                    "paxType" => $_POST["passenger_pax_{$type}_{$i}"] ?? '',
                    "areaCode" => $_POST["passenger_area_code_{$type}_{$i}"] ?? '',
                    "mobile" => $_POST["passenger_mobile_{$type}_{$i}"] ?? '',
                    "passengerNationality" => $_POST["passenger_nationality_{$type}_{$i}"] ?? '',
                    "passportNumber" => $_POST["passenger_passport_number_{$type}_{$i}"] ?? '',
                    "passportDOI" => $_POST["passenger_passport_doi_{$type}_{$i}"] ?? '',
                    "passportDOE" => $_POST["passenger_passport_doe_{$type}_{$i}"] ?? '',
                    "passportIssuedCountry" => $_POST["passenger_passport_ic_{$type}_{$i}"] ?? '',
                ];

                // add extra bags if exists
                if (isset($selected_bags[$extra_bags_id])) {
                    // unset the amount first
                    unset($selected_bags[$extra_bags_id]["amount"]);
                    $passenger_data["serviceReference"][] = $selected_bags[$extra_bags_id];
                }

                // Add each passenger's data to the main passengers array
                $passenger_array[] = $passenger_data;
            }
        }

        private function get_flight_price_details($fareGroup, $passengersCount)
        {
            $fares = $fareGroup['fares'];

            $paxTypes = array_map(function ($fare) use ($passengersCount) {
                $paxType = $fare['paxType'];
                $passengerCount = isset($passengersCount[$paxType]) ? $passengersCount[$paxType] : 0;
                $basePrice = $fare['base'];
                $taxes = array_reduce($fare['taxes'], function ($acc, $tax) {
                    return $acc + $tax['amt'];
                }, 0);

                return [
                    'type' => $paxType,
                    'num' => $passengerCount,
                    'basePrice' => $basePrice,
                    'tax' => $taxes
                ];
            }, $fares);

            return $paxTypes;
        }

        private function get_flight_total_price($fareGroup, $passengersCount)
        {
            $priceDetails = $this->get_flight_price_details($fareGroup, $passengersCount);
            $totalPrice = 0;

            foreach ($priceDetails as $detail) {
                $basePrice = $detail['basePrice'];
                $tax = $detail['tax'];
                $num = $detail['num'];
                $totalPrice += ($basePrice + $tax) * $num;
            }

            return $totalPrice;
        }

        private function maybe_confirm_flight($is_hold, $hold_action, $flight_order_id)
        {
            if ($is_hold && !$hold_action) {
                $data = array(
                    "OrderRefId" => $flight_order_id
                );
                $api = Flights_API::get_instance();
                // $api->issue_ticket($data);
            }
        }

        /**
         * Book_Flight instance
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

    Book_Flight::get_instance();
}
