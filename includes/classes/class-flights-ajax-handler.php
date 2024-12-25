<?php
include 'class-flights-api.php';
class Flight_Ajax_Handler
{
    private $flights;

    public function __construct()
    {
        $this->flights = Flights_API::get_instance();

        add_action('wp_ajax_nopriv_omdr_search_flights', [$this, 'search_flights']);
        add_action('wp_ajax_omdr_search_flights', [$this, 'search_flights']);

        add_action('wp_ajax_nopriv_omdr_cancel_ticket', [$this, 'cancel_ticket']);
        add_action('wp_ajax_omdr_cancel_ticket', [$this, 'cancel_ticket']);

        add_action('wp_ajax_nopriv_omdr_issue_ticket', [$this, 'issue_ticket']);
        add_action('wp_ajax_omdr_issue_ticket', [$this, 'issue_ticket']);

        add_action('wp_ajax_nopriv_omdr_get_fares', [$this, 'get_fares']);
        add_action('wp_ajax_omdr_get_fares', [$this, 'get_fares']);

        add_action('wp_ajax_nopriv_omdr_revalidate_flights', [$this, 'revalidate_flights']);
        add_action('wp_ajax_omdr_revalidate_flights', [$this, 'revalidate_flights']);
    }

    // 1. AJAX function to search flights
    public function search_flights()
    {
        // Initial response data
        $response_data = array(
            "success" => true,
            "message" => __("Flights retrieved successfully", TEXT_DOMAIN),
        );

        try {

            $errors = $this->validate_search_data();

            // If validation errors exist, send a JSON error response
            if (!empty($errors)) {
                wp_send_json_error(
                    array(
                        "errors" => $errors
                    )
                );
                return;
            }

            // prepare orgin destinations
            $originDestinations = [];
            if ($_POST['airTravelType'] === "OneWay") {
                // One-way trip, single origin-destination set.
                $originDestinations[] = [
                    'departureDateTime' => sanitize_text_field($_POST['departureDateTime']),
                    'origin'            => sanitize_text_field($_POST['origin']),
                    'destination'       => sanitize_text_field($_POST['destination']),
                ];
            } elseif ($_POST['airTravelType'] === "RoundTrip") {
                // Round-trip, two origin-destination sets (outbound and return).
                $originDestinations[] = [
                    'departureDateTime' => sanitize_text_field($_POST['departureDateTime']),
                    'origin'            => sanitize_text_field($_POST['origin']),
                    'destination'       => sanitize_text_field($_POST['destination']),
                ];

                $originDestinations[] = [
                    'departureDateTime' => sanitize_text_field($_POST['returnDateTime']),
                    'origin'            => sanitize_text_field($_POST['destination']),
                    'destination'       => sanitize_text_field($_POST['origin']),
                ];
            }
            // Prepare sanitized data for the API
            $data = [
                'originDestinations'  =>  $originDestinations,
                'airTravelType'       => sanitize_text_field($_POST['airTravelType']),
                'adultCount'          => intval($_POST['adultCount']),
                'childCount'          => intval($_POST['childCount']) ?? 0,
                'infantCount'         => intval($_POST['infantCount']) ?? 0,
                'cabinClass'          => sanitize_text_field($_POST['cabinClass']),
                'cabinPreferenceType' => "Restricted",
                "includeBaggage"      => true,
                "includeMiniRules"    => true,
                "isBrandFareEnabled"  => true
            ];

            // Send the sanitized data to the flights API
            $response = $this->flights->search_flights($data);

            // Handle different API responses based on status code
            if ($response['code'] == 200) {
                // Success - Add flight data to response
                $response_data['data'] = $response['data'];
            } elseif ($response['code'] == 400) {
                // Validation errors from API - Return these to the user
                wp_send_json_error([
                    'success' => false,
                    'message' => __("Validation error(s) from API.", TEXT_DOMAIN),
                    'errors'  => $response['data']
                ]);
                return;
            } else {
                // Other errors - Raise an exception with a user-friendly message
                throw new Exception(__("No flights found. Please try again with different criteria.", TEXT_DOMAIN));
            }
        } catch (Exception $e) {
            // Handle exceptions with a JSON error response
            wp_send_json_error(array(
                'success' => false,
                'message' => $e->getMessage(),
            ));
        }

        // Send JSON success response if no errors
        wp_send_json($response_data);
    }

