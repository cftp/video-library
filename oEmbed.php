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

class oEmbed {

	public $url         = null;
	public $provider    = null;

	public function __construct( $url ) {

		$this->url      = $url;
		$this->provider = VideoLibrary::init()->get_oembed_provider( $url );

	}

	public function fetch_details( array $args = null ) {

		if ( ! $this->url or ! $this->provider )
			return false;

		$args = wp_parse_args( $args, wp_embed_defaults() );

		return _wp_oembed_get_object()->fetch( $this->provider, $this->url, $args );

	}

}
