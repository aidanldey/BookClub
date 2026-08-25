/**
 * The BookLoversClub reader — chrome around foliate-js.
 *
 * foliate-js does the hard part (unzipping the EPUB, laying out columns,
 * tracking positions as CFIs). Everything here is the part a club needs: a
 * table of contents, reading settings that persist, a position that survives
 * closing the tab, and a link you can send someone that opens on the same page.
 *
 * The markup is rendered by single-library_book.php, so with JS off a visitor
 * still gets the book's details and a download link. This module only wires up
 * controls that are already on the page.
 *
 * @package BookLoversClub
 */

import '../vendor/foliate-js/view.js';

const SETTINGS_KEY = 'blc-reader-settings';
const locationKey = ( key ) => `blc-reader-loc:${ key }`;

const DEFAULTS = {
	fontSize: 100,     // percent
	lineHeight: 1.6,
	family: 'book',    // 'book' | 'serif' | 'sans'
	flow: 'paginated', // 'paginated' | 'scrolled'
	justify: true,
};

const FONT_STACKS = {
	serif: "var(--font-display, Lora, Georgia, serif)",
	sans: "var(--font-body, Inter, system-ui, sans-serif)",
};

const clamp = ( value, min, max ) => Math.min( max, Math.max( min, value ) );

/* -------------------------------------------------------------------------
 * Storage — every read is wrapped, because Safari private mode throws.
 * ---------------------------------------------------------------------- */
const store = {
	get( key, fallback = null ) {
		try {
			const raw = localStorage.getItem( key );
			return raw === null ? fallback : JSON.parse( raw );
		} catch ( e ) {
			return fallback;
		}
	},
	set( key, value ) {
		try {
			localStorage.setItem( key, JSON.stringify( value ) );
		} catch ( e ) {
			/* Out of quota, or storage is blocked. Reading still works. */
		}
	},
};

/* -------------------------------------------------------------------------
 * Book styling — derived from the theme's own tokens, so the page the reader
 * renders matches the page it sits on, in both light and lamplight dark.
 * ---------------------------------------------------------------------- */
const palette = () => {
	const style = getComputedStyle( document.documentElement );
	const token = ( name, fallback ) =>
		( style.getPropertyValue( name ) || '' ).trim() || fallback;
	return {
		text: token( '--text', '#1A1410' ),
		accent: token( '--accent', '#C4956A' ),
		muted: token( '--text-secondary', '#6B7280' ),
		page: token( '--bg-card', '#FAF8F5' ),
	};
};

const isDark = () => document.documentElement.getAttribute( 'data-theme' ) === 'dark';

/**
 * Styles for the book's own document, as [beforeBookCSS, afterBookCSS].
 *
 * The first sheet is prepended to the book's <head>, so the edition's own
 * stylesheet still wins on typography — a Standard Ebooks title keeps its
 * small caps and its drop caps. The second is appended, and carries only what
 * has to win: the reader's size and spacing choices, and the colors that keep
 * the text legible against the club's background.
 */
const bookStyles = ( settings ) => {
	const { text, accent, muted, page } = palette();
	const dark = isDark();
	const family = FONT_STACKS[ settings.family ];

	const before = `
		html {
			color-scheme: ${ dark ? 'dark' : 'light' };
			font-family: ${ family || 'inherit' };
		}
		p, li, blockquote, dd, div {
			line-height: ${ settings.lineHeight };
			text-align: ${ settings.justify ? 'justify' : 'start' };
			hyphens: ${ settings.justify ? 'auto' : 'manual' };
			-webkit-hyphens: ${ settings.justify ? 'auto' : 'manual' };
			-webkit-hyphenate-limit-before: 3;
			-webkit-hyphenate-limit-after: 2;
			hanging-punctuation: allow-end last;
			widows: 2;
			orphans: 2;
		}
		/* Don't let the justify setting override an explicit alignment. */
		[align="left"], .left { text-align: left; }
		[align="right"], .right { text-align: right; }
		[align="center"], .center { text-align: center; }
		pre { white-space: pre-wrap !important; }
	`;

	// Anything a book's stylesheet might contradict goes here, after it.
	const after = `
		/* The edition's own background would be white — and leaving it unset is
		   no better, since the browser paints its own canvas for the colour
		   scheme and it lands a shade off the reader's. Both get the reader's
		   page colour, so the book and its frame are one surface. */
		html {
			font-size: ${ settings.fontSize }% !important;
			background: ${ page } !important;
		}
		body {
			background: transparent !important;
			color: ${ text } !important;
		}
		${ family ? `
		body, body *:not(code):not(pre):not(kbd):not(samp) {
			font-family: ${ family } !important;
		}` : '' }
		${ dark ? `
		/* Editions that hard-code near-black text would vanish on the dark
		   page, so in dark mode the reader's ink wins everywhere. */
		body *:not(a) { color: inherit !important; }
		img, svg { filter: brightness(0.86) contrast(1.04); }` : '' }
		a, a:link, a:visited { color: ${ accent } !important; }
		hr { border-color: ${ muted } !important; opacity: 0.4; }
		::selection { background: ${ accent }55; }
	`;

	return [ before, after ];
};

/* -------------------------------------------------------------------------
 * Fetch with progress — a 2 MB book on a phone deserves a progress bar.
 * ---------------------------------------------------------------------- */
async function fetchBook( url, onProgress ) {
	const response = await fetch( url );
	if ( ! response.ok ) {
		throw new Error( `${ response.status } ${ response.statusText }` );
	}

	const name = decodeURIComponent( new URL( response.url, location.href ).pathname.split( '/' ).pop() ) || 'book.epub';
	const total = Number( response.headers.get( 'content-length' ) ) || 0;
	const options = { type: 'application/epub+zip' };

	if ( ! response.body || ! total ) {
		return new File( [ await response.blob() ], name, options );
	}

	const reader = response.body.getReader();
	const chunks = [];
	let loaded = 0;
	for ( ;; ) {
		const { done, value } = await reader.read();
		if ( done ) {
			break;
		}
		chunks.push( value );
		loaded += value.length;
		onProgress( loaded / total );
	}
	return new File( chunks, name, options );
}

/**
 * An EPUB is a zip. Anything else — a truncated upload, an HTML error page
 * served with a 200 — would otherwise be handed to foliate-js, which would go
 * looking for a MOBI parser this theme doesn't ship and report that instead of
 * the real problem.
 */
async function assertEPUB( file ) {
	const head = new Uint8Array( await file.slice( 0, 4 ).arrayBuffer() );
	const isZip = head[ 0 ] === 0x50 && head[ 1 ] === 0x4b && head[ 2 ] === 0x03 && head[ 3 ] === 0x04;
	if ( ! isZip ) {
		throw new Error( 'That file is not a valid EPUB.' );
	}
}

/* -------------------------------------------------------------------------
 * Table of contents
 * ---------------------------------------------------------------------- */
function renderTOC( toc, container, onSelect ) {
	const build = ( items, depth ) => {
		const list = document.createElement( 'ul' );
		list.className = depth === 0 ? 'blc-toc-list' : 'blc-toc-sublist';
		for ( const item of items ) {
			const li = document.createElement( 'li' );
			const button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'blc-toc-link';
			button.textContent = item.label || '—';
			if ( item.href ) {
				button.dataset.href = item.href;
				button.addEventListener( 'click', () => onSelect( item.href ) );
			} else {
				button.disabled = true;
			}
			li.append( button );
			if ( item.subitems?.length ) {
				li.append( build( item.subitems, depth + 1 ) );
			}
			list.append( li );
		}
		return list;
	};

	container.replaceChildren( build( toc, 0 ) );
}

/* -------------------------------------------------------------------------
 * The reader
 * ---------------------------------------------------------------------- */
class Reader {
	constructor( root ) {
		this.root = root;
		this.key = root.dataset.key || root.dataset.epub;
		this.settings = { ...DEFAULTS, ...( store.get( SETTINGS_KEY ) || {} ) };
		this.$ = ( selector ) => root.querySelector( selector );
	}

