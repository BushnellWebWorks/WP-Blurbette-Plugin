jQuery().ready( function( $ ) {
	tinymce.create( 'tinymce.plugins.WPCX_BlurbettePlugin', {
        init : function( ed, url ) {
           ed.addButton( 'WPCXBlurbette', {
                title: 'Blurbette', 
                image: url + '/mce_button.png', 
                onclick: function() {
					wpcx_ajaxRetrieveBlurbetteOpts( ed );
                }
            } );
        }, 
        createControl: function( n, cm ) {
            return null;
        }, 
        getInfo: function() {
            return {
                longname: 'Blurbette', 
                author: 'Dave Bushnell', 
                authorurl: 'http://www.wpcraftsman.com', 
                infourl: 'http://www.wpcraftsman.com', 
                version: '1.0.0'
            };
        }
        
	} );
    
	tinymce.PluginManager.add( 'WPCXBlurbette', tinymce.plugins.WPCX_BlurbettePlugin );
	
	function wpcx_generateShortcode( blurbid, value_type ) {
	    tinymce.activeEditor.execCommand(
	    	'mceInsertContent',
	    	false,
	    	'[blurbette ' + value_type + '="' + unescape( blurbid ) + '"]'
	    	);
	}

    $( '#Blurbette_MCE_dialog #blurbetteSelector' ).change( function() {
	    if ( '' != $(this).val() ) {
			$( '#Blurbette_MCE_dialog' ).dialog( 'close' );
			wpcx_generateShortcode( $(this).val(), 'slug' );
		}		    
    } );
    function wpcx_ajaxRetrieveBlurbetteOpts() {
	    $.getJSON( 
	        wpcxAjaxVars.url, 
	        'action=wpcx_get_blurbette_opts&post_type=' + wpcxAjaxVars.post_type + '&exclude_id=' + wpcxAjaxVars.post_id, 
	        function( jjson ) {
	            if ( 'ok' == jjson.status ) {
		            wpcx_popNShowBlurbetteDialog( jjson );
		        } else {
			        alert( jjson.errorString );
		        }
	        }
	 );
	}
	
	function wpcx_popNShowBlurbetteDialog( jjson ) {
		$( '#Blurbette_MCE_dialog #blurbetteSelector' ).empty();
		$( '#Blurbette_MCE_dialog #blurbetteSelector' ).append( $( '<option />' )
			.attr( 'value', '' )
			.html( 'Choose...' )
		 );
		for ( var ix in jjson.opts ) {
			$( '#Blurbette_MCE_dialog #blurbetteSelector' ).append( $( '<option />' )
				.attr( 'value', jjson.opts[ix].post_name )
				.html( jjson.opts[ix].label )
			 );
		}
		$( '#Blurbette_MCE_dialog' ).dialog( {
			dialogClass:	'wp-dialog', 
			modal:			true
		} );
	}
} ); // end ready()