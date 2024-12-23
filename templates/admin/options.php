<div class="wrap">
    <h1>GFA-HUB Flights Settings</h1>
    <form method="post">
        <?php
        settings_fields('gfa_hub_flights');
        do_settings_sections('gfa_hub_flights');
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">API Base URL</th>
                <td><input type="text" name="gfa_hub_api_base_url" value="<?php echo esc_attr(get_option('gfa_hub_api_base_url')); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Client ID</th>
                <td><input type="text" name="gfa_hub_client_id" value="<?php echo esc_attr(get_option('gfa_hub_client_id')); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Client Secret</th>
                <td><input type="password" name="gfa_hub_client_secret" value="<?php echo esc_attr(get_option('gfa_hub_client_secret')); ?>" /></td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>