<?php
/*
Plugin Name: Part J. AQI Widget
Plugin URI: https://github.com/lightcompass/wpplugin-aqi/
Description: Simple AQI widget from aqicn.org
Version: 1.0.0
License: GPL-2.0+
Author: Part Jaithong
Author URI: https://github.com/lightcompass
Text domain: partj-aqi

*/

/* exist if directly accessed */
if (!defined('ABSPATH')) {
	exit;
}

// define variable for path to this plugin file.
define('PARTJ_AQI_LOCATION', dirname(__FILE__));
define('PARTJ_AQI_LOCATION_URL', plugins_url('', __FILE__));

require_once PARTJ_AQI_LOCATION . '/includes/page-setup.php';

// define API
define("TOKEN", esc_attr( get_option('token')) );
define("URL", "https://api.waqi.info/feed/");


/**
 * Register menu for token and option
 */
function partj_aqi_admin_menu()
{
	add_options_page(
		__('PartJ AQI Setting', 'partj-aqi'),
		__('PartJ AQI', 'partj-aqi'),
		'administrator',
		'partj-aqi',
        'partj_aqi_admin_page_contents'
	);

	//call register settings function
	add_action( 'admin_init', 'register_partj_aqi_plugin_settings' );
}
add_action('admin_menu', 'partj_aqi_admin_menu');

function register_partj_aqi_plugin_settings() {
	register_setting( 'partj-aqi-option', 'token' );
}


/**
 * Register the AQI widget with WordPress.
 */
function register_aqi_widget()
{
	register_widget('PARTJ_AQI_Widget');
}

add_action('widgets_init', 'register_aqi_widget');

/**
 * Extend the widgets class for our new AQI widget.
 */
class PARTJ_AQI_Widget extends WP_Widget
{
	/**
	 * Setup the widget.
	 */
	public function __construct()
	{

		/* Widget settings. */
		$widget_ops = array(
			'classname'   => 'partj-aqi',
			'description' => __('Display AQI from aqi.org', 'partj-aqi'),
		);

		/* Widget control settings. */
		$control_ops = array(
			'id_base' => 'partj_aqi',
		);

		/* Create the widget. */
		parent::__construct('partj_aqi', 'Part J. AQI Widget', $widget_ops, $control_ops);
	}

	/**
	 * Output the widget front-end.
	 */
	public function widget($args, $instance)
	{

		// output the before widget content.
		echo wp_kses_post($args['before_widget']);

		/**
		 * Call an action which outputs the widget.
		 *
		 * @param $args is an array of the widget arguments e.g. before_widget.
		 * @param $instance is an array of the widget instances.
		 *
		 */
		do_action('partj_aqi_widget_output', $args, $instance);

		// output the after widget content.
		echo wp_kses_post($args['after_widget']);
	}

	/**
	 * Output the backend widget form.
	 */
	public function form($instance)
	{

		// get the saved title.
		$title = !empty($instance['title']) ? $instance['title'] : '';
		$city = !empty($instance['city']) ? $instance['city'] : '';
	?>

		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_attr_e('Title:', 'partj-aqi'); ?></label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr($this->get_field_id('city')); ?>"><?php esc_attr_e('AQI City:', 'partj-aqi'); ?></label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('city')); ?>" name="<?php echo esc_attr($this->get_field_name('city')); ?>" type="text" value="<?php echo esc_attr($city); ?>">
		</p>

		<?php echo __('Type in any city name eg. Bangkok (the name is case in-sensitive) ', 'partj-aqi'); ?>

<?php

	}

	/**
	 * Controls the save function when the widget updates.
	 *
	 * @param  array $new_instance The newly saved widget instance.
	 * @param  array $old_instance The old widget instance.
	 * @return array               The new instance to update.
	 */
	public function update($new_instance, $old_instance)
	{

		// create an empty array to store new values in.
		$instance = array();

		// add the title to the array, stripping empty tags along the way.
		$instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
		$instance['city'] = (!empty($new_instance['city'])) ? strip_tags($new_instance['city']) : '';

		// return the instance array to be saved.
		return $instance;
	}
}

/**
 * Outputs the widget title
 *
 * @param  array $args     An array of widget args.
 * @param  array $instance The current instance of widget data.
 */
function partj_aqi_output_widget_title($args, $instance)
{

	// if we have before widget content.
	if (!empty($instance['title'])) {

		// if we have before title content.
		if (!empty($args['before_title'])) {

			// output the before title content.
			echo wp_kses_post($args['before_title']);
		}

		// output the before widget content.
		echo esc_html($instance['title']);

		// if we have after title content.
		if (!empty($args['after_title'])) {

			// output the after title content.
			echo wp_kses_post($args['after_title']);
		}
	}
}

add_action('partj_aqi_widget_output', 'partj_aqi_output_widget_title', 10, 2);

/**
 * Outputs the widget content
 *
 * @param  array $args     An array of widget args.
 * @param  array $instance The current instance of widget data.
 */
function partj_aqi_output_widget_content($args,$instance)
{

	$city = esc_html($instance['city']);

	$url = URL . $city . '/?token=' . TOKEN;

	$response = wp_remote_get($url);

	if (is_array($response) && !is_wp_error($response)) {
		$headers = $response['headers']; // array of http header lines
		$body    = json_decode($response['body']); // use the content
	}

	if (isset($body->data->aqi)) {
		$aqi = colorize($body->data->aqi);
	}

	echo isset($body->data->aqi) ? "<p class='aqi-result'>{$city} {$aqi}</p>" : "no data";
}

add_action('partj_aqi_widget_output', 'partj_aqi_output_widget_content', 20, 2);

/**
 * Colorize aqi base on their number
 *
 * @param  int $aqi     An Aqi number.
 */
function colorize($aqi) {
	$spectrum = array (
		array(0,"#cccccc","#ffffff","Good"),
		array(50,"#009966","#ffffff","Good"),
		array(100, "#ffde33", "#000000","Moderate"),
		array(150, "#ff9933", "#000000","Unhealthy for Sensitive Groups"),
		array(200, "#cc0033", "#ffffff","Unhealthy"),
		array(300, "#660099", "#ffffff","Very Unhealthy"),
		array(500, "#7e0023", "#ffffff","Hazardous")
	);
	$length = count($spectrum);
	for ($i = 0; $i < $length-1; $i++) {
		if ($aqi == "-" || $aqi <= $spectrum[$i][0]) break;
	}

	return "<span style='color:{$spectrum[$i][2]}; background:{$spectrum[$i][1]};'>{$spectrum[$i][3]}</span>";
}
