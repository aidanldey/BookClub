<?php
/**
 * Reading Room — the `library_book` post type and the embedded EPUB reader.
 *
 * A library book is a public-domain edition the club hosts and reads in the
 * browser: an EPUB in the media library, a rights statement, and a link back to
 * the source. Fan pages link into it, so `/books/frankenstein/` can hand a
 * reader straight to `/read/frankenstein/`.
 *
 * Rendering is done client-side by foliate-js (assets/vendor/foliate-js), so
 * there is no server-side conversion step and no third-party embed. WordPress's
 * only jobs are storing the file, describing its provenance, and telling the
 * reader where to find it.
 *
 * The library is public-domain only by design — an un-DRM'd EPUB served over
 * HTTP is downloadable by anyone who opens it, so nothing in copyright belongs
 * here. See wordpress/README.md.
 *
 * @package BookLoversClub
 */

/* -------------------------------------------------------------------------
 * 1. Post type
 * ---------------------------------------------------------------------- */
function blc_register_library_books() {
	register_post_type( 'library_book', array(
		'labels' => array(
			'name'          => __( 'Reading Room', 'bookloversclub' ),
			'singular_name' => __( 'Library Book', 'bookloversclub' ),
			'add_new_item'  => __( 'Add Book to the Reading Room', 'bookloversclub' ),
			'edit_item'     => __( 'Edit Library Book', 'bookloversclub' ),
		),
		'public'       => true,
		'has_archive'  => 'library',
		'rewrite'      => array( 'slug' => 'read', 'with_front' => false ),
		'menu_icon'    => 'dashicons-book-alt',
		'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ),
		'show_in_rest' => true,
	) );

	register_taxonomy_for_object_type( 'genre', 'library_book' );
}
add_action( 'init', 'blc_register_library_books' );

/* -------------------------------------------------------------------------
 * 2. EPUB uploads
 * ---------------------------------------------------------------------- */

/**
 * Let the media library accept .epub. WordPress blocks it by default.
 */
function blc_reader_upload_mimes( $mimes ) {
	$mimes['epub'] = 'application/epub+zip';
	return $mimes;
}
add_filter( 'upload_mimes', 'blc_reader_upload_mimes' );

/**
 * Keep the real-mime check from rejecting valid EPUBs.
 *
 * An EPUB is a zip, so fileinfo often reports `application/zip`. WordPress
 * treats that disagreement with the extension as a forgery and drops the
 * upload; this accepts it when the extension is .epub and the sniffed type is
 * one of the ways a zip shows up.
 */
function blc_reader_check_filetype( $data, $file, $filename, $mimes, $real_mime = null ) {
	if ( ! empty( $data['ext'] ) && ! empty( $data['type'] ) ) {
		return $data;
	}
	if ( 'epub' !== strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
		return $data;
	}

	$zip_types = array( 'application/epub+zip', 'application/zip', 'application/octet-stream' );
	if ( null === $real_mime || in_array( $real_mime, $zip_types, true ) ) {
		$data['ext']  = 'epub';
		$data['type'] = 'application/epub+zip';
	}
	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'blc_reader_check_filetype', 10, 5 );

/* -------------------------------------------------------------------------
 * 3. The shelf — /shelf/ on the site root
 * ---------------------------------------------------------------------- */

/**
 * Public URL of the shelf directory, with a trailing slash.
 *
 * The club keeps its own copies of each edition in a plain folder at the site
 * root, outside the media library, so a book can be added by dropping a file in
 * over SFTP. Filter `blc_reader_shelf_url` (and `blc_reader_shelf_path` with
 * it) to put the shelf somewhere else.
 *
 * @return string
 */
function blc_reader_shelf_url() {
	return trailingslashit( apply_filters( 'blc_reader_shelf_url', home_url( '/shelf' ) ) );
}

/**
 * Filesystem path of the shelf directory, with a trailing slash.
 *
 * Used to list what's available and to read file sizes. The reader itself only
 * ever needs the URL, so a shelf served from somewhere unreadable still works —
 * it just can't show a size or offer the filename list in wp-admin.
 *
 * @return string
 */
