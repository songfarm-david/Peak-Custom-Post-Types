<?php
/*
 * Plugin Name: 	PEAK Custom Post Types
 * Plugin URI: 	https://peakwebsites.ca
 * Description: 	Create custom post types and custom taxonomies easily for your theme
 * Version: 		1.0
 * Author: 			David Gaskin
 * Author URI:		https://peakwebsites.ca
 * Text Domain: 	peak-theme
 */

// NOTE: cool tut: https://wpshout.com/wordpress-options-page/

$pk_custom_post_types_options = 'pk_custom_posts';
$post_type_name					= 'post-name';
$post_type_description 			= 'post-description';
$post_type_taxonomy				= 'custom-taxonomy';

/*
 * Load plugin styles
 */
function pk_load_styles() {

	wp_register_style ( 'peak-custom-posts-styles', plugins_url ( 'css/styles.css', __FILE__ ) );
	wp_register_script( 'peak-custom-post-types-js', plugins_url ( 'js/refresh-page.js', __FILE__ ) );

	wp_enqueue_style( 'peak-custom-posts-styles' );
	// make sure script loads in footer
	wp_enqueue_script( 'peak-custom-post-types-js', null, null, null, true );
}
add_action( 'init', 'pk_load_styles', 9);

/*
 * Create menu tab in admin dashboard
 */
function pk_create_options_menu() {

 	add_menu_page(
		__('PEAK Custom Post Types Menu', 'peak-theme'),
		__('Custom Post Types', 'peak-theme'),
		'manage_options',
		'pk-custom-post-types',
		'pk_toplevel_page',
		null,
		5
 	);

}
add_action( 'admin_menu', 'pk_create_options_menu', 10);

/*
 * Create plugin page layout
 */
function pk_toplevel_page() {

	// must check that the user has the required capability
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __('You do not have sufficient permissions to access this page.', 'peak-theme') );
	}

	global $pk_custom_post_types_options,
	$post_type_name,
	$post_type_description,
	$post_type_taxonomy;

	$custom_post_types_data;
	$db_result;
	$hidden_field_name 	= 'hidden-field'; // could use env vars here instead or constants
	$hidden_field_value	= 'bobo';

	// page header
	echo '<div class="wrap"><h1>'.__( 'Add New Custom Post Type', 'peak-theme').'</h1>';

	// verify form submission when new post type is created
	if ( isset( $_POST[ $hidden_field_name ] ) && $_POST[ $hidden_field_name ] == $hidden_field_value && trim( $_POST[ $post_type_name ] != "" ) ) {

		 // if option not set yet
		 // second condition defends against empty objects/arrays in database
		 if ( ! get_option( $pk_custom_post_types_options ) && !empty( get_option( $pk_custom_post_types_options ) )) {

			 // clean post data and return to array
			 $custom_post_types_data[] = pk_sanitize_entry( $_POST );

			 $db_result = add_option( $pk_custom_post_types_options, $custom_post_types_data );

		 } else {

			 // get existing custom post types options
			 $custom_post_types_data = get_option( $pk_custom_post_types_options );

			 // add sanitize array to exisiting array
			 $custom_post_types_data[] = pk_sanitize_entry( $_POST );
			 $db_result = update_option( $pk_custom_post_types_options, $custom_post_types_data );

		 }

		 // NOTE: put this in a function
		 // check for successful db operation
		 // output status message
		 if ( $db_result ) {
			 echo '<div class="updated">
   		 	<p>'.__( 'Post type created!', 'peak-theme' ).'</p>
   		 </div>';
		 } else {
			 echo '<div class="error">
   		 	<p>'.__( 'The post type was not updated', 'peak-theme' ).'</p>
   		 </div>';
		 }

	 }

	// check for option delete buttons
	// NOTE: does this need better checking?
	if ( isset( $_POST[ 'delete-item' ] ) ) {

		// get index of selected item
	 	$i = $_POST[ 'deleted-item-index' ];

	 	$custom_post_types_data = get_option( $pk_custom_post_types_options );

		// remove array item at $i
		unset( $custom_post_types_data[ $i] );
		$custom_post_types_data = array_values( $custom_post_types_data );

		update_option( $pk_custom_post_types_options, $custom_post_types_data );

	}

	?>

	<form name="custom-post-types-form" method="post" action="">

		 <!-- hidden field for security verification -->
		 <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="<?php echo $hidden_field_value ?>">

		 <!-- custom post type name -->
		 <p>
		 	<label class="pk-label">
				<?php _e( 'Create new custom post type:', 'peak-theme') ?>

			 	<input type="text" name="<?php echo $post_type_name ?>" value="" placeholder="What should you call your new post type?" style="display:block;min-width:300px;" required>
		 	</label>

			<span class="description">Ex: 'Movies'.</span>
		 </p>

		 <!-- textarea for description -->
		 <p>
		 	<label class="pk-label">
				<?php _e( 'Post type description:', 'peak-theme') ?>

			 	<textarea name="<?php echo $post_type_description ?>" rows="4" placeholder="Describe the purpose of this post type (max 160 characters)" maxlength="160" style="vertical-align:inherit;display:block;min-width:300px;"></textarea>

		 	</label>
		 </p>

		 <!-- optional section for custom taxonomy -->
		 <h4>Optional:</h4>
		 <p>
		 	<label class="pk-label">
				<?php _e( 'Add a custom taxonomy:', 'peak-theme') ?>

				<input type="text" name="<?php echo $post_type_taxonomy ?>" value="" placeholder="Enter taxonomy name" style="display:block;min-width:300px;">
			</label>

			<span class="description">Ex: 'Genres'.</span>
		 </p>

		 <!-- submit button -->
		 <p class="submit">
			 <input type="submit" name="Submit" class="button-primary button" value="<?php esc_attr_e( 'Create Custom Post Type', 'peak-theme'); ?>">
		 </p>

	 </form>

	<!-- start second section -->
	<?php
	echo '<hr />';

	if ( ! get_option( $pk_custom_post_types_options ) ) {
		echo '<p class="description">No custom post types yet!</p>';
	} else {

		// loop over array
		echo '<h3>Created Post Types:</h3>';
		echo '<ul class="list">';

		$custom_post_types_data = get_option( $pk_custom_post_types_options );
		pk_create_post_types_list( $custom_post_types_data );

		echo '</ul>';

	}

	// end div class="wrap"
	echo '</div>';

} // end of: pk_toplevel_page

