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

// Video model

var VideoModel = Backbone.Model.extend({

	initialize : function() {
		this.on( 'change:imported_thumbnail_id', this.setThumb, this );
	},

	setVideo : function( url ) {

		this.set( 'url', url );
		this.trigger( 'doLoading' );

		jQuery.post( ajaxurl, {

			action  : 'video_library_fetch',
			url     : url,
			post_id : this.get('post').id

		}, _.bind( function( response ) {

			this.trigger( 'doLoaded' );

			if ( response.success ) {

				// We could short-circuit this with `this.set( response.data )` but the below adds clarity
				if ( response.data.imported_thumbnail_id )
					this.set( 'imported_thumbnail_id', response.data.imported_thumbnail_id );
				if ( response.data.favicon )
					this.set( 'favicon', response.data.favicon );
				if ( response.data.title )
					this.set( 'title', response.data.title );
				if ( response.data.html )
					this.set( 'html', response.data.html );

			} else if ( response.data.error_message ) {

				this.trigger( 'doError', response.data.error_message )

			}

		}, this ), 'json' );

	},

	setThumb : function( model, value ) {

		wp.media.post( 'set-post-thumbnail', {
			json         : true,
			post_id      : this.get('post').id,
			_wpnonce     : this.get('post').nonce,
			thumbnail_id : value
		} ).done( _.bind( function( html ) {
			this.set( 'imported_thumbnail_markup', html );
		}, this ) );

	}

});

// Video input view

var VideoInput = Backbone.View.extend({

	events : {
		'paste :input[name="video-library-url"]' : 'doChange',
		'drop :input[name="video-library-url"]'  : 'doChange'
	},

	initialize : function ( options ) {

		this.model.on( 'doLoading', this.doLoading, this );
		this.model.on( 'doLoaded',  this.doLoaded,  this );
		this.model.on( 'doError',   this.doError,   this );

	},

	doChange : function( event ) {

		_.defer( _.bind( function( event ) {

			var url = this.$( event ).val();

			if ( !url )
				return;

			this.model.setVideo( url );

		}, this ), [ event.currentTarget ] );

	},

	doLoading : function() {
		this.$( '.favicon' ).hide();
		this.$( '.spinner' ).show();
	},

	doLoaded : function() {
		this.$( '.spinner' ).hide();
	},

	doError : function( message ) {
		alert( message );
	}

});

// Video thumbnail view

var VideoThumbnail = Backbone.View.extend({

	initialize : function ( options ) {

		this.$( '.inside' ).append( '<p class="description">' + options.note + '</p>' );

		this.model.on( 'change:imported_thumbnail_markup', this.setThumbnail, this );

	},

	setThumbnail : function( model, value ) {
		this.$( '.inside' ).html( value );
	}

});

// Video preview view

var VideoPreview = Backbone.View.extend({

	initialize : function ( options ) {
		this.model.on( 'change:html', this.setPreview, this );
	},

	setPreview : function( model, value ) {
		this.$('.previewer').html( value );
	}

});

// Video title view

var VideoTitle = Backbone.View.extend({

	initialize : function ( options ) {
		this.model.on( 'change:title', this.setTitle, this );
	},

	setTitle : function( model, value ) {
		if ( !this.$( '#title' ).val() ) {
			this.$( '#title-prompt-text' ).addClass( 'screen-reader-text' );
			this.$( '#title' ).val( value );
		}
	}

});

// Video favicon view

var VideoFavicon = Backbone.View.extend({

	initialize : function ( options ) {
		this.model.on( 'change:favicon', this.setFavicon, this );
	},

	setFavicon : function( model, value ) {
		this.$( '.favicon' ).css( 'background-image', 'url("' + value + '")' ).show();
	}

});



jQuery( function( $ ) {

	var vid = new VideoModel({
		post : vl.post
	});

	new VideoInput( {
		model : vid,
		el    : $( '#video-library-url-div' )
	} );
	new VideoThumbnail( {
		note  : vl.featured_image_note,
		model : vid,
		el    : $( '#postimagediv' )
	} );
	new VideoPreview( {
		model : vid,
		el    : $( '#video-library-preview' )
	} );
	new VideoTitle( {
		model : vid,
		el    : $( '#titlediv' )
	} );
	new VideoFavicon( {
		model : vid,
		el    : $( '#video-library-url-div' )
	} );

	/* Shift the URL field above the title field */
	$( '#video-library-url-div' ).insertBefore( '#titlediv' );

	/* Focus the URL field */
	if ( !$( '#video-library-url' ).val() )
		$( '#video-library-url' ).focus();

} );
