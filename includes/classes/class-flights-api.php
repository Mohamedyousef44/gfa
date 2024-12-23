<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists('Flights_API')) {
    class Flights_API
    {
        private static $instance;

        private $client_scope = 'FlightEngine';
        private $api_base_url;
        private $client_id;
        private $client_secret;

        private function __construct()
        {
            $this->api_base_url = get_option('gfa_hub_api_url', '');
            $this->client_id = get_option('gfa_hub_client_id', '');
            $this->client_secret = get_option('gfa_hub_client_secret', '');
        }

        /**
         * Flights_List_Shortcode instance
         *
         * @return object
         */
        public static function get_instance()
        {
            if (!isset(self::$instance) || is_null(self::$instance))
                self::$instance = new self();

            return self::$instance;
        }

        // Get OAuth token
        private function get_access_token()
        {
            $response = wp_remote_post($this->api_base_url . '/connect/token', [
                'body' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'scope' => $this->client_scope
                ]
            ]);

            $body = json_decode(wp_remote_retrieve_body($response), true);
            return $body['access_token'] ?? null;
        }

        private function get_headers()
        {
            $access_token = $this->get_access_token();
            return [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json'
            ];
        }

        // 1. Search Flights
        public function search_flights($data)
        {
            $endpoint = '/Flights/Search/v1';
            return $this->send_request('POST', $endpoint, $data);
        }

        // 2. Flight Revalidation
        public function flight_revalidation($data)
        {
            $endpoint = '/Flights/Revalidation/v1';
            return $this->send_request('POST', $endpoint, $data);
        }

        // 3. Seat Map
        public function get_seat_map($data)
        {
            $endpoint = '/Flights/Revalidation/SeatMap/v1';
            return $this->send_request('POST', $endpoint, $data);
        }

        // 4. Flight Booking
        public function book_flight($data)
        {
            $endpoint = '/Flights/Booking/CreatePNR/v1';
            return $this->send_request('POST', $endpoint, $data);
        }

        // 5. Get Booking
        public function get_booking($order_id)
        {
            $endpoint = "/Flights/Booking/GetBooking/v1/$order_id";
            return $this->send_request('GET', $endpoint);
        }

        // 6. Ticket Cancellation
        public function cancel_ticket($data)
        {
            $endpoint = '/Flights/Booking/CreatePNR/v1/ReleasePNR';
            return $this->send_request('POST', $endpoint, $data);
        }

        // 7. Ticket Issue
        public function issue_ticket($data)
        {
            $endpoint = '/Flights/Booking/TicketOrder/v1';
            return $this->send_request('POST', $endpoint, $data);
        }

        // 8. Get Fares
        public function get_fares($data)
        {
            $endpoint = '/Flights/Revalidation/v1/FareRule';
            return $this->send_request('POST', $endpoint, $data);
        }

        // Helper function to send requests
        private function send_request($method, $endpoint, $data = null)
        {
            $headers = $this->get_headers();

            $args = [
                'headers' => $headers,
                'method' => $method,
                'timeout' => 60,
            ];

            if ($data) {
                $args['body'] = json_encode($data);
            }

            $response = wp_remote_request($this->api_base_url . $endpoint, $args);

            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);

            // Log the status code for debugging
            // error_log("Status code: " . $status_code);

            // Handle specific status codes
            if ($status_code == 401) {
                $args['headers'] = $this->get_headers();
                return $this->send_request($method, $endpoint, $data, false);
            } elseif ($status_code == 400) {
                // Decode error response body and throw exception with details
                $response_data = json_decode($response_body, true);
                // error_log($response_body);
                return [
                    'code' => $status_code,
                    'data' => $response_data["errors"] ?? array('detail' => $response_data["detail"])
                ];
            } elseif ($status_code == 200) {
                // Successful response: decode and return the data
                $response_data = json_decode($response_body, true);
                return [
                    'code' => $status_code,
                    'data' => $response_data
                ];
            } else {
                return [
                    'code' => $status_code,
                    'data' => "An error occurred with status code " . $status_code
                ];
            }
        }
    }

    Flights_API::get_instance();
}
