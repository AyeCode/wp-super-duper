/**
 * Background videos (AyeCode\SuperDuper\BackgroundVideo).
 *
 * A video is attached once the page has loaded and its block is in view, fades in
 * over the image when it is playing, and is paused while its block is off screen.
 * It is left out for visitors who asked for reduced motion or to save data, and on
 * small screens when the block says so.
 */
( function () {
	'use strict';

	var small    = window.matchMedia( '(max-width: 767.98px)' );
	var reduced  = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var observer = null;

	function allowed( el ) {
		var connection = navigator.connection;

		if ( reduced.matches || ( connection && connection.saveData ) ) {
			return false;
		}

		return ! ( '0' === el.dataset.mobile && small.matches );
	}

	function show( el ) {
		el.classList.remove( 'opacity-0' );
	}

	function post( frame, message ) {
		if ( frame && frame.contentWindow ) {
			frame.contentWindow.postMessage( JSON.stringify( message ), new URL( frame.src ).origin );
		}
	}

	function command( el, play ) {
		var frame = el.querySelector( 'iframe' );

		if ( 'youtube' === el.dataset.provider ) {
			post( frame, { event: 'command', func: play ? 'playVideo' : 'pauseVideo', args: [] } );
		} else {
			post( frame, { method: play ? 'play' : 'pause' } );
		}
	}

	function embed( el ) {
		var frame = document.createElement( 'iframe' );
		var src   = el.dataset.embed;

		if ( 'youtube' === el.dataset.provider ) {
			src += '&origin=' + encodeURIComponent( window.location.origin );
		}

		// The focal point, as two percentages: the part of the player kept in view.
		var at = ( el.dataset.position || '0% 0%' ).split( ' ' );
		var x  = parseFloat( at[0] ) || 0;
		var y  = parseFloat( at[1] ) || 0;

		frame.className = 'position-absolute border-0';
		// Covers the box whatever its shape, for a 16:9 player.
		frame.style.cssText = 'width:100cqw;height:100cqh;min-width:177.78cqh;min-height:56.25cqw;left:' + x + '%;top:' + y + '%;transform:translate(-' + x + '%,-' + y + '%);';
		frame.allow         = 'autoplay; encrypted-media';
		frame.tabIndex      = -1;
		frame.title         = '';
		frame.setAttribute( 'aria-hidden', 'true' );

		frame.addEventListener( 'load', function () {
			// Ask the player to report when it plays.
			if ( 'youtube' === el.dataset.provider ) {
				post( frame, { event: 'listening', id: 1, channel: 'widget' } );
			} else {
				post( frame, { method: 'addEventListener', value: 'playing' } );
			}

			// A player that never reports is shown anyway.
			window.setTimeout( function () {
				show( el );
			}, 4000 );
		} );

		frame.src = src;
		el.appendChild( frame );
	}

	function start( el ) {
		if ( ! allowed( el ) ) {
			return;
		}

		if ( 'VIDEO' === el.tagName ) {
			if ( ! el.getAttribute( 'src' ) ) {
				el.addEventListener( 'playing', function () {
					show( el );
				}, { once: true } );
				el.muted = true;
				el.src   = el.dataset.src;
			}

			var playing = el.play();
			if ( playing && playing.catch ) {
				playing.catch( function () {} );
			}
		} else if ( el.querySelector( 'iframe' ) ) {
			command( el, true );
		} else {
			embed( el );
		}
	}

	function stop( el ) {
		if ( 'VIDEO' === el.tagName ) {
			el.pause();
		} else if ( el.querySelector( 'iframe' ) ) {
			command( el, false );
		}
	}

	// The players report over postMessage: fade one in when it says it is playing.
	window.addEventListener( 'message', function ( event ) {
		if ( ! /^https:\/\/(www\.youtube-nocookie\.com|player\.vimeo\.com)$/.test( event.origin ) ) {
			return;
		}

		var data = event.data;

		try {
			data = 'string' === typeof data ? JSON.parse( data ) : data;
		} catch ( e ) {
			return;
		}

		if ( ! data ) {
			return;
		}

		var frames = document.querySelectorAll( '.bg-media-video iframe' );

		for ( var i = 0; i < frames.length; i++ ) {
			if ( frames[ i ].contentWindow !== event.source ) {
				continue;
			}

			if ( 'ready' === data.event ) {
				post( frames[ i ], { method: 'addEventListener', value: 'playing' } );
			} else if ( 'playing' === data.event || 'play' === data.event || ( data.info && 1 === data.info.playerState ) || ( 'onStateChange' === data.event && 1 === data.info ) ) {
				show( frames[ i ].parentNode );
			}
		}
	} );

	function init( root ) {
		if ( ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		observer = observer || new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					start( entry.target );
				} else {
					stop( entry.target );
				}
			} );
		}, { rootMargin: '200px 0px' } );

		( root || document ).querySelectorAll( '.bg-media-video' ).forEach( function ( el ) {
			observer.observe( el );
		} );
	}

	// For markup added after the page loaded.
	window.sdBackgroundMedia = { init: init };

	if ( 'complete' === document.readyState ) {
		init();
	} else {
		window.addEventListener( 'load', function () {
			init();
		} );
	}
} )();