	async start() {
		const url = this.root.dataset.epub;
		if ( ! url ) {
			return;
		}

		this.root.classList.add( 'is-loading' );
		const bar = this.$( '.blc-reader-loading-bar' );

		let file;
		try {
			file = await fetchBook( url, ( fraction ) => {
				if ( bar ) {
					bar.style.width = `${ Math.round( fraction * 100 ) }%`;
				}
			} );
		} catch ( error ) {
			this.fail( error );
			return;
		}

		try {
			await assertEPUB( file );
		} catch ( error ) {
			this.fail( error );
			return;
		}

		this.view = document.createElement( 'foliate-view' );
		this.view.setAttribute( 'autohide-cursor', '' );
		this.$( '.blc-reader-viewport' ).append( this.view );

		try {
			await this.view.open( file );
		} catch ( error ) {
			this.view.remove();
			this.fail( error );
			return;
		}

		this.view.addEventListener( 'relocate', ( event ) => this.onRelocate( event.detail ) );
		this.view.addEventListener( 'load', ( event ) => this.onLoad( event.detail ) );
		this.view.addEventListener( 'external-link', ( event ) => {
			// Let the browser handle links out of the book, in a new tab.
			event.preventDefault();
			window.open( event.detail.href_, '_blank', 'noopener' );
		} );

		this.applySettings();
		this.buildTOC();
		this.bindControls();
		this.watchTheme();
		this.fitToViewport();

		await this.view.init( { lastLocation: this.startLocation(), showTextStart: true } );

		this.root.classList.remove( 'is-loading' );
		this.root.classList.add( 'is-ready' );
	}

	/** Where to open: a shared ?loc= wins over where this reader left off. */
	startLocation() {
		const shared = this.root.dataset.loc;
		if ( shared ) {
			return shared;
		}
		const saved = store.get( locationKey( this.key ) );
		return saved?.cfi || null;
	}

	fail( error ) {
		this.root.classList.remove( 'is-loading' );
		this.root.classList.add( 'has-error' );
		const message = this.$( '.blc-reader-error-detail' );
		if ( message ) {
			message.textContent = String( error?.message || error );
		}
		console.error( 'Reader could not open the book:', error );
	}

	/* ---- Settings ---- */
	applySettings() {
		const { renderer } = this.view;
		// These land in the renderer's own custom properties verbatim, so the
		// units matter: a bare number invalidates its grid template, and the
		// column math reads them back with parseFloat — which would take
		// "38rem" for 38 pixels. Percentages for the gap, pixels for the rest.
		renderer.setAttribute( 'flow', this.settings.flow );
		renderer.setAttribute( 'gap', '6%' );
		renderer.setAttribute( 'margin', '28px' );
		renderer.setAttribute( 'max-inline-size', '560px' );
		renderer.setAttribute( 'max-column-count', this.settings.flow === 'scrolled' ? '1' : '2' );
		renderer.setStyles?.( bookStyles( this.settings ) );

		this.root.dataset.flow = this.settings.flow;
		const size = this.$( '.blc-reader-fontsize-value' );
		if ( size ) {
			size.textContent = `${ this.settings.fontSize }%`;
		}
		for ( const button of this.root.querySelectorAll( '[data-setting]' ) ) {
			const { setting, value } = button.dataset;
			const current = String( this.settings[ setting ] );
			button.setAttribute( 'aria-pressed', String( current === value ) );
		}
	}

	update( changes ) {
		Object.assign( this.settings, changes );
		store.set( SETTINGS_KEY, this.settings );
		this.applySettings();
	}

	/**
	 * Give the reader the viewport that is left under the page header.
	 *
	 * The CSS default is a fraction of the viewport, which is close but leaves
	 * the position bar under the fold on a short window. The exact figure is
	 * only knowable once the header above has been laid out.
	 */
	fitToViewport() {
		const fit = () => {
			const top = this.root.getBoundingClientRect().top + window.scrollY;
			const height = clamp( window.innerHeight - top - 24, 360, window.innerHeight - 24 );
			this.root.style.setProperty( '--blc-reader-height', `${ Math.round( height ) }px` );
		};

		fit();
		let pending;
		window.addEventListener( 'resize', () => {
			clearTimeout( pending );
			pending = setTimeout( fit, 150 );
		} );
	}

