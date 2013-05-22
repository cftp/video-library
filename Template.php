<?php
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

namespace VideoLibrary;

class Template {

	public function __construct() {

		add_filter( 'the_content', array( $this, 'filter_the_content' ), 999 );

	}

	function filter_the_content( $content ) {

		if ( ! is_singular() )
			return $content;
		
		if ( function_exists( 'bbl_get_base_post_type' ) )
			$type = bbl_get_base_post_type( get_post_type() );
		else
			$type = get_post_type();
		
		if ( 'video' != $type )
			return $content;

		$media = new PostMedia( get_the_ID() );

		if ( ! $code = $media->get_embed_code() )
			return $content;	

		$html = sprintf( '<div class="video-library video-library-%1$s">%2$s</div>',
			esc_attr( $media->get_field( 'type' ) ),
			$code
		);

		return $html . $content;
		
	}
	 
}
