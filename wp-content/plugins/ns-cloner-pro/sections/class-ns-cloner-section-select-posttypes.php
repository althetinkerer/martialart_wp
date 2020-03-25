<?php
/**
 * Clone Post Types Section class
 *
 * @package NS_Cloner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class NS_Cloner_Section_Select_Posttypes
 *
 * Enables customizing the selection of post types to clone.
 */
class NS_Cloner_Section_Select_Posttypes extends NS_Cloner_Section {

	/**
	 * Mode ids that this section should be visible and active for.
	 *
	 * @var array
	 */
	public $modes_supported = [ 'core', 'clone_over' ];

	/**
	 * DOM id for section box.
	 *
	 * @var string
	 */
	public $id = 'select_posttypes';

	/**
	 * Priority relative to other section boxes in UI.
	 *
	 * @var int
	 */
	public $ui_priority = 500;


	/**
	 * NS_Cloner_Section_Select_Posttypes constructor.
	 */
	public function __construct() {
		parent::__construct();
		// Set up ajax action for js to fetch post types.
		add_action( 'wp_ajax_ns_cloner_get_post_types', array( $this, 'ajax_get_post_types' ) );
	}

	/**
	 * Do any setup before starting the cloning process (like hooks to modify the process).
	 */
	public function process_init() {
		// Don't copy rows of post type that were de-selected.
		add_filter( 'ns_cloner_rows_where', array( $this, 'filter_rows_where' ), 10, 3 );
		// Filter source tables based on selection of replace or leave content tables in place.
		add_filter( 'ns_cloner_site_tables', array( $this, 'filter_tables_to_clone' ), 10, 3 );
	}

	/**
	 * Output content for section settings box on admin page.
	 */
	public function render() {
		$this->open_section_box( __( 'Clone Post Types', 'ns-cloner' ), __( 'Copy Post Types', 'ns-cloner' ) );
		?>
		<h5><?php esc_html_e( 'Which post types should be cloned?', 'ns-cloner' ); ?></h5>
		<label>
			<input type="radio" name="do_copy_posts" value="0"/>
			<?php esc_html_e( 'Leave all posts (posts/pages/comments/categories) on target site in place and clone no post types from the source site', 'ns-cloner' ); ?>
		</label>
		<label>
			<input type="radio" name="do_copy_posts" value="1" checked/>
			<?php esc_html_e( 'Empty all posts (posts/pages/comments/categories) from target site and clone the following post types from the source site:', 'ns-cloner' ); ?>
		</label>
		<div class="ns-cloner-multi-checkbox-wrapper ns-cloner-select-posttypes-control loading"></div>
		<?php
		$this->close_section_box();
	}

	/**
	 * Get all used post types from the database, and put them into array with their user friendly labels
	 */
	public function ajax_get_post_types() {
		ns_cloner()->check_permissions();
		$post_types      = [];
		$source_prefix   = is_multisite()
			? esc_sql( ns_cloner()->db->get_blog_prefix( ns_cloner_request()->get( 'source_id' ) ) )
			: ns_cloner()->db->prefix;
		$used_post_types = ns_cloner()->db->get_col( "SELECT post_type FROM {$source_prefix}posts GROUP BY post_type" );
		foreach ( $used_post_types as $post_type ) {
			$post_type_object         = get_post_type_object( $post_type );
			$post_type_label          = ! is_null( $post_type_object ) ? $post_type_object->label : $post_type;
			$post_types[ $post_type ] = $post_type_label;
		}
		wp_send_json_success( [ 'post_types' => $post_types ] );
	}

	/**
	 * Prevent copying rows of disabled post types.
	 *
	 * If the post types is an empty array (all boxes unchecked), exclude all posts.
	 * If the post types is not even set (not even array, called programatically) include all posts.
	 *
	 * @param string $where Where query clause.
	 * @param string $table Source table name.
	 * @param string $source_prefix Source site database prefix.
	 * @return bool
	 */
	public function filter_rows_where( $where, $table, $source_prefix ) {
		$post_types = ns_cloner_request()->get( 'post_types_to_clone' );
		// Only filter if post types is real array - if blank, assume all post types.
		if ( is_array( $post_types ) ) {
			$types = join( "', '", array_map( 'esc_sql', $post_types ) );
			// Posts table.
			if ( $source_prefix . 'posts' === $table ) {
				$where .= " AND post_type IN ('$types')";
			}
			// Postmeta table.
			if ( $source_prefix . 'postmeta' === $table ) {
				$join  = "JOIN {$source_prefix}posts as p on post_id = p.ID ";
				$where = $join . $where . " AND p.post_type IN ('$types')";
			}
			// Term relationships table.
			if ( $source_prefix . 'term_relationships' === $table ) {
				$join  = "JOIN {$source_prefix}posts as p on object_id = p.ID ";
				$where = $join . $where . " AND post_type IN ('$types')";
			}
		}
		return $where;
	}

	/**
	 * Prevent cloning post related tables if do_copy_posts is off.
	 *
	 * @param array $tables List of table names.
	 * @return array
	 */
	public function filter_tables_to_clone( $tables ) {
		if ( ! ns_cloner_request()->get( 'do_copy_posts' ) ) {
			$clone_tables = [];
			$skip_tables  = [];
			foreach ( $tables as $table ) {
				$post_table_pattern = '/(posts|postmeta|comments|commentmeta|term_relationships|term_taxonomy|terms|termmeta)$/';
				if ( ! preg_match( $post_table_pattern, $table ) ) {
					array_push( $clone_tables, $table );
				} else {
					array_push( $skip_tables, $table );
				}
			}
			ns_cloner()->log->log( [ 'Skipping post tables because do_copy_posts was false:', $skip_tables ] );
			$tables = $clone_tables;
		}
		return $tables;
	}

}
