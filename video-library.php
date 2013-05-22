<?php
/*
Plugin Name: Video Library
Plugin URI:  https://github.com/cftp/video-library
Description: Creates a new custom post type for exernal videos
Author:      Code For The People
Version:     2.0
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

spl_autoload_register( function( $class ) {

	if ( false === strpos( $class, 'VideoLibrary' ) )
		return;

	$name = ltrim( $class, '\\' );
	$name = str_replace( array( '\\', '_' ), '/', $name );
	$name = preg_replace( '|^VideoLibrary/|', '', $name );

	$file = sprintf( '%1$s/%2$s.php',
		dirname( __FILE__ ),
		$name
	);

	if ( is_readable( $file ) )
		include $file;

} );

# Load required files
require_once ABSPATH . WPINC . '/class-oembed.php';

# Load services
foreach ( glob( dirname( __FILE__ ) . '/services/*.php' ) as $service )
	include $service;

# Go!
\VideoLibrary\VideoLibrary::init( __FILE__ );