function blc_reader_shelf_path() {
	return trailingslashit( apply_filters( 'blc_reader_shelf_path', ABSPATH . 'shelf' ) );
}

/**
 * Reduce an editor's input to a bare EPUB filename, or nothing.
 *
 * Only a filename is ever accepted: basename() drops any path an editor pasted
 * (or tried to), and the pattern refuses anything that isn't a plain .epub.
 * Directory traversal can't survive either step.
 *
 * @param string $raw Whatever was typed or stored.
 * @return string Safe filename, or '' when it isn't one.
 */
function blc_reader_sanitize_shelf_file( $raw ) {
	$file = basename( trim( wp_unslash( (string) $raw ) ) );
	$file = str_replace( chr( 0 ), '', $file );

	if ( ! preg_match( '/^[A-Za-z0-9][A-Za-z0-9 ._-]*\.epub$/i', $file ) ) {
		return '';
	}
	return $file;
}

/**
 * URL for a file on the shelf.
 *
 * @param string $file Filename, sanitized or not.
 * @return string Empty when the name isn't a usable EPUB filename.
 */
function blc_reader_shelf_file_url( $file ) {
	$file = blc_reader_sanitize_shelf_file( $file );
	return $file ? blc_reader_shelf_url() . rawurlencode( $file ) : '';
}

/**
 * Every EPUB currently sitting on the shelf.
 *
 * @return string[] Filenames, sorted. Empty when the directory can't be read.
 */
function blc_reader_shelf_files() {
	static $files = null;
	if ( null !== $files ) {
		return $files;
	}

	$found = glob( blc_reader_shelf_path() . '*.epub' );
	$files = $found ? array_values( array_filter( array_map( function ( $path ) {
		return blc_reader_sanitize_shelf_file( basename( $path ) );
	}, $found ) ) ) : array();

	sort( $files );
	return $files;
}

/**
 * The shelf file that belongs to a slug, when there is exactly one.
 *
 * Standard Ebooks names its downloads `<author>_<title>.epub`, so the file for
 * `frankenstein` arrives called `mary-shelley_frankenstein.epub`. An exact
 * `<slug>.epub` wins; otherwise a file whose name ends in the slug counts, but
 * only if it is the only one — two candidates is a question for a human, not a
 * guess.
 *
 * @param string $slug Post slug.
 * @return string Filename, or '' when there is no unambiguous match.
 */
function blc_reader_shelf_file_for_slug( $slug ) {
	$slug = sanitize_title( $slug );
	if ( ! $slug ) {
		return '';
	}

	$files = blc_reader_shelf_files();
	if ( in_array( $slug . '.epub', $files, true ) ) {
		return $slug . '.epub';
	}

	$matches = array();
	foreach ( $files as $file ) {
		$stem = strtolower( preg_replace( '/\.epub$/i', '', $file ) );
		if ( preg_match( '/(^|[_-])' . preg_quote( $slug, '/' ) . '$/', $stem ) ) {
			$matches[] = $file;
		}
	}

	return 1 === count( $matches ) ? $matches[0] : '';
}

/* -------------------------------------------------------------------------
 * 4. Data layer
 * ---------------------------------------------------------------------- */

/**
 * Everything the templates need to know about one library book.
 *
 * @param int|null $post_id Post ID, or null for the current post.
 * The file can come from three places, in this order: the media library, the
 * shelf directory, or a URL typed in by hand. Whichever answers first wins, and
 * `epub_from` says which one did.
 *
 * @return array {
 *     @type string $epub_url    URL of the EPUB, '' when unset.
 *     @type int    $epub_id     Attachment ID, 0 unless it came from the media library.
 *     @type string $epub_file   Shelf filename, '' unless it came from the shelf.
 *     @type string $epub_from   'media', 'shelf', 'url', or '' when there is no file.
 *     @type int    $epub_size   File size in bytes, 0 when unknown.
 *     @type string $author      Author, for the byline.
 *     @type string $translator  Translator, when the edition has one.
 *     @type string $year        Year first published.
 *     @type string $rights      Rights statement, e.g. "Public domain in the US".
 *     @type string $source_name Where the edition came from.
 *     @type string $source_url  Link to that source.
 * }
 */
function blc_reader_meta( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();

	$epub_id   = (int) get_post_meta( $post_id, '_blc_epub_id', true );
	$epub_file = blc_reader_sanitize_shelf_file( get_post_meta( $post_id, '_blc_epub_file', true ) );
	$epub_url  = (string) get_post_meta( $post_id, '_blc_epub_url', true );
	$from      = '';
	$size      = 0;

	if ( $epub_id ) {
		$attached = wp_get_attachment_url( $epub_id );
		if ( $attached ) {
			$epub_url = $attached;
			$from     = 'media';
			$path     = get_attached_file( $epub_id );
			if ( $path && file_exists( $path ) ) {
				$size = (int) filesize( $path );
			}
		} else {
			// The attachment was deleted out from under the post.
			$epub_id = 0;
		}
	}

	if ( ! $from && $epub_file ) {
		$epub_url = blc_reader_shelf_file_url( $epub_file );
		$from     = 'shelf';
		$path     = blc_reader_shelf_path() . $epub_file;
		// The shelf may be served from somewhere this process can't read; that
		// costs a file size in wp-admin and nothing else.
		if ( is_readable( $path ) ) {
			$size = (int) filesize( $path );
		}
	}

	if ( ! $from && $epub_url ) {
		$from = 'url';
	}

	return array(
		'epub_url'    => $epub_url,
		'epub_id'     => $epub_id,
		'epub_file'   => $from === 'shelf' ? $epub_file : '',
		'epub_from'   => $from,
		'epub_size'   => $size,
		'author'      => (string) get_post_meta( $post_id, '_blc_book_author', true ),
		'translator'  => (string) get_post_meta( $post_id, '_blc_translator', true ),
		'year'        => (string) get_post_meta( $post_id, '_blc_year_published', true ),
		'rights'      => (string) get_post_meta( $post_id, '_blc_rights', true ),
		'source_name' => (string) get_post_meta( $post_id, '_blc_source_name', true ),
		'source_url'  => (string) get_post_meta( $post_id, '_blc_source_url', true ),
	);
}

/**
 * Whether a library book is actually readable — i.e. it has a file.
 *
 * @param int|null $post_id Post ID.
 * @return bool
 */
function blc_reader_has_epub( $post_id = null ) {
	$meta = blc_reader_meta( $post_id );
	return '' !== $meta['epub_url'];
}

/**
 * The library book for a fan page, if the club hosts a readable edition.
 *
 * Editors can pin one in the Fan Page Details box. Left unset, a library book
 * sharing the fan page's slug is used — which is the normal case, since both
 * are created from the same `data/books.json` entry.
 *
 * @param int|null $fan_post_id Fan page ID, or null for the current post.
 * @return WP_Post|null Published library book with a file, or null.
 */
function blc_reader_book_for_fan_page( $fan_post_id = null ) {
	static $cache = array();

	$fan_post_id = $fan_post_id ? (int) $fan_post_id : (int) get_the_ID();
	if ( isset( $cache[ $fan_post_id ] ) ) {
		return $cache[ $fan_post_id ];
	}

	$book   = null;
	$pinned = (int) get_post_meta( $fan_post_id, '_blc_library_book_id', true );

	if ( $pinned ) {
		$candidate = get_post( $pinned );
		if ( $candidate && 'library_book' === $candidate->post_type && 'publish' === $candidate->post_status ) {
			$book = $candidate;
		}
	} else {
		$fan = get_post( $fan_post_id );
		if ( $fan && $fan->post_name ) {
			$found = get_posts( array(
				'post_type'        => 'library_book',
				'name'             => $fan->post_name,
				'post_status'      => 'publish',
				'numberposts'      => 1,
				'suppress_filters' => false,
			) );
			if ( $found ) {
				$book = $found[0];
			}
		}
	}

	// A library book with no file is a stub, not a reading link.
	if ( $book && ! blc_reader_has_epub( $book->ID ) ) {
		$book = null;
	}

	$cache[ $fan_post_id ] = $book;
	return $book;
}

/**
 * The fan page for a library book, when the club has written one.
 *
 * The mirror of blc_reader_book_for_fan_page(): a pinned link wins, then a fan
 * page sharing the slug. Lets the reader offer "what the club said about it".
 *
 * @param int|null $book_id Library book ID, or null for the current post.
 * @return WP_Post|null
 */
