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

class Structure {

	public function __construct() {

		add_action( 'init', array( $this, 'action_init' ) );

	}

	public function action_init() {

		register_post_type( 'video', apply_filters( 'video_library_post_type_args', array(
			'labels' => array(
				# @TODO other labels:
				'name'               => _x( 'Videos', 'post type general name', 'video-library' ),
				'singular_name'      => _x( 'Video', 'post type singular name', 'video-library' ),
				'add_new_item'       => __( 'Add New Video', 'video-library' ),
				'edit_item'          => __( 'Edit Video', 'video-library' ),
				'new_item'           => __( 'New Video', 'video-library' ),
				'view_item'          => __( 'View Video', 'video-library' ),
				'search_items'       => __( 'Search Videos', 'video-library' ),
				'not_found'          => __( 'No videos found', 'video-library' ),
				'not_found_in_trash' => __( 'No videos found in trash', 'video-library' ),
				'parent_item_colon'  => __( 'Parent Video', 'video-library' ),
				'all_items'          => __( 'All Videos', 'video-library' ),
			),
			'public' => true,
			'rewrite' => array(
				'slug'       => 'media', # @TODO <- "videos"?
				'with_front' => false,
			),
			'capability_type' => 'page',
			'menu_position' => 17,
			'supports' => array(
				'title',
				'excerpt',
				'editor',
				'thumbnail',
				'comments',
			),
			'has_archive' => true
		) ) );

		register_taxonomy( 'mediasource', 'video', apply_filters( 'video_library_mediasource_args', array(
			'public' => true,
			'hierarchical' => false,
			'show_ui' => false,
			'show_admin_column' => true,
			'labels' => array(
				'name'          => __( 'Sources', 'video-library' ),
				'singular_name' => __( 'Source', 'video-library' ),
			)
		) ) );

	}

}
