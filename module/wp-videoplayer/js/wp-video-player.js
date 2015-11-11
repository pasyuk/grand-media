/*
 * Title      : WP Video Player Module for Gmedia Gallery plugin
 * Version    : 1.4
 * Copyright  : 2015 CodEasily.com
 * Website    : http://www.codeasily.com
 */
/*globals window, document, jQuery, _, Backbone, _wpmejsSettings */

(function ($, _, Backbone) {
	"use strict";

	var WPPlaylistView = Backbone.View.extend({
		initialize : function (options) {
			this.index = 0;
			this.settings = {};
			this.data = options.metadata || $.parseJSON( this.$('script').html() );
			this.playerNode = this.$( this.data.type );

			this.tracks = new Backbone.Collection( this.data.tracks );
			this.current = this.tracks.first();

			if ( 'audio' === this.data.type ) {
				this.currentTemplate = wp.template( 'wp-playlist-current-item' );
				this.currentNode = this.$( '.wp-playlist-current-item' );
			}

			if ( this.data.tracklist ) {
				this.itemTemplate = wp.template( 'wp-playlist-item' );
				this.playingClass = 'wp-playlist-playing';
				this.renderTracks();
			}

			this.playerNode.attr( 'src', this.current.get( 'src' ) );

			_.bindAll( this, 'bindPlayer', 'bindResetPlayer', 'setPlayer', 'ended', 'clickTrack' );

			if ( ! _.isUndefined( window._wpmejsSettings ) ) {
				this.settings.pluginPath = _wpmejsSettings.pluginPath;
			}
			this.settings.success = this.bindPlayer;
			this.setPlayer();

			this.renderCurrent();
		},

		bindPlayer : function (mejs) {
			this.mejs = mejs;
			this.mejs.addEventListener( 'ended', this.ended );
		},

		bindResetPlayer : function (mejs) {
			this.bindPlayer( mejs );
			this.playCurrentSrc();
		},

		setPlayer: function (force) {
			if ( this.player ) {
				this.player.pause();
				this.player.remove();
				this.playerNode = this.$( this.data.type );
			}

			if (force) {
				this.playerNode.attr( 'src', this.current.get( 'src' ) );
				this.settings.success = this.bindResetPlayer;
			}

			/**
			 * This is also our bridge to the outside world
			 */
			this.player = new MediaElementPlayer( this.playerNode.get(0), this.settings );
		},

		playCurrentSrc : function () {
			this.renderCurrent();
			if(this.mejs){
				this.mejs.setSrc(this.playerNode.attr('src'));
				this.mejs.load();
				this.mejs.play();
			}
		},

		renderCurrent : function () {
			var dimensions, defaultImage = 'wp-includes/images/media/video.png';
			if ( 'video' === this.data.type ) {
				if ( this.data.images && this.current.get( 'image' ) && -1 === this.current.get( 'image' ).src.indexOf( defaultImage ) ) {
					this.playerNode.attr( 'poster', this.current.get( 'image' ).src );
				}
				dimensions = this.current.get( 'dimensions' ).resized;
				this.playerNode.attr( dimensions );
				var cannotplay = this.playerNode.parent().find('.me-cannotplay');
				if(cannotplay.length){
					cannotplay.css({'background':'rgba(255, 255, 255, 0.95) url('+this.current.get( 'image' ).src+') 50% 50% no-repeat', 'height':'100%'}).find('a').attr({'href':this.playerNode.attr('src'), 'download':'download'}).css({'color':'#2e6286'});
				}
			} else {
				if ( ! this.data.images ) {
					this.current.set( 'image', false );
				}
				this.currentNode.html( this.currentTemplate( this.current.toJSON() ) );
			}
		},

		renderTracks : function () {
			var self = this, i = 1, tracklist = $( '<div class="wp-playlist-tracks"></div>' );
			this.tracks.each(function (model) {
				if ( ! self.data.images ) {
					model.set( 'image', false );
				}
				model.set( 'artists', self.data.artists );
				model.set( 'index', self.data.tracknumbers ? i : false );
				tracklist.append( self.itemTemplate( model.toJSON() ) );
				i += 1;
			});
			this.$el.append( tracklist );

			this.$( '.wp-playlist-item' ).eq(0).addClass( this.playingClass );
		},

		events : {
			'click .wp-playlist-item' : 'clickTrack',
			'click .wp-playlist-next' : 'next',
			'click .wp-playlist-prev' : 'prev'
		},

		clickTrack : function (e) {
			e.preventDefault();

			this.index = this.$( '.wp-playlist-item' ).index( e.currentTarget );
			this.setCurrent();
		},

		ended : function () {
			if ( this.index + 1 < this.tracks.length ) {
				this.next();
			} else {
				this.index = 0;
				this.current = this.tracks.at( this.index );
				this.loadCurrent();
			}
		},

		next : function () {
			this.index = this.index + 1 >= this.tracks.length ? 0 : this.index + 1;
			this.setCurrent();
		},

		prev : function () {
			this.index = this.index - 1 < 0 ? this.tracks.length - 1 : this.index - 1;
			this.setCurrent();
		},

		loadCurrent : function () {
			var last = this.playerNode.attr( 'src' ) && this.playerNode.attr( 'src' ).split('.').pop(),
				current = this.current.get( 'src' ).split('.').pop();

			this.mejs && this.mejs.pause();

			if ( last !== current ) {
				this.setPlayer( true );
			} else {
				this.playerNode.attr( 'src', this.current.get( 'src' ) );
				this.playCurrentSrc();
			}
		},

		setCurrent : function () {
			this.current = this.tracks.at( this.index );

			if ( this.data.tracklist ) {
				this.$( '.wp-playlist-item' )
					.removeClass( this.playingClass )
					.eq( this.index )
					.addClass( this.playingClass );
			}

			this.loadCurrent();
		}
	});

	$(document).ready(function () {
		if(!$('#tmpl-wp-playlist-current-item').length){
			$('body').append('<script type="text/html" id="tmpl-wp-playlist-current-item"> <# if ( data.image ) { #><img src="{{ data.thumb.src }}"/> <# } #> <div class="wp-playlist-caption"> <span class="wp-playlist-item-meta wp-playlist-item-title">&#8220;{{ data.title }}&#8221;</span> <# if ( data.meta.album ) { #><span class="wp-playlist-item-meta wp-playlist-item-album">{{ data.meta.album }}</span><# } #> <# if ( data.meta.artist ) { #><span class="wp-playlist-item-meta wp-playlist-item-artist">{{ data.meta.artist }}</span><# } #> </div> </script> <script type="text/html" id="tmpl-wp-playlist-item"> <div class="wp-playlist-item"> <a class="wp-playlist-caption" href="{{ data.src }}"> {{ data.index ? ( data.index + ". " ) : "" }} <# if ( data.caption ) { #> {{ data.caption }} <# } else { #> <span class="wp-playlist-item-title">&#8220;{{{ data.title }}}&#8221;</span> <# if ( data.artists && data.meta.artist ) { #> <span class="wp-playlist-item-artist"> &mdash; {{ data.meta.artist }}</span> <# } #> <# } #> </a> <# if ( data.meta.length_formatted ) { #> <div class="wp-playlist-item-length">{{ data.meta.length_formatted }}</div> <# } #> </div> </script>');
		}
		$('.gmedia-wp-playlist').each(function () {
			return new WPPlaylistView({ el: this });
		});
	});

	window.WPPlaylistView = WPPlaylistView;

}(jQuery, _, Backbone));