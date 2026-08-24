<?php
/**
 * "About the author" — including the tension readers feel about them.
 *
 * @package BookLoversClub
 */

$data = isset( $args['data'] ) ? $args['data'] : array();

blc_fan_section_open( 'author', __( 'The author', 'bookloversclub' ), __( 'About the author', 'bookloversclub' ) );

$name = blc_fan_get( $data, 'author.name' );
if ( $name ) {
	printf( '<h3 class="fan-author-name">%s</h3>', esc_html( $name ) );
}

$bio = blc_fan_get( $data, 'author.biography_that_colors_the_read' );
if ( $bio ) {
	printf( '<p class="fan-lede">%s</p>', esc_html( $bio ) );
}

$placement = blc_fan_get( $data, 'author.where_it_sits_in_their_work' );
if ( $placement ) {
	printf( '<p>%s</p>', esc_html( $placement ) );
}

$quotes = blc_fan_get( $data, 'author.author_on_the_book', array() );
if ( $quotes ) {
	printf( '<h3>%s</h3>', esc_html__( 'In their own words', 'bookloversclub' ) );
	foreach ( $quotes as $quote ) {
		if ( empty( $quote['quote'] ) ) {
			continue;
		}
		$source = ! empty( $quote['source'] ) ? $quote['source'] : '';
		$year   = ! empty( $quote['year'] ) ? $quote['year'] : '';
		$cite   = trim( implode( ', ', array_filter( array( $source, $year ) ) ) );

		echo '<figure class="fan-quote">';
		printf( '<blockquote><p>%s</p></blockquote>', esc_html( $quote['quote'] ) );
		if ( $cite ) {
			if ( ! empty( $quote['url'] ) ) {
				printf(
					'<figcaption><a href="%s" rel="noopener" target="_blank">%s</a></figcaption>',
					esc_url( $quote['url'] ),
					esc_html( $cite )
				);
			} else {
				printf( '<figcaption>%s</figcaption>', esc_html( $cite ) );
			}
		}
		echo '</figure>';
	}
}

$tension = blc_fan_get( $data, 'author.reader_tension' );
if ( $tension ) {
	printf(
		'<div class="fan-note fan-note-rust"><div class="fan-note-label">%s</div><p>%s</p></div>',
		esc_html__( 'The complication', 'bookloversclub' ),
		esc_html( $tension )
	);
}

$unfinished = blc_fan_get( $data, 'author.unfinished_work_note' );
if ( $unfinished ) {
	printf( '<p class="fan-aside">%s</p>', esc_html( $unfinished ) );
}

$further = blc_fan_get( $data, 'author.further_reading', array() );
if ( $further ) {
	printf(
		'<p class="fan-aside"><strong>%s</strong> %s</p>',
		esc_html__( 'More by them:', 'bookloversclub' ),
		esc_html( implode( ' · ', $further ) )
	);
}

blc_fan_section_close();
