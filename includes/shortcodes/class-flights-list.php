<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists('Flights_List_Shortcode')) {

    class Flights_List_Shortcode
    {
        private static $instance;

        private function __construct()
        {
            add_shortcode('gfa-hub-flights-list', array($this, 'render'));
            add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        }

        public function render()
        {
            ob_start();
            include GFA_HUB_FLIGHTS_TEMPLATES_FRONTEND . '/flight-list.php';
            return ob_get_clean();
        }

        public function register_scripts()
        {
            // Enqueue Bootstrap styles and scripts
            wp_enqueue_style('bootstrap-style', GFA_HUB_FLIGHTS_ASSETS_CSS . 'bootstrap.min.css');
            wp_enqueue_script('bootstrap-script', GFA_HUB_FLIGHTS_ASSETS_JS . 'bootstrap.min.js', array('jquery'), null, true);

            // Enqueue custom flights list styles and scripts
            wp_enqueue_style('flights-list-style', GFA_HUB_FLIGHTS_ASSETS_CSS . 'flights-list.css', array('bootstrap-style'), '1.0.0');
            wp_enqueue_script('flights-list-script', GFA_HUB_FLIGHTS_ASSETS_JS . 'flights-list.js', array('jquery', 'select2'), '2.0.0', true);

            // Localize script with AJAX URL
            wp_localize_script('flights-list-script', 'flights_list', array(
                'ajax_url' => admin_url('admin-ajax.php'),
            ));
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
    }

    Flights_List_Shortcode::get_instance();
}
