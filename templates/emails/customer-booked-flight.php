<?php
/* Booking Confirmation Email Template */
/* Available variables: $order */
$guest_token = get_post_meta($order->get_id(), 'order_guest_token', true);
do_action('woocommerce_email_header', $email_heading, $to);
?>
<p><?php printf(esc_html__('Hi %s,', TEXT_DOMAIN), esc_html($order->get_billing_first_name())); ?></p>
<p><?php esc_html_e('Your flight has been booked! You have to confirm your flight. For more details, click the link below:', TEXT_DOMAIN); ?></p>
<a href="<?php echo esc_url(home_url('booking-details') . '/?order_id=' . $order->get_id() . (!empty($guest_token) ? '&token=' . $guest_token : '')); ?>">
    <?php esc_html_e('View Booking Details', TEXT_DOMAIN); ?>
</a>
<?php do_action('woocommerce_email_footer', $to); ?>