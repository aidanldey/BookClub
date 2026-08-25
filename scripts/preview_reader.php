<?php
/**
 * Render the reader and the Reading Room to static HTML — no WordPress needed.
 *
 * Same trick as preview_fan_page.php: shim the handful of WordPress functions
 * the templates call, then include the real template files, so what you see is
 * what the theme renders. Unlike that script the output is not self-contained —
 * the reader is an ES module that fetches an EPUB, so the page links to the
 * real asset files and has to be served over HTTP rather than opened off disk.
 *
 *   python3 scripts/make_test_epub.py
 *   php scripts/preview_reader.php
 *   php -S localhost:8000
 *   open http://localhost:8000/wordpress/preview/reader.html
 *
 * @package BookLoversClub
 */

define( 'BLC_THEME_FILES', __DIR__ . '/../wordpress/theme-files' );
define( 'BLC_PREVIEW_DIR', __DIR__ . '/../wordpress/preview' );
define( 'BLC_VERSION', 'preview' );
// WordPress always has this; inc/reader.php reads it for the default shelf
// location, which the filters below then override.
define( 'ABSPATH', dirname( __DIR__ ) . '/' );

// The preview serves its own shelf out of wordpress/preview/shelf/, the same
// way the live site serves /shelf/ from the web root.
define( 'BLC_PREVIEW_SHELF', BLC_PREVIEW_DIR . '/shelf' );

/* -------------------------------------------------------------------------
 * The shelf under preview. Three books, one of them fileless, so the archive
 * filter and the "not uploaded yet" state both have something to show.
 * ---------------------------------------------------------------------- */
$GLOBALS['blc_books'] = array(
	array(
		'ID'         => 101,
		'post_title' => 'A Test Book for the Reading Room',
		'post_name'  => 'test-book',
		'excerpt'    => 'A fixture: five chapters, a nested contents tree, and enough prose to paginate.',
		'meta'       => array(
			'_blc_epub_file'     => 'test-book.epub',
			'_blc_epub_url'      => '',
			'_blc_epub_id'       => 0,
			'_blc_book_author'   => 'BookLoversClub',
			'_blc_translator'    => '',
			'_blc_year_published'=> '2026',
			'_blc_rights'        => 'Public domain — written for this fixture.',
			'_blc_source_name'   => 'BookLoversClub',
			'_blc_source_url'    => 'https://bookloversclub.com',
		),
	),
	array(
		'ID'         => 102,
		'post_title' => 'Frankenstein',
		'post_name'  => 'frankenstein',
		'excerpt'    => 'The 1818 text. Public domain everywhere.',
		'meta'       => array(
			'_blc_epub_file'     => 'mary-shelley_frankenstein.epub',
			'_blc_epub_url'      => '',
			'_blc_epub_id'       => 0,
			'_blc_book_author'   => 'Mary Shelley',
			'_blc_translator'    => '',
			'_blc_year_published'=> '1818',
			'_blc_rights'        => 'Public domain in the United States.',
			'_blc_source_name'   => 'Standard Ebooks',
			'_blc_source_url'    => 'https://standardebooks.org',
		),
	),
	array(
		'ID'         => 103,
		'post_title' => 'Crime and Punishment',
		'post_name'  => 'crime-and-punishment',
		'excerpt'    => 'Constance Garnett’s translation — the edition whose copyright has expired.',
		'meta'       => array(
			'_blc_epub_file'     => '',
			'_blc_epub_url'      => '',
			'_blc_epub_id'       => 0,
			'_blc_book_author'   => 'Fyodor Dostoevsky',
			'_blc_translator'    => 'Constance Garnett',
			'_blc_year_published'=> '1866',
			'_blc_rights'        => 'Public domain in the United States.',
			'_blc_source_name'   => 'Standard Ebooks',
			'_blc_source_url'    => 'https://standardebooks.org',
		),
	),
);

/* -------------------------------------------------------------------------
 * WordPress, in miniature
 * ---------------------------------------------------------------------- */
