<?php
	/**
	 * Widget that displays a selected Blurbette.
	 * 
	 * All methods override parent methods
	 * 
	 * @extends WP_Widget
	 */
	class WPCX_Blurbette_Widget extends WP_Widget {
		/**
		 * Constructor, in the format recommended by WP.
		 * 
		 * @return void
		 */
		public function __construct() {
			$widget_ops = array( 
				'classname' => 'wpcx_blurbettes', 
				'description' => __( 'Display a Blurbette', WPCX_Blurbette_Def::TEXT_DOMAIN )
			 );
			parent::__construct(
				'wpcx_blurbette_widget',
				__( 'Blurbette', WPCX_Blurbette_Def::TEXT_DOMAIN ),
				$widget_ops
				);
		}
		
		/**
		 * Display the widget selector form in the Appearance panel.
		 * 
		 * @param array $instance
		 * @return void
		 */
		public function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, array( 
				'title' => '', 
				'blurbette_id' => null
			 ) );
			$title = esc_attr( $instance['title'] );
			$bbt_id = $instance['blurbette_id'];
			$drop_ops = WPCX_Blurbette_Def::get_blurbettes_pairs( 'widget' );
			?>
			<p><label><?php
				_e( 'Title:', WPCX_Blurbette_Def::TEXT_DOMAIN );
			?><input class="widefat" type="text" id="<?php
				echo $this->get_field_id( 'title' );
			?>" name="<?php
				echo $this->get_field_name( 'title' );
			?>" value="<?php
				echo $title;
			?>" />
			</label></p>
			
			<p><label for="<?php
				echo $this->get_field_id( 'blurbette_id' );
			?>"><?php
				_e( 'Blurbette:', WPCX_Blurbette_Def::TEXT_DOMAIN );
			?></label>
			<select id="<?php
				echo $this->get_field_id( 'blurbette_id' );
			?>" name="<?php
				echo $this->get_field_name( 'blurbette_id' );
			?>"><option value=""><?php
				_e( 'Choose...', WPCX_Blurbette_Def::TEXT_DOMAIN );
			?></option>
			<?php foreach ( ( array ) $drop_ops['opts'] as $dop ): ?>
				<option value="<?php
					echo esc_attr( $dop['ID'] );
				?>" <?php
					echo selected( $dop['ID'], $bbt_id );
				?>><?php 
					echo esc_html( substr( $dop['label'], 0, 50 ) );
				?></option>
			<?php endforeach; ?>
				</select>
			</p>
			<?php
			
			do_action( 'wpcx_blurbette_widget_form', $instance );
		}
		
		/**
		 * Update upon form submission.
		 *
		 * Must return an $instance which is further processed by parent.
		 * 
		 * @param array $new_instance
		 * @param array $old_instance
		 * @return void
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title'] = strip_tags( $new_instance['title'] );
			$instance['blurbette_id'] = $new_instance['blurbette_id'];

			/**
			 * Filter the assembled widget instance to be returned.
			 *
			 * @param array $instance 
			 * @param array $new_instance
			 * @param array $old_instance
			 */
			return apply_filters( 'wpcx_blurbette_widget_update', $instance, $new_instance, $old_instance );
		}
		
		/**
		 * Output the widget.
		 * 
		 * @param mixed $args Defined by register_sidebar()
		 * @param mixed $instance The widget instance
		 * @return void
		 */
		public function widget( $args, $instance ) {
			if ( empty( $instance['blurbette_id'] ) ) return;
			/**
			 * Filter the output title of the widget.
			 *
			 * @param array $instance 
			 * @param array $this->id_base
			 */
			$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
			if ( WPCX_Blurbette_Def::check_availability( $instance['blurbette_id'], 'widget' ) ):
				echo $args['before_widget'];
				if ( ! empty( $title ) ):
					echo $args['before_title'] . $title . $args['after_title'];
				endif;
					echo '<div class="textwidget blurbettewidget">', PHP_EOL;
					/**
					 * Filter the assembled shortcode for output.
					 *
					 * @param array $args Defined by register_sidebar() 
					 * @param array $instance The widget instance
					 */
					echo do_shortcode( apply_filters( 'wpcx_blurbette_widget_shortcode', '[blurbette id="'.$instance['blurbette_id'].'" context="widget"]', $args, $instance ) );
					echo '</div>', PHP_EOL;
				echo $args['after_widget'];
			endif;
		}
		
	} // end WPCX_Blurbette_Widget
