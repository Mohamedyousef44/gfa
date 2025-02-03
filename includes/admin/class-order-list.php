<?php
if (!class_exists('GFA_Orders_List')) {
    class GFA_Orders_List
    {
        /**
         * @var GFA_Orders_List
         */
        public static $instance;

        public function __construct()
        {
            add_filter('manage_edit-shop_order_columns', array($this, 'list_orders_cols'));

            add_action('manage_shop_order_posts_custom_column', array($this, 'populate_orders_list_column_data'), 25, 2);
        }

        public function list_orders_cols($columns)
        {
            $columns['order_ref_id'] = __('GSF ID', 'GFA');
            return $columns;
        }

        public function populate_orders_list_column_data($col_name, $order_id)
        {
            if ($order = wc_get_order($order_id)) {
                switch ($col_name) {
                    case 'order_ref_id':
                        $flight_orderId = get_post_meta($order_id, 'flight_orderId', true);
                        echo $flight_orderId ? $flight_orderId : "__";
                        break;
                    default:
                        # code...
                        break;
                }
            }
        }

        /**
         * @return GFA_Orders_List
         */
        public static function get_instance()
        {

            if (null == self::$instance) {
                self::$instance = new self;
            }
            return self::$instance;
        }
    }

    GFA_Orders_List::get_instance();
}
