<?php
	/**
	 * Post 'Copy to Blurbette' metabox, enables spawning of new Blurbette.
	 *
	 * Also lists and hyperlinks any previously-copied Blurbettes. Uses AJAX only.
	 * 
	 * @extends WPCX_Abs_Metabox
	 */
	class WPCX_Blurbette_Copy_Metabox extends WPCX_Abs_Metabox {
		
		/**
		 * AJAX action name
		 * 
		 * @const string
		 */
		const AJAX_ACTION_SAVE = 'wpcx_ajax_save_copy';
		
		/**
		 * Register all WP action hooks, expands parent method.
		 * 
		 * @access protected
		 * @return void
		 */
		protected function do_all_hooks() {
			parent::do_all_hooks();
			// ajax...
			add_action( 'wp_ajax_' . self::AJAX_ACTION_SAVE, array( $this, 'ajax_save_copy' ) );
			add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION_SAVE, array( $this, 'ajax_save_copy' ) );
		}
		
		/**
		 * Callback for 'admin_enqueue_scripts', enqueues jQuery.
		 * 
		 * @return void
		 */
		function q_admin_scripts() {
			wp_enqueue_script( 'jquery' );
		}
		
		/**
		 * Output the metabox HTML.
		 * 
		 * @param WP_Post $post
		 * @return void
		 */
		function output_meta_box( $post ) {
			$copied_ids = ( array ) get_post_meta( $post->ID, WPCX_Blurbette_Def::COPIED_TO_METAKEY, false );
			?><div id="wpcx_copied_blurbettes"<?php if ( ! count( $copied_ids ) ) echo ' style="display:none"'; ?>>
				<p><?php _e( 'This post has been copied to these blurbettes:', WPCX_Blurbette_Def::TEXT_DOMAIN) ?></p>
				<ul class="blurbette_list">
				<?php foreach( $copied_ids as $id ) :
					echo $this->blurbette_editor_html( $id );
				endforeach; ?>
				</ul>
			</div>
			<a href="javascript:void( 0 )" onclick="wpcxCopyNewBlurbetteAjax()" class="button">Copy to a new Blurbette</a>
			<?php do_action( 'wpcx_blurbette_copy_mbox_output', $post, $copied_ids ); ?>
			<script type="text/javascript">
				function wpcxCopyNewBlurbetteAjax() {
				   var newtitle = prompt( "<?php _e( "What would you like the Blurbette's title to be?", WPCX_Blurbette_Def::TEXT_DOMAIN ) ?>", jQuery( 'input#title' ).val() );
				   if ( !newtitle || !newtitle.length ) { return; }
				   jQuery.post( 
				        wpcxAjaxVars.url, 
				        {	"action"		: "<?php echo self::AJAX_ACTION_SAVE ?>", 
				        	"post_id"		: <?php echo $post->ID ?>, 
				        	"title"			: newtitle, 
				        	"content"		: wpcxGetEditorContent(), 
				        	"<?php echo $this->noncename ?>" : "<?php echo wp_create_nonce( $this->nonceval ) ?>"
				        }, 
				        
				        function( jjson ){
				            if ( 'ok' == jjson.status ) {
					            jQuery( '#wpcx_copied_blurbettes ul.blurbette_list' ).append( jjson.payload.editor_html );
					            jQuery( '#wpcx_copied_blurbettes' ).show();
					        } else {
						        alert( jjson.errorString );
					        }
				        }, 
				        'json'
				 );
				}
				function wpcxGetEditorContent() {
					var isRich = ( typeof tinyMCE != "undefined" ) && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden();
					if ( isRich ) {
						var ed = tinyMCE.get( 'content' );
						if ( ed ) { return ed.getContent(); } else { return false; }
					} else {
						return jQuery( '#wp-content-editor-container .wp-editor-area' ).val();
					}
				}
			</script>
			
			<?php
		}
		
		/**
		 * Output list-item HTML (<li>...</li>) for one Blurbette.
		 * 
		 * @param int $id The Blurbette's ID
		 * @return void
		 */
		function blurbette_editor_html( $id ) {
			$editor_url = add_query_arg( 
				array( 
					'post' => $id, 
					'action' => 'edit'
				 ), 
				admin_url( 'post.php' )
			 );
			$title = get_the_title( $id );
			/**
			 * Filter the string output.
			 * 
			 * @param int $id The Blurbette's ID
			 */
			return apply_filters( 'wpcx_blurbette_copy_mbox_li', sprintf( '<li><a href="%s" title="%s">%s</a></li>', 
				$editor_url, 
				esc_attr( $title ), 
				esc_html( $title )
			) . PHP_EOL, $id );
		}
		
		/**
		 * Called by AJAX (POST), inserts a new Blurbette and adds metadata.
		 *
		 * $_POST must contain {
		 *		@type int $post_id The originating post's ID
		 *		@type string $content The post_content
		 *		@type string {noncename} as defined for this instance
		 * }
		 *
		 * Outputs JSON-encoded array {
		 *		@type string $status 'ok' on success
		 *		@type string $errorString The displayable message on failure
		 *		@type array $payload [ $ID, $editor_html, $post_title, $post_content, $post_type, $post_status ]
		 * }
		 * @see blurbette_editor_html()
		 * @return void
		 */
		public function ajax_save_copy() {
			$return = array( 'payload'=>array(), 'status'=>'error', 'errorString'=>'Unauthorized.' );
			if ( ! ( $_POST['post_id'] = intval( $_POST['post_id'] ) ) ) die( json_encode( $return ) );
			if ( ! wp_verify_nonce( $_POST[$this->noncename], $this->nonceval ) ) die( json_encode( $return ) );
			if ( ! current_user_can( $this->capability ) ) die( json_encode( $return ) );
			$compos = wp_parse_args( 
				$_POST, 
				array( 
					'title' => __( 'Blurbette Copied From ' . $_POST['post_id'], WPCX_Blurbette_Def::TEXT_DOMAIN ), 
					'content' => ''
				 )
			);
			/**
			 * Filter the assoc. array used for wp_insert_post.
			 * 
			 */
			$blurbette_post = apply_filters( 'wpcx_insert_blurbette_copy', array( 
				'post_title' => $compos['title'], 
				'post_content' => $compos['content'], 
				'post_type' => WPCX_Blurbette_Def::POST_TYPE, 
				'post_status' => 'publish'
				) );
			$blurbette_result = wp_insert_post( $blurbette_post, true );
			if ( is_wp_error( $blurbette_result ) ) :
				$return['errorString'] = array_pop( $blurbette_result->get_error_message() );
				die( json_encode( $return ) );
			endif;
			
			$return['errorString'] = __( 'Sorry, there was a problem updating your blurbette data.', WPCX_Blurbette_Def::TEXT_DOMAIN );
			if ( ! add_post_meta( $_POST['post_id'], WPCX_Blurbette_Def::COPIED_TO_METAKEY, $blurbette_result ) )   die( json_encode( $return ) );
			if ( ! add_post_meta( $blurbette_result, WPCX_Blurbette_Def::COPIED_FROM_METAKEY, $_POST['post_id'] ) ) die( json_encode( $return ) );
			
			$return['status'] = 'ok';
			$return['payload'] = $blurbette_post;
			$return['payload']['ID'] = $blurbette_result;
			$return['payload']['editor_html'] = $this->blurbette_editor_html( $blurbette_result );
			
			add_post_meta( $blurbette_result, WPCX_Blurbette_Def::ALLOWED_POSTTYPE_METAKEY, WPCX_Blurbette_Def::POST_TYPE );

			if ( !empty( $this->registry->options['copied_everywhere'] ) && $this->registry->options['copied_everywhere'] == 'y' ) :
				add_post_meta( $blurbette_result, WPCX_Blurbette_Def::ALLOWED_WIDGET_METAKEY, 1 );
				foreach( (array) WPCX_Blurbette_Opts_Metabox::eligible_post_types( false ) as $post_type => $label ):
					add_post_meta( $blurbette_result, WPCX_Blurbette_Def::ALLOWED_POSTTYPE_METAKEY, $post_type );
				endforeach;
			endif;
			
			do_action( 'wpcx_blurbette_copy_aftersave', $blurbette_result );
			/**
			 * Filter the JSON-encode array output.
			 */
			echo json_encode( apply_filters( 'wpcx_blurbette_copy_saved', $return ) );
			die();
		}
		
	} // end class WPCX_Blurbette_Copy_Metabox