function blc_reader_fan_page_for_book( $book_id = null ) {
	$book_id = $book_id ? (int) $book_id : (int) get_the_ID();

	$pinned = get_posts( array(
		'post_type'        => 'fan_page',
		'post_status'      => 'publish',
		'numberposts'      => 1,
		'meta_key'         => '_blc_library_book_id',
		'meta_value'       => $book_id,
		'suppress_filters' => false,
	) );
	if ( $pinned ) {
		return $pinned[0];
	}

	$book = get_post( $book_id );
	if ( ! $book || ! $book->post_name ) {
		return null;
	}

	$found = get_posts( array(
		'post_type'        => 'fan_page',
		'name'             => $book->post_name,
		'post_status'      => 'publish',
		'numberposts'      => 1,
		'suppress_filters' => false,
	) );
	return $found ? $found[0] : null;
}

/**
 * Permalink into the reader, optionally opening at a position.
 *
 * @param int|WP_Post $book Library book.
 * @param string      $loc  Optional EPUB CFI or section href to open at.
 * @return string
 */
function blc_reader_url( $book, $loc = '' ) {
	$url = get_permalink( $book );
	if ( ! $url ) {
		return '';
	}
	return $loc ? add_query_arg( 'loc', rawurlencode( $loc ), $url ) : $url;
}

/**
 * Byline for a library book: author, then translator when there is one.
 *
 * @param array $meta From blc_reader_meta().
 * @return string
 */
function blc_reader_byline( $meta ) {
	$parts = array();
	if ( $meta['author'] ) {
		$parts[] = $meta['author'];
	}
	if ( $meta['translator'] ) {
		/* translators: %s: translator name */
		$parts[] = sprintf( __( 'translated by %s', 'bookloversclub' ), $meta['translator'] );
	}
	return implode( ' · ', $parts );
}

/**
 * Human file size, for the "download the EPUB" link.
 *
 * @param int $bytes File size.
 * @return string Empty when unknown.
 */
function blc_reader_filesize( $bytes ) {
	return $bytes ? size_format( $bytes, $bytes > 1048576 ? 1 : 0 ) : '';
}

/* -------------------------------------------------------------------------
 * 5. Admin
 * ---------------------------------------------------------------------- */
function blc_reader_meta_boxes() {
	add_meta_box( 'blc_reader_file', __( 'The Edition', 'bookloversclub' ), 'blc_reader_file_meta_box_html', 'library_book', 'normal', 'high' );
	add_meta_box( 'blc_reader_fan_link', __( 'Reading Room', 'bookloversclub' ), 'blc_reader_fan_link_meta_box_html', 'fan_page', 'side' );
}
add_action( 'add_meta_boxes', 'blc_reader_meta_boxes' );

