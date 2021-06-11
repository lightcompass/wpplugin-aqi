<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Setup option page for the plugin
 *
 */
function partj_aqi_admin_page_contents() {
?>

<div class="wrap">
    <h1><?php _e('PartJ AQI Setting','partj-aqi'); ?></h1>
    <form method="post" action="options.php">
    <?php settings_fields( 'partj-aqi-option' ); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('AQI API Token:','partj-aqi'); ?></th>
                <td>
                    <input type="text" class="regular-text" name="token" value="<?php echo esc_attr( get_option('token') ); ?>">
                    <p class="description"><?php _e('* Generate api token on aqicn.org website','partj-aqi')?> <a href="https://aqicn.org/" target="_blank">aqicn.org</a> </p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>

<?php
}
