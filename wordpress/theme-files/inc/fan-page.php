<?php
/**
 * Fan Pages — custom post type, research-JSON data layer, and template helpers.
 *
 * A fan page is rendered from the research JSON produced by /research-book
 * (see research/fan-page.schema.json). The JSON lives in the `_blc_fan_data`
 * post meta; the templates only ever read it through blc_fan_get(), so a
 * missing or partial field degrades to an unrendered section rather than a
 * fatal error.
 *
 * @package BookLoversClub
 */

/* -------------------------------------------------------------------------
 * 1. Post type
 * ---------------------------------------------------------------------- */
function blc_register_fan_pages() {
	register_post_type( 'fan_page', array(
		'labels' => array(
			'name'          => __( 'Fan Pages', 'bookloversclub' ),
			'singular_name' => __( 'Fan Page', 'bookloversclub' ),
			'add_new_item'  => __( 'Add New Fan Page', 'bookloversclub' ),
		),
		'public'       => true,
		'has_archive'  => 'books',
		'rewrite'      => array( 'slug' => 'books' ),
		'menu_icon'    => 'dashicons-heart',
		'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'author', 'revisions' ),
		'show_in_rest' => true,
	) );

	register_taxonomy_for_object_type( 'genre', 'fan_page' );
}
add_action( 'init', 'blc_register_fan_pages' );

/* -------------------------------------------------------------------------
 * 2. Data layer
 * ---------------------------------------------------------------------- */

/**
 * Decode the research JSON for a post, once per request.
 *
 * @param int|null $post_id Post ID, or null for the current post.
 * @return array Decoded research data, or an empty array when absent/invalid.
 */
function blc_fan_data( $post_id = null ) {
	static $cache = array();

	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$raw     = get_post_meta( $post_id, '_blc_fan_data', true );
	$decoded = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : null;

	$cache[ $post_id ] = is_array( $decoded ) ? $decoded : array();
	return $cache[ $post_id ];
}

/**
 * Read a dot-path out of the research data.
 *
 * blc_fan_get( $data, 'book.title' ) — returns $default when any step is
 * missing, null, or an empty string/array, so templates can branch on truthiness
 * without nested isset() chains.
 *
 * @param array  $data    Research data.
 * @param string $path    Dot-separated path, e.g. 'craft.standout_detail'.
 * @param mixed  $default Returned when the path is absent or empty.
 * @return mixed
 */
function blc_fan_get( $data, $path, $default = null ) {
	$node = $data;
	foreach ( explode( '.', $path ) as $key ) {
		if ( ! is_array( $node ) || ! array_key_exists( $key, $node ) ) {
			return $default;
		}
		$node = $node[ $key ];
	}
	if ( null === $node || '' === $node || array() === $node ) {
		return $default;
	}
	return $node;
}

/**
 * Whether a section has enough content to be worth rendering.
 *
 * The cherry-pick tier in the README leans on this: a section whose listed
 * fields are all empty is dropped rather than rendered as a stub.
 *
 * @param array  $data   Research data.
 * @param string $path   Dot-path to the section.
 * @param array  $fields Field names within it; empty means "the section itself".
 * @return bool
 */
function blc_fan_has( $data, $path, $fields = array() ) {
	$section = blc_fan_get( $data, $path );
	if ( ! $section ) {
		return false;
	}
	if ( ! $fields ) {
		return true;
	}
	foreach ( $fields as $field ) {
		if ( blc_fan_get( $data, $path . '.' . $field ) ) {
			return true;
		}
	}
	return false;
}

/**
 * The representative quote for the hero.
 *
 * Editors can pin one via the Fan Page Details meta box; otherwise the first
 * non-spoiler `tattoo_tier` quote wins, then any non-spoiler quote. The hero
 * never shows a spoiler.
 *
 * @param array $data    Research data.
 * @param int   $post_id Post ID.
 * @return array|null { text, attribution } or null when there is nothing safe to show.
 */
