<?php
/**
 * Class containing common methods for parsing and outputting the [blurbette...] shortcode.
 *
 *	All methods are static, no need to instantiate.
 */
class WPCX_Blurbette_Shortcode {
	/**
	 * Initiator, registers necessary WP hooks and adds the shortcode callback.
	 * 
	 * @static
	 * @return void
	 */
	public static function do_all_hooks() {
		add_shortcode( 'blurbette', array( __CLASS__, 'shortcode' ) );
		add_filter( 'widget_text', array( __CLASS__, 'widget_text_shortcode' ) );
		add_filter( 'shortcode_atts_caption', array( __CLASS__, 'caption_shortcode' ) ); // WP 3.6
	}
	/**
	 * Defines the [blurbette ...] shortcode.
	 *
	 * @static
	 * @param array $atts {
	 *		The shortcode attributes
	 *
	 *		@type string $slug 		The slug matching the desired Blurbette
	 *		@type int $id			Alternately, the ID of the desired Blurbette
	 *		@type string $context	Post type name or 'widget' where this is appearing, 
	 *								used to restrict output according to user preferences
	 * }
	 * @param mixed $content (default: null) Never parsed, not expected.
	 * @return string Parsed content.
	 */
	public static function shortcode( $atts, $content=null ) {
		$current_context = ( is_object( $GLOBALS['post'] ) )? $GLOBALS['post']->post_type : null;
		$parms = shortcode_atts(
			array(
				'slug'		=> null,
				'id'		=> null,
				'context'	=> $current_context
			), $atts );

		$bbt_data = self::get_content_by_idslug( $parms );
		if ( ! empty( $bbt_data['content'] ) && WPCX_Blurbette_Def::check_availability( $bbt_data['id'], $parms['context'] ) ) :
			
			// must prevent recursion...
			$nested_ids = array( $bbt_data['id'] );
			if ( self::recursion_buster( $bbt_data['content'], $nested_ids ) ) :
				return null;
			endif;
			
			$bbt_data['content'] = preg_replace( '|[\n\r]+|', '<br />', trim( $bbt_data['content'] ) );
			$bbt_data['content'] = apply_filters( 'the_content', $bbt_data['content'] );
			$bbt_data['content'] = str_replace( ']]>', ']]&gt;', $bbt_data['content'] );
			$bbt_data['content'] = preg_replace( '|(</?p[^>]*>)+|i', '', $bbt_data['content'] );
			/**
			 * Filter the result of shortcode processing.
			 *
			 * @param array $bbt_data ['id' => ..., 'content' => ...] 
			 */
			return apply_filters( 'wpcx_blurbette_shortcode_output', $bbt_data['content'], $bbt_data );
		endif;
		
		return null;
	}
	
	/**
	 * Return the ID and post_content of a blurbette given array ['id'=>...] or array ['slug'=>...].
	 * 
	 * @access protected
	 * @static
	 * @param array $atts
	 *		
	 * @return array ['id'=>...,'content'=>...]
	 */
	protected static function get_content_by_idslug( $atts ) {
		$return = array( 'id'=>null, 'content'=>null );
		if ( isset( $atts['slug'] ) && ! empty( $atts['slug'] ) ) :
			$search_key = 'name';
			$search_value = $atts['slug'];
		elseif ( isset( $atts['id'] ) && ! empty( $atts['id'] ) ) :
			$search_key = 'p';
			$search_value = $atts['id'];
		endif;
		if ( isset( $search_key ) ) :
			/**
			 * Filter the query args for Blurbette lookup.
			 *
			 * @param array $atts ['id' => ...] or ['slug' => ...] 
			 */
			$query_args = apply_filters( 'wpcx_blurbette_content_query', array(
				'post_type'		=> WPCX_Blurbette_Def::POST_TYPE,
				$search_key		=> $search_value
			), $atts );
			$results = get_posts( $query_args );
			if ( is_array( $results ) && count( $results ) ) :
				$return['id'] = $results[0]->ID;
				$return['content'] = $results[0]->post_content;
			endif;
		endif;
		/**
		 * Filter the returned Blurbette lookup.
		 *
		 * @param array $return ['id' => ..., 'content' => ...]
		 * @param array $atts ['id' => ...] or ['slug' => ...] 
		 */
		return apply_filters( 'wpcx_blurbette_content_idslug', $return, $atts );
	}
	
