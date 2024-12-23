<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Omdr_Email_Handler')) {
    class Omdr_Email_Manager
    {
        // Hold the class instance
        private static $instance = null;

        // Private constructor to prevent multiple instances
        private function __construct() {}

        // Get the single instance of the class
        public static function get_instance()
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        // Method to send emails
        public function send($to, $subject, $template_name, $data = [], $headers = [])
        {
            // Default headers
            if (empty($headers)) {
                $headers[] = 'Content-Type: text/html; charset=UTF-8';
            }

            // Load the template file
            $template_path = GFA_HUB_FLIGHTS_TEMPLATES_EMAILS . "$template_name.php";
            if (!file_exists($template_path)) {
                error_log("Email template $template_name does not exist.");
                return false;
            }

            // Extract data to variables
            extract($data);

            // Capture the email body from the template
            ob_start();
            $email_heading = $this->get_heading();
            include $template_path;
            $email_body = ob_get_clean();
            // Send the email using wp_mail
            return WC()->mailer()->send($to, $subject, $email_body, $headers);
        }

        public function get_heading()
        {
            return __('Confirm Your Flight', TEXT_DOMAIN);
        }
    }
    Omdr_Email_Manager::get_instance();
}
