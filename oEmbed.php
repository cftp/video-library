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

	public function __construct( $url, $post_type ) {

		$this->url      = $url;
		$this->provider = VideoLibrary::init()->get_oembed_provider( $url, $post_type );

	}

	public function fetch_details( array $args = null ) {

		if ( ! $this->url or ! $this->provider )
			return false;

		$args = wp_parse_args( $args, wp_embed_defaults() );

		$details = _wp_oembed_get_object()->fetch( $this->provider, $this->url, $args );

		if ( $details )
			$details->html = $this->get_html( $details );

		return $details;

	}

	public function get_html( \stdClass $details ) {

		if ( isset( $details->html ) and ! empty( $details->html ) )
			return $details->html;

		switch ( $details->type ) {

			case 'photo':
				if ( isset( $details->web_page ) and ! empty( $details->web_page ) ) {
					return sprintf( '<a href="%s"><img src="%s" alt="" /></a>',
						esc_url( $details->web_page ),
						esc_url( $details->url )
					);
				} else {
					return sprintf( '<img src="%s" alt="" />',
						esc_url( $details->url )
					);
				}
				break;

			case 'link':
				return sprintf( '<a href="%s">%s</a>',
					esc_url( $details->url ),
					esc_html( $details->title )
				);
				break;

		}

		return null;

	}

}
