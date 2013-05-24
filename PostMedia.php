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

class PostMedia {

	public $post    = null;
	public $url     = null;
	public $details = null;
	public $oembed  = null;

	public function __construct( $post_id ) {

		$this->post = get_post( $post_id );
		$this->url  = $this->get_meta_field();

	}

	public function get_embed_code( array $args = null ) {

		if ( ! $codes = get_post_meta( $this->post->ID, '_video-library-embed-codes', true ) )
			$codes = array();

		$args = wp_parse_args( $args, wp_embed_defaults() );

		$embed_key = md5( serialize( $args ) );

		if ( ! isset( $codes[$embed_key] ) ) {

			if ( ! $details = $this->oembed()->fetch_details( $args ) )
				return null;

			$codes[$embed_key] = $details->html;
			update_post_meta( $this->post->ID, '_video-library-embed-codes', $codes );

		}

		return $codes[$embed_key];

	}

	public function get_field( $field ) {

		if ( ! $details = $this->get_details() )
			return null;

		if ( 'html' == $field )
			return $this->get_embed_code();

		if ( ! isset( $details->$field ) )
			return null;

		return $details->$field;

	}

	public function get_details() {

		return get_post_meta( $this->post->ID, '_video-library-details', true );

	}

	public function update_details() {

		$args = wp_embed_defaults();

		if ( ! $details = $this->oembed()->fetch_details( $args ) )
			return false;

		$this->details = $details;

		$embed_key = md5( serialize( $args ) );

		update_post_meta( $this->post->ID, '_video-library-details', $details );
		update_post_meta( $this->post->ID, '_video-library-embed-codes', array(
			$embed_key => $details->html
		) );

		return $this->details;

	}

	public function get_url() {

		return $this->get_meta_field();

	}

	public function update_url( $url ) {

		if ( empty( $url ) )
			return false;

		$url = esc_url_raw( $url );

		if ( ! VideoLibrary::init()->get_oembed_provider( $url, $this->post->post_type ) )
			return false;

		$this->url = $url;

		update_post_meta( $this->post->ID, '_video-library-url', $url );
		delete_post_meta( $this->post->ID, '_extmedia-youtube' );

		return true;

	}

	public function delete_url() {

		$this->url = null;

		delete_post_meta( $this->post->ID, '_video-library-url' );
		delete_post_meta( $this->post->ID, '_extmedia-youtube' );

		return true;

	}

	protected function oembed() {

		if ( ! $this->oembed )
			$this->oembed = new oEmbed( $this->url, $this->post->post_type );

		return $this->oembed;

	}

	protected function get_meta_field() {

		$meta = get_post_meta( $this->post->ID, '_video-library-url', true );

		if ( empty( $meta ) ) {

			# If we don't have a URL, fall back to looking for a YouTube ID (CPT External Media v1):
			if ( $yt = get_post_meta( $this->post->ID, '_extmedia-youtube', true ) ) {
				$meta = sprintf( 'http://www.youtube.com/watch?v=%s', $yt );
				$this->update_url( $meta );
				$this->update_details();
			}

		}

		if ( !empty( $meta ) )
			return esc_url_raw( $meta );
		else
			return null;

	}

}
