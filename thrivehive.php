<?php
   /*
   Plugin Name: ThriveHive
   Plugin URI: http://thrivehive.com
   Description: A plugin to include ThriveHive's tracking code
   Version: 0.1
   Author: ThriveHive
   Author URI: http://thrivehive.com
   */
// create menu
add_action('admin_menu', 'thrivehive_create_menu');

function thrivehive_create_menu() {
		//create new top-level menu
	add_menu_page('ThriveHive Plugin Settings', 'ThriveHive', 'administrator', __FILE__, 'thrivehive_settings_page',plugins_url('/images/icon.png', __FILE__));
	//call register settings function
	add_action( 'admin_init', 'register_thrivehive_settings' );
}
function register_thrivehive_settings() {
	//register settings
	register_setting( 'thrivehive-settings-group', 'th_tracking_code' );	register_setting( 'thrivehive-settings-group', 'th_phone_number' );	register_setting( 'thrivehive-settings-group', 'th_form_html' );
}
function thrivehive_settings_page() {
?>
<div class="wrap">
<h2>ThriveHive Settings</h2>
<p>Please fill out the following information to set up your site with basic tracking assets.</p>

<form method="post" action="options.php">
    <?php settings_fields( 'thrivehive-settings-group' ); ?>
    <?php do_settings_fields( 'thrivehive-settings-group', 'thrivehive-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
			<th scope="row">ThriveHive Account ID</th>
			<td>				<input type="text" name="th_tracking_code" value="<?php echo get_option('th_tracking_code'); ?>" />			</td>
        </tr>        <tr valign="top">			<th scope="row">ThriveHive Phone Number</th>			<td>				<input type="text" name="th_phone_number" value="<?php echo get_option('th_phone_number'); ?>" />			</td>			</tr>        <tr valign="top">        <th scope="row">ThriveHive Contact Us Form HTML</th>        <td>			<textarea rows="15" cols="100" name="th_form_html" /><?php echo get_option('th_form_html'); ?></textarea>		</td>        </tr>
    </table>
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>
</form></div>
<?php }//shortcodes//[th_form]function th_display_form( $atts ){ return get_option('th_form_html');}add_shortcode( 'th_form', 'th_display_form' );//[th_phone]function th_display_phone( $atts ){ return get_option('th_phone_number');}add_shortcode( 'th_phone', 'th_display_phone' );
//instrument site
function thrivehive_instrumentation() {
    $account_id = get_option('th_tracking_code');
	echo <<<END
	<script type="text/javascript">
		var scripturl = (("https:" == document.location.protocol) ? "https://" : "http://") + "qa.thrivehive.com/content/WebTrack/catracker.js";
		document.write(unescape("%3Cscript src='" + scripturl + "' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
		try {
		var cat = new CATracker("$account_id");
		cat.Pause = true; cat.TrackOutboundLinks(); cat.PageView();
		} catch (err) {document.write("There has been an error initializing web tracking.");}
	</script>
	<noscript><img src='http://qa.thrivehive.com?noscript=1&aweid=$account_id&action=PageView'/></noscript>
END;
}
add_action('wp_footer', 'thrivehive_instrumentation');
// admin messages hook!
add_action('admin_notices', 'thrivehive_admin_msgs');
?><?php
 /**
 * Helper function for creating admin messages
 * src: http://www.wprecipes.com/how-to-show-an-urgent-message-in-the-wordpress-admin-area
 * found at: http://wp.tutsplus.com/tutorials/using-the-settings-api-part-1-create-a-theme-options-page/
 *
 * @param (string) $message The message to echo
 * @param (string) $msgclass The message class
 * @return echoes the message
 */
	function thrivehive_show_msg($message, $msgclass = 'info') {
	echo "<div id='message' class='$msgclass'>$message</div>";
}

 /**
 * Callback function for displaying admin messages
 *
 * @return calls thrivehive_show_msg()
 */
function thrivehive_admin_msgs() {
	// check for our settings page - need this in conditional further down
	$thrivehive_settings_pg = strpos($_GET['page'], thrivehive);
	// collect setting errors/notices: //http://codex.wordpress.org/Function_Reference/get_settings_errors
	$set_errors = get_settings_errors();

	//display admin message only for the admin to see, only on our settings page and only when setting errors/notices are returned!
	if(current_user_can ('manage_options') && $thrivehive_settings_pg !== FALSE && !empty($set_errors)){

		// have our settings succesfully been updated?
		if($set_errors[0]['code'] == 'settings_updated' && isset($_GET['settings-updated'])){
			thrivehive_show_msg("<p>" . $set_errors[0]['message'] . "</p>", 'updated');

		// have errors been found?
		}else{
			// there maybe more than one so run a foreach loop.
			foreach($set_errors as $set_error){
				// set the title attribute to match the error "setting title"
				thrivehive_show_msg("<p class='setting-error-message' title='" . $set_error['setting'] . "'>" . $set_error['message'] . "</p>", 'error');
			}
		}
	}
}
?>