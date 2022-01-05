<?php
/**
* Photo Uploader pro Skautí fotobanku
*
* Plugin Name:       Photo Uploader pro Skautí fotobanku
* Description:       Plugin propojující pluginy Gravity Forms a Use-Your-Drive umožňující nahrávání fotografií na  Google Drive skautské fotobanky.
* Version:           1.1.0
* Requires at least: 5.2
* Requires PHP:      7.2
* Author:            Honza Kopecký, honza.kopecky95@gmail.com
* License:           GPL v2 or later
* Text Domain:       skaut-photo-uplaoder
* Domain Path:       /lang
*/

function skautphotouploader_load_textdomain() {
	load_plugin_textdomain( 'skaut-photo-uplaoder', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
}
add_action( 'init', 'skautphotouploader_load_textdomain' );

/**
 * Attach script that sets the generated file description to each file added through Use-Your-Drive plugin.
 */
function load_drive_scripts() {
	if(is_admin())
		return;
	wp_enqueue_script('drive_upload', plugin_dir_url(__FILE__) . '/js/drive-upload.js', ['jquery'], '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'load_drive_scripts');

/** Check whether the form is a photo uploader and thus whether the plugin should intervene. Photo uploader form must have a CSS class of 'photo-uploader'.
 *
 * @param $form array Form to be checked
 *
 * @return bool True if the form has the correct CSS class, false otherwise
 */
function is_photo_uploader($form) {
	return isset($form['cssClass']) && (strstr($form['cssClass'], 'photo-uploader') !== false);
}

/** Use the data that user filled into the first page of the form and generate the uploaded file description.
 *
 * @param $form array Form that is being rendered.
 *
 * @return array Modified form with generated description.
 */
function populate_field_values_to_form($form)
{
	// Exit if not photo uploader form or second page is not being rendered
	if (!is_photo_uploader($form) || GFFormDisplay::get_current_page($form['id']) != 2)
		return $form;

	// Get description field which contains description template. Example of this can be found in 'drive-description-template.html' file of this plugin.
	$description_field = get_field_by_type($form, 'html');
	if(!$description_field)
		return $form;

	// Generate unique ID for this batch
	$entry_id = uniqid("ID");

	// If copyright field is empty, generate default one from othe fields
	if((!get_post_value($form, $_POST, 'copyright') || get_post_value($form, $_POST, 'copyright') === '') && get_post_value($form, $_POST, 'first_name') && get_post_value($form, $_POST, 'last_name') && get_post_value($form, $_POST, 'nickname'))
		$_POST['input_' . get_field_by_admin_label($form, 'copyright')->id] =
			get_post_value($form, $_POST, 'first_name') . ' ' . get_post_value($form, $_POST, 'last_name') . ' - ' .
			get_post_value($form, $_POST, 'nickname');

	// Replace all template fields with values entered by the user
	$content = $description_field->content;
	$content = preg_replace_callback(
		'@%[^%]+%@',
		function($match) use ($form, $entry_id) {
			$match = $match[0];
			$key = str_replace('%', '', strtolower($match));
			$value = get_post_value($form, $_POST, $key);

			if($key == 'id')
				$value = $entry_id;

			return $value ? $value : "";
		},
		$content
	);
	$description_field->content = $content;

	// Insert unique ID of this batch into the settings of the Use-Your-Drive shortcode
	$useyourdrive_shortcode = get_field_by_type($form, 'useyourdrive');
	if($useyourdrive_shortcode)
		$useyourdrive_shortcode->UseyourdriveShortcode = str_replace('%id%', $entry_id, $useyourdrive_shortcode->UseyourdriveShortcode);

	// Output little snippet that hides the descriptions from the user
	echo "
	<style>
	#UseyourDrive .fileupload-table-text-subtitle {
	display: none;
	}
	</style>
	";

	// Return form with replaced description field and Use-Your-Drive shortcode
	return $form;
}
add_filter("gform_pre_render", "populate_field_values_to_form");

/** Get field object from a form based on admin field configured in the form settings.
 *
 * @param $form array Gravity Form Object
 * @param $admin_label string Admin label to get the field by
 *
 * @return array|null Form Field object or null if not found
 */
function get_field_by_admin_label($form, $admin_label) {
	$filtered = array_filter($form['fields'], function($field) use ($admin_label) {
		/** @var $field GF_Field */
		return $field->adminLabel == $admin_label;
	});

	if(count($filtered) != 1)
		return null;

	return array_pop($filtered);
}

/** Get an entry value from a $_POST object or Gravity Forms Entry object based on admin label.
 *
 * @param $form array Gravity Forms object that contains information about the fields
 * @param $entry array $_POST or Gravity Forms Entry object to get the value from
 * @param $admin_label string Admin Label of the field configured by the admin
 * @param $key_prefix string Prefix value used when searching in the entry object ($_POST has prefixes, Entry object does not)
 *
 * @return string|null Field value entered by the user, null if field with the admin label was not found.
 */
function get_post_value($form, $entry, $admin_label, $key_prefix = 'input_') {
	// Obtains field object by searching the Form object
	$field = get_field_by_admin_label($form, $admin_label);
	if(!$field)
		return null;

	// Filters all keys of the entry array to the ones that match the field id
	// checkboxes, radio buttons etc. would have keys like 'input_1_1', 'input_1_2'  for each option that's why the regex below
	$field_name = $key_prefix . $field->id;
	$keys = array_keys($entry);
	$field_keys = array_filter($keys, function($key) use ($field_name) {
		return preg_match("@^$field_name($|[_.][0-9]+$)@", $key) === 1;
	});

	// Obtain values for all keys that were not filtered out before
	$values = [];
	foreach( $field_keys as $key ) {
		$values[] = rgar($entry, $key);
	}

	// Return all values as a string connected by ','
	// remove all new lines
	return htmlspecialchars(trim(preg_replace('/\s+/', ' ', join(',', $values))));
}

/** Will return first field object of the form corresponding to the type
 * @param $form array Gravity Forms form object
 * @param $type string Field type of interest
 *
 * @return GF_Field|null Gravity Forms Field object if one found, null if not
 */
function get_field_by_type($form, $type) {
	foreach ($form['fields'] as $field)
		if($field->type == $type)
			return $field;

	return null;
}

/** Obtains values to be prefilled into the photo uploader form.
 *
 * @return array Default values for form fields. Keys are admin labels of the fields in question.
 */
function get_upload_form_defaults() {
	// User must be logged in to use the photo uploader anyway.
	if(!is_user_logged_in())
		return null;

	// Default values are obtained from the user object.
	$user = wp_get_current_user();
	$user_defaults = [
		'email' => $user->user_email,
		'first_name' => $user->first_name,
		'last_name' => $user->last_name,
		'nickname' => $user->user_login
	];

	// More defaults are obtain from a user meta stored once the user submits the uploader for the first time.
	$user_cached = get_user_meta(get_current_user_id(), 'photo_uploader_defaults', true);
	$user_cached = $user_cached ? $user_cached : [];

	// Defaults obtained from user object are rewritten by the ones stored in meta (and more are likely added).
	return array_merge($user_defaults, $user_cached);
}

/** Fills in default values for the form fields
 *
 * @param $text string Text that can obtain merge tags.
 * @param $form array Gravity Forms Form object which is being rendered.
 *
 * @return string Text with replaced merge tags
 */
function replace_photo_uploader_tags($text, $form) {
	// Get all data to be replaced, keys of teh array are admin labels of form fields
	$defaults = get_upload_form_defaults();
	$text = replace_single_photo_uploader_merge_tag($text, 'first_name', $defaults);
	$text = replace_single_photo_uploader_merge_tag($text, 'last_name', $defaults);
	$text = replace_single_photo_uploader_merge_tag($text, 'nickname', $defaults);
	$text = replace_single_photo_uploader_merge_tag($text, 'email', $defaults);
	$text = replace_single_photo_uploader_merge_tag($text, 'phone', $defaults);
	$text = replace_single_photo_uploader_merge_tag($text, 'copyright', $defaults);

	return $text;
}
add_filter('gform_replace_merge_tags', 'replace_photo_uploader_tags', 10, 2);

/** Performs a single replace in a merge tag string.
 *
 * @param $text string String that we are replacing merge tags in.
 * @param $tag string Tag to replace (without the {} wrapping parenthesis)
 * @param $all_values array Array of all values that we have available (indices are admin labels)
 *
 * @return string Text with one replaced merge tag
 */
function replace_single_photo_uploader_merge_tag($text, $tag, $all_values) {
	$value = '';
	if(isset($all_values[$tag]))
		$value = $all_values[$tag];
	return str_replace("{default_$tag}", $value, $text);
}

/** Saves data that user entered in the form and we want them to be prefilled next time.
 *
 * @param $entry array Gravity Forms entry object with the just submitted data
 * @param $form array Gravity Forms Form object that was submitted
 */
function save_photo_uploader_defaults($entry, $form) {
	if(!is_photo_uploader($form) || !is_user_logged_in())
		return;

	// Get the values entered by the user from the entry object/array
	$defaults = [
		'first_name' => get_post_value($form, $entry, 'first_name', ''),
		'last_name' => get_post_value($form, $entry, 'last_name', ''),
		'nickname' => get_post_value($form, $entry, 'nickname', ''),
		'email' => get_post_value($form, $entry, 'email', ''),
		'phone' => get_post_value($form, $entry, 'phone', ''),
		'copyright' => get_post_value($form, $entry, 'copyright', '')
	];

	// Unset empty values
	foreach ($defaults as $key => $value)
		if(!$value)
			unset($defaults[$key]);

	// Save the values to teh user meta table for later use
	update_user_meta(wp_get_current_user()->ID, 'photo_uploader_defaults', $defaults);
}
add_filter( 'gform_entry_post_save', 'save_photo_uploader_defaults', 10, 2 );

add_filter( 'gform_field_validation', 'validate_date_fields', 10, 4 );
function validate_date_fields( $result, $value, $form, $field ) {
	if(!is_photo_uploader($form))
		return $result;

	if ( $field->get_input_type() == 'date' ) {
		try {
			$start = new DateTime( get_post_value( $form, $_POST, 'start' ) );
			$end   = new DateTime( get_post_value( $form, $_POST, 'end' ) );
		} catch(Exception $e) {
			// date is likely in a wrong format but this should be handled by gravity forms itself
		}

		if ( $start > $end ) {
			$result['is_valid'] = false;
			$result['message']  = __('Event start must be before it\'s end.', 'skaut-photo-uploader');
		}
	}

	return $result;
}