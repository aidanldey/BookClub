<?php
/**
 * Render a fan page to static HTML — no WordPress required.
 *
 * Shims the handful of WordPress functions the fan-page templates call, then
 * includes the real template files. The templates are the single source of
 * truth: what you see here is what the theme renders.
 *
 * Usage:
 *   php scripts/preview_fan_page.php <research.json> [out.html] [--cover=path]
 *
 * @package BookLoversClub
 */

$argv_in = $argv;
array_shift( $argv_in );

$options = array( 'cover' => 'wordpress/preview/sample-cover.svg' );
$paths   = array();
foreach ( $argv_in as $arg ) {
	if ( 0 === strpos( $arg, '--cover=' ) ) {
		$options['cover'] = substr( $arg, 8 );
	} else {
		$paths[] = $arg;
	}
}

if ( ! $paths ) {
	fwrite( STDERR, "usage: php scripts/preview_fan_page.php <research.json> [out.html] [--cover=path]\n" );
	exit( 1 );
}

$json_path = $paths[0];
$out_path  = isset( $paths[1] ) ? $paths[1] : 'wordpress/preview/preview.html';

if ( ! is_readable( $json_path ) ) {
	fwrite( STDERR, "cannot read {$json_path}\n" );
	exit( 1 );
}

$research = json_decode( file_get_contents( $json_path ), true );
if ( ! is_array( $research ) ) {
	fwrite( STDERR, "not valid JSON: {$json_path} — " . json_last_error_msg() . "\n" );
	exit( 1 );
}

define( 'BLC_THEME_FILES', __DIR__ . '/../wordpress/theme-files' );
define( 'BLC_VERSION', 'preview' );

/* -------------------------------------------------------------------------
 * The post under preview
 * ---------------------------------------------------------------------- */
$GLOBALS['blc_preview'] = array(
	'id'      => 1,
	'title'   => isset( $research['book']['title'] ) ? $research['book']['title'] : 'Untitled',
	'content' => '',
	'cover'   => ( $options['cover'] && is_readable( $options['cover'] ) ) ? $options['cover'] : '',
	'meta'    => array( '_blc_fan_data' => json_encode( $research ) ),
);

/* -------------------------------------------------------------------------
 * WordPress shims — only what the fan-page templates actually touch
 * ---------------------------------------------------------------------- */