	watchTheme() {
		// The site's light/dark toggle flips data-theme on <html>; the book
		// has to follow it, and it lives in an iframe that won't inherit.
		const observer = new MutationObserver( () =>
			this.view.renderer.setStyles?.( bookStyles( this.settings ) ) );
		observer.observe( document.documentElement, { attributes: true, attributeFilter: [ 'data-theme' ] } );
	}

	/* ---- Contents ---- */
	buildTOC() {
		const panel = this.$( '.blc-reader-toc-body' );
		const toc = this.view.book?.toc;
		if ( ! panel || ! toc?.length ) {
			this.$( '.blc-reader-toc-button' )?.setAttribute( 'hidden', '' );
			return;
		}
		renderTOC( toc, panel, ( href ) => {
			this.view.goTo( href ).catch( ( error ) => console.error( error ) );
			this.closePanels();
		} );
	}

	markCurrentTOC( href ) {
		if ( ! href ) {
			return;
		}
		for ( const link of this.root.querySelectorAll( '.blc-toc-link' ) ) {
			link.classList.toggle( 'is-current', link.dataset.href === href );
		}
	}

	/* ---- Panels ---- */
	togglePanel( name, force ) {
		const panel = this.$( `.blc-reader-${ name }` );
		const button = this.$( `.blc-reader-${ name }-button` );
		if ( ! panel ) {
			return;
		}
		const open = force ?? panel.hasAttribute( 'hidden' );
		panel.toggleAttribute( 'hidden', ! open );
		button?.setAttribute( 'aria-expanded', String( open ) );
		if ( open ) {
			// Only one panel at a time — they occupy the same corner.
			for ( const other of [ 'toc', 'settings' ] ) {
				if ( other !== name ) {
					this.togglePanel( other, false );
				}
			}
		}
	}

	closePanels() {
		this.togglePanel( 'toc', false );
		this.togglePanel( 'settings', false );
	}

	/* ---- Events ---- */
	bindControls() {
		const on = ( selector, event, handler ) =>
			this.root.querySelector( selector )?.addEventListener( event, handler );

		on( '.blc-reader-prev', 'click', () => this.view.goLeft() );
		on( '.blc-reader-next', 'click', () => this.view.goRight() );
		on( '.blc-reader-toc-button', 'click', () => this.togglePanel( 'toc' ) );
		on( '.blc-reader-settings-button', 'click', () => this.togglePanel( 'settings' ) );

		for ( const close of this.root.querySelectorAll( '.blc-reader-panel-close' ) ) {
			close.addEventListener( 'click', () => this.closePanels() );
		}

		on( '.blc-reader-smaller', 'click', () =>
			this.update( { fontSize: clamp( this.settings.fontSize - 10, 70, 220 ) } ) );
		on( '.blc-reader-larger', 'click', () =>
			this.update( { fontSize: clamp( this.settings.fontSize + 10, 70, 220 ) } ) );

		for ( const button of this.root.querySelectorAll( '[data-setting]' ) ) {
			button.addEventListener( 'click', () => {
				const { setting, value } = button.dataset;
				const parsed = value === 'true' ? true : value === 'false' ? false : isNaN( value ) ? value : Number( value );
				this.update( { [ setting ]: parsed } );
			} );
		}

		const slider = this.$( '.blc-reader-slider' );
		slider?.addEventListener( 'input', ( event ) => {
			this.seeking = true;
			this.view.goToFraction( parseFloat( event.target.value ) )
				.catch( ( error ) => console.error( error ) )
				.finally( () => { this.seeking = false; } );
		} );

		on( '.blc-reader-focus', 'click', () => this.toggleFocus() );
		on( '.blc-reader-share', 'click', () => this.share() );

		document.addEventListener( 'keydown', ( event ) => this.onKeydown( event ) );

		// pagehide is the one teardown event that fires reliably on mobile
		// Safari; visibilitychange covers tab switches and app backgrounding.
		window.addEventListener( 'pagehide', () => this.savePosition() );
		document.addEventListener( 'visibilitychange', () => {
			if ( document.visibilityState === 'hidden' ) {
				this.savePosition();
			}
		} );
	}

