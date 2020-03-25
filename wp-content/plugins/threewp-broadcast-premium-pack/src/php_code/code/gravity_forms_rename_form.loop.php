// This script renames a form. Change the old form name and the new form name.

$old_form_name = 'The old name of the form';

$new_form_name = 'Brand new name!';

// Done! Run the script
// --------------------

global $wpdb;

$wpdb->update(
	// Table
	$wpdb->prefix . 'gf_form',
	// Data
	[ 'title' => $new_form_name ],
	// where
	[ 'title' => $old_form_name ]
);