	/**
	 * Recursive method; returns true if two nested Blurbette IDs match.
	 * 
	 * @access protected
	 * @static
	 * @param string $content Content of blurbette
	 * @param array &$nested_ids Growing list of nested IDs
	 * @return boolean
	 */
	protected static function recursion_buster( $content, &$nested_ids ) {
		static $shortcode_regex;
		if ( empty( $shortcode_regex ) ) :
			$shortcode_regex = get_shortcode_regex();
		endif;
		preg_match_all( "/$shortcode_regex/", $content, $matches );
		if ( in_array( 'blurbette', $matches[2] ) ) :
			for ( $ix = 0; $ix < count( $matches[2] ); $ix++ ) :
				if ( 'blurbette' != $matches[2][$ix] ) continue;
				$atts = shortcode_parse_atts( $matches[3][$ix] );
				$nested_data = self::get_content_by_idslug( $atts );
				if ( ! empty( $nested_data['id'] ) ) :
					if ( in_array( $nested_data['id'], $nested_ids ) ) return true;
					$nested_ids[] = $nested_data['id'];
					if ( self::recursion_buster( $nested_data['content'], $nested_ids ) ) return true;
				endif;
			endfor;
		endif;
		return false;
	}

	/**
	 * Callback filter for 'widget_text'; finds and replaces only the [blurbette...] shortcode.
	 *
	 * For pre-PHP 5.3 compatibility, uses a separate callback function; since that
	 * means it cannot pass additional args ($context) a separate callback is defined.
	 * 
	 * @access public
	 * @static
	 * @param mixed $widget_text
	 * @return string Processed shortcode
	 */
	public static function widget_text_shortcode( $widget_text ) {
		$widget_text = preg_replace_callback( '/\[blurbette.+?\]/i', array( __CLASS__, 'shortcode_on_preg_widget' ), $widget_text );
		return $widget_text;
	}
	/**
	 * Callback for above.
	 * 
	 * @static
	 * @param array $matches Result of regex search
	 * @return string Processed shortcode
	 */
	public static function shortcode_on_preg_widget( $matches ) {
		if ( ! preg_match( '/context\=/i', $matches[0] ) ) :
			$matches[0] = str_replace( ']', ' context="widget"]', $matches[0] );
		endif;
		return do_shortcode( strtolower( $matches[0] ) );
	}

	/**
	 * Callback filter for 'shortcode_atts_caption'; finds and replaces only the [blurbette...] shortcode.
	 *
	 * For pre-PHP 5.3 compatibility, uses a separate callback function; since that
	 * means it cannot pass additional args ($context) a separate callback is defined.
	 *
	 * Requires WordPress 3.6+
	 * 
	 * @static
	 * @param array $output Assoc. array containing a 'caption' key
	 * @return array 
	 */
	public static function caption_shortcode( $output ) {
		$output['caption'] = preg_replace_callback( '/\[blurbette.+?\]/i', array( __CLASS__, 'shortcode_on_preg_caption' ), $output['caption'] );
		return $output;
	}
	/**
	 * Callback for above.
	 * 
	 * @static
	 * @param array $matches Result of regex search
	 * @return string Processed shortcode
	 */
	public static function shortcode_on_preg_caption( $matches ) {
		if ( ! preg_match( '/context\=/i', $matches[0] ) ) :
			$matches[0] = str_replace( ']', ' context="attachment"]', $matches[0] );
		endif;
		return do_shortcode( strtolower( $matches[0] ) );
	}

}