function blc_reader_file_meta_box_html( $post ) {
	wp_nonce_field( 'blc_reader_meta', 'blc_reader_meta_nonce' );
	$meta = blc_reader_meta( $post->ID );

	$shelf = blc_reader_shelf_files();

	$fields = array(
		'blc_epub_file'    => array( __( 'Shelf file', 'bookloversclub' ), (string) get_post_meta( $post->ID, '_blc_epub_file', true ), sprintf( /* translators: %s: shelf URL */ __( 'Filename of the EPUB in %s. Leave every file field blank and save, and a file on the shelf whose name ends in this book\'s slug is picked up automatically.', 'bookloversclub' ), blc_reader_shelf_url() ) ),
		'blc_epub_id'      => array( __( 'EPUB attachment ID', 'bookloversclub' ), $meta['epub_id'] ? $meta['epub_id'] : '', __( 'For files kept in the media library instead. Takes precedence over the shelf.', 'bookloversclub' ) ),
		'blc_epub_url'     => array( __( 'EPUB URL', 'bookloversclub' ), 'url' === $meta['epub_from'] ? $meta['epub_url'] : '', __( 'A last resort, used only when the two above are empty. Must be same-origin — a cross-origin file needs CORS headers the reader cannot add.', 'bookloversclub' ) ),
		'blc_book_author'  => array( __( 'Author', 'bookloversclub' ), $meta['author'], '' ),
		'blc_translator'   => array( __( 'Translator', 'bookloversclub' ), $meta['translator'], __( 'Leave blank unless the edition is translated. A translation has its own copyright — only a public-domain one belongs here.', 'bookloversclub' ) ),
		'blc_year_published' => array( __( 'First published', 'bookloversclub' ), $meta['year'], '' ),
		'blc_rights'       => array( __( 'Rights statement', 'bookloversclub' ), $meta['rights'], __( 'Required. e.g. "Public domain in the United States". Shown on the reader page.', 'bookloversclub' ) ),
		'blc_source_name'  => array( __( 'Source', 'bookloversclub' ), $meta['source_name'], __( 'e.g. Standard Ebooks, Project Gutenberg.', 'bookloversclub' ) ),
		'blc_source_url'   => array( __( 'Source URL', 'bookloversclub' ), $meta['source_url'], '' ),
	);
	?>
	<p class="description" style="margin-bottom:12px;">
		<?php esc_html_e( 'Public-domain editions only. The file is served as a plain download to anyone who opens the reader, so an in-copyright book must not be published here.', 'bookloversclub' ); ?>
	</p>
	<table class="form-table" role="presentation">
		<?php foreach ( $fields as $name => $field ) : ?>
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $field[0] ); ?></label>
				</th>
				<td>
					<input type="text" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>"
						class="widefat" value="<?php echo esc_attr( $field[1] ); ?>"
						<?php echo ( 'blc_epub_file' === $name && $shelf ) ? 'list="blc_shelf_files"' : ''; ?>>
					<?php if ( $field[2] ) : ?>
						<p class="description"><?php echo esc_html( $field[2] ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>

	<?php if ( $shelf ) : ?>
		<datalist id="blc_shelf_files">
			<?php foreach ( $shelf as $file ) : ?>
				<option value="<?php echo esc_attr( $file ); ?>"></option>
			<?php endforeach; ?>
		</datalist>
	<?php endif; ?>

	<?php
	$origins = array(
		'media' => __( 'media library', 'bookloversclub' ),
		'shelf' => __( 'the shelf', 'bookloversclub' ),
		'url'   => __( 'a URL', 'bookloversclub' ),
	);

	if ( $meta['epub_url'] ) {
		printf(
			'<p><strong>%s</strong> <a href="%s" rel="nofollow">%s</a> %s %s</p>',
			esc_html__( 'Serving:', 'bookloversclub' ),
			esc_url( $meta['epub_url'] ),
			esc_html( rawurldecode( basename( wp_parse_url( $meta['epub_url'], PHP_URL_PATH ) ) ) ),
			esc_html( $meta['epub_size'] ? '(' . blc_reader_filesize( $meta['epub_size'] ) . ')' : '' ),
			esc_html( isset( $origins[ $meta['epub_from'] ] ) ? '— ' . $origins[ $meta['epub_from'] ] : '' )
		);
	} else {
		printf(
			'<p style="color:#b32d2e;"><strong>%s</strong></p>',
			esc_html__( 'No EPUB attached — this book will not appear in the Reading Room or link from its fan page.', 'bookloversclub' )
		);
		if ( $shelf ) {
			$listed = array_map( function ( $file ) {
				return '<code>' . esc_html( $file ) . '</code>';
			}, array_slice( $shelf, 0, 12 ) );

			printf(
				'<p class="description">%s %s</p>',
				esc_html( sprintf( /* translators: %d: number of files */ _n( '%d file on the shelf:', '%d files on the shelf:', count( $shelf ), 'bookloversclub' ), count( $shelf ) ) ),
				implode( ', ', $listed )
			);
		} else {
			printf(
				'<p class="description">%s</p>',
				esc_html( sprintf( /* translators: %s: shelf path */ __( 'The shelf at %s is empty or unreadable from here.', 'bookloversclub' ), blc_reader_shelf_path() ) )
			);
		}
	}
}

