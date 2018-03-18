<?php
	/**
	 * Blurbette Options metabox, enables user to check which contexts to allow a Blurbette.
	 * 
	 * @extends WPCX_Abs_Metabox Abstract class
	 */
	class WPCX_Blurbette_Opts_Metabox extends WPCX_Abs_Metabox {
		
		/**
		 * Outputs the metabox HTML content (callback for add_meta_box()).
		 * 
		 * @param WP_Post $post
		 * @return void
		 */
		function output_meta_box( $post ) {
			$allowed_post_types = ( array ) get_post_meta( $post->ID, WPCX_Blurbette_Def::ALLOWED_POSTTYPE_METAKEY, false );
			$allowed_in_widget = get_post_meta( $post->ID, WPCX_Blurbette_Def::ALLOWED_WIDGET_METAKEY, true );
			$copied_from = get_post_meta( $post->ID, WPCX_Blurbette_Def::COPIED_FROM_METAKEY, true );

			$this->noncefield();

			?><p><?php _e( 'Allow this blurbette in:', WPCX_Blurbette_Def::TEXT_DOMAIN ) ?></p>
			<ul>
				<li><label><input type="checkbox" name="wpcx_blurbette_widg" value="1" <?php
				checked( ! empty( $allowed_in_widget ), true );
			?> /> <?php _e( 'Widgets', WPCX_Blurbette_Def::TEXT_DOMAIN ) ?></label></li>
			<?php
			foreach( (array) self::eligible_post_types( false ) as $post_type => $label ): ?>
					<li><label><input type="checkbox" name="wpcx_blurbette_pt[<?php echo $post_type ?>]" value="1" <?php
						checked( in_array( $post_type, $allowed_post_types ), true );
					?> /> <?php
						echo esc_html( $label );
					?></label></li>
			<?php endforeach; ?>
			</ul>
			
			<?php
			if ( ! empty( $copied_from ) ) :
				$editor_url = add_query_arg( 
					array( 
						'post' => $copied_from, 
						'action' => 'edit'
					 ), 
					admin_url( 'post.php' )
				 );
				$title = get_the_title( $copied_from );
				if ( empty( $title ) ) $title = __( '( Untitled )', WPCX_Blurbette_Def::TEXT_DOMAIN );
				echo sprintf( '<p>'. __( 'This Blurbette was copied from: ', WPCX_Blurbette_Def::TEXT_DOMAIN ) .
					'<a href="%s" title="%s">%s</a></p>', 
						$editor_url, 
						esc_attr( $title ), 
						esc_html( $title )
						), PHP_EOL;
			endif;
			
			/**
			 * End of metabox output
			 * 
			 * @param WP_Post $post
			 * @param bool $allowed_in_widget Post metadata
			 * @param array $allowed_post_types Post metadata
			 * @param int $copied_from Post metadata
			 */
			do_action( 'wpcx_blurbette_opts_metabox', $post, $allowed_in_widget, $allowed_post_types, $copied_from );
		}
		
		/**
		 * Save posted data. Callback for 'save_post' action
		 * 
		 * @param int $postid
		 * @return void
		 */
		function save_meta_box( $postid ){
			if ( ! $this->okay_to_save( $postid ) ) return;
			if ( empty( $_POST[$this->noncename] ) ) return;

			$filtered_input = array( 
				'wpcx_blurbette_widg' => filter_var( 
					$_POST['wpcx_blurbette_widg'], 
					FILTER_VALIDATE_BOOLEAN, 
					FILTER_NULL_ON_FAILURE
				 ), 
				'wpcx_blurbette_pt' => filter_var_array( 
					( array ) $_POST['wpcx_blurbette_pt'], 
					FILTER_VALIDATE_BOOLEAN
				 )
			 );
			if ( 
				in_array( false, $filtered_input['wpcx_blurbette_pt'], true ) ||
				$filtered_input['wpcx_blurbette_widg'] === null
			) return; // suspicious because a value other than '1' was passed

			$filtered_input['wpcx_blurbette_pt'][WPCX_Blurbette_Def::POST_TYPE] = true;
			if ( $filtered_input['wpcx_blurbette_widg'] ) :
				update_post_meta( $postid, WPCX_Blurbette_Def::ALLOWED_WIDGET_METAKEY, 1 );
			else:
				delete_post_meta( $postid, WPCX_Blurbette_Def::ALLOWED_WIDGET_METAKEY );
			endif;
			
			delete_post_meta( $postid, WPCX_Blurbette_Def::ALLOWED_POSTTYPE_METAKEY );
			foreach ( array_keys( $filtered_input['wpcx_blurbette_pt'] ) as $post_type ) :
				add_post_meta( $postid, WPCX_Blurbette_Def::ALLOWED_POSTTYPE_METAKEY, $post_type );
			endforeach;
			
			add_action( 'wpcx_blurbette_opts_save', $postid );
		}
		
		/**
		 * Determine which post types are able to display a Blurbette.
		 * 
		 * @static
		 * @param bool $include_blurbettes (default: true) Whether to include the Blurbette post type in this list.
		 * @return array [post_type_name => Label, ...]
		 */
		public static function eligible_post_types( $include_blurbettes = true ) {
			$return = array();
			if ( $include_blurbettes ):
				$return[WPCX_Blurbette_Def::POST_TYPE] = __( 'Blurbettes', WPCX_Blurbette_Def::TEXT_DOMAIN );
			endif;
			$all_post_types = get_post_types( array( 
				'public' => true, 
				), 'objects' );
			if ( is_array( $all_post_types ) ) :
				foreach( $all_post_types as $post_type => $defs ) :
					if ( WPCX_Blurbette_Def::POST_TYPE == $post_type ) continue;
					if ( 'attachment' != $post_type && ! in_array( 'editor', array_keys( $GLOBALS['_wp_post_type_features'][$post_type] ) ) ) continue;
					$return[$post_type] = $defs->label;
				endforeach;
			endif;
			/**
			 * Filter the resulting array.
			 * 
			 * @param array $return The assoc. array [post_type_name => Label, ...]
			 * @param bool $include_blurbettes (default: true) Whether to include the Blurbette post type in this list.
			 */
			return apply_filters( 'wpcx_blurbette_eligible_post_types', $return, $include_blurbettes );
		}
	} // end class WPCX_Blurbette_Opts_Metabox