    // 2. AJAX function to cancel a ticket
    public function cancel_ticket()
    {
        $response_data = array(
            "success" => true,
        );

        try {
            // Get the order and flight order references from the POST request
            $order_id = isset($_POST['orderId']) ? intval($_POST['orderId']) : 0;
            $flight_order_id = sanitize_text_field($_POST['flightOrderId'] ?? '');
            $guest_token = sanitize_text_field($_POST['guestToken'] ?? '');

            if (!$order_id || !$flight_order_id) {
                throw new Exception(__("Invalid order or flight reference ID.", TEXT_DOMAIN));
            }

            // Validate if the order belongs to the user (either logged-in or guest with a valid token)
            $this->validate_order_owner($order_id, $guest_token);

            // Prepare data to send to the API
            $data = array(
                "OrderRefId" => $flight_order_id
            );

            error_log($flight_order_id);
            // Call the API to cancel the ticket
            $response = $this->flights->cancel_ticket($data);
            error_log(json_encode($response));

            // Check the API response code
            if ($response['code'] == 200) {
                if (isset($response['data']) && $response['data']['isCancelled']) {
                    $response_data['message'] = __("Flight has been cancelled successfully", TEXT_DOMAIN);
                    $this->process_refund($order_id, __("User Cancelled His flight"));
                    update_post_meta($order_id, 'flight_status', 'cancelled');
                } else {
                    throw new Exception(__("Cancellation failed. Please check the details and try again.", TEXT_DOMAIN));
                }
            } elseif ($response['code'] == 400) {
                $response_data = array(
                    "success" => false,
                    "message" => __("Cancellation failed. Please check the details and try again.", TEXT_DOMAIN)
                );
            } else {
                throw new Exception(__("An error occurred during cancellation. Please try again later.", TEXT_DOMAIN));
            }
        } catch (Exception $e) {
            // Handle exceptions and return a JSON error response
            $response_data = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }

        // Send JSON response
        wp_send_json($response_data);
    }

    // 3. AJAX function to issue a ticket
    public function issue_ticket()
    {
        $response_data = array(
            "success" => true,
        );

        try {
            // Get the order and flight order references from the POST request
            $order_id = isset($_POST['orderId']) ? intval($_POST['orderId']) : 0;
            $flight_order_id = sanitize_text_field($_POST['flightOrderId'] ?? '');
            $guest_token = sanitize_text_field($_POST['guestToken'] ?? '');

            if (!$order_id || !$flight_order_id) {
                throw new Exception(__("Invalid order or flight reference ID.", TEXT_DOMAIN));
            }

            // Validate if the order belongs to the user (either logged-in or guest with a valid token)
            $this->validate_order_owner($order_id, $guest_token);

            // Prepare data to send to the API
            $data = array(
                "OrderRefId" => $flight_order_id
            );

            // Call the API to cancel the ticket
            $response = $this->flights->issue_ticket($data);

            // Check the API response code
            if ($response['code'] == 200) {
                $response_data['message'] = __("Flight has been Confirmed successfully", TEXT_DOMAIN);
                update_post_meta($order_id, 'flight_status', 'confirmed');
            } elseif ($response['code'] == 400) {
                $response_data = array(
                    "success" => false,
                    "message" => __("Flight confirmation failed. Please try again later.", TEXT_DOMAIN)
                );
            } else {
                throw new Exception(__("An error occurred during confirmation. Please try again later.", TEXT_DOMAIN));
            }
        } catch (Exception $e) {
            // Handle exceptions and return a JSON error response
            $response_data = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }

        // Send JSON response
        wp_send_json($response_data);
    }

    // 4. AJAX function to issue a ticket
    public function get_fares()
    {
        $response_data = array(
            "success" => true,
        );

        try {
            // Get the order and flight order references from the POST request
            $order_id = isset($_POST['orderId']) ? intval($_POST['orderId']) : 0;

            $order = wc_get_order($order_id);

            if (!$order) {
                throw new Exception(__("There is no data for this flight", TEXT_DOMAIN));
            }

            $trace_id = get_post_meta($order_id, "flight_trace_id", true);
            $purchase_id = get_post_meta($order_id, "flight_purchase_id", true);

            // Prepare data to send to the API
            $data = array(
                "traceId" => $trace_id,
                "isRevalidation" => true,
                "purchaseIds" => [$purchase_id]
            );

            // Call the API to cancel the ticket
            $response = $this->flights->get_fares($data);
            error_log(json_encode($response));
            // Check the API response code
            if ($response['code'] == 200) {
                $response_data["data"] = $response["data"]["segGroups"] ?? [];
            } else {
                throw new Exception(__("There is no data for this flight", TEXT_DOMAIN));
            }
        } catch (Exception $e) {
            // Handle exceptions and return a JSON error response
            $response_data = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }

        // Send JSON response
        wp_send_json($response_data);
    }

