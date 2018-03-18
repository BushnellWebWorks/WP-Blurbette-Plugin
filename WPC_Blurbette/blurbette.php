<?php
/**
**************************************************************************
Plugin Name:  Blurbettes
Plugin URI:   http://www.wpcraftsman.com
Description:  Repurposable text clips which can be easily inserted within posts, pages & widgets.
Version:      1.0.0
Author:       Dave Bushnell
Author URI:   http://www.wpcraftsman.com
**************************************************************************/

if ( ! defined( 'ABSPATH' ) ) die( 'Nothing to see here' );

register_activation_hook( __FILE__, array( 'WPCX_Blurbette_Def', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPCX_Blurbette_Def', 'deactivate' ) );

/**
 * Defines all static aspects of Blurbette.
 *
 * Not intended to be instantiated; all methods herein are declared static.
 */
class WPCX_Blurbette_Def {
	const POST_TYPE = 'wpcx_blurbette';
	const TEXT_DOMAIN = 'wpcx_blurbette';
	const OPTION_METAKEY = 'wpcx_blurbette_options';
	const COPIED_TO_METAKEY = 'wpcx_blurbette_copied_to';
	const COPIED_FROM_METAKEY = 'wpcx_blurbette_copied_from';
	const ALLOWED_POSTTYPE_METAKEY = 'blurbette_allowed_post_type';
	const ALLOWED_WIDGET_METAKEY = 'blurbette_allowed_in_widget';

	/**
	 * Registers all necessary action hooks
	 * 
	 * @static
	 * @return void
	 */
	public static function do_all_hooks() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'define_js_ajax_vars' ) );
		add_action( 'before_delete_post', array( __CLASS__, 'delete_copied_meta' ), 15, 1 );
	}
	
	/**
	 * Callback for 'init' action, defines the Blurbette post type.
	 * 
	 * @static
	 * @return void
	 */
	public static function register_cpt() {
		register_post_type( self::POST_TYPE,
			array(
				'labels' 		=> array(
					'name' 			=> __( 'Blurbettes', self::TEXT_DOMAIN ),
					'singular_name'	=> __( 'Blurbette', self::TEXT_DOMAIN ),
					'edit_item' 	=> __( 'Edit Blurbette', self::TEXT_DOMAIN ),
					'view_item' 	=> __( 'View as Standalone Page', self::TEXT_DOMAIN ),
					'add_new_item'	=> __( 'Add New Blurbette', self::TEXT_DOMAIN ),
					'new_item'		=> __( 'New Blurbette', self::TEXT_DOMAIN )
				),
				'public' 		=> false,
				'show_ui' 		=> true,
				'has_archive' 	=> false,
				'hierarchical'	=> true,
				'menu_icon'		=> ( get_bloginfo( 'version' ) >= 3.8 ) ? 'dashicons-text' : plugin_dir_url( __FILE__ ) . 'mce/mce_button.png',
				'supports' 		=> array( 'title', 'editor' )
			)
		);
	}
	
	/**
	 * Check if this blurbette has been allowed in this context.
	 * 
	 * @access public
	 * @static
	 * @param int $id The post ID of blurbette
	 * @param string $context Name of a post type or the string 'widget'
	 * @return boolean
	 */
	public static function check_availability( $id, $context ) {
		if ( empty( $context ) ) return true;
		if ( 'widget' == $context ) :
			$is_available = get_post_meta( $id, self::ALLOWED_WIDGET_METAKEY, true );
		
		else:
			$allowed_post_types = (array) get_post_meta( $id, self::ALLOWED_POSTTYPE_METAKEY, false );
			$is_available = ( in_array( $context, $allowed_post_types ) );
		endif;
		
		/**
		 * Filter the boolean result of checking availability.
		 * 
		 * @param bool $is_available
		 * @param int $id The post ID of blurbette
		 * @param string $context Name of a post type or the string 'widget'
		 */
		return apply_filters( 'wpcx_blurbette_check_availability', $is_available, $id, $context );
	}

	/**
	 * Return qualifying blurbettes as array
	 * 
	 * @static
	 * @param string $context (default: null) Expecting 'widget', post type name, or null
	 * @param int $exclude_id (default: 0) If a post ID is provided then it will be omitted
	 * @return array {
	 * 		@type string $status 'error', 'empty', or 'ok'
	 *		@type string $errorMessage Can be displayed upon failure
	 *		@type array $opts {
	 *				@type int $ID The ID of a Blurbette post
	 *				@type string $post_type The post_content
	 *				@type string $label The post_title
	 *		}
	 * }
	 */
	public static function get_blurbettes_pairs( $context = null, $exclude_id = 0 ) {

		$query_args = array(
			'post_type'			=> self::POST_TYPE,
			'posts_per_page' 	=> -1,
			'orderby'			=> 'title',
			'order'				=> 'ASC'
		);
		$all_blurbettes = get_posts( $query_args );
		$return = array(
			'opts'			=> array(),
			'status'		=> 'empty',
			'errorString'	=> __( 'No Blurbettes are available.', self::TEXT_DOMAIN )
		);
		if ( is_array( $all_blurbettes ) ) :
			foreach( $all_blurbettes as $bbt ) :
				if ( $bbt->ID == $exclude_id ) continue;
				if ( ! self::check_availability( $bbt->ID, $context ) ) continue;
				$return['opts'][] = array(
					'ID'			=> esc_attr($bbt->ID),
					'post_name'		=> esc_attr($bbt->post_name),
					'label'			=> esc_html($bbt->post_title)
				);
			endforeach;
			if ( count( $return['opts'] ) ) :
				$return['status'] = 'ok';
			endif;
		else:
			$return = array(
				'status'		=> 'error',
				'errorString'	=> __( 'Sorry, there was a problem retrieving data.', self::TEXT_DOMAIN )
			);
		endif;
		/**
		 * Filter the output of the get_blurbettes_pairs() method
		 *
		 * @param array $return
		 * @param string $context
		 * @param int $exclude_id
		 */
		return apply_filters( 'wpcx_blurbette_get_pairs', $return, $context, $exclude_id );
	}
	
	/**
	 * Callback for 'admin_enqueue_scripts', outputs some common js vars used in ajax calls
	 * 
	 * @static
	 * @return void
	 */
	public static function define_js_ajax_vars() {
		?>
			<script type="text/javascript">
			var wpcxAjaxVars = {
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				post_type: '<?php echo $GLOBALS['post_type'] ?>',
				post_id: '<?php echo get_the_ID(); ?>'
			};
			</script>
		<?php
	}

	/**
	 * Callback for 'before_delete_post', deletes associated post metadata for blurbette or post.
	 * 
	 * @static
	 * @param int $postid
	 * @return void
	 */
	public static function delete_copied_meta( $postid ) {
		if ( empty( $postid ) || ! is_numeric( $postid ) ) return;
		global $wpdb;
		switch( get_post_type( $postid ) ) :
			case 'post': 
				$meta_key = self::COPIED_FROM_METAKEY;
			break;
			case self::POST_TYPE :
				$meta_key = self::COPIED_TO_METAKEY;
			break;
			default:
				return;
			break;
		endswitch;
		$wpdb->delete(
			$wpdb->postmeta,
			array(
				'meta_key'		=> $meta_key,
				'meta_value'	=> $postid
			),
			array( '%s', '%d' )
		);
	}
	
	/**
	 * Callback for plugin activation. Inits option data.
	 * 
	 * @static
	 * @return void
	 */
	public static function activate() {
		if ( ! get_option( self::OPTION_METAKEY ) ) :
			add_option( self::OPTION_METAKEY, array(
				'use_shortcode' 	=> 1,
				'use_widget' 		=> 1,
				'use_copy_metabox' 	=> 1,
				'copied_everywhere'	=> 'y'
			));
		endif;
	}

	/**
	 * Callback for plugin deactivation.
	 *
	 * If preferred by user, deletes all Blurbette post types, option data, and associated post metadata.
	 * 
	 * @static
	 * @return void
	 */
	public static function deactivate() {
		$options = get_option( self::OPTION_METAKEY );
		if ( ! empty( $options['clear_on_deactivate'] ) ) :
			delete_option( self::OPTION_METAKEY );
			$blurbettes = query_posts(
				array(
					'post_type' 		=> self::POST_TYPE,
					'post_status' 		=> 'any',
					'posts_per_page' 	=> -1
				) );
			if ( ! is_array( $blurbettes ) ) return;
			foreach ( $blurbettes as $bbt ) :
				wp_delete_post( $bbt->ID, true );
			endforeach;
		endif;
	}
	
} // end class WPCX_Blurbette_Def


