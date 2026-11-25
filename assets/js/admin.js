/**
 * Karina Star Ratings admin screens.
 */
( function ( $ ) {
	'use strict';

	var data = window.ratingpressAdmin || {};
	var i18n = data.i18n || {};
	var form = document.getElementById( 'rp-settings-form' );
	var TAB_KEY = 'ratingpress_tab';

	/* Toast ------------------------------------------------------------ */

	var toastTimer;

	function toast( message, isError ) {
		var el = document.querySelector( '.rp-toast' );
		if ( ! el ) {
			el = document.createElement( 'div' );
			el.className = 'rp-toast';
			el.setAttribute( 'role', 'status' );
			el.setAttribute( 'aria-live', 'polite' );
			document.body.appendChild( el );
		}
		el.classList.toggle( 'is-error', !! isError );
		el.innerHTML = '';
		var icon = document.createElement( 'span' );
		icon.className = 'dashicons ' + ( isError ? 'dashicons-warning' : 'dashicons-yes-alt' );
		icon.setAttribute( 'aria-hidden', 'true' );
		el.appendChild( icon );
		el.appendChild( document.createTextNode( message ) );
		el.classList.add( 'is-visible' );
		window.clearTimeout( toastTimer );
		toastTimer = window.setTimeout( function () {
			el.classList.remove( 'is-visible' );
		}, 3200 );
	}

	/* Copy to clipboard ------------------------------------------------ */

	function copyText( text ) {
		if ( navigator.clipboard && window.isSecureContext ) {
			return navigator.clipboard.writeText( text );
		}
		return new Promise( function ( resolve, reject ) {
			var area = document.createElement( 'textarea' );
			area.value = text;
			area.setAttribute( 'readonly', '' );
			area.style.position = 'fixed';
			area.style.opacity = '0';
			document.body.appendChild( area );
			area.select();
			try {
				document.execCommand( 'copy' );
				resolve();
			} catch ( e ) {
				reject( e );
			}
			document.body.removeChild( area );
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-rp-copy]' );
		if ( ! button ) {
			return;
		}
		var source = document.querySelector( button.getAttribute( 'data-rp-copy' ) );
		if ( ! source ) {
			return;
		}
		copyText( source.textContent ).then( function () {
			button.classList.add( 'is-copied' );
			toast( i18n.copied );
			window.setTimeout( function () {
				button.classList.remove( 'is-copied' );
			}, 1500 );
		} );
	} );

	if ( ! form ) {
		return;
	}

	/* Tabs ------------------------------------------------------------- */

	var tabs = Array.prototype.slice.call( form.querySelectorAll( '[data-rp-tab]' ) );
	var panels = form.querySelectorAll( '[data-rp-panel]' );

	function activate( name, focus ) {
		var found = tabs.some( function ( tab ) {
			return tab.getAttribute( 'data-rp-tab' ) === name;
		} );
		if ( ! found ) {
			name = 'general';
		}

		tabs.forEach( function ( tab ) {
			var active = tab.getAttribute( 'data-rp-tab' ) === name;
			tab.setAttribute( 'aria-selected', active ? 'true' : 'false' );
			tab.setAttribute( 'tabindex', active ? '0' : '-1' );
			if ( active && focus ) {
				tab.focus();
			}
		} );

		Array.prototype.forEach.call( panels, function ( panel ) {
			panel.hidden = panel.getAttribute( 'data-rp-panel' ) !== name;
		} );

		try {
			window.sessionStorage.setItem( TAB_KEY, name );
		} catch ( e ) {}

		if ( window.history && window.history.replaceState ) {
			window.history.replaceState( null, '', '#' + name );
		}
	}

	tabs.forEach( function ( tab, index ) {
		tab.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			activate( tab.getAttribute( 'data-rp-tab' ) );
		} );

		tab.addEventListener( 'keydown', function ( event ) {
			var next = null;
			var rtl = document.documentElement.dir === 'rtl';
			switch ( event.key ) {
				case 'ArrowDown':
				case ( rtl ? 'ArrowLeft' : 'ArrowRight' ):
					next = tabs[ ( index + 1 ) % tabs.length ];
					break;
				case 'ArrowUp':
				case ( rtl ? 'ArrowRight' : 'ArrowLeft' ):
					next = tabs[ ( index - 1 + tabs.length ) % tabs.length ];
					break;
				case 'Home':
					next = tabs[ 0 ];
					break;
				case 'End':
					next = tabs[ tabs.length - 1 ];
					break;
			}
			if ( next ) {
				event.preventDefault();
				activate( next.getAttribute( 'data-rp-tab' ), true );
			}
		} );
	} );

	var initialTab = window.location.hash.replace( '#', '' );
	if ( ! initialTab ) {
		try {
			initialTab = window.sessionStorage.getItem( TAB_KEY ) || '';
		} catch ( e ) {}
	}
	activate( initialTab || 'general' );

	window.addEventListener( 'hashchange', function () {
		activate( window.location.hash.replace( '#', '' ) );
	} );

	/* Saved feedback --------------------------------------------------- */

	if ( /[?&]settings-updated=true/.test( window.location.search ) ) {
		var coreNotice = document.getElementById( 'setting-error-settings_updated' );
		if ( coreNotice ) {
			coreNotice.parentNode.removeChild( coreNotice );
		}
		toast( i18n.saved );
	}

	/* Dirty state ------------------------------------------------------ */

	var dirty = false;
	var submitting = false;
	var statusText = form.querySelector( '.rp-savebar-text' );
	var savedText = statusText ? statusText.textContent : '';

	function markDirty() {
		if ( dirty ) {
			return;
		}
		dirty = true;
		form.classList.add( 'is-dirty' );
		if ( statusText ) {
			statusText.textContent = i18n.unsaved;
		}
	}

	form.addEventListener( 'input', function ( event ) {
		if ( event.target.name ) {
			markDirty();
		}
	} );
	form.addEventListener( 'change', function ( event ) {
		if ( event.target.name ) {
			markDirty();
		}
	} );
	form.addEventListener( 'submit', function () {
		submitting = true;
		if ( statusText ) {
			statusText.textContent = i18n.working || savedText;
		}
	} );
	window.addEventListener( 'beforeunload', function ( event ) {
		if ( dirty && ! submitting ) {
			event.preventDefault();
			event.returnValue = i18n.leave;
			return i18n.leave;
		}
	} );

	/* Range read-outs -------------------------------------------------- */

	Array.prototype.forEach.call( form.querySelectorAll( '.rp-range input[type="range"]' ), function ( range ) {
		var output = range.parentNode.querySelector( 'output' );
		range.addEventListener( 'input', function () {
			if ( output ) {
				output.textContent = range.value + ( range.getAttribute( 'data-rp-unit' ) || '' );
			}
		} );
	} );

	/* Live preview ----------------------------------------------------- */

	var preview = document.getElementById( 'rp-preview' );
	var numberLocale = document.documentElement.lang || undefined;

	function field( key ) {
		return form.querySelector( '[name="ratingpress_settings[' + key + ']"]' );
	}

	function value( key ) {
		var checked = form.querySelector( '[name="ratingpress_settings[' + key + ']"]:checked' );
		if ( checked ) {
			return checked.value;
		}
		var el = field( key );
		if ( ! el ) {
			return '';
		}
		if ( el.type === 'checkbox' || el.type === 'radio' ) {
			return el.checked ? el.value : '';
		}
		return el.value;
	}

	function formatNumber( number, decimals ) {
		try {
			return new Intl.NumberFormat( numberLocale, {
				minimumFractionDigits: decimals,
				maximumFractionDigits: decimals
			} ).format( number );
		} catch ( e ) {
			return number.toFixed( decimals );
		}
	}

	function renderPreview() {
		if ( ! preview ) {
			return;
		}

		var best = parseInt( value( 'stars' ), 10 ) || 5;
		var decimals = parseInt( value( 'decimals' ), 10 ) || 0;
		var sample = data.sample || { average: 3.7, count: 128 };
		var average = ( sample.average / 5 ) * best;
		var icon = value( 'icon' ) || 'star';

		preview.className = [
			'ratingpress',
			'rp-context-preview',
			'is-interactive',
			'rp-icon-' + icon,
			'rp-align-' + ( value( 'alignment' ) || 'start' ),
			'rp-layout-' + ( value( 'layout' ) || 'inline' ),
			value( 'animations' ) ? 'has-animations' : ''
		].join( ' ' );

		[ [ 'size', '--rp-size', 'px' ], [ 'gap', '--rp-gap', 'px' ], [ 'color_active', '--rp-active', '' ], [ 'color_inactive', '--rp-inactive', '' ], [ 'color_hover', '--rp-hover', '' ] ].forEach( function ( map ) {
			var current = value( map[ 0 ] );
			if ( current !== '' ) {
				preview.style.setProperty( map[ 1 ], current + map[ 2 ] );
			}
		} );

		var stars = preview.querySelector( '.rp-stars' );
		if ( stars.children.length !== best ) {
			stars.innerHTML = '';
			for ( var i = 1; i <= best; i++ ) {
				var button = document.createElement( 'button' );
				button.type = 'button';
				button.className = 'rp-star';
				button.setAttribute( 'data-rp-value', i );
				button.setAttribute( 'tabindex', '-1' );
				button.setAttribute( 'aria-hidden', 'true' );
				var iconEl = document.createElement( 'span' );
				iconEl.className = 'rp-star-icon';
				button.appendChild( iconEl );
				stars.appendChild( button );
			}
		}
		Array.prototype.forEach.call( stars.children, function ( star, index ) {
			var fill = Math.max( 0, Math.min( 100, ( average - index ) * 100 ) );
			star.style.setProperty( '--rp-fill', fill.toFixed( 1 ) + '%' );
		} );

		var heading = preview.querySelector( '.rp-heading' );
		var headingText = value( 'heading' );
		heading.textContent = headingText;
		heading.hidden = ! headingText;

		var legend = preview.querySelector( '.rp-legend' );
		var template = value( 'legend' ) || ( field( 'legend' ) && field( 'legend' ).placeholder ) || '';
		legend.hidden = ! value( 'show_legend' );
		legend.textContent = template
			.split( '{avg}' ).join( formatNumber( average, decimals ) )
			.split( '{best}' ).join( formatNumber( best, 0 ) )
			.split( '{count}' ).join( formatNumber( sample.count, 0 ) )
			.split( '{votes}' ).join( sample.count === 1 ? i18n.vote : i18n.votes )
			.split( '{percent}' ).join( formatNumber( ( average / best ) * 100, 0 ) + '%' );
	}

	if ( preview ) {
		form.addEventListener( 'input', renderPreview );
		form.addEventListener( 'change', renderPreview );

		preview.addEventListener( 'mouseover', function ( event ) {
			var star = event.target.closest( '.rp-star' );
			if ( ! star ) {
				return;
			}
			var target = parseInt( star.getAttribute( 'data-rp-value' ), 10 );
			var group = preview.querySelector( '.rp-stars' );
			group.classList.add( 'is-hovering' );
			Array.prototype.forEach.call( group.children, function ( item, index ) {
				item.classList.toggle( 'is-hover', index < target );
			} );
		} );

		preview.addEventListener( 'mouseleave', function () {
			var group = preview.querySelector( '.rp-stars' );
			group.classList.remove( 'is-hovering' );
			Array.prototype.forEach.call( group.children, function ( item ) {
				item.classList.remove( 'is-hover' );
			} );
		} );

		Array.prototype.forEach.call( document.querySelectorAll( '[data-rp-preview-bg]' ), function ( button ) {
			button.addEventListener( 'click', function () {
				var stage = document.querySelector( '.rp-preview-stage' );
				stage.setAttribute( 'data-bg', button.getAttribute( 'data-rp-preview-bg' ) );
				Array.prototype.forEach.call( document.querySelectorAll( '[data-rp-preview-bg]' ), function ( other ) {
					var active = other === button;
					other.classList.toggle( 'is-active', active );
					other.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
				} );
			} );
		} );
	}

	/* Color pickers ---------------------------------------------------- */

	if ( $ && $.fn.wpColorPicker ) {
		$( '.rp-color' ).wpColorPicker( {
			change: function ( event, ui ) {
				event.target.value = ui.color.toString();
				markDirty();
				renderPreview();
			},
			clear: function () {
				markDirty();
				window.setTimeout( renderPreview, 0 );
			}
		} );
	}

	renderPreview();

	/* Placeholder chips ------------------------------------------------ */

	Array.prototype.forEach.call( form.querySelectorAll( '[data-rp-insert]' ), function ( button ) {
		button.addEventListener( 'click', function () {
			var target = document.getElementById( button.closest( '[data-rp-target]' ).getAttribute( 'data-rp-target' ) );
			if ( ! target ) {
				return;
			}
			var tag = button.getAttribute( 'data-rp-insert' );
			var start = typeof target.selectionStart === 'number' ? target.selectionStart : target.value.length;
			var end = typeof target.selectionEnd === 'number' ? target.selectionEnd : target.value.length;
			target.value = target.value.slice( 0, start ) + tag + target.value.slice( end );
			target.focus();
			target.setSelectionRange( start + tag.length, start + tag.length );
			target.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		} );
	} );

	/* Tools ------------------------------------------------------------ */

	var apiFetch = window.wp && window.wp.apiFetch;

	function setLoading( button, loading ) {
		button.disabled = loading;
		button.classList.toggle( 'is-loading', loading );
	}

	/**
	 * Runs a batched tool until it reports completion.
	 *
	 * @param {string}   route      Tool route.
	 * @param {string}   cursorKey  Request argument that receives the batch cursor ("offset" or "after").
	 * @param {Function} onProgress Called with every batch response.
	 * @return {Promise} Resolves with the last response.
	 */
	function runBatches( route, cursorKey, onProgress ) {
		function step( cursor ) {
			var payload = {};
			payload[ cursorKey ] = cursor;

			return apiFetch( {
				path: '/ratingpress/v1/tools/' + route,
				method: 'POST',
				data: payload
			} ).then( function ( response ) {
				onProgress( response );
				if ( response.done || response.next === cursor ) {
					return response;
				}
				return step( response.next );
			} );
		}
		return step( 0 );
	}

	function inlineStatus( button, text, type ) {
		var status = button.parentNode.querySelector( '.rp-inline-status' );
		if ( status ) {
			status.className = 'rp-inline-status' + ( type ? ' is-' + type : '' );
			status.textContent = text;
		}
	}

	function sprintf( format ) {
		var args = Array.prototype.slice.call( arguments, 1 );
		return format.replace( /%(\d+)\$s/g, function ( match, index ) {
			return args[ index - 1 ];
		} );
	}

	Array.prototype.forEach.call( form.querySelectorAll( '[data-rp-run]' ), function ( button ) {
		button.addEventListener( 'click', function () {
			if ( ! apiFetch ) {
				return;
			}

			var tool = button.getAttribute( 'data-rp-run' );

			if ( tool === 'import-kk' ) {
				var box = button.closest( '.rp-import' );
				var progress = box.querySelector( '.rp-progress' );
				var bar = box.querySelector( '.rp-progress-bar' );
				var barFill = bar.querySelector( 'span' );
				var text = box.querySelector( '.rp-progress-text' );
				var total = parseInt( box.getAttribute( 'data-rp-total' ), 10 ) || 0;

				progress.hidden = false;
				setLoading( button, true );

				runBatches( 'import-kk', 'offset', function ( response ) {
					total = response.total || total;
					var percent = total ? Math.min( 100, Math.round( ( response.processed / total ) * 100 ) ) : 100;
					barFill.style.width = percent + '%';
					bar.setAttribute( 'aria-valuenow', percent );
					text.textContent = sprintf( i18n.importProgress, formatNumber( response.processed, 0 ), formatNumber( total, 0 ) );
				} ).then( function () {
					barFill.style.width = '100%';
					text.textContent = i18n.importDone;
					toast( i18n.importDone );
				} ).catch( function ( error ) {
					text.textContent = ( error && error.message ) || i18n.error;
					toast( ( error && error.message ) || i18n.error, true );
				} ).then( function () {
					setLoading( button, false );
				} );
				return;
			}

			if ( tool === 'recalculate' ) {
				setLoading( button, true );
				inlineStatus( button, i18n.working, '' );
				runBatches( 'recalculate', 'after', function () {} ).then( function () {
					inlineStatus( button, i18n.recalcDone, 'success' );
					toast( i18n.recalcDone );
				} ).catch( function ( error ) {
					inlineStatus( button, ( error && error.message ) || i18n.error, 'error' );
				} ).then( function () {
					setLoading( button, false );
				} );
				return;
			}

			if ( tool === 'reset' ) {
				if ( ! window.confirm( i18n.resetConfirm ) ) {
					return;
				}
				setLoading( button, true );
				apiFetch( { path: '/ratingpress/v1/tools/reset', method: 'POST' } ).then( function () {
					inlineStatus( button, i18n.resetDone, 'success' );
					toast( i18n.resetDone );
				} ).catch( function ( error ) {
					inlineStatus( button, ( error && error.message ) || i18n.error, 'error' );
				} ).then( function () {
					setLoading( button, false );
				} );
			}
		} );
	} );
}( window.jQuery ) );