    public function revalidate_flights()
    {

        $response_data = array(
            "success" => true,
        );

        try {
            $trace_id = isset($_POST['trace_id']) ? ($_POST['trace_id']) : "";
            $purchase_id = isset($_POST['purchase_id']) ? ($_POST['purchase_id']) : "";

            if (empty($trace_id) || empty($purchase_id)) {
                throw new Exception(__("there is some data missing", TEXT_DOMAIN));
            }

            // Prepare data to send to the API
            $data = array(
                "traceId" => $trace_id,
                "purchaseIds" => [$purchase_id]
            );

            // Call the API to cancel the ticket
            $response = $this->flights->flight_revalidation($data);
            error_log(json_encode($response));
            // Check the API response code
            if ($response['code'] == 200) {
                $response_data["data"] = $response["data"]["flights"][0]["additionalServices"] ?? [];
                error_log(json_encode($response_data["data"]));
            } else {
                throw new Exception(__("There is issue happened please try again later", TEXT_DOMAIN));
            }
        } catch (Exception $e) {
            // Handle exceptions and return a JSON error response
            $response_data = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }

        // Send JSON response
        wp_send_json($response_data);
    }

    private function validate_search_data()
    {
        // Required fields and initialization
        $required_fields = [
            'airTravelType',
            'origin',
            'destination',
            'departureDateTime',
            'adultCount',
            'cabinClass',
        ];

        if ($_POST['airTravelType'] === "RoundTrip") {
            $required_fields[] = 'returnDateTime';
        }

        $errors = [];
        // Check for missing fields
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = sprintf(__("%s is required.", TEXT_DOMAIN), ucfirst($field));
            }
        }

        // Additional validation for departure date
        if (!empty($_POST['departureDateTime'])) {
            $departure_date = DateTime::createFromFormat('Y-m-d', $_POST['departureDateTime']);

            // Check if the date format is valid
            if (!$departure_date || $departure_date->format('Y-m-d') !== $_POST['departureDateTime']) {
                $errors[] = __("Invalid departure date format. Use YYYY-MM-DD.", TEXT_DOMAIN);
            } else {
                // Get today's date with no time component for accurate comparison
                $today = new DateTime();
                $today->setTime(0, 0, 0);

                // Validate if the departure date is today or later
                if ($departure_date < $today) {
                    $errors[] = __("Departure date must be today or a future date.", TEXT_DOMAIN);
                }
            }
        }

        // Additional validation for return date
        if ($_POST['airTravelType'] === "RoundTrip" && !empty($_POST['returnDateTime'])) {
            $return_date = DateTime::createFromFormat('Y-m-d', $_POST['returnDateTime']);

            // Check if the date format is valid
            if (!$return_date || $return_date->format('Y-m-d') !== $_POST['returnDateTime']) {
                $errors[] = __("Invalid departure date format. Use YYYY-MM-DD.", TEXT_DOMAIN);
            } else {
                // Get today's date with no time component for accurate comparison
                $departure_date = DateTime::createFromFormat('Y-m-d', $_POST['departureDateTime']);

                // Validate if the departure date is today or later
                if ($return_date < $departure_date) {
                    $errors[] = __("Return date must be greater than departure Date.", TEXT_DOMAIN);
                }
            }
        }

        // Adult count validation
        if (!empty($_POST['adultCount']) && (!is_numeric($_POST['adultCount']) || intval($_POST['adultCount']) < 1)) {
            $errors[] = __("Adult count must be a positive integer.", TEXT_DOMAIN);
        }

        // Child count validation
        if (!empty($_POST['childCount']) && (!is_numeric($_POST['childCount']) || intval($_POST['childCount']) < 1)) {
            $errors[] = __("Child count must be a positive integer.", TEXT_DOMAIN);
        }

        // Infant count validation
        if (!empty($_POST['infantCount']) && !empty($_POST['adultCount']) &&  intval($_POST['infantCount']) > intval($_POST['adultCount'])) {
            $errors[] = __("Infant count must be a smaller than adult count.", TEXT_DOMAIN);
        }

        // Infant count validation
        if (!empty($_POST['infantCount']) && (!is_numeric($_POST['infantCount']) || intval($_POST['infantCount']) < 1)) {
            $errors[] = __("Infant count must be a positive integer.", TEXT_DOMAIN);
        }

        return $errors;
    }

    private function validate_order_owner($order_id, $guest_token = null)
    {
        $order = wc_get_order($order_id);

        if (!$order) {
            throw new Exception(__("Order not found.", TEXT_DOMAIN));
        }

        // Check if the user is logged in
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();

            // Ensure the order belongs to the logged-in user
            if ($order->get_user_id() != $user_id) {
                throw new Exception(__("This order does not belong to you.", TEXT_DOMAIN));
            }
        } else {
            // Check for guest token if user is not logged in
            if (empty($guest_token)) {
                throw new Exception(__("This order does not belong to you.", TEXT_DOMAIN));
            }

            // Validate guest token against the order meta
            $stored_guest_token = get_post_meta($order->get_id(), 'order_guest_token', true);
            if ($stored_guest_token !== $guest_token) {
                throw new Exception(__("This order does not belong to you.", TEXT_DOMAIN));
            }
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
}

// Instantiate the AJAX handler
new Flight_Ajax_Handler();
