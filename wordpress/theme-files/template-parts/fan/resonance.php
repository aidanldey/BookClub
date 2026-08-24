<?php
/**
 * "Why readers love it" — the emotional core of the page.
 *
 * @package BookLoversClub
 */

$data = isset( $args['data'] ) ? $args['data'] : array();

blc_fan_section_open( 'resonance', __( 'Why it lands', 'bookloversclub' ), __( 'Why readers love it', 'bookloversclub' ) );

$core = blc_fan_get( $data, 'resonance.core_reason' );
if ( $core ) {
	printf( '<p class="fan-lede">%s</p>', esc_html( $core ) );
}

$effects = blc_fan_get( $data, 'resonance.what_it_does_to_readers', array() );
if ( $effects ) {
	echo '<ul class="fan-list fan-list-marked">';
	foreach ( $effects as $effect ) {
		printf( '<li>%s</li>', esc_html( $effect ) );
	}
	echo '</ul>';
}

$moment = blc_fan_get( $data, 'resonance.the_moment' );
if ( $moment ) {
	printf(
		'<div class="fan-note"><div class="fan-note-label">%s</div><p>%s</p></div>',
		esc_html__( 'The moment it clicks', 'bookloversclub' ),
		esc_html( $moment )
	);
}

// Short facts that read better as a row of chips than as prose.
$facts = array_filter( array(
	__( 'Feels like', 'bookloversclub' )    => blc_fan_get( $data, 'resonance.dominant_emotion' ),
	__( 'Best read at', 'bookloversclub' )  => blc_fan_get( $data, 'resonance.best_life_stage' ),
	__( 'On a reread', 'bookloversclub' )   => blc_fan_get( $data, 'resonance.reread_value' ),
) );
if ( $facts ) {
	echo '<div class="fan-facts">';
	foreach ( $facts as $label => $value ) {
		printf(
			'<div class="fan-fact"><span class="fan-fact-label">%s</span><span class="fan-fact-value">%s</span></div>',
			esc_html( $label ),
			esc_html( $value )
		);
	}
	echo '</div>';
}

$comparisons = blc_fan_get( $data, 'resonance.comparisons', array() );
if ( $comparisons ) {
	printf(
		'<p class="fan-aside">%s %s</p>',
		esc_html__( 'Readers reach for:', 'bookloversclub' ),
		esc_html( implode( ' · ', $comparisons ) )
	);
}

blc_fan_section_close();