/**
 * Inits, instantiates and stores all Blurbette classes and objects.
 */
class WPCX_Blurbette_Registry {
	/**
	 * All Blurbette object instances
	 * 
	 * @var array
	 */
	public $objects = array();
	/**
	 * Options retrieved from the options database
	 * 
	 * @var array
	 */
	public $options = array();
	
	/**
	 * Constructor.
	 * 
	 * @return void
	 */
	function __construct() {
		spl_autoload_register( array( $this, 'class_autoloader' ) );
		$this->options = get_option( WPCX_Blurbette_Def::OPTION_METAKEY );
		
		WPCX_Blurbette_Def::do_all_hooks();
		if ( ! empty( $this->options['use_shortcode'] ) ) :
			WPCX_Blurbette_Shortcode::do_all_hooks();
 		endif;

		if ( ! empty( $this->options['use_widget'] ) ) :
			add_action( 'widgets_init', array( $this, 'register_widget' ) );
		endif;

		add_action( 'plugins_loaded', array( $this, 'instantiate_the_rest' ) );
	}
	
	/**
	 * Autoloader for classes defined in this plugin.
	 * 
	 * @param string $classname
	 * @return null If class doesn't belong to this plugin
	 */
	function class_autoloader( $classname ) {
		if ( strpos( $classname, 'WPCX_' ) !== 0 ) return null;
		require_once plugin_dir_path( __FILE__ ) . 'classes/' . strtolower( $classname ) . '.php';
	}

