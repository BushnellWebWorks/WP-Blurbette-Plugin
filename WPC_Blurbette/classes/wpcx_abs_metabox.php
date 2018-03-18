<?php
	/**
	 * Abstract WPCX_Abs_Metabox class. For construction args, see __construct()
	 * 
	 * Children must define this method:
	 * output_meta_box($post) = the html / form elements output within the metabox; $post is the current post object
	 * 
	 * Children may define these public methods (herein void):
	 * q_admin_scripts() = hooked when admin scripts are enqueued.
	 * save_meta_box($postid) = hooked when admin panel data is saved.
	 *
	 * output_meta_box() may call $this->noncefield if data is to be saved
	 * save_meta_box() may call okay_to_save($postid); requires above noncefield and checks certain conditions; returns boolean
	 *
	 * @abstract
	 */
	abstract class WPCX_Abs_Metabox {
		
		protected $noncename = 'wpcx_nonce';
		protected $nonceval;
		protected $capability;
		protected $post_types;
		protected $priority;
		protected $title;
		protected $domid;
		
		/**
		 * Constructor.
		 * 
		 * @param array opts {
		 *		Assoc. array of properties, all keys become properties of this instance. Expecting:
		 *
		 *		@type string $title Required. The title of the metabox
		 *		@type array $post_types Optional. Array of post types for this metabox, default array('post').
		 *		@type string $context Optional. WP-defined context like 'main', 'side', etc. Default 'side'.
		 *		@type string $priority Optional. WP-defined priority like 'high', 'low', 'core', etc. Default 'default'.
		 *		@type string $capability Optional. Minimum capability for this metabox. Default 'edit_post'.
		 *		@type string $domid Optional. The element id attribute. Default generated, changes every instance.
		 *		@type string $noncename Optional. The name of the nonce field. Default generated, remains constant.
		 * }
		 * @return void
		 */
		function __construct( array $opts ) {
			if ( empty( $opts['title'] ) ) :
				throw new Exception('Metabox title not specified.');
			endif;
			$opts = wp_parse_args( $opts, array(
				'title'			=> null,
				'post_types'	=> array('post'),
				'context'		=> 'side',
				'priority'		=> 'default',
				'capability'	=> 'edit_post',
				'domid'			=> uniqid('wpcxid'),
				'noncename'		=> null
			));
			foreach( $opts as $k => $v ) :
				$this->$k = $v;
			endforeach;
			if ( empty( $this->nonceval ) ) :
				$this->nonceval = plugin_basename( __FILE__ ) . __CLASS__ . get_current_user_id();
			endif;
			if ( empty( $opts['noncename'] ) ) :
				$this->create_noncename();
			endif;
			$this->do_all_hooks();
		}
		/**
		 * Register the action hooks for defined methods herein.
		 * 
		 * @access protected
		 * @return void
		 */
		protected function do_all_hooks() {
			add_action( 'admin_enqueue_scripts', array( $this, 'q_admin_scripts' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_meta_box' ) );
		}
		
		/**
		 * Register hook to output the metabox for each defined post types.
		 * 
		 * @return void
		 */
		public function add_meta_box() {
			if ( ! is_array( $this->post_types ) ) :
				$this->post_types = array( $this->post_types );
			endif;
			foreach( $this->post_types as $post_type ):
				add_meta_box( $this->domid, $this->title, array( $this, 'output_meta_box' ), $post_type, $this->context, $this->priority );
			endforeach;
		}
		
		/**
		 * Define a nonce name if one was not provided at __construct().
		 * 
		 * @access protected
		 * @return void
		 */
		protected function create_noncename() {
			$this->noncename = 'wpcx_' . str_rot13( strtolower( get_class( $this ) ) );
		}
		
		/**
		 * Check common conditions in prep for saving data
		 * 
		 * Should call before updating data and filtering input.
		 * 
		 * @access protected
		 * @param int $postid
		 * @return bool
		 */
		protected function okay_to_save( $postid ) {
			// bail with common conditions
			if ( ! is_numeric( $postid ) || ! $postid ) return false;
			if ( ! current_user_can( $this->capability, $postid ) ) return false;
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return false;
			if ( ! isset( $_POST[$this->noncename] ) ) return false;
			if ( ! wp_verify_nonce( $_POST[$this->noncename], $this->nonceval ) ) return false;
		    return true;
		}
		
		/**
		 * Output a noncefield, tailored to this instance.
		 * 
		 * @access protected
		 * @return void
		 */
		protected function noncefield() {
			wp_nonce_field( $this->nonceval, $this->noncename );
		}
		
		/**
		 * Enqueue admin scripts & styles.
		 * 
		 * @return void
		 */
		public function q_admin_scripts() {}
		
		/**
		 * Save the metabox data.
		 * 
		 * @param mixed $postid
		 * @return void
		 */
		public function save_meta_box( $postid ) {}
		
		/**
		 * Display the metabox contents.
		 * 
		 * @abstract
		 * @param WP_Post $post
		 * @return void
		 */
		abstract public function output_meta_box( $post );
		
	}
