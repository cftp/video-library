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

class Admin {

	public $no_recursion = false;

	public $preview_args = array(
		'width'  => 256, /* fits admin area meta box */
		'height' => 512
	);

	public function __construct() {

		# Filters
		add_filter( 'manage_posts_columns',         array( $this, 'filter_posts_columns' ), 10, 2 );
		add_filter( 'wp_insert_post_empty_content', array( $this, 'filter_post_empty_content' ), 10, 2 );

		# Actions
		add_action( 'edit_form_after_title',        array( $this, 'action_edit_form_after_title' ) );
		add_action( 'add_meta_boxes',               array( $this, 'action_add_meta_boxes' ), 10, 2 );
		add_action( 'right_now_content_table_end',  array( $this, 'action_right_now' ) );
		add_action( 'save_post',                    array( $this, 'action_save_post' ), 10, 2 );
		add_action( 'load-post.php',                array( $this, 'action_load_post' ) );
		add_action( 'load-post-new.php',            array( $this, 'action_load_post' ) );
		add_action( 'admin_enqueue_scripts',        array( $this, 'enqueue_styles' ) );

		# AJAX Actions:
		add_action( 'wp_ajax_video_library_fetch',  array( $this, 'ajax_fetch' ) );

	}

	public function ajax_fetch() {

		# @TODO correct cap
		if ( ! current_user_can( 'edit_posts' ) )
			die( '-1' );

		if ( ! isset( $_POST['post_id'] ) or ! $post = get_post( absint( $_POST['post_id'] ) ) ) {
			wp_send_json_error( array(
				'error_code'    => 'no_post',
				'error_message' => __( 'Invalid post ID.', 'video-library' )
			) );
		}

		if ( ! isset( $_POST['url'] ) or ! $url = trim( stripslashes( $_POST['url'] ) ) ) {
			wp_send_json_error( array(
				'error_code'    => 'no_url',
				'error_message' => __( 'No URL entered.', 'video-library' )
			) );
		}

		$url = esc_url_raw( $url );

		if ( ! VideoLibrary::init()->get_oembed_provider( $url, $post->post_type ) ) {
			wp_send_json_error( array(
				'error_code'    => 'no_provider',
				'error_message' => __( 'The URL you entered is not supported.', 'video-library' )
			) );
		}

		$oembed = new oEmbed( $url, $post->post_type );

		if ( ! $details = $oembed->fetch_details( $this->preview_args ) ) {
			wp_send_json_error( array(
				'error_code'    => 'no_results',
				'error_message' => __( 'Item details could not be fetched. Please try again shortly.', 'video-library' )
			) );
		}

		if ( post_type_supports( $post->post_type, 'thumbnail' ) and ! has_post_thumbnail( $post->ID ) ) {

			switch ( $details->type ) {

				case 'photo':
					$field = 'url';
					break;

				case 'rich':
				case 'link':
				case 'video':
					$field = 'thumbnail_url';
					break;

			}

			if ( isset( $details->$field ) and $details->$field ) {

				$filename = $post->ID . '-' . basename( $details->$field );
				$photo    = new ExternalPhoto( $details->$field );

				if ( $photo->import( $filename ) ) {
					$photo->attach_to( $post );
					$details->imported_thumbnail    = $photo->get_attachment();
					$details->imported_thumbnail_id = $photo->get_attachment_ID();
				}

			}

		}

		if ( $details->provider_url )
			$details->favicon = $this->favicon( $details->provider_url );

		wp_send_json_success( $details );

	}

	public function filter_post_empty_content( $maybe_empty, $post_arr ) {

		$post_type = $post_arr['post_type'];

		if ( function_exists('bbl_get_base_post_type') )
			$post_type = bbl_get_base_post_type( $post_type );

		if ( post_type_supports( $post_type, 'video-library' ) )
			return false;

		return $maybe_empty;

	}

	public function filter_posts_columns( $columns, $post_type ) {

		# Replace the 'name' label with 'singular_name'
		if ( isset( $columns['taxonomy-mediasource'] ) )
			$columns['taxonomy-mediasource'] = get_taxonomy( 'mediasource' )->labels->singular_name;

		return $columns;

	}

	public function action_add_meta_boxes( $post_type, $post ) {

		if ( ! post_type_supports( $post_type, 'video-library' ) )
			return;

		add_meta_box(
			'video-library-meta',
			__( 'Preview', 'video-library' ),
			array( $this, 'metabox_meta' ),
			$post_type,
			'side'
		);

	}

	public function action_load_post() {

		$post_type = get_current_screen()->post_type;

		if ( function_exists('bbl_get_base_post_type') )
			$post_type = bbl_get_base_post_type( $post_type );

		if ( ! post_type_supports( $post_type, 'video-library' ) )
			return;

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}

	public function enqueue_styles() {

		$vl = VideoLibrary::init();

		wp_enqueue_style(
			'video-library',
			$vl->plugin_url( 'assets/admin.css' ),
			array( 'wp-admin' ),
			$vl->plugin_ver( 'assets/admin.css' )
		);

	}

