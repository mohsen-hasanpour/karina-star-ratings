/**
 * Karina Star Ratings front-end voting.
 *
 * Dependency free. Uses event delegation so widgets added after page load
 * (AJAX content, popups, page builders) work without re-initialization.
 */
( function () {
	'use strict';

	var config = window.ratingpressConfig || {};
	var i18n = config.i18n || {};
	var STORAGE_KEY = 'ratingpress_voted';
	var STAR_SELECTOR = '.ratingpress.is-interactive button.rp-star';

	function storedVotes() {
		try {
			var list = JSON.parse( window.localStorage.getItem( STORAGE_KEY ) || '[]' );
			return Array.isArray( list ) ? list : [];
		} catch ( e ) {
			return [];
		}
	}

	function rememberVote( postId ) {
		try {
			var list = storedVotes().filter( function ( id ) {
				return id !== postId;
			} );
			list.push( postId );
			window.localStorage.setItem( STORAGE_KEY, JSON.stringify( list.slice( -300 ) ) );
		} catch ( e ) {}
	}

	function hasVoted( postId ) {
		return config.restriction !== 'none' && storedVotes().indexOf( postId ) !== -1;
	}

	function postIdOf( widget ) {
		return parseInt( widget.getAttribute( 'data-rp-post' ), 10 ) || 0;
	}

	function widgetsFor( postId ) {
		return document.querySelectorAll( '.ratingpress[data-rp-post="' + postId + '"]' );
	}

	function each( list, callback ) {
		Array.prototype.forEach.call( list, callback );
	}

	function setHover( widget, value ) {
		var group = widget.querySelector( '.rp-stars' );
		if ( ! group ) {
			return;
		}
		group.classList.toggle( 'is-hovering', value > 0 );
		each( group.querySelectorAll( '.rp-star' ), function ( star, index ) {
			star.classList.toggle( 'is-hover', index < value );
		} );
	}

	function showMessage( widget, text, type, link ) {
		var box = widget.querySelector( '.rp-message' );
		if ( ! box ) {
			return;
		}
		box.className = 'rp-message' + ( type ? ' is-' + type : '' );
		box.textContent = text || '';

		if ( link ) {
			var anchor = document.createElement( 'a' );
			anchor.href = link.href;
			anchor.textContent = link.label;
			box.appendChild( document.createTextNode( ' ' ) );
			box.appendChild( anchor );
		}
	}

	function loginLink() {
		if ( ! config.loginUrl ) {
			return null;
		}
		var separator = config.loginUrl.indexOf( '?' ) === -1 ? '?' : '&';
		return {
			href: config.loginUrl + separator + 'redirect_to=' + encodeURIComponent( window.location.href ),
			label: i18n.loginCta || 'Log in'
		};
	}

	function lock( widget ) {
		widget.classList.add( 'is-voted' );
		setHover( widget, 0 );
		each( widget.querySelectorAll( 'button.rp-star' ), function ( button ) {
			button.setAttribute( 'aria-disabled', 'true' );
		} );
	}

	function applyRating( postId, data ) {
		each( widgetsFor( postId ), function ( widget ) {
			each( widget.querySelectorAll( '.rp-star' ), function ( star, index ) {
				var fill = data.fills && typeof data.fills[ index ] !== 'undefined' ? data.fills[ index ] : 0;
				star.style.setProperty( '--rp-fill', fill + '%' );
				star.style.setProperty( '--rp-i', index );
			} );

			var legend = widget.querySelector( '.rp-legend' );
			if ( legend && data.legend ) {
				legend.textContent = data.legend;
			}

			var summary = widget.querySelector( '.rp-summary' );
			if ( summary && data.summary ) {
				summary.textContent = data.summary;
			}

			if ( config.restriction !== 'none' ) {
				lock( widget );
			}

			widget.classList.add( 'is-celebrating' );
			window.setTimeout( function () {
				widget.classList.remove( 'is-celebrating' );
			}, 900 );
		} );
	}

	function send( postId, value, withNonce ) {
		var headers = { 'Content-Type': 'application/json' };
		if ( withNonce && config.nonce ) {
			headers[ 'X-WP-Nonce' ] = config.nonce;
		}

		return window.fetch( config.restUrl + 'rate', {
			method: 'POST',
			credentials: 'same-origin',
			headers: headers,
			body: JSON.stringify( { post_id: postId, rating: value } )
		} ).then( function ( response ) {
			return response.json().catch( function () {
				return {};
			} ).then( function ( body ) {
				// A stale nonce (e.g. cached page) should not block voting: retry anonymously.
				if ( ! response.ok && withNonce && body && body.code === 'rest_cookie_invalid_nonce' ) {
					return send( postId, value, false );
				}
				return { ok: response.ok, status: response.status, body: body || {} };
			} );
		} );
	}

	function vote( widget, value ) {
		var postId = postIdOf( widget );

		if ( ! postId || widget.classList.contains( 'is-busy' ) ) {
			return;
		}

		if ( widget.classList.contains( 'is-voted' ) || hasVoted( postId ) ) {
			lock( widget );
			showMessage( widget, i18n.voted, 'error' );
			return;
		}

		if ( widget.hasAttribute( 'data-rp-login' ) ) {
			showMessage( widget, i18n.login, 'error', loginLink() );
			return;
		}

		widget.classList.add( 'is-busy' );
		widget.setAttribute( 'aria-busy', 'true' );
		showMessage( widget, i18n.sending, '' );

		send( postId, value, true ).then( function ( result ) {
			var body = result.body;

			if ( result.ok ) {
				rememberVote( postId );
				applyRating( postId, body );
				showMessage( widget, body.message || i18n.thanks, 'success' );
				return;
			}

			if ( body.code === 'ratingpress_already_voted' ) {
				rememberVote( postId );
				each( widgetsFor( postId ), lock );
			}

			showMessage(
				widget,
				body.message || i18n.error,
				'error',
				body.code === 'ratingpress_login_required' ? loginLink() : null
			);
		} ).catch( function () {
			showMessage( widget, i18n.error, 'error' );
		} ).then( function () {
			widget.classList.remove( 'is-busy' );
			widget.removeAttribute( 'aria-busy' );
		} );
	}

	function starFromEvent( event ) {
		var target = event.target;
		if ( ! target || typeof target.closest !== 'function' ) {
			return null;
		}
		var star = target.closest( STAR_SELECTOR );
		if ( ! star ) {
			return null;
		}
		var widget = star.closest( '.ratingpress' );
		if ( ! widget || widget.classList.contains( 'is-voted' ) || widget.classList.contains( 'is-busy' ) ) {
			return null;
		}
		return { star: star, widget: widget, value: parseInt( star.getAttribute( 'data-rp-value' ), 10 ) || 0 };
	}

	function preview( event ) {
		var hit = starFromEvent( event );
		if ( hit ) {
			setHover( hit.widget, hit.value );
		}
	}

	function clearPreview( event ) {
		var target = event.target;
		if ( ! target || typeof target.closest !== 'function' ) {
			return;
		}
		var group = target.closest( '.ratingpress .rp-stars' );
		if ( group && ! group.contains( event.relatedTarget ) ) {
			setHover( group.closest( '.ratingpress' ), 0 );
		}
	}

	document.addEventListener( 'mouseover', preview );
	document.addEventListener( 'focusin', preview );
	document.addEventListener( 'mouseout', clearPreview );
	document.addEventListener( 'focusout', clearPreview );

	document.addEventListener( 'click', function ( event ) {
		var target = event.target;
		if ( ! target || typeof target.closest !== 'function' ) {
			return;
		}
		var star = target.closest( STAR_SELECTOR );
		if ( ! star ) {
			return;
		}
		event.preventDefault();
		vote( star.closest( '.ratingpress' ), parseInt( star.getAttribute( 'data-rp-value' ), 10 ) || 0 );
	} );

	function init() {
		each( document.querySelectorAll( '.ratingpress.is-interactive' ), function ( widget ) {
			if ( hasVoted( postIdOf( widget ) ) ) {
				lock( widget );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
