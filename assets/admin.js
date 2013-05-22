/*
Copyright © 2013 Code for the People Ltd

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

jQuery( function( $ ) {

	$( '#video-library-url-div' ).insertBefore( '#titlediv' );

	if ( !$( '#video-library-url' ).val() )
		$( '#video-library-url' ).focus();

	$( '#video-library-url' ).on( 'paste drop', function() {

		_.defer( function( el ) {

			var url = $.trim( $(el).val() );
			var args = {
				action  : 'video_library_fetch',
				url     : url,
				post_id : vl.post.id
			};

			if ( !url )
				return;

			// show spinner
			$( '#video-library-url-div .favicon' ).hide();
			$( '#video-library-url-div .spinner' ).show();

			$.post( ajaxurl, args, function( response ) {

				// hide spinner
				$( '#video-library-url-div .spinner' ).hide();

				if ( response.success ) {

					// set thumbnail if response.data.imported_thumbnail_id exists
					if ( response.data.imported_thumbnail_id ) {
						wp.media.post( 'set-post-thumbnail', {
							json:         true,
							post_id:      vl.post.id,
							_wpnonce:     vl.post.nonce,
							thumbnail_id: response.data.imported_thumbnail_id
						}).done( function( html ) {
							$( '.inside', '#postimagediv' ).html( html );
						});
					}

					if ( response.data.favicon )
						$( '#video-library-url-div .favicon' ).css( 'background-image', 'url("' + response.data.favicon + '")' ).show();

					// @TODO set content if content field is empty

					// set title if title field is empty
					if ( !$( '#title' ).val() ) {
						$( '#title-prompt-text' ).addClass( 'screen-reader-text' );
						$( '#title' ).val( response.data.title );
					}

					// display preview using response html
					$( '#video-library-preview' ).html( response.data.html );

				} else if ( response.data.error_message ) {

					alert( response.data.error_message );

				}

			}, 'json' );

		}, [ this ] );

	} );

	$( '#postimagediv .inside' ).append( '<p class="description">' + vl.featured_image_note + '</p>' );

} );
