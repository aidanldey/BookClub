/**
 * "Where you left off" — the only client-side half of the Reading Room.
 *
 * The reader stores each book's position in localStorage. This reads it back on
 * the Reading Room index and on fan pages, so a card can say "43% · Chapter 12"
 * and a "Read it free" button can become "Continue reading". Nothing here is
 * load-bearing: with JS off, or on a device that has never opened the book,
 * every link still works and simply starts at the beginning.
 *
 * @package BookLoversClub
 */
(function () {
	'use strict';

	var targets = document.querySelectorAll( '[data-blc-progress]' );
	if ( ! targets.length ) {
		return;
	}

	function readPosition( key ) {
		try {
			var raw = localStorage.getItem( 'blc-reader-loc:' + key );
			if ( ! raw ) {
				return null;
			}
			var saved = JSON.parse( raw );
			return ( saved && typeof saved.fraction === 'number' ) ? saved : null;
		} catch ( e ) {
			return null;
		}
	}

	Array.prototype.forEach.call( targets, function ( element ) {
		var key = element.getAttribute( 'data-blc-progress' );
		var saved = key && readPosition( key );
		if ( ! saved ) {
			return;
		}

		var percent = Math.round( saved.fraction * 100 );

		// Only claim progress once there is some: 0% is just "not started".
		if ( percent < 1 ) {
			return;
		}

		var label = element.getAttribute( 'data-continue-label' );
		if ( label ) {
			element.textContent = label;
		}

		var readout = element.querySelector( '.blc-progress-readout' ) ||
			document.querySelector( '[data-blc-progress-for="' + key + '"]' );
		if ( readout ) {
			readout.textContent = saved.chapter ? percent + '% · ' + saved.chapter : percent + '%';
			readout.hidden = false;
		}

		var meter = document.querySelector( '[data-blc-progress-bar="' + key + '"]' );
		if ( meter ) {
			meter.style.width = percent + '%';
			meter.parentNode.hidden = false;
		}

		element.setAttribute( 'data-started', 'true' );
	} );
})();
