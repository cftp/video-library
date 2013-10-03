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

class VideoLibrary extends Plugin {

	public $providers = array();

	protected function __construct( $file ) {

		# Actions
		add_action( 'plugins_loaded', array( $this, 'action_plugins_loaded' ) );

		# Parent setup:
		parent::__construct( $file );

	}

	public static function init( $file = null ) {
		static $instance = null;

		if ( !$instance )
			$instance = new VideoLibrary( $file );

		return $instance;

	}

	public function get_oembed_provider( $url, $post_type ) {

		$provider = false;

		if ( ! trim( $url ) )
			return $provider;

		$providers = $this->get_oembed_providers();

		if ( ! isset( $providers[$post_type] ) or empty( $providers[$post_type] ) )
			return $provider;

		# See http://core.trac.wordpress.org/ticket/24381

		foreach ( $providers[$post_type] as $matchmask => $data ) {
			list( $providerurl, $regex ) = $data;

			// Turn the asterisk-type provider URLs into regex
			if ( !$regex ) {
				$matchmask = '#' . str_replace( '___wildcard___', '(.+)', preg_quote( str_replace( '*', '___wildcard___', $matchmask ), '#' ) ) . '#i';
				$matchmask = preg_replace( '|^#http\\\://|', '#https?\://', $matchmask );
			}

			if ( preg_match( $matchmask, $url ) ) {
				$provider = str_replace( '{format}', 'json', $providerurl ); // JSON is easier to deal with than XML
				break;
			}
		}

		return $provider;

	}

	public function get_oembed_providers() {

		if ( !empty( $this->providers ) )
			return $this->providers;

		require_once ABSPATH . WPINC . '/class-oembed.php';

		# This is a subset of WordPress' supported oEmbed providers. We're limiting this
		# list to video services, however any oEmbed provider will work. 
		$services = apply_filters( 'video_library_services', array(
			'youtube.com'     => 'YouTube',
			'blip.tv'         => 'Blip.tv',
			'vimeo.com'       => 'Vimeo',
			'dailymotion.com' => 'Dailymotion',
			'flickr.com'      => 'Flickr',
			'hulu.com'        => 'Hulu',
			'viddler.com'     => 'Viddler',
			'qik.com'         => 'Qik',
			'wordpress.tv'    => 'WordPress.tv',
		) );

		foreach ( $this->get_post_types() as $post_type ) {

			$support = get_all_post_type_supports( $post_type );

			if ( is_array( $support['video-library'] ) )
				$post_type_services = reset( $support['video-library'] );
			else
				$post_type_services = $services;

			foreach ( _wp_oembed_get_object()->providers as $matchmask => $data ) {
				foreach ( $post_type_services as $domain => $service ) {
					if ( false !== strpos( $data[0], $domain ) )
						$this->providers[$post_type][$matchmask] = $data;
				}
			}

		}

		return $this->providers;

	}

	public function action_plugins_loaded() {

		$this->structure = new Structure;

		if ( is_admin() )
			$this->admin = new Admin;
		else
			$this->template = new Template;

	}

	public function get_post_types() {
		
		$post_types = array();

		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'video-library' ) )
				$post_types[] = $post_type;
		}

		return $post_types;

	}

}