	public function enqueue_scripts() {

		$vl = VideoLibrary::init();

		wp_enqueue_script(
			'video-library',
			$vl->plugin_url( 'assets/admin.js' ),
			array( 'backbone', 'jquery' ),
			$vl->plugin_ver( 'assets/admin.js' )
		);

		wp_localize_script(
			'video-library',
			'vl',
			array(
				'featured_image_note' => __( 'If no featured image is specified, the video thumbnail will be fetched automatically.', 'video-library' ),
				'post' => array(
					'id'    => get_the_ID(),
					'nonce' => wp_create_nonce( 'update-post_' . get_the_ID() ),
				),
			)
		);

	}

	public function action_edit_form_after_title() {

		$post_type = get_current_screen()->post_type;

		if ( function_exists( 'bbl_get_base_post_type' ) )
			$post_type = bbl_get_base_post_type( $post_type );

		if ( ! post_type_supports( $post_type, 'video-library' ) )
			return;

		$post  = get_post( get_the_ID() );
		$media = new PostMedia( $post );

		if ( $provider_url = $media->get_field( 'provider_url' ) )
			$favicon = $this->favicon( $provider_url );
		else
			$favicon = '';

		wp_nonce_field( "video-library-{$post->ID}", '_video_library_nonce' );

		// Here we're adding our faux-metabox for the video URL field

		?>
		<div id="video-library-url-div">
			<div id="video-library-url-wrap">

				<input type="text" placeholder="<?php esc_attr_e( 'Enter video page URL here', 'video-library' ); ?>" name="video-library-url" size="30" value="<?php echo esc_url( $media->get_url() ); ?>" id="video-library-url" autocomplete="off" />
				<span class="spinner"></span>
				<?php if ( $favicon ) { ?>
					<span class="favicon" style="background-image: url('<?php echo $favicon; ?>')"></span>
				<?php } else { ?>
					<span class="favicon"></span>
				<?php } ?>

			</div>
		</div>
		<?php

	}

	public function metabox_meta( $post, $args ) {

		$media = new PostMedia( $post );

		?>
		<div id="video-library-preview">
			<?php echo $media->get_embed_code( $this->preview_args ); ?>
		</div>
		<?php

	}

	public function action_save_post( $post_id, $post ) {

		if ( $this->no_recursion )
			return;

		$post_type = $post->post_type;

		if ( function_exists( 'bbl_get_base_post_type' ) )
			$post_type = bbl_get_base_post_type( $post->post_type );

		if ( ! post_type_supports( $post_type, 'video-library' ) )
			return;

		// Check that the fields were included on the screen, we
		// can do this by checking for the presence of the nonce.
		if ( !isset( $_POST[ '_video_library_nonce' ] ) )
			return;

		// While we're at it, let's check the nonce
		check_admin_referer( "video-library-{$post->ID}", '_video_library_nonce' );
		
		$media = new PostMedia( $post );

		$url = trim( stripslashes( $_POST['video-library-url'] ) );

		if ( ! empty( $url ) ) {

			if ( ! $media->update_url( $url ) ) {
				# @TODO set admin error
				return;
			}

			$media->update_details();

			if ( post_type_supports( $post_type, 'thumbnail' ) and ! has_post_thumbnail( $post->ID ) ) {

				switch ( $details->type ) {

					case 'photo':
						$field = 'url';
						break;

					case 'rich':
					case 'link':
					case 'video':
						$field = 'thumbnail_url';
						break;

				}

				if ( $thumbnail_url = $media->get_field( $field ) ) {

					$filename = $post->post_name . '-' . basename( $thumbnail_url );
					$photo    = new ExternalPhoto( $thumbnail_url );

					if ( $photo->import( $filename ) ) {
						$photo->attach_to( $post );
						set_post_thumbnail( $post->ID, $photo->get_attachment_ID() );
					}

				}

			}

			if ( empty( $post->post_title ) or empty( $post->post_content ) ) {

				$update = array( 'ID' => $post->ID );

				if ( empty( $post->post_title ) )
					$update['post_title'] = $media->get_field( 'title' );
				if ( empty( $post->post_content ) )
					$update['post_content'] = make_clickable( $media->get_field( 'description' ) );

				$this->no_recursion = true;
				wp_update_post( $update );
				$this->no_recursion = false;

			}

			if ( $provider_name = $media->get_field( 'provider_name' ) )			
				wp_set_post_terms( $post->ID, $provider_name, 'mediasource', false );

		} else {

			$media->delete_url();

			# @TODO delete associated terms

		}

	}

	public function action_right_now() {

		$num_video = wp_count_posts( 'video' );
		$num = number_format_i18n( $num_video->publish );
		$text = _n( 'Video', 'Videos', $num_video->publish, 'video-library' );
		# @TODO correct cap:
		if ( current_user_can( 'edit_posts' ) ) {
			$num = "<a href='edit.php?post_type=video'>$num</a>";
			$text = "<a href='edit.php?post_type=video'>$text</a>";
		}
		echo '<td class="first b b_video">' . $num . '</td>';
		echo '<td class="t video">' . $text . '</td>';
		echo '</tr>';

	}

	public function favicon( $url ) {

		# YouTube's favicon looks like a play button, so we'll override it
		if ( false !== strpos( $url, 'youtube' ) )
			return VideoLibrary::init()->plugin_url( 'assets/youtube.png' );

		return set_url_scheme( sprintf( 'http://www.google.com/s2/favicons?domain=%s', urlencode( $url ) ) );

	}

}
