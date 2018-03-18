<?php
/**
 * Format a Blurbette TinyMCE control which opens a dialog upon clicking.
 * 
 * @extends WPCX_MCEControl
 */
class WPCX_Blurbette_MCE_WithDialog extends WPCX_MCEControl {
	
	/**
	 * AJAX action name
	 * 
	 * @const string
	 */
	const AJAX_GET_OPTS = 'wpcx_get_blurbette_opts';
	
	/**
	 * Register all WP action hooks, expands upon parent.
	 * 
	 * @access protected
	 * @return void
	 */
	protected function do_all_hooks() {
		parent::do_all_hooks();
		add_action( 'admin_enqueue_scripts', array( $this, 'q_admin_scripts' ) );
		add_action( 'admin_footer', array( $this, 'output_hidden_dialog' ) );
		
		// ajax...
		add_action( 'wp_ajax_' . self::AJAX_GET_OPTS, array( $this, 'get_blurbette_opts_ajax' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_GET_OPTS, array( $this, 'get_blurbette_opts_ajax' ) );
	}
	
	/**
	 * Callback for 'admin_enqueue_scripts' action. Enqueues necessary jQuery libraries and styles.
	 * 
	 * @return void
	 */
	function q_admin_scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-dialog', null, 'jquery' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
	}

	/**
	 * Outputs a <DIV> for use in jQuery UI Dialog, sets style to display:none.
	 * 
	 * @return void
	 */
	function output_hidden_dialog() {
		?><div id="Blurbette_MCE_dialog" style="display:none" title="<?php _e( 'Blurbette', WPCX_Blurbette_Def::TEXT_DOMAIN ) ?>">
			<p><label><?php _e( 'Choose a Blurbette:', WPCX_Blurbette_Def::TEXT_DOMAIN ) ?>
			<select id="blurbetteSelector">
				<option></option>
			</select>
			</label></p>
			<?php 
			/**
			 * End of hidden <DIV> dialog output
			 */
			add_action( 'wpcx_blurbette_hidden_dialog' ); ?>
		</div>
		<?php
	}
	
	/**
	 * Called by AJAX ($_GET), outputs available Blurbettes as JSON.
	 * 
	 * @see WPCX_Blurbette_Def::get_blurbettes_pairs()
	 * @return void
	 */
	function get_blurbette_opts_ajax() {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) die();
		if ( ! empty( $_GET['post_type'] ) ) :
			$post_type = sanitize_key( $_GET['post_type'] );
		endif;
		if ( ! empty( $_GET['exclude_id'] ) ) :
			$exclude_id = intval( $_GET['exclude_id'] );
		endif;
		$jjson = WPCX_Blurbette_Def::get_blurbettes_pairs( $post_type, $exclude_id );

		echo json_encode( $jjson );
		die();
	}
	
}