function blc_fan_hero_quote( $data, $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();

	$pinned = get_post_meta( $post_id, '_blc_hero_quote', true );
	if ( $pinned ) {
		return array(
			'text'        => $pinned,
			'attribution' => get_post_meta( $post_id, '_blc_hero_quote_attribution', true ),
		);
	}

	$quotes = blc_fan_get( $data, 'book_quotes', array() );
	if ( ! is_array( $quotes ) ) {
		return null;
	}

	$fallback = null;
	foreach ( $quotes as $quote ) {
		if ( empty( $quote['text'] ) || ! empty( $quote['spoiler'] ) ) {
			continue;
		}
		$entry = array(
			'text'        => $quote['text'],
			'attribution' => blc_fan_quote_attribution( $quote, $data ),
		);
		if ( ! empty( $quote['tattoo_tier'] ) ) {
			return $entry;
		}
		if ( null === $fallback ) {
			$fallback = $entry;
		}
	}
	return $fallback;
}

/**
 * Attribution line for a book quote: speaker and/or location, else the author.
 *
 * @param array $quote One `book_quotes` entry.
 * @param array $data  Research data.
 * @return string
 */
function blc_fan_quote_attribution( $quote, $data ) {
	$parts = array();
	if ( ! empty( $quote['speaker'] ) ) {
		$parts[] = $quote['speaker'];
	}
	if ( ! empty( $quote['location'] ) ) {
		$parts[] = $quote['location'];
	}
	if ( ! $parts ) {
		$author = blc_fan_get( $data, 'book.author' );
		if ( $author ) {
			$parts[] = $author;
		}
	}
	return implode( ', ', $parts );
}

/* -------------------------------------------------------------------------
 * 3. Template helpers
 * ---------------------------------------------------------------------- */

/**
 * Open a fan-page section. Pair with blc_fan_section_close().
 *
 * @param string $id     Anchor id, also used by the section nav.
 * @param string $kicker Space Mono eyebrow above the heading.
 * @param string $title  Section heading.
 * @param string $width  'reading-column' (default) or '' for the full container.
 */
function blc_fan_section_open( $id, $kicker, $title, $width = 'reading-column' ) {
	printf(
		'<section class="fan-section" id="%s"><div class="container %s">',
		esc_attr( $id ),
		esc_attr( $width )
	);
	if ( $kicker ) {
		printf( '<div class="fan-kicker">%s</div>', esc_html( $kicker ) );
	}
	if ( $title ) {
		printf( '<h2 class="fan-section-title">%s</h2>', esc_html( $title ) );
	}
}

function blc_fan_section_close() {
	echo '</div></section>';
}

/**
 * Wrap spoiler content in a click-to-reveal shell.
 *
 * Degrades to plain visible text without JS only after the reader opts in —
 * the markup ships hidden, so a no-JS visitor sees the notice, not the spoiler.
 *
 * @param string $html       Already-escaped HTML to hide.
 * @param bool   $is_spoiler When false, $html is returned untouched.
 * @return string
 */
function blc_fan_spoiler( $html, $is_spoiler ) {
	if ( ! $is_spoiler ) {
		return $html;
	}
	return '<span class="fan-spoiler" data-spoiler>'
		. '<button type="button" class="fan-spoiler-toggle">' . esc_html__( 'Spoiler — tap to reveal', 'bookloversclub' ) . '</button>'
		. '<span class="fan-spoiler-content" hidden>' . $html . '</span>'
		. '</span>';
}

/**
 * Human label for a reader-voice angle (the `angle` enum in the schema).
 *
 * @param string $angle Enum value.
 * @return string
 */
function blc_fan_angle_label( $angle ) {
	$labels = array(
		'love'                 => __( 'Why they love it', 'bookloversclub' ),
		'craft'                => __( 'On the craft', 'bookloversclub' ),
		'character'            => __( 'On a character', 'bookloversclub' ),
		'emotional-impact'     => __( 'Emotional impact', 'bookloversclub' ),
		'reading-experience'   => __( 'Reading experience', 'bookloversclub' ),
		'reread'               => __( 'On rereading', 'bookloversclub' ),
		'converted-skeptic'    => __( 'Converted skeptic', 'bookloversclub' ),
		'critical'             => __( 'The other view', 'bookloversclub' ),
		'format-recommendation'=> __( 'Format advice', 'bookloversclub' ),
		'funny'                => __( 'Funny', 'bookloversclub' ),
	);
	return isset( $labels[ $angle ] ) ? $labels[ $angle ] : ucfirst( str_replace( '-', ' ', (string) $angle ) );
}

