<?php
/**
 * "The book's life" — reception history.
 *
 * @package BookLoversClub
 */

$data = isset( $args['data'] ) ? $args['data'] : array();

blc_fan_section_open( 'reception', __( 'Its life', 'bookloversclub' ), __( 'The book\'s life since', 'bookloversclub' ) );

$initial = blc_fan_get( $data, 'reception.initial_reception' );
if ( $initial ) {
	printf(
		'<p><span class="fan-inline-label">%s</span> %s</p>',
		esc_html__( 'At publication', 'bookloversclub' ),
		esc_html( $initial )
	);
}

$arc = blc_fan_get( $data, 'reception.reputation_arc' );
if ( $arc ) {
	printf(
		'<p><span class="fan-inline-label">%s</span> %s</p>',
		esc_html__( 'Since', 'bookloversclub' ),
		esc_html( $arc )
	);
}

$moment = blc_fan_get( $data, 'reception.cultural_moment' );
if ( $moment ) {
	printf( '<p>%s</p>', esc_html( $moment ) );
}

$split = blc_fan_get( $data, 'reception.generational_split' );
if ( $split ) {
	printf(
		'<div class="fan-note"><div class="fan-note-label">%s</div><p>%s</p></div>',
		esc_html__( 'Reads differently by generation', 'bookloversclub' ),
		esc_html( $split )
	);
}

$awards = blc_fan_get( $data, 'reception.awards', array() );
if ( $awards ) {
	echo '<ul class="fan-chips">';
	foreach ( $awards as $award ) {
		printf( '<li>%s</li>', esc_html( $award ) );
	}
	echo '</ul>';
}

$banned = blc_fan_get( $data, 'reception.banned_or_challenged' );
if ( is_array( $banned ) ) {
	$detail = '';
	foreach ( array( 'summary', 'note', 'reason', 'details' ) as $key ) {
		if ( ! empty( $banned[ $key ] ) && is_string( $banned[ $key ] ) ) {
			$detail = $banned[ $key ];
			break;
		}
	}
	printf(
		'<div class="fan-note fan-note-rust"><div class="fan-note-label">%s</div>%s</div>',
		esc_html__( 'Banned or challenged', 'bookloversclub' ),
		$detail ? '<p>' . esc_html( $detail ) . '</p>' : ''
	);
}

blc_fan_section_close();
