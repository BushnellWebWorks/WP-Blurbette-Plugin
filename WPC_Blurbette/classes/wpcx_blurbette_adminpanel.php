<?php
	/**
	 * Output and manage the Admin Settings control panel for Blurbettes.
	 */
	class WPCX_Blurbette_AdminPanel {
		
		/**
		 * The Registry object that spawned this
		 * 
		 * @var WPCX_Blurbette_Registry
		 * @access private
		 */
		private $registry;
		
		/**
		 * The minimum user capability to access this page
		 * 
		 * @const string
		 */
		const CAPABILITY = 'manage_options';
		/**
		 * The nonce name
		 * 
		 * @const string
		 */
		const NONCE_NAME = 'wpcx_blurbette_admin_nonce';
		/**
		 * The nonce value for hashing
		 * 
		 * @const string
		 */
		const NONCE_VALUE = __FILE__;
		
		/**
		 * Constructor.
		 * 
		 * @access public
		 * @param array $options
		 * @return void
		 */
		function __construct( $options ) {
			$this->registry = $options['registry'];
			add_action( 'admin_menu', array( $this, 'register_submenu' ) );
		}
		
		/**
		 * Callback for 'admin_menu' action, registers the submenu page.
		 * 
		 * @return void
		 */
		function register_submenu() {
			add_submenu_page( 
				'options-general.php', 
				__( 'Blurbette Settings', WPCX_Blurbette_Def::TEXT_DOMAIN ), 
				__( 'Blurbettes', WPCX_Blurbette_Def::TEXT_DOMAIN ), 
				self::CAPABILITY, 
				WPCX_Blurbette_Def::POST_TYPE . '_mgmt', 
				array( $this, 'control_panel' )
			 );
		}
		
		/**
		 * Callback for add_submenu_page, outputs the HTML page.
		 * 
		 * @return void
		 */
		function control_panel() {
			if ( isset( $_POST[self::NONCE_NAME] ) ) :
				$update_message = $this->save_posted_settings();
			endif;
			
			$settings = $this->registry->options;
			
			?><div class="wrap">

				<h2><?php echo $GLOBALS['title'] ?></h2>

				<?php 
				/**
				 * Prior to page output, after title
				 */
				do_action( 'wpcx_blurbette_before_controlpanel' ); ?>
				
				<?php if ( isset( $update_message ) ) : ?>
					<div id="setting-error-settings_updated" class="<?php echo ( stripos( $update_message, 'saved' ) === false )? 'error' : 'updated'; ?> settings-error"> 
					<p><strong><?php echo $update_message ?></strong></p></div>
				<?php endif; ?>
				
				<form name="wpcx_blurbette_optsform" method="post" action="<?php
					echo add_query_arg( array( 
						'page' => $GLOBALS['plugin_page']
					 ), admin_url( $GLOBALS['pagenow'] ) );
				
				?>">
				<?php wp_nonce_field( self::NONCE_VALUE, self::NONCE_NAME ); ?>
			    <table class="form-table">
			        <tr valign="top">
			        <th scope="row"><?php _e( 'Shortcodes', WPCX_Blurbette_Def::TEXT_DOMAIN ) ?></th>
			        <td><label><input type="checkbox" name="blurbette_opt[use_shortcode]" value="1" <?php
			        	checked( $settings['use_shortcode'], 1 );
			        ?> /> <?php _e( 'Use Shortcodes', WPCX_Blurbette_Def::TEXT_DOMAIN ) ?></label>
			        </td>
			        </tr>
			        
			        <tr valign="top">
			        <th scope="row"><?php _e( 'Widgets', WPCX_Blurbette_Def::TEXT_DOMAIN ) ?></th>
			        <td><label><input type="checkbox" name="blurbette_opt[use_widget]" value="1" <?php
			        	checked( $settings['use_widget'], 1 );
			        ?> /> <?php _e( 'Use Widget', WPCX_Blurbette_Def::TEXT_DOMAIN ) ?></label>
			        </td>
			        </tr>
			        
			        <tr valign="top">
			        <th scope="row"><?php _e( 'Copy', WPCX_Blurbette_Def::TEXT_DOMAIN ) ?></th>
			        <td><label><input type="checkbox" name="blurbette_opt[use_copy_metabox]" value="1" <?php
			        	checked( $settings['use_copy_metabox'], 1 );
			        ?> /> <?php _e( 'Enable &quot;Copy&quot; button in post panels', WPCX_Blurbette_Def::TEXT_DOMAIN ) ?></label>
			        <blockquote><?php _e( 'New Blurbettes are available:', WPCX_Blurbette_Def::TEXT_DOMAIN ) ?>
			        <br />
			        <label><input type="radio" name="blurbette_opt[copied_everywhere]" value="y" <?php
			        	checked( $settings['copied_everywhere'], 'y' );
			        ?> /> <?php _e( 'Everywhere', WPCX_Blurbette_Def::TEXT_DOMAIN ) ?></label>
			        &nbsp;
			        <label><input type="radio" name="blurbette_opt[copied_everywhere]" value="n" <?php
			        	checked( $settings['copied_everywhere'], 'n' );
			        ?> /> <?php _e( 'Nowhere', WPCX_Blurbette_Def::TEXT_DOMAIN ) ?></label>
			        </blockquote>
			        </td>
			        </tr>
			        
			        <tr valign="top">
			        <th scope="row"><?php _e( 'Deactivation', WPCX_Blurbette_Def::TEXT_DOMAIN ) ?></th>
			        <td><label><input type="checkbox" name="blurbette_opt[clear_on_deactivate]" value="1" <?php
			        	checked( $settings['clear_on_deactivate'] , 1 );
			        ?> /> <?php _e( 'Clear everything when deactivating this plugin', WPCX_Blurbette_Def::TEXT_DOMAIN ) ?></label>
			        </td>
			        </tr>
			        
				</table>
				<?php
				/**
				 * After page output, before submit button.
				 */
				do_action( 'wpcx_blurbette_form_controlpanel' ); ?>
				<?php submit_button(); ?>
				</form>
			</div>
			<?php
			
		}
		
		/**
		 * Save the $_POSTed data.
		 * 
		 * @access protected
		 * @return string
		 */
		protected function save_posted_settings() {
			if ( ! wp_verify_nonce( $_POST[self::NONCE_NAME], self::NONCE_VALUE ) ) :
				return __( 'Sorry, there was an error on this form page.', WPCX_Blurbette_Def::TEXT_DOMAIN );
			endif;
			if ( ! current_user_can( self::CAPABILITY ) ) :
				return __( 'Unauthorized.', WPCX_Blurbette_Def::TEXT_DOMAIN );
			endif;
			$filtered_input = filter_var( 
				$_POST['blurbette_opt'], 
				FILTER_CALLBACK, 
				array( 'options' => array( $this, 'single_wordchar' ) )
			 );
			$settings_changed = false;
			foreach( $filtered_input as $key=>$value ) :
				if ( $value != $this->registry->options[$key] ) :
					$settings_changed = true;
					break;
				endif;
			endforeach;

			/**
			 * Filter the options for database update.
			 * 
			 * @param array $filtered_input
			 * @param bool $settings_changed
			 */
			$success = update_option( WPCX_Blurbette_Def::OPTION_METAKEY, apply_filters( 'wpcx_blurbette_save_settings', $filtered_input, $settings_changed ) );
			
			/**
			 * After settings have updated.
			 * 
			 * @param bool $success Result of update_option()
			 * @param array $filtered_input
			 * @param bool $settings_changed
			 */
			do_action( 'wpcx_blurbette_after_save_settings', $success, $filtered_input, $settings_changed );
			if ( $success || ! $settings_changed ) :
				$this->registry->options = $filtered_input;
				return __( 'Settings saved.', WPCX_Blurbette_Def::TEXT_DOMAIN );
			else:
				return __( 'Sorry, settings were not updated.', WPCX_Blurbette_Def::TEXT_DOMAIN );
			endif;
		}
		
		/**
		 * Callback for filter_var() function.
		 *
		 * Filters any non-word characters, returns only the first one.
		 * 
		 * @access protected
		 * @param string $val
		 * @return string Single character
		 */
		protected function single_wordchar( $val ) {
			$filtered = preg_replace( '/[^\w]/', '', $val );
			return $filtered[0];
		}
	} // end class WPCX_Blurbette_AdminPanel
