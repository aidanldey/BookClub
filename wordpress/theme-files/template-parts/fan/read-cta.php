<?php
/**
 * "Read it free" — the fan page's link into the Reading Room.
 *
 * Renders only when the club hosts a public-domain edition of this book, which
 * is the minority of fan pages. Everything in copyright shows nothing here.
 *
 * Expects $args['book'] — a library_book post from blc_reader_book_for_fan_page().
 *
 * @package BookLoversClub
 */

$book = isset( $args['book'] ) ? $args['book'] : null;
if ( ! $book ) {
	return;
}

$meta = blc_reader_meta( $book->ID );
$slug = $book->post_name;
?>
<div class="fan-read-cta">
	<a class="btn blc-read-cta-button" href="<?php echo esc_url( get_permalink( $book ) ); ?>"
		data-blc-progress="<?php echo esc_attr( $slug ); ?>"
		data-continue-label="<?php esc_attr_e( 'Continue reading', 'bookloversclub' ); ?>">
		<?php esc_html_e( 'Read it free', 'bookloversclub' ); ?>
	</a>

	<span class="fan-read-cta-note">
		<span class="blc-progress-readout" data-blc-progress-for="<?php echo esc_attr( $slug ); ?>" hidden></span>
		<?php
		if ( $meta['source_name'] ) {
			printf(
				/* translators: %s: name of the source, e.g. Standard Ebooks */
				esc_html__( 'In your browser — %s edition, public domain.', 'bookloversclub' ),
				esc_html( $meta['source_name'] )
			);
		} else {
			esc_html_e( 'In your browser — public domain, nothing to install.', 'bookloversclub' );
		}
		?>
	</span>
</div>