function blc_reader_fan_link_meta_box_html( $post ) {
	wp_nonce_field( 'blc_reader_fan_meta', 'blc_reader_fan_meta_nonce' );
	$pinned = (int) get_post_meta( $post->ID, '_blc_library_book_id', true );
	$books  = get_posts( array(
		'post_type'   => 'library_book',
		'post_status' => 'publish',
		'numberposts' => 200,
		'orderby'     => 'title',
		'order'       => 'ASC',
	) );
	?>
	<p>
		<label for="blc_library_book_id"><strong><?php esc_html_e( 'Readable edition', 'bookloversclub' ); ?></strong></label>
		<select id="blc_library_book_id" name="blc_library_book_id" class="widefat">
			<option value="0"><?php esc_html_e( '— Match by slug —', 'bookloversclub' ); ?></option>
			<?php foreach ( $books as $book ) : ?>
				<option value="<?php echo esc_attr( $book->ID ); ?>" <?php selected( $pinned, $book->ID ); ?>>
					<?php echo esc_html( $book->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="description">
		<?php esc_html_e( 'Adds a "Read it free" button to the hero. Left on "match by slug", the fan page links to the library book with the same slug, if there is one.', 'bookloversclub' ); ?>
	</p>
	<?php
}

function blc_reader_save_meta( $post_id ) {
	if ( ! isset( $_POST['blc_reader_meta_nonce'] ) || ! wp_verify_nonce( $_POST['blc_reader_meta_nonce'], 'blc_reader_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$text_fields = array(
		'blc_book_author'    => '_blc_book_author',
		'blc_translator'     => '_blc_translator',
		'blc_year_published' => '_blc_year_published',
		'blc_rights'         => '_blc_rights',
		'blc_source_name'    => '_blc_source_name',
	);
	foreach ( $text_fields as $field => $key ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
		}
	}

	if ( isset( $_POST['blc_source_url'] ) ) {
		update_post_meta( $post_id, '_blc_source_url', esc_url_raw( wp_unslash( $_POST['blc_source_url'] ) ) );
	}
	if ( isset( $_POST['blc_epub_url'] ) ) {
		update_post_meta( $post_id, '_blc_epub_url', esc_url_raw( wp_unslash( $_POST['blc_epub_url'] ) ) );
	}
	if ( isset( $_POST['blc_epub_id'] ) ) {
		update_post_meta( $post_id, '_blc_epub_id', absint( wp_unslash( $_POST['blc_epub_id'] ) ) );
	}
	if ( isset( $_POST['blc_epub_file'] ) ) {
		// Anything that isn't a plain .epub filename is stored as nothing,
		// rather than kept and quietly failing to load later.
		update_post_meta( $post_id, '_blc_epub_file', blc_reader_sanitize_shelf_file( $_POST['blc_epub_file'] ) );
	}

	blc_reader_adopt_shelf_file( $post_id );
}

/**
 * Adopt a shelf file named after this book, when no file was named by hand.
 *
 * Drop `mary-shelley_frankenstein.epub` on the shelf, publish a library book
 * with the slug `frankenstein`, and the two find each other. Only ever fills an
 * empty field — an editor's explicit choice is never overwritten.
 *
 * @param int $post_id Library book ID.
 * @return string The filename adopted, or '' when nothing changed.
 */
function blc_reader_adopt_shelf_file( $post_id ) {
	$meta = blc_reader_meta( $post_id );
	if ( $meta['epub_url'] ) {
		return '';
	}

	$found = blc_reader_shelf_file_for_slug( get_post_field( 'post_name', $post_id ) );
	if ( ! $found ) {
		return '';
	}

	update_post_meta( $post_id, '_blc_epub_file', $found );
	return $found;
}
add_action( 'save_post_library_book', 'blc_reader_save_meta' );

function blc_reader_save_fan_meta( $post_id ) {
	if ( ! isset( $_POST['blc_reader_fan_meta_nonce'] ) || ! wp_verify_nonce( $_POST['blc_reader_fan_meta_nonce'], 'blc_reader_fan_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['blc_library_book_id'] ) ) {
		update_post_meta( $post_id, '_blc_library_book_id', absint( wp_unslash( $_POST['blc_library_book_id'] ) ) );
	}
}
add_action( 'save_post_fan_page', 'blc_reader_save_fan_meta' );

/**
 * Flag the two things that quietly break a library book, in the posts list.
 */
function blc_reader_admin_columns( $columns ) {
	$columns['blc_epub']   = __( 'EPUB', 'bookloversclub' );
	$columns['blc_rights'] = __( 'Rights', 'bookloversclub' );
	return $columns;
}
add_filter( 'manage_library_book_posts_columns', 'blc_reader_admin_columns' );

function blc_reader_admin_column( $column, $post_id ) {
	if ( 'blc_epub' !== $column && 'blc_rights' !== $column ) {
		return;
	}
	$meta  = blc_reader_meta( $post_id );
	$value = 'blc_epub' === $column ? $meta['epub_url'] : $meta['rights'];

	if ( ! $value ) {
		printf( '<span style="color:#b32d2e;">%s</span>', esc_html__( 'missing', 'bookloversclub' ) );
		return;
	}
	echo esc_html( 'blc_epub' === $column ? ( blc_reader_filesize( $meta['epub_size'] ) ?: __( 'linked', 'bookloversclub' ) ) : $value );
}
add_action( 'manage_library_book_posts_custom_column', 'blc_reader_admin_column', 10, 2 );

/* -------------------------------------------------------------------------
 * 6. The Reading Room index
 * ---------------------------------------------------------------------- */

/**
 * Keep fileless stubs out of the Reading Room.
 *
 * A library book with no EPUB is a shelf label with nothing behind it, so the
 * archive lists only the ones that can actually be opened. Editors still see
 * them in wp-admin, flagged in the EPUB column.
 */
function blc_reader_archive_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'library_book' ) ) {
		return;
	}

	$query->set( 'orderby', 'title' );
	$query->set( 'order', 'ASC' );
	$query->set( 'meta_query', array(
		'relation' => 'OR',
		array(
			'key'     => '_blc_epub_id',
			'value'   => 0,
			'compare' => '>',
			'type'    => 'NUMERIC',
		),
		array(
			'key'     => '_blc_epub_file',
			'value'   => '',
			'compare' => '!=',
		),
		array(
			'key'     => '_blc_epub_url',
			'value'   => '',
			'compare' => '!=',
		),
	) );
}
add_action( 'pre_get_posts', 'blc_reader_archive_query' );