	/**
	 * Instantiate a class and store it in $this->objects.
	 * 
	 * @param string $classname
	 * @param array $opts Differs for each class.
	 * @return void
	 */
	public function register( $classname, $opts = null ) {
		$this->objects[$classname][] = new $classname( $opts );
	}
	
	/**
	 * Callback for 'plugins_loaded'.
	 *
	 * Conditionally instantiates all plugin classes which cannot be instantiated at __construct()
	 * because WordPress load timeline has not reached certain milestones.
	 * 
	 * @return void
	 */
	function instantiate_the_rest() {
		if ( is_admin() ) :
			if ( ! empty( $this->options['use_shortcode'] ) ) :
				$this->register( 'WPCX_Blurbette_MCE_WithDialog', array(
					'registry'	=> $this,
					'name'		=> 'WPCXBlurbette',
					'row'		=> 2,
					'js' 		=> plugin_dir_url( __FILE__ ) . 'mce/mce_blurbette.js'
				) );
			endif;
			$this->register( 'WPCX_Blurbette_AdminPanel', array(
				'registry'=>$this
			) );
			if ( ! empty( $this->options['use_copy_metabox'] ) ) :
				$this->register( 'WPCX_Blurbette_Copy_Metabox', array(
					'registry'		=> $this,
					'title'			=> 'Copy to Blurbette',
					'post_types'	=> array( 'post', 'page' )
				) );
			endif;
			$this->register( 'WPCX_Blurbette_Opts_Metabox', array(
				'registry'		=> $this,
				'title'			=> 'Blurbette Options',
				'post_types'	=> array( WPCX_Blurbette_Def::POST_TYPE )
			) );
		endif;
	}
	
	/**
	 * Callback for 'widgets_init'. Registers the Blurbette Widget.
	 * 
	 * @access public
	 * @return void
	 */
	public function register_widget() {
		register_widget( 'WPCX_Blurbette_Widget' );
	}
	
}
$WPCX_Blurbette = new WPCX_Blurbette_Registry();