function __( $text, $domain = null ) { return $text; }
function _e( $text, $domain = null ) { echo $text; }
function esc_html__( $text, $domain = null ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr__( $text, $domain = null ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html_e( $text, $domain = null ) { echo esc_html__( $text ); }
function esc_attr_e( $text, $domain = null ) { echo esc_attr__( $text ); }
function _n( $single, $plural, $number, $domain = null ) { return 1 === (int) $number ? $single : $plural; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $url ) { return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' ); }
function esc_textarea( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function wp_kses_post( $html ) { return $html; }

// Hook/registration APIs are inert in the preview.
function add_action() {}
function add_filter() {}
function add_meta_box() {}
function register_post_type() {}
function register_taxonomy_for_object_type() {}
function wp_enqueue_style() {}
function wp_enqueue_script() {}
function get_template_directory_uri() { return '.'; }
function is_post_type_archive() { return false; }
function is_singular() { return true; }

function get_the_ID() { return $GLOBALS['blc_preview']['id']; }
function get_the_title() { return $GLOBALS['blc_preview']['title']; }
function the_title() { echo esc_html( get_the_title() ); }
function get_the_content() { return $GLOBALS['blc_preview']['content']; }
function the_content() { echo $GLOBALS['blc_preview']['content']; }
function the_permalink() { echo '#'; }
function post_class( $extra = '' ) { echo 'class="' . esc_attr( trim( 'post ' . $extra ) ) . '"'; }
function comments_open() { return false; }
function get_comments_number() { return 0; }
function comments_template() {}

function get_post_meta( $post_id, $key, $single = false ) {
	$meta = $GLOBALS['blc_preview']['meta'];
	return isset( $meta[ $key ] ) ? $meta[ $key ] : '';
}

function has_post_thumbnail() { return '' !== $GLOBALS['blc_preview']['cover']; }

function the_post_thumbnail( $size = '', $attr = array() ) {
	$cover = $GLOBALS['blc_preview']['cover'];
	if ( ! $cover ) {
		return;
	}
	$alt  = isset( $attr['alt'] ) ? $attr['alt'] : '';
	$type = 'svg' === strtolower( pathinfo( $cover, PATHINFO_EXTENSION ) ) ? 'image/svg+xml' : 'image/jpeg';
	printf(
		'<img src="data:%s;base64,%s" alt="%s">',
		$type,
		base64_encode( file_get_contents( $cover ) ),
		esc_attr( $alt )
	);
}

/**
 * Include a real template part, passing $args the way WordPress 5.5+ does.
 */
function get_template_part( $slug, $name = null, $args = array() ) {
	$file = BLC_THEME_FILES . '/' . $slug . ( $name ? '-' . $name : '' ) . '.php';
	if ( ! is_readable( $file ) ) {
		fwrite( STDERR, "missing template part: {$file}\n" );
		return;
	}
	include $file;
}

// The loop, for one post.
$GLOBALS['blc_loop_done'] = false;
function have_posts() {
	if ( $GLOBALS['blc_loop_done'] ) {
		return false;
	}
	return true;
}
function the_post() { $GLOBALS['blc_loop_done'] = true; }

/* -------------------------------------------------------------------------
 * Static page shell, standing in for header.php / footer.php
 * ---------------------------------------------------------------------- */
function blc_preview_read( $path ) {
	$full = __DIR__ . '/../' . $path;
	return is_readable( $full ) ? file_get_contents( $full ) : "/* missing: {$path} */";
}

function get_header() {
	$title = esc_html( get_the_title() );
	// Inlined so the preview is a single self-contained file.
	$css = blc_preview_read( 'wordpress/preview/theme-style.css' )
		. "\n" . blc_preview_read( 'wordpress/theme-files/assets/css/fan-page.css' );
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
<style>{$css}</style>
<style>
/* Preview chrome only — not part of the theme. */
.blc-preview-banner {
  font-family: var(--font-mono);
  font-size: 11px;
  letter-spacing: 0.06em;
  text-align: center;
  padding: 6px 16px;
  background: var(--color-forest);
  color: #fff;
}
.blc-preview-banner code { font-size: 11px; color: #fff; opacity: 0.85; }
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
<body class="has-reading-ribbon">
<div class="blc-preview-banner">Static preview — rendered from the real theme templates by <code>scripts/preview_fan_page.php</code></div>
<header class="site-header">
  <div class="container">
    <a class="brand" href="#">
      <svg class="brand-mark" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
        <path d="M4 2h18l6 6v22H4V2z" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
        <path d="M22 2v6h6" fill="#C4956A" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
        <path d="M11 10h5.2c2.2 0 3.6 1.1 3.6 2.9 0 1.2-.7 2.1-1.7 2.5 1.4.4 2.3 1.5 2.3 3 0 2-1.6 3.3-4 3.3H11V10z" fill="currentColor"/>
      </svg>
      <span class="brand-name">BookLoversClub</span>
    </a>
    <nav class="main-nav" aria-label="Primary">
      <ul>
        <li><a href="#">Reviews</a></li>
        <li><a href="#">Discussions</a></li>
        <li><a href="#">Book Lists</a></li>
        <li class="current-menu-item"><a href="#">Fan Pages</a></li>
      </ul>
    </nav>
    <div class="header-actions">
      <button class="theme-toggle" type="button" aria-pressed="false" aria-label="Toggle dark mode">
        <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
        <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
      </button>
    </div>
  </div>
</header>
<main id="main">
HTML;
}

function get_footer() {
	$js = blc_preview_read( 'wordpress/theme-files/assets/js/fan-page.js' );
	echo <<<HTML
</main>
<footer class="site-footer">
  <div class="container">
    <div>
      <h4>BookLoversClub</h4>
      <p style="color: var(--text-secondary); font-size: 14px;">A club for people who underline things.</p>
    </div>
    <div class="footer-colophon">Preview build — not a live page.</div>
  </div>
</footer>
<script>
document.querySelector('.theme-toggle').addEventListener('click', function () {
  var dark = document.documentElement.getAttribute('data-theme') === 'dark';
  document.documentElement.setAttribute('data-theme', dark ? 'light' : 'dark');
  try { localStorage.setItem('blc-theme', dark ? 'light' : 'dark'); } catch (e) {}
});
</script>
<script>{$js}</script>
</body>
</html>
HTML;
}

/* -------------------------------------------------------------------------
 * Render
 * ---------------------------------------------------------------------- */
require BLC_THEME_FILES . '/inc/fan-page.php';

ob_start();
require BLC_THEME_FILES . '/single-fan_page.php';
$html = ob_get_clean();

$dir = dirname( $out_path );
if ( ! is_dir( $dir ) ) {
	mkdir( $dir, 0777, true );
}
file_put_contents( $out_path, $html );

$sections = blc_fan_sections( $research );
printf(
	"wrote %s (%d KB)\n  sections rendered: %d — %s\n",
	$out_path,
	(int) round( strlen( $html ) / 1024 ),
	count( $sections ),
	implode( ', ', array_keys( $sections ) )
);
