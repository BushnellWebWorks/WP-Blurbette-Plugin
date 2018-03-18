<?php 
/**
 * General-purpose class for structuring a TinyMCE control.
 */
class WPCX_MCEControl {
	
	/**
	 * A unique name for the control
	 * 
	 * @var string
	 * @access protected
	 */
	protected $name;
	
	/**
	 * The number of the row in which the control appears.
	 * 
	 * @var int
	 * @access protected
	 */
	protected $row;
	
	/**
	 * The URL of the javascript engine.
	 * 
	 * @var string
	 * @access protected
	 */
	protected $js;
	
	/**
	 * Constructor.
	 * 
	 * @param array $opts {
	 *		All keys become properties of the class as defined, and may include more than listed.
	 *
	 *		@type string $name	Required. The name of the control.
	 *		@type string $js	Required. The URL to the javascript file.
	 *		@type int $row		Optional. Which button row the control appears in.
	 * }
	 * @return void
	 */
	function __construct( $opts ) {
		if ( empty( $opts['name'] ) ) throw new Exception( 'MCE Plugin name not specified.' );
		if ( empty( $opts['js'] ) ) throw new Exception( 'MCE Plugin script filepath not specified.' );
		if ( is_array( $opts ) ) :
			$opts = wp_parse_args( $opts, array( 
				'name'=>null, 
				'row'=>2, 
				'js'=>null
			 ) );
			foreach( $opts as $k => $v ) :
				$this->$k = $v;
			endforeach;
		endif;
		$this->do_all_hooks();
	}
	
	/**
	 * Register the WP hooks that activate this.
	 * 
	 * @access protected
	 * @return void
	 */
	protected function do_all_hooks() {
		add_action( 'admin_init', array( $this, 'add_mce_control' ) );
		add_filter( 'tiny_mce_version', array( __CLASS__, 'force_mce_refresh' ) );
	}
	
	/**
	 * Add the control to TinyMCE by registering additional filters.
	 *
	 * Requires a logged-in user with 'edit_posts' or 'edit_pages' capability
	 * 
	 * @return void
	 */
	public function add_mce_control() {
		if ( ! current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) return;
		if ( get_user_option( 'rich_editing' ) == 'true' ) :
			add_filter( 'mce_external_plugins', array( $this, 'plugin_array' ) );
			add_filter( 'mce_buttons_' . $this->row, array( $this, 'ctrls_array' ) );
		endif;
	}
	
	/**
	 * For some reason, this causes TinyMCE to refresh and incorporate new changes.
	 * 
	 * @param int $ver
	 * @return void
	 */
	public static function force_mce_refresh( $ver ) {
		return $ver + 99;
	}
	
	/**
	 * Add our name to the MCE plugins array.
	 * 
	 * @param array $mce_plugins
	 * @return void
	 */
	public function plugin_array( array $mce_plugins ) {
		$mce_plugins[$this->name] = $this->js;
		return $mce_plugins;
	}
	/**
	 * Add our custom control to the row of buttons in MCE ( row spec'd on __construct() ).
	 * 
	 * @param array $mce_ctrls
	 * @return void
	 */
	public function ctrls_array( array $mce_ctrls ) {
		$mce_ctrls[] = $this->name;
		return $mce_ctrls;
	}
	
}
