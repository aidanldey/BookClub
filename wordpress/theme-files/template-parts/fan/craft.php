<?php
/**
 * "What readers point to" — the craft section.
 *
 * @package BookLoversClub
 */

$data = isset( $args['data'] ) ? $args['data'] : array();

blc_fan_section_open( 'craft', __( 'The craft', 'bookloversclub' ), __( 'What readers point to', 'bookloversclub' ) );

$element = blc_fan_get( $data, 'craft.standout_element' );
if ( $element ) {
	printf(
		'<p class="fan-standout"><span class="badge badge-author">%s</span></p>',
		esc_html( ucfirst( (string) $element ) )
	);
}

$detail = blc_fan_get( $data, 'craft.standout_detail' );
if ( $detail ) {
	printf( '<p class="fan-lede">%s</p>', esc_html( $detail ) );
}

$claim = blc_fan_get( $data, 'craft.unique_claim' );
if ( $claim ) {
	printf(
		'<div class="fan-note"><div class="fan-note-label">%s</div><p>%s</p></div>',
		esc_html__( 'What only this book does', 'bookloversclub' ),
		esc_html( $claim )
	);
}

$choices = blc_fan_get( $data, 'craft.formal_choices', array() );
if ( $choices ) {
	printf( '<h3>%s</h3><ul class="fan-list fan-list-marked">', esc_html__( 'Choices worth noticing', 'bookloversclub' ) );
	foreach ( $choices as $choice ) {
		printf( '<li>%s</li>', esc_html( $choice ) );
	}
	echo '</ul>';
}

$curve = blc_fan_get( $data, 'craft.difficulty_curve' );
if ( $curve ) {
	printf(
		'<p class="fan-aside"><strong>%s</strong> %s</p>',
		esc_html__( 'Difficulty:', 'bookloversclub' ),
		esc_html( $curve )
	);
}

blc_fan_section_close();