/**
 * The sections this page actually renders, in order, for the sticky nav.
 *
 * Mirrors the render conditions in single-fan_page.php: always-on sections are
 * listed unconditionally, cherry-pick sections only when they carry content.
 *
 * @param array $data Research data.
 * @return array Map of anchor id => nav label.
 */
function blc_fan_sections( $data ) {
	$sections = array();

	$add = function ( $id, $label, $condition = true ) use ( &$sections ) {
		if ( $condition ) {
			$sections[ $id ] = $label;
		}
	};

	$add( 'resonance', __( 'Why it lands', 'bookloversclub' ), blc_fan_has( $data, 'resonance', array( 'core_reason', 'what_it_does_to_readers', 'the_moment' ) ) );
	$add( 'craft', __( 'The craft', 'bookloversclub' ), blc_fan_has( $data, 'craft', array( 'standout_detail', 'unique_claim', 'formal_choices' ) ) );
	$add( 'characters', __( 'Characters', 'bookloversclub' ), (bool) blc_fan_get( $data, 'characters' ) );
	$add( 'quotes', __( 'Lines', 'bookloversclub' ), (bool) blc_fan_get( $data, 'book_quotes' ) );
	$add( 'voices', __( 'From the club', 'bookloversclub' ), (bool) blc_fan_get( $data, 'reader_voices' ) );
	$add( 'setting', __( 'The place', 'bookloversclub' ), blc_fan_has( $data, 'setting', array( 'place', 'setting_as_character', 'sensory_signature' ) ) );
	$add( 'misconceptions', __( 'What you\'ve heard', 'bookloversclub' ), (bool) blc_fan_get( $data, 'misconceptions' ) );
	$add( 'debates', __( 'Contested', 'bookloversclub' ), (bool) blc_fan_get( $data, 'debates' ) );
	$add( 'reception', __( 'Its life', 'bookloversclub' ), blc_fan_has( $data, 'reception', array( 'initial_reception', 'reputation_arc', 'cultural_moment', 'awards' ) ) );
	$add( 'author', __( 'The author', 'bookloversclub' ), blc_fan_has( $data, 'author', array( 'biography_that_colors_the_read', 'where_it_sits_in_their_work', 'author_on_the_book' ) ) );
	$add( 'fandom', __( 'The fandom', 'bookloversclub' ), blc_fan_has( $data, 'fandom', array( 'communities', 'rituals', 'artifacts', 'running_jokes' ) ) );
	$add( 'before-you-start', __( 'Before you start', 'bookloversclub' ), blc_fan_has( $data, 'before_you_start', array( 'who_its_for', 'wish_id_known', 'audiobook', 'content_warnings' ) ) );
	$add( 'club-kit', __( 'Run it in your club', 'bookloversclub' ), blc_fan_has( $data, 'club_kit', array( 'discussion_questions', 'read_aloud_passages', 'pairing' ) ) );
	$add( 'if-you-loved-this', __( 'Read next', 'bookloversclub' ), (bool) blc_fan_get( $data, 'if_you_loved_this' ) );

	return $sections;
}

/* -------------------------------------------------------------------------
 * 4. Admin: research JSON + pinned hero quote
 * ---------------------------------------------------------------------- */