/*
 * Register custom post types and optional custom taxonomies
 */
function pk_create_custom_post_types() {

	global $pk_custom_post_types_options,
	$post_type_name,
	$post_type_description,
	$post_type_taxonomy;

	// if there is no option (data), abort
	if ( ! get_option( $pk_custom_post_types_options ) ) {
		return;
	}

	$all_posts = get_option( $pk_custom_post_types_options );

	foreach ($all_posts as $post) {

		$post_name 			= $post[ $post_type_name ];
		$post_description = $post[ $post_type_description ];
		$post_taxonomy		= $post[ $post_type_taxonomy ];

		// if a custom taxonomy is defined
		if ( $post_taxonomy ) {
			pk_create_custom_tax( $post_taxonomy, $post_name );
		}

		pk_create_custom_post_type( $post_name, $post_description, $post_taxonomy);

	}

}
add_action( 'init', 'pk_create_custom_post_types', 10);

/*
 * Helper function to register the taxonomy
 */
function pk_create_custom_tax( $post_taxonomy, $post_name ) {

	// @see: https://codex.wordpress.org/Function_Reference/register_taxonomy
	register_taxonomy(
		strtolower( $post_taxonomy ),
		strtolower( $post_name ),
		array(
			'label'   					=> ucfirst( $post_taxonomy ),
			'rewrite' 					=> array( 'slug' => strtolower( $post_taxonomy ) ),
			'public' 					=> true,
			'hierarchical' 			=> true,
			'show_ui'         		=> true,
			'show_admin_column' 		=> true,
			'show_in_menu'				=> true,
			'show_in_rest'				=> true,
			'query_var'         		=> true,
			'update_count_callback' => '_update_post_term_count',
		)
	);
}

/*
 * Register the custom post type
 */
function pk_create_custom_post_type( $post_name, $post_description, $post_taxonomy ) {

	$str_ucfirst 	= ucfirst( $post_name );
	$str_lower		= strtolower( $post_name );

	// register the post types
	register_post_type(
		$post_name,
		array(
			'label' => $post_name,
			'labels' => array(
				'name' 				=> __( $str_ucfirst, 'peak-theme'),
				'singular_name' 	=> __( $str_ucfirst, 'peak-theme'),
				'add_new_item'		=> __('Add new '.$str_lower, 'peak-theme'),
				'edit_item'			=> __('Edit '.$str_lower, 'peak-theme'),
				'new_item'			=> __('New '.$str_lower, 'peak-theme'),
				'view_item'			=> __('View '.$str_lower, 'peak-theme')
			),
			'description' => $post_description,
			'public' => true,
			'has_archive' => true,
			'hierarchical' => true,
			'menu_icon' => 'dashicons-art',
			'menu_position' => 15,
			'taxonomies' => (array) $post_taxonomy,
			'show_in_menu' => true,
			'rewrite' => array(
				'slug' => __( $str_lower, 'peak-theme'),
				'with_front' => false
			),
			'show_in_rest' => true,
			'supports' => array(
				'title',
				'editor',
				'author',
				'excerpt',
				'thumbnail',
				'trackbacks',
				'revisions',
				'custom-fields',
				'page-attributes',
				'post-formats'
			)
		)
	);

}

/*
 * Output post types in a list structure with option to delete single items
 */
function pk_create_post_types_list( $custom_posts ) {

	// import global vars
	// NOTE: $post_type_taxonomy, $post_type_description are not currently being outputted
	global $post_type_name, $post_type_taxonomy, $post_type_description;
	$index = 0;

	foreach ( $custom_posts as $post ) {
		foreach ( $post as $key => $value ) {

			if ( $key == $post_type_name ) {
				echo
				'<li data-attr-index="'.$index.'" class="pk-post-type-list-item">'
					.$post[$post_type_name].
					'<form method="post" action="" class="alignright">
						<input type="hidden" name="deleted-item-index" value="'.$index.'" />
						<input type="submit" name="delete-item" value="X" onclick="return confirm(\'Are you sure you want to delete this post type?\')">
					</form>
				</li>';
			}

		} // end: foreach ( $post as $key => $value )

		$index++;

	} // end: foreach ( $custom_posts as $post )

}

/*
 * Sanitizes post data and returns an array
 */
function pk_sanitize_entry( $entry ) {

	global $post_type_name, $post_type_description, $post_type_taxonomy;
	$entry_array = array();

	foreach ($entry as $key => $value) {
		if ( $key == $post_type_name ) {
			$entry_array[$key] = preg_replace("/[^A-Za-z0-9 -]/", '', $value);
		}
		if ( $key == $post_type_description ) {
			$entry_array[$key] = sanitize_text_field($value);
		}
		if ( $key == $post_type_taxonomy ) {
			// NOTE: spaces should be separated with dashes
			( $value != "" ) ? $entry_array[$key] = trim($value) : $entry_array[$key] = false;
		}
	}

	return $entry_array;

}
