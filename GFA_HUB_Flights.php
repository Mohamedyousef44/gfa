<?php
/*
Plugin Name: GFA-HUB Flights
Description: A plugin to manage flight bookings, cancellations, and integrations with WooCommerce.
Version: 1.0
Author: Mohamed Yossef
*/

// Activation hook to run when the plugin is activated
register_activation_hook(__FILE__, 'gfa_hub_flights_activate');

function gfa_hub_flights_activate()
{
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__)); // Deactivate the plugin
        wp_die(__('This plugin requires WooCommerce to be installed and active.', 'gfa-hub-flights'));
    }
}

// Initialize the plugin class
add_action('plugins_loaded', function () {
    new GFA_HUB_Flights();
});

class GFA_HUB_Flights
{
    public function __construct()
    {
        // Initialize plugin functionalities
        $this->init();
    }

    private function init()
    {
        // Include dependencies
        require_once plugin_dir_path(__FILE__) . '/config.php';
        require_once GFA_HUB_FLIGHTS_INCLUDES . '/index.php';
    }
}