function blc_fan_meta_boxes() {
	add_meta_box( 'blc_fan_hero', __( 'Fan Page Details', 'bookloversclub' ), 'blc_fan_hero_meta_box_html', 'fan_page', 'side' );
	add_meta_box( 'blc_fan_json', __( 'Research Data (JSON)', 'bookloversclub' ), 'blc_fan_json_meta_box_html', 'fan_page', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'blc_fan_meta_boxes' );

function blc_fan_hero_meta_box_html( $post ) {
	wp_nonce_field( 'blc_fan_meta', 'blc_fan_meta_nonce' );
	$quote       = get_post_meta( $post->ID, '_blc_hero_quote', true );
	$attribution = get_post_meta( $post->ID, '_blc_hero_quote_attribution', true );
	?>
	<p>
		<label for="blc_hero_quote"><strong><?php esc_html_e( 'Representative quote', 'bookloversclub' ); ?></strong></label>
		<textarea id="blc_hero_quote" name="blc_hero_quote" class="widefat" rows="4"><?php echo esc_textarea( $quote ); ?></textarea>
	</p>
	<p>
		<label for="blc_hero_quote_attribution"><strong><?php esc_html_e( 'Quote attribution', 'bookloversclub' ); ?></strong></label>
		<input type="text" id="blc_hero_quote_attribution" name="blc_hero_quote_attribution" class="widefat" value="<?php echo esc_attr( $attribution ); ?>">
	</p>
	<p class="description">
		<?php esc_html_e( 'Leave the quote blank to use the first non-spoiler tattoo-tier line from the research data. Set the book cover as the Featured Image.', 'bookloversclub' ); ?>
	</p>
	<?php
}

function blc_fan_json_meta_box_html( $post ) {
	$raw = get_post_meta( $post->ID, '_blc_fan_data', true );
	?>
	<p class="description" style="margin-bottom:8px;">
		<?php esc_html_e( 'Paste the contents of data/pages/<slug>.json. Everything below the hero renders from this.', 'bookloversclub' ); ?>
	</p>
	<textarea id="blc_fan_data" name="blc_fan_data" class="widefat code" rows="18" spellcheck="false"><?php echo esc_textarea( $raw ); ?></textarea>
	<?php
	$error = get_post_meta( $post->ID, '_blc_fan_data_error', true );
	if ( $error ) {
		printf(
			'<p style="color:#b32d2e;margin-top:8px;"><strong>%s</strong> %s</p>',
			esc_html__( 'Last save could not parse as JSON:', 'bookloversclub' ),
			esc_html( $error )
		);
	}
}

function blc_fan_save_meta( $post_id ) {
	if ( ! isset( $_POST['blc_fan_meta_nonce'] ) || ! wp_verify_nonce( $_POST['blc_fan_meta_nonce'], 'blc_fan_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['blc_hero_quote'] ) ) {
		update_post_meta( $post_id, '_blc_hero_quote', sanitize_textarea_field( wp_unslash( $_POST['blc_hero_quote'] ) ) );
	}
	if ( isset( $_POST['blc_hero_quote_attribution'] ) ) {
		update_post_meta( $post_id, '_blc_hero_quote_attribution', sanitize_text_field( wp_unslash( $_POST['blc_hero_quote_attribution'] ) ) );
	}

	if ( isset( $_POST['blc_fan_data'] ) ) {
		$raw = trim( wp_unslash( $_POST['blc_fan_data'] ) );
		if ( '' === $raw ) {
			delete_post_meta( $post_id, '_blc_fan_data' );
			delete_post_meta( $post_id, '_blc_fan_data_error' );
		} else {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				// Store re-encoded so the meta is always canonical, valid JSON.
				update_post_meta( $post_id, '_blc_fan_data', wp_slash( wp_json_encode( $decoded ) ) );
				delete_post_meta( $post_id, '_blc_fan_data_error' );
			} else {
				// Keep the editor's text so their work isn't lost, and flag why.
				update_post_meta( $post_id, '_blc_fan_data', wp_slash( $raw ) );
				update_post_meta( $post_id, '_blc_fan_data_error', json_last_error_msg() );
			}
		}
	}
}
add_action( 'save_post_fan_page', 'blc_fan_save_meta' );

/* -------------------------------------------------------------------------
 * 5. Assets — loaded only where a fan page is on screen
 * ---------------------------------------------------------------------- */
function blc_fan_assets() {
	if ( ! is_singular( 'fan_page' ) && ! is_post_type_archive( 'fan_page' ) ) {
		return;
	}
	wp_enqueue_style(
		'blc-fan-page',
		get_template_directory_uri() . '/assets/css/fan-page.css',
		array( 'blc-style' ),
		BLC_VERSION
	);
	if ( is_singular( 'fan_page' ) ) {
		wp_enqueue_script(
			'blc-fan-page',
			get_template_directory_uri() . '/assets/js/fan-page.js',
			array(),
			BLC_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'blc_fan_assets' );

/**
 * Give fan pages the reading-progress ribbon the other long reads get.
 */
function blc_fan_body_classes( $classes ) {
	if ( is_singular( 'fan_page' ) ) {
		$classes[] = 'has-reading-ribbon';
	}
	return $classes;
}
add_filter( 'body_class', 'blc_fan_body_classes' );
