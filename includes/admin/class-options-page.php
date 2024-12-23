<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists('Flights_API')) {

    class Options_Page
    {
        private static $instance = null;

        /**
         * Get the singleton instance of the class.
         */
        public static function get_instance()
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Constructor (private to enforce singleton pattern).
         */
        private function __construct()
        {
            add_action('admin_menu', array($this, 'add_plugin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
        }

        /**
         * Add plugin settings menu.
         */
        public function add_plugin_menu()
        {
            add_menu_page(
                __('GFA HUB Settings', 'gfa_hub'),
                __('GFA HUB Settings', 'gfa_hub'),
                'manage_options',
                'gfa-hub-settings',
                array($this, 'render_settings_page'),
                'dashicons-admin-generic',
                80 // Suggested position
            );
        }

        /**
         * Register plugin settings.
         */
        public function register_settings()
        {
            // Add a settings section
            add_settings_section(
                'gfa_hub_settings_section',
                __('GFA HUB Settings', 'gfa_hub'),
                array($this, 'plugin_section_callback'),
                'gfa-hub-settings'
            );

            // Register and add fields
            $fields = [
                'gfa_hub_api_url' => __('API URL', 'gfa_hub'),
                'gfa_hub_client_id' => __('Client ID', 'gfa_hub'),
                'gfa_hub_client_secret' => __('Client Secret', 'gfa_hub')
            ];

            foreach ($fields as $key => $label) {
                register_setting(
                    'gfa-hub-settings-group',
                    $key,
                    array('sanitize_callback' => $key === 'gfa_hub_api_url' ? 'esc_url_raw' : 'sanitize_text_field')
                );

                add_settings_field(
                    $key,
                    $label,
                    array($this, 'plugin_field_callback'),
                    'gfa-hub-settings',
                    'gfa_hub_settings_section',
                    array('label_for' => $key)
                );
            }
        }

        /**
         * Callback for the settings section description.
         */
        public function plugin_section_callback()
        {
            echo '<p>' . __('Configure your GFA HUB plugin settings below.', 'gfa_hub') . '</p>';
        }

        /**
         * Callback for rendering input fields.
         */
        public function plugin_field_callback($args)
        {
            $value = esc_attr(get_option($args['label_for'], ''));
            $type = $args['label_for'] === 'gfa_hub_client_secret' ? 'password' : 'text';
            echo "<input type='{$type}' id='{$args['label_for']}' name='{$args['label_for']}' value='{$value}' />";
        }

        /**
         * Render the settings page.
         */
        public function render_settings_page()
        {
?>
            <div class="wrap">
                <h1><?php esc_html_e('GFA HUB Settings', 'gfa_hub'); ?></h1>
                <form action="options.php" method="post">
                    <?php
                    settings_fields('gfa-hub-settings-group');
                    do_settings_sections('gfa-hub-settings');
                    submit_button();
                    ?>
                </form>

                <?php $this->render_shortcodes_section(); ?>
            </div>
        <?php
        }

        /**
         * Render shortcodes section.
         */
        private function render_shortcodes_section()
        {
            $shortcodes = [
                [
                    'title' => __('Flight Search', 'gfa_hub'),
                    'shortcode' => '[gfa-hub-flights-list]',
                    'description' => __('Displays the flight search and result in list.', 'gfa_hub'),
                ],
                [
                    'title' => __('Flight Booking Details', 'gfa_hub'),
                    'shortcode' => '[gfa-hub-flight-booking-details]',
                    'description' => __('Shows the flight booking details after booking process.', 'gfa_hub'),
                ]
            ];
        ?>
            <div class="postbox">
                <h2 class="hndle"><?php esc_html_e('Shortcodes', 'gfa_hub'); ?></h2>
                <div class="inside">
                    <p><?php esc_html_e('Below are the available shortcodes for the GFA HUB plugin. Copy and paste them into your desired pages.', 'gfa_hub'); ?></p>
                    <table class="widefat fixed">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Title', 'gfa_hub'); ?></th>
                                <th><?php esc_html_e('Shortcode', 'gfa_hub'); ?></th>
                                <th><?php esc_html_e('Description', 'gfa_hub'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shortcodes as $shortcode) : ?>
                                <tr>
                                    <td><?php echo esc_html($shortcode['title']); ?></td>
                                    <td>
                                        <code><?php echo esc_html($shortcode['shortcode']); ?></code>
                                    </td>
                                    <td><?php echo esc_html($shortcode['description']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
<?php
        }
    }

    // Initialize the singleton instance
    Options_Page::get_instance();
}