function __( $text, $domain = null ) { return $text; }
function _e( $text, $domain = null ) { echo $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $url ) { return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' ); }
function esc_textarea( $text ) { return esc_html( $text ); }
function esc_html__( $text, $domain = null ) { return esc_html( $text ); }
function esc_attr__( $text, $domain = null ) { return esc_attr( $text ); }
function esc_html_e( $text, $domain = null ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = null ) { echo esc_attr( $text ); }
function _n( $single, $plural, $number, $domain = null ) { return 1 === (int) $number ? $single : $plural; }
function esc_url_raw( $url ) { return $url; }
function sanitize_text_field( $text ) { return trim( strip_tags( (string) $text ) ); }
function wp_unslash( $value ) { return $value; }
function absint( $value ) { return abs( (int) $value ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function add_query_arg( $key, $value, $url ) { return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . $key . '=' . $value; }
function size_format( $bytes, $decimals = 0 ) {
	$units = array( 'B', 'KB', 'MB', 'GB' );
	$i     = $bytes > 0 ? (int) floor( log( $bytes, 1024 ) ) : 0;
	$i     = min( $i, count( $units ) - 1 );
	return round( $bytes / pow( 1024, $i ), $decimals ) . ' ' . $units[ $i ];
}

function trailingslashit( $string ) { return rtrim( $string, '/\\' ) . '/'; }
function home_url( $path = '' ) { return ltrim( (string) $path, '/' ); }

/* Real enough to relocate the shelf the way a live site would. */
$GLOBALS['blc_filters'] = array();
function add_filter( $tag, $callback = null, $priority = 10, $accepted = 1 ) {
	if ( is_callable( $callback ) ) {
		$GLOBALS['blc_filters'][ $tag ][] = $callback;
	}
}
function apply_filters( $tag, $value ) {
	foreach ( isset( $GLOBALS['blc_filters'][ $tag ] ) ? $GLOBALS['blc_filters'][ $tag ] : array() as $callback ) {
		$value = call_user_func( $callback, $value );
	}
	return $value;
}
function sanitize_title( $title ) { return strtolower( trim( preg_replace( '/[^A-Za-z0-9-]+/', '-', (string) $title ), '-' ) ); }
function _x( $text, $context, $domain = null ) { return $text; }

function add_action() {}
function add_meta_box() {}
function register_post_type() {}
function register_taxonomy_for_object_type() {}
function wp_enqueue_style() {}
function wp_enqueue_script() {}
function wp_nonce_field() {}
function get_template_directory_uri() { return '../theme-files'; }
function is_admin() { return false; }
function is_singular( $type = '' ) { return 'library_book' === $type; }
function is_post_type_archive( $type = '' ) { return false; }
function get_post_type_archive_link( $type ) { return 'library.html'; }
function has_post_thumbnail() { return false; }
function the_post_thumbnail( $size = '', $attr = array() ) {}
function has_excerpt() { return '' !== blc_preview_current( 'excerpt' ); }
function get_the_excerpt() { return blc_preview_current( 'excerpt' ); }
function the_posts_pagination( $args = array() ) {}
function comments_open() { return false; }
function get_comments_number() { return 0; }

/* ---- The loop ---- */
function blc_preview_current( $field = null ) {
	$post = isset( $GLOBALS['blc_current'] ) ? $GLOBALS['blc_current'] : $GLOBALS['blc_books'][0];
	return null === $field ? $post : ( isset( $post[ $field ] ) ? $post[ $field ] : '' );
}
function have_posts() { return $GLOBALS['blc_i'] < count( $GLOBALS['blc_queue'] ); }
function the_post() { $GLOBALS['blc_current'] = $GLOBALS['blc_queue'][ $GLOBALS['blc_i']++ ]; }
function get_the_ID() { return blc_preview_current( 'ID' ); }
function get_the_title() { return blc_preview_current( 'post_title' ); }
function the_title() { echo esc_html( get_the_title() ); }
function get_the_content() { return ''; }
function the_content() {}
function the_permalink() { echo esc_url( get_permalink( get_the_ID() ) ); }
function post_class( $extra = '' ) { echo 'class="' . esc_attr( trim( 'post ' . $extra ) ) . '"'; }
function get_post_field( $field, $post = null ) { return blc_preview_current( $field ); }

function blc_preview_find( $id ) {
	foreach ( $GLOBALS['blc_books'] as $book ) {
		if ( (int) $book['ID'] === (int) $id ) {
			return $book;
		}
	}
	return null;
}

function get_post_meta( $post_id, $key, $single = false ) {
	$book = blc_preview_find( $post_id );
	return ( $book && isset( $book['meta'][ $key ] ) ) ? $book['meta'][ $key ] : '';
}

function get_post( $id ) {
	$book = blc_preview_find( $id );
	if ( ! $book ) {
		return null;
	}
	return (object) array(
		'ID'          => $book['ID'],
		'post_type'   => 'library_book',
		'post_name'   => $book['post_name'],
		'post_title'  => $book['post_title'],
		'post_status' => 'publish',
	);
}

function get_permalink( $post = null ) {
	if ( is_object( $post ) && isset( $post->post_type ) && 'fan_page' === $post->post_type ) {
		return 'preview.html';
	}
	$id   = is_object( $post ) ? $post->ID : ( $post ? $post : get_the_ID() );
	$book = blc_preview_find( $id );
	return $book ? 'reader-' . $book['post_name'] . '.html' : '#';
}

function wp_get_attachment_url( $id ) { return false; }
function get_attached_file( $id ) { return false; }

/**
 * Only ever asked for the fan page behind a library book, in this preview.
 */
function get_posts( $args = array() ) {
	if ( isset( $args['post_type'] ) && 'fan_page' === $args['post_type'] ) {
		return array( (object) array(
			'ID'          => 201,
			'post_type'   => 'fan_page',
			'post_name'   => 'test-book',
			'post_title'  => 'A Test Book',
			'post_status' => 'publish',
		) );
	}
	return array();
}

function get_template_part( $slug, $name = null, $args = array() ) {
	$file = BLC_THEME_FILES . '/' . $slug . ( $name ? '-' . $name : '' ) . '.php';
	if ( ! is_readable( $file ) ) {
		fwrite( STDERR, "missing template part: {$file}\n" );
		return;
	}
	include $file;
}

/* -------------------------------------------------------------------------
 * Page chrome, standing in for header.php / footer.php
 * ---------------------------------------------------------------------- */
function get_header() {
	$title = esc_html( get_the_title() );
	echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$title} — BookLoversClub (preview)</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;1,400&family=Inter:wght@400;500;600&family=Space+Mono:wght@400&display=swap">
<link rel="stylesheet" href="theme-style.css">
<link rel="stylesheet" href="../theme-files/assets/css/fan-page.css">
<link rel="stylesheet" href="../theme-files/assets/css/reader.css">
<style>
.blc-preview-banner {
  font-family: var(--font-mono); font-size: 11px; letter-spacing: 0.06em;
  text-align: center; padding: 6px 16px; background: var(--color-forest); color: #fff;
}
</style>
<script>
(function () {
  try {
    var t = localStorage.getItem('blc-theme');
    if (!t && window.matchMedia('(prefers-color-scheme: dark)').matches) { t = 'dark'; }
    if (t === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); }
  } catch (e) {}
})();
</script>
</head>
<body>
<div class="blc-preview-banner">Static preview — rendered from the real theme templates by <code>scripts/preview_reader.php</code></div>
<header class="site-header">
  <div class="container">
    <a class="brand" href="#"><span class="brand-name">BookLoversClub</span></a>
    <nav class="main-nav" aria-label="Primary">
      <ul>
        <li><a href="preview.html">Fan Pages</a></li>
        <li class="current-menu-item"><a href="library.html">Reading Room</a></li>
      </ul>
    </nav>
    <div class="header-actions">
      <button class="theme-toggle" type="button" aria-pressed="false" aria-label="Toggle dark mode">☾</button>
    </div>
  </div>
</header>
<main id="main">
HTML;
}

function get_footer() {
	$script = 0 === strpos( $GLOBALS['blc_out'], 'reader' )
		? '<script type="module" src="../theme-files/assets/js/reader.js"></script>'
		: '<script src="../theme-files/assets/js/library.js"></script>';
	echo <<<HTML
</main>
<footer class="site-footer">
  <div class="container"><div class="footer-colophon">Preview build — not a live page.</div></div>
</footer>
<script>
document.querySelector('.theme-toggle').addEventListener('click', function () {
  var dark = document.documentElement.getAttribute('data-theme') === 'dark';
  document.documentElement.setAttribute('data-theme', dark ? 'light' : 'dark');
  try { localStorage.setItem('blc-theme', dark ? 'light' : 'dark'); } catch (e) {}
});
</script>
{$script}
</body>
</html>
HTML;
}

/* -------------------------------------------------------------------------
 * Render
 * ---------------------------------------------------------------------- */
require BLC_THEME_FILES . '/inc/reader.php';

// The preview's shelf sits next to the generated HTML — the same two filters a
// live site would use to move its shelf somewhere other than the web root.
add_filter( 'blc_reader_shelf_path', function () { return BLC_PREVIEW_SHELF; } );
add_filter( 'blc_reader_shelf_url', function () { return 'shelf'; } );

/**
 * Render one template over a queue of posts.
 */
function blc_preview_render( $template, $queue, $out ) {
	$GLOBALS['blc_queue'] = $queue;
	$GLOBALS['blc_i']     = 0;
	$GLOBALS['blc_out']   = $out;

	ob_start();
	require BLC_THEME_FILES . '/' . $template;
	$html = ob_get_clean();

	$path = BLC_PREVIEW_DIR . '/' . $out;
	file_put_contents( $path, $html );
	printf( "wrote %s (%d KB)\n", $path, (int) round( strlen( $html ) / 1024 ) );
}

// One reader page per book, plus reader.html as the documented entry point.
foreach ( $GLOBALS['blc_books'] as $book ) {
	blc_preview_render( 'single-library_book.php', array( $book ), 'reader-' . $book['post_name'] . '.html' );
}
blc_preview_render( 'single-library_book.php', array( $GLOBALS['blc_books'][0] ), 'reader.html' );

// The Reading Room, on the books that have a file — the same rule
// blc_reader_archive_query() applies to the real archive.
$shelf = array_values( array_filter( $GLOBALS['blc_books'], function ( $book ) {
	return '' !== $book['meta']['_blc_epub_file'] || '' !== $book['meta']['_blc_epub_url'];
} ) );
blc_preview_render( 'archive-library_book.php', $shelf, 'library.html' );

if ( ! file_exists( BLC_PREVIEW_SHELF . '/test-book.epub' ) ) {
	fwrite( STDERR, "\nNo test-book.epub on the preview shelf — run: python3 scripts/make_test_epub.py\n" );
}
