/*
 * Title      : Music Player Module for Gmedia Gallery plugin
 * Version    : 2.7
 * Copyright  : 2013-2015 CodEasily.com
 * Website    : http://www.codeasily.com
 */
(function($) {
	$.fn.gmMusicPlayer = function(playlist, userOptions) {
		var $self = this, opt_str, opt_int, opt_bool, opt_obj, options, cssSelector, appMgr, playlistMgr, interfaceMgr, ratingsMgr,
				layout, ratings, myPlaylist, current,
			$uid = $(this).data('uid');
		cssSelector = {
			jPlayer: ".gm-music-player",
			jPlayerInterface: '.jqmp-'+$uid,
			playerPrevious: ".jp-interface .jp-previous",
			playerNext: ".jp-interface .jp-next",
			trackList:'.gmmp-tracklist',
			tracks:'.gmmp-tracks',
			track:'.gmmp-track',
			trackRating:'.gmmp-rating-bar',
			rating:'.gmmp-rating',
			ratingLevel:'.gmmp-rating-level',
			ratingLevelOn:'.gmmp-on',
			title: '.gmmp-track-title',
			text: '.gmmp-track-description',
			duration: '.gmmp-duration',
			button:'.gmmp-button',
			buttonNotActive:'.gmmp-not-active',
			playing:'.gmmp-playing',
			moreButton:'.gmmp-more',
			player:'.gmmp-player',
			//artist:'.gmmp-artist',
			//artistOuter:'.gmmp-artist-outer',
			albumCover:'.gmmp-img',
			description:'.gmmp-description',
			descriptionShowing:'.gmmp-showing'
		};

		opt_str = {
			width:'auto',
			buttonText:'Download',
			moreText:'View More...',
			ajaxurl:''
		};
		opt_int = {
			maxwidth:0,
			tracksToShow:5
		};
		opt_bool = {
			rating:true,
			autoplay:false
		};
		opt_obj = {
			jPlayer:{
				swfPath: userOptions.pluginUrl + '/assets/jplayer'
			}
		};

		options = $.extend(true, {}, opt_str, opt_int, opt_bool, opt_obj, userOptions);
		$.each(options, function(key, val){
			if(key in opt_bool){
				options[key] = (!(!val || val == '0' || val == 'false'));
			} else if(key in opt_int){
				options[key] = parseInt(val);
			}
		});

		myPlaylist = playlist;

		current = 0;

		appMgr = function() {
			playlist = new playlistMgr();
			layout = new interfaceMgr();

			layout.buildInterface();
			playlist.init(options.jPlayer);

			//don't initialize the ratings until the playlist has been built, which wont happen until after the jPlayer ready event
			$self.on('mbPlaylistLoaded', function() {
				if(options.rating){
					$self.on('mbInterfaceBuilt', function() {
						ratings = new ratingsMgr();
					});
				}
				layout.init();

			});
		};

		playlistMgr = function() {

			var playing = false, markup, $myJplayer = {},$tracks,$tracksWrapper, $more;

			markup = {
				listItem:'<li class="gmmp-track"><section>' +
										 '<span class="gmmp-maxwidth"><span class="gmmp-track-title-wrapper">&nbsp;<span class="gmmp-track-title"></span></span></span>' +
										 '<span>' +
											 '<span class="gmmp-duration">&nbsp;</span>' +
											 (options.rating ? '<span class="gmmp-rating"></span>' : '') +
											 '<a href="#" class="gmmp-button gmmp-not-active" target="_blank"></a>' +
										 '</span>' +
						'</section></li>',
				ratingBar:'<span class="gmmp-rating-level gmmp-rating-bar"></span>'
			};

			function init(playlistOptions) {
				$myJplayer = $('.gm-music-player .jPlayer-container', $self);


				var jPlayerDefaults, jPlayerOptions;

				jPlayerDefaults = {
					swfPath: "jplayer",
					supplied: "mp3, oga",
					cssSelectorAncestor:  cssSelector.jPlayerInterface,
					errorAlerts: false,
					warningAlerts: false
				};

				//apply any user defined jPlayer options
				jPlayerOptions = $.extend(true, {}, jPlayerDefaults, playlistOptions);

				$myJplayer.on($.jPlayer.event.ready, function() {

					//Bind jPlayer events. Do not want to pass in options object to prevent them from being overridden by the user
					$myJplayer.on($.jPlayer.event.ended, function(event) {
						playlistNext();
					});

					$myJplayer.on($.jPlayer.event.play, function(event) {
						$myJplayer.jPlayer("pauseOthers");
						$tracks.eq(current).addClass(attr(cssSelector.playing)).siblings().removeClass(attr(cssSelector.playing));
					});

					$myJplayer.on($.jPlayer.event.playing, function(event) {
						playing = true;
					});

					$myJplayer.on($.jPlayer.event.pause, function(event) {
						playing = false;
					});

					$myJplayer.on($.jPlayer.event.loadeddata, function(event) {
						if(event.jPlayer.status.duration != 'NaN'){
							$tracks.eq(current).find(cssSelector.duration).text($.jPlayer.convertTime( event.jPlayer.status.duration ));
						}
					});

					//Bind next/prev click events
					$(cssSelector.playerPrevious, $self).click(function() {
						playlistPrev();
						$(this).blur();
						return false;
					});

					$(cssSelector.playerNext, $self).click(function() {
						playlistNext();
						$(this).blur();
						return false;
					});

					$self.on('mbInitPlaylistAdvance', function(e) {
						var changeTo = this.getData('mbInitPlaylistAdvance');

						if (changeTo != current) {
							current = changeTo;
							playlistAdvance(current);
						}
						else {
							if (!$myJplayer.data('jPlayer').status.srcSet) {
								playlistAdvance(0);
							}
							else {
								togglePlay();
							}
						}
					});

					buildPlaylist();
					//If the user doesn't want to wait for widget loads, start playlist now
					$self.trigger('mbPlaylistLoaded');

					playlistInit(options.autoplay);

				});

				//Initialize jPlayer
				$myJplayer.jPlayer(jPlayerOptions);
			}

			function playlistInit(autoplay) {
				current = 0;

				if (autoplay) {
					playlistAdvance(current);
				}
				else {
					playlistConfig(current);
					$self.trigger('mbPlaylistInit');
				}
			}

			function playlistConfig(index) {
				current = index;
				$myJplayer.jPlayer("setMedia", myPlaylist[current]);
			}

			function playlistAdvance(index) {
				playlistConfig(index);

				if (index >= options.tracksToShow)
					showMore();

				$self.trigger('mbPlaylistAdvance');
				$myJplayer.jPlayer("play");
			}

			function playlistNext() {
				var index = (current + 1 < myPlaylist.length) ? current + 1 : 0;
				playlistAdvance(index);
			}

			function playlistPrev() {
				var index = (current - 1 >= 0) ? current - 1 : myPlaylist.length - 1;
				playlistAdvance(index);
			}

			function togglePlay() {
				if (!playing)
					$myJplayer.jPlayer("play");
				else $myJplayer.jPlayer("pause");
			}

			function buildPlaylist() {
				$tracksWrapper = $self.find(cssSelector.tracks);

				if(options.rating){
					var $ratings = $();
					//set up the html for the track ratings
					for (var i = 0; i < 10; i++)
						$ratings = $ratings.add(markup.ratingBar);
				}

				for (var j = 0; j < myPlaylist.length; j++) {
					var $track = $(markup.listItem, $self);

					$track.find(cssSelector.title).html(trackName(j));

					if(options.rating){
						//since $ratings refers to a specific object, if we just use .html($ratings) we would be moving the $rating object from one list item to the next
						$track.find(cssSelector.rating).html($ratings.clone());
						setRating('track', $track, j);
					}

					setLink($track, j);

					$track.data('index', j);

					$tracksWrapper.append($track);
				}

				$tracks = $(cssSelector.track, $self);

				$tracks.eq(options.tracksToShow - 1).nextAll().hide();

				if (options.tracksToShow < myPlaylist.length) {
					var $trackList = $(cssSelector.trackList, $self);

					$trackList.addClass('gmmp-show-more-button');

					$trackList.find(cssSelector.moreButton).click(function() {
						$more = $(this);

						showMore();
					});
				}

				$tracks.find(cssSelector.title).click(function() {
					playlistAdvance($(this).parents('li').data('index'));
				});
			}

			function showMore() {
				if (isUndefined($more))
					$more = $self.find(cssSelector.moreButton);

				$tracksWrapper.css('height', $tracksWrapper.height());
				$tracks.show();
				var tracks_height = $tracks.eq(0).outerHeight() * myPlaylist.length + 1;
				$tracksWrapper.animate({height: tracks_height}, 400);
				$more.removeClass('anim').animate({'height': 0}, 400, function() {
					$more.parents(cssSelector.trackList).removeClass('gmmp-show-more-button');
					$more.remove();
				});
			}

			function setLink($track, index) {
				if (myPlaylist[index].button !== '') {
					$track.find(cssSelector.button).removeClass(attr(cssSelector.buttonNotActive)).attr('href', myPlaylist[index].button).html(options.buttonText);
					var ext = myPlaylist[index].button.slice(-4);
					if(('.mp3' == ext) || ('.ogg' == ext) ){
						$track.find(cssSelector.button).attr('download', '');
					}
				}
			}

			return{
				init:init,
				playlistInit:playlistInit,
				playlistAdvance:playlistAdvance,
				playlistNext:playlistNext,
				playlistPrev:playlistPrev,
				togglePlay:togglePlay,
				$myJplayer:$myJplayer
			};

		};

		ratingsMgr = function() {

			var $tracks = $self.find(cssSelector.track);

			function bindEvents() {

				//Handler for when user hovers over a rating
				$(cssSelector.rating, $self).find(cssSelector.ratingLevel).hover(function() {
					$(this).addClass('gmmp-hover').prevAll().addClass('gmmp-hover').end().nextAll().removeClass('gmmp-hover');
				});

				//Restores previous rating when user is finished hovering (assuming there is no new rating)
				$(cssSelector.rating, $self).mouseleave(function() {
					$(this).find(cssSelector.ratingLevel).removeClass('gmmp-hover');
				});

				//Set the new rating when the user clicks
				$(cssSelector.ratingLevel, $self).click(function() {
					var $this = $(this), rating = $this.parent().children().index($this) + 1, index;

					if ($this.hasClass(attr(cssSelector.trackRating))) {
						rating = rating / 2;
						index = $this.parents('li').data('index');

						if (index == current)
							applyCurrentlyPlayingRating(rating);
					}
					else {
						index = current;
						applyTrackRating($tracks.eq(index), rating);
					}


					$this.prevAll().add($this).addClass(attr(cssSelector.ratingLevelOn)).end().end().nextAll().removeClass(attr(cssSelector.ratingLevelOn));

					if(options.ajaxurl){
						processRating(index, rating);
					}
				});
			}

			function processRating(index, rating) {
				myPlaylist[index].rating = rating;
				var gmid = myPlaylist[index].id,
					uip = userOptions.ip;
				$.post(options.ajaxurl, { action: 'gmedia_module_interaction', rate: {uip:uip,gmid:gmid,rate:rating} }, function(r){
					console.log(r);
				});

				//runCallback(options.ratingCallback, index, myPlaylist[index], rating);
			}

			bindEvents();
		};

		interfaceMgr = function() {

			var $player, $title, $text, $artist, $albumCover;


			function init() {
				$player = $(cssSelector.player, $self),
						$title = $player.find(cssSelector.title),
						$text = $player.find(cssSelector.text),
						//$artist = $player.find(cssSelector.artist),
						$albumCover = $player.find(cssSelector.albumCover);

				setDescription();

				$self.on('mbPlaylistAdvance mbPlaylistInit', function() {
					setTitle();
					//setArtist();
					setText();
					if(options.rating){
						setRating('current', null, current);
					}
					setCover();
				});
			}

			function buildInterface() {
				var markup, $interface;

				//I would normally use the templating plugin for something like this, but I wanted to keep this plugin's footprint as small as possible
				markup =
						'<div class="gm-music-player">' +
								'	<div class="gmmp-player jp-interface jqmp-'+$uid+'">' +
								'		<div class="gmmp-album-cover">' +
								'			<span class="gmmp-img"></span>' +
								'   	<span class="gmmp-highlight"></span>' +
										(options.rating ?
								'     <div class="gmmp-rating">' +
								'     	<span class="gmmp-rating-level gmmp-rating-star gmmp-on"></span>' +
								'       <span class="gmmp-rating-level gmmp-rating-star gmmp-on"></span>' +
								'       <span class="gmmp-rating-level gmmp-rating-star gmmp-on"></span>' +
								'       <span class="gmmp-rating-level gmmp-rating-star gmmp-on"></span>' +
								'       <span class="gmmp-rating-level gmmp-rating-star"></span>' +
								'     </div>' : '') +
								'   </div>' +
								'   <div class="gmmp-track-title"></div>' +
								'   <div class="gmmp-player-controls">' +
								'   	<div class="gmmp-main">' +
								'     	<div class="gmmp-previous jp-previous"></div>' +
								'       <div class="gmmp-play jp-play"></div>' +
								'       <div class="gmmp-pause jp-pause"></div>' +
								'       <div class="gmmp-next jp-next"></div>' +
								'<!-- These controls aren\'t used by this plugin, but jPlayer seems to require that they exist -->' +
								'       <span class="gmmp-unused-controls">' +
								'       	<span class="jp-video-play"></span>' +
								'         <span class="jp-stop"></span>' +
								'         <span class="jp-mute"></span>' +
								'         <span class="jp-unmute"></span>' +
								'         <span class="jp-volume-bar"></span>' +
								'         <span class="jp-volume-bar-value"></span>' +
								'         <span class="jp-volume-max"></span>' +
								'         <span class="jp-current-time"></span>' +
								'         <span class="jp-duration"></span>' +
								'         <span class="jp-full-screen"></span>' +
								'         <span class="jp-restore-screen"></span>' +
								'         <span class="jp-repeat"></span>' +
								'         <span class="jp-repeat-off"></span>' +
								'         <span class="jp-gui"></span>' +
								'       </span>' +
								'     </div>' +
								'     <div class="gmmp-progress-wrapper">' +
								'     	<div class="gmmp-progress jp-seek-bar">' +
								'       	<div class="gmmp-elapsed jp-play-bar"></div>' +
								'       </div>' +
								'     </div>' +
								'   </div>' +
								' 	<div class="gmmp-track-description"></div>' +
								' </div>' +
								' <div class="gmmp-description"></div>' +
								' <div class="gmmp-tracklist">' +
								' 	<ol class="gmmp-tracks"></ol>' +
								'   <div class="gmmp-more gmmp-anim">' + options.moreText + '</div>' +
								' </div>' +
								' <div class="jPlayer-container"></div>' +
								'</div>';

				var mw = (0 === options.maxwidth)? 'none' : options.maxwidth;
				$interface = $(markup).css({display:'none', opacity:0, width: options.width, 'max-width': mw}).appendTo($self).slideDown('slow', function() {
					$interface.animate({opacity:1});

					$self.trigger('mbInterfaceBuilt');
				});
			}

			function setTitle() {
				$title.html(trackName(current));
			}

			/*
			function setArtist() {
				if (isUndefined(myPlaylist[current].artist))
					$artist.parent(cssSelector.artistOuter).animate({opacity:0}, 'fast');
				else {
					$artist.html(myPlaylist[current].artist).parent(cssSelector.artistOuter).animate({opacity:1}, 'fast');
				}
			}
			*/

			function setText() {
				if (myPlaylist[current].text === '')
					$text.animate({opacity:0}, 'fast', function(){ $(this).empty() });
				else {
					$text.html(myPlaylist[current].text).animate({opacity:1}, 'fast');
				}
			}

			function setCover() {
				$albumCover.animate({opacity:0}, 'fast', function() {
					$(this).empty();
					if (!isUndefined(myPlaylist[current].cover) || myPlaylist[current].cover !== '') {
						var now = current;
						$('<img src="' + myPlaylist[current].cover + '" alt="album cover" />').load(function(){
							if(now == current)
								$albumCover.html($(this)).animate({opacity:1})
						});
					}
				});
			}

			function setDescription() {
				if (!isUndefined(options.description))
					$self.find(cssSelector.description).html(options.description).addClass(attr(cssSelector.descriptionShowing)).slideDown();
			}

			return{
				buildInterface:buildInterface,
				init:init
			}

		};

		/** Common Functions **/
		function trackName(index) {
			if (myPlaylist[index].title !== '')
				return myPlaylist[index].title;
			if (myPlaylist[index].mp3 !== '')
				return fileName(myPlaylist[index].mp3);
			if (myPlaylist[index].oga !== '')
				return fileName(myPlaylist[index].oga);
			return 'NaN';
		}

		function fileName(path) {
			path = path.split('/');
			return path[path.length - 1];
		}

		function setRating(type, $track, index) {
			if (type == 'track') {
				if (!isUndefined(myPlaylist[index].rating)) {
					applyTrackRating($track, myPlaylist[index].rating);
				}
			}
			else {
				//if the rating isn't set, use 0
				var rating = isUndefined(myPlaylist[index].rating)? 0 : Math.ceil(myPlaylist[index].rating);
				applyCurrentlyPlayingRating(rating);
			}
		}

		function applyCurrentlyPlayingRating(rating) {
			//reset the rating to 0, then set the rating defined above
			$self.find(cssSelector.player).find(cssSelector.ratingLevel).removeClass(attr(cssSelector.ratingLevelOn)).slice(0, rating).addClass(attr(cssSelector.ratingLevelOn));

		}

		function applyTrackRating($track, rating) {
			//multiply rating by 2 since the list ratings have 10 levels rather than 5
			$track.find(cssSelector.ratingLevel).removeClass(attr(cssSelector.ratingLevelOn)).slice(0, rating * 2).addClass(attr(cssSelector.ratingLevelOn));

		}


		/** Utility Functions **/
		function attr(selector) {
			return selector.substr(1);
		}

		/*
		function runCallback(callback) {
			var functionArgs = Array.prototype.slice.call(arguments, 1);

			if ($.isFunction(callback)) {
				callback.apply(this, functionArgs);
			}
		}
		*/

		function isUndefined(value) {
			return typeof value == 'undefined';
		}

		appMgr();
	};
})(jQuery);