	onKeydown( event ) {
		if ( event.target.closest?.( 'input, textarea, select' ) ) {
			return;
		}
		switch ( event.key ) {
			case 'ArrowLeft':
			case 'PageUp':
				this.view.goLeft();
				break;
			case 'ArrowRight':
			case 'PageDown':
			case ' ':
				this.view.goRight();
				break;
			case 'Escape':
				this.closePanels();
				break;
			default:
				return;
		}
		event.preventDefault();
	}

	onLoad( { doc } ) {
		// Keys pressed while the book iframe has focus never reach the page.
		doc.addEventListener( 'keydown', ( event ) => this.onKeydown( event ) );
		doc.addEventListener( 'click', () => this.closePanels() );
	}

	onRelocate( location ) {
		const { tocItem, cfi } = location;

		const chapter = this.$( '.blc-reader-chapter' );
		if ( chapter ) {
			chapter.textContent = tocItem?.label || '';
		}
		this.markCurrentTOC( tocItem?.href );

		// Turning past the last page reports no fraction at all, and the
		// renderer can overshoot 1 mid-section. Neither is a position: taking
		// them at face value would jump the readout to 0% and overwrite a
		// perfectly good bookmark with the start of the book.
		const raw = location.fraction;
		if ( typeof raw !== 'number' || ! isFinite( raw ) ) {
			return;
		}
		const fraction = clamp( raw, 0, 1 );

		const slider = this.$( '.blc-reader-slider' );
		if ( slider && ! this.seeking ) {
			slider.value = fraction;
		}
		const readout = this.$( '.blc-reader-percent' );
		if ( readout ) {
			readout.textContent = `${ Math.round( fraction * 100 ) }%`;
		}

		if ( ! cfi ) {
			return;
		}
		this.lastCFI = cfi;
		this.pending = {
			cfi,
			fraction,
			chapter: tocItem?.label || '',
			at: Date.now(),
		};

		clearTimeout( this.saveTimer );
		this.saveTimer = setTimeout( () => this.savePosition(), 400 );
	}

	/** Write the last position out. Debounced while reading, immediate on exit. */
	savePosition() {
		if ( ! this.pending ) {
			return;
		}
		clearTimeout( this.saveTimer );
		store.set( locationKey( this.key ), this.pending );
		this.pending = null;
	}

	/* ---- Focus mode and sharing ---- */
	toggleFocus() {
		const button = this.$( '.blc-reader-focus' );
		if ( document.fullscreenElement ) {
			document.exitFullscreen?.();
			button?.setAttribute( 'aria-pressed', 'false' );
			return;
		}
		this.root.requestFullscreen?.().then( () => {
			button?.setAttribute( 'aria-pressed', 'true' );
		} ).catch( () => {
			// Fullscreen refused (iOS Safari); fall back to a tall reader.
			this.root.classList.toggle( 'is-focus' );
			button?.setAttribute( 'aria-pressed', String( this.root.classList.contains( 'is-focus' ) ) );
		} );
	}

	async share() {
		const button = this.$( '.blc-reader-share' );
		const url = new URL( location.href );
		url.searchParams.delete( 'loc' );
		if ( this.lastCFI ) {
			url.searchParams.set( 'loc', this.lastCFI );
		}

		const done = ( message ) => {
			if ( ! button ) {
				return;
			}
			const original = button.dataset.label || button.textContent;
			button.dataset.label = original;
			button.textContent = message;
			setTimeout( () => { button.textContent = original; }, 2000 );
		};

		try {
			await navigator.clipboard.writeText( url.toString() );
			done( button?.dataset.copied || 'Link copied' );
		} catch ( error ) {
			// Clipboard blocked — put the link where it can be copied by hand.
			window.prompt( button?.dataset.copied || 'Link to this spot', url.toString() );
		}
	}
}

const root = document.querySelector( '.blc-reader[data-epub]' );
if ( root ) {
	new Reader( root ).start().catch( ( error ) => console.error( error ) );
}
