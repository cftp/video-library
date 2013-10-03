<?php
/*
Plugin Name: Flickr Library
Description: Creates a new custom post type for Flickr images. Uses the Video Library plugin.
Author:      Code For The People
Version:     1.0
Author URI:  http://codeforthepeople.com/
Text Domain: video-library
Domain Path: /languages/
License:     GPL v2 or later

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

defined( 'ABSPATH' ) or die();

class VideoLibraryFlickr {

	public function __construct() {

		add_action( 'init', array( $this, 'action_init' ) );

	}

	public function action_init() {

		register_post_type( 'flickr', array(
			'labels' => array(
				'name'               => _x( 'Flickr', 'post type general name', 'flickr-library' ),
				'singular_name'      => _x( 'Flickr', 'post type singular name', 'flickr-library' ),
				'add_new_item'       => __( 'Add New Flickr Image', 'flickr-library' ),
				'edit_item'          => __( 'Edit Flickr Image', 'flickr-library' ),
				'new_item'           => __( 'New Flickr Image', 'flickr-library' ),
				'view_item'          => __( 'View Flickr Image', 'flickr-library' ),
				'search_items'       => __( 'Search Flickr Images', 'flickr-library' ),
				'not_found'          => __( 'No Flickr images found', 'flickr-library' ),
				'not_found_in_trash' => __( 'No Flickr images found in trash', 'flickr-library' ),
				'parent_item_colon'  => __( 'Parent Flickr Image', 'flickr-library' ),
				'all_items'          => __( 'All Flickr Images', 'flickr-library' ),
			),
			'public' => true,
			'rewrite' => array(
				'slug'       => 'images',
				'with_front' => false,
			),
			'capability_type' => 'page',
			'menu_position' => 17,
			'supports' => array(
				'title',
				'editor',
				'thumbnail',
				'video-library-prepend',
			),
			'has_archive' => true
		) );

		add_post_type_support( 'flickr', 'video-library', array(
			'flickr.com' => 'Flickr'
		) );

	}

}

new VideoLibraryFlickr;