/* -------------------------------------------------------------------------
 * 7. Assets
 * ---------------------------------------------------------------------- */
function blc_reader_assets() {
	$is_reader  = is_singular( 'library_book' );
	$is_library = is_post_type_archive( 'library_book' );

	if ( ! $is_reader && ! $is_library && ! is_singular( 'fan_page' ) ) {
		return;
	}

	wp_enqueue_style(
		'blc-reader',
		get_template_directory_uri() . '/assets/css/reader.css',
		array( 'blc-style' ),
		BLC_VERSION
	);

	if ( $is_reader ) {
		// foliate-js is ESM and loads its parsers with dynamic import(), so the
		// entry point ships as a module — see blc_reader_module_tag().
		wp_enqueue_script(
			'blc-reader',
			get_template_directory_uri() . '/assets/js/reader.js',
			array(),
			BLC_VERSION,
			true
		);
	} else {
		// Reading positions live in the reader's localStorage, so "where you
		// left off" can only be filled in on the client.
		wp_enqueue_script(
			'blc-library',
			get_template_directory_uri() . '/assets/js/library.js',
			array(),
			BLC_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'blc_reader_assets' );

/**
 * Ship the reader entry point as `type="module"`.
 */
function blc_reader_module_tag( $tag, $handle ) {
	if ( 'blc-reader' !== $handle ) {
		return $tag;
	}
	return str_replace( '<script ', '<script type="module" ', $tag );
}
add_filter( 'script_loader_tag', 'blc_reader_module_tag', 10, 2 );

/**
 * The reader owns the viewport; the progress ribbon would be measuring the
 * wrong thing, so it doesn't run here.
 */
function blc_reader_body_classes( $classes ) {
	if ( is_singular( 'library_book' ) ) {
		$classes[] = 'blc-reading';
	}
	return $classes;
}
add_filter( 'body_class', 'blc_reader_body_classes' );
