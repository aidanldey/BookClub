<?php
/**
 * "The place" — setting as a character in its own right.
 *
 * @package BookLoversClub
 */

$data = isset( $args['data'] ) ? $args['data'] : array();

blc_fan_section_open( 'setting', __( 'The place', 'bookloversclub' ), __( 'Where it puts you', 'bookloversclub' ) );

$place = blc_fan_get( $data, 'setting.place' );
if ( $place ) {
	printf( '<p class="fan-lede">%s</p>', esc_html( $place ) );
}

$as_character = blc_fan_get( $data, 'setting.setting_as_character' );
if ( $as_character ) {
	printf( '<p>%s</p>', esc_html( $as_character ) );
}

$senses = blc_fan_get( $data, 'setting.sensory_signature', array() );
if ( $senses ) {
	echo '<ul class="fan-chips">';
	foreach ( $senses as $sense ) {
		printf( '<li>%s</li>', esc_html( $sense ) );
	}
	echo '</ul>';
}

$lingering = blc_fan_get( $data, 'setting.lingering_detail' );
if ( $lingering ) {
	printf(
		'<div class="fan-note"><div class="fan-note-label">%s</div><p>%s</p></div>',
		esc_html__( 'The detail that stays', 'bookloversclub' ),
		esc_html( $lingering )
	);
}

$conditions = blc_fan_get( $data, 'setting.best_reading_conditions' );
if ( $conditions ) {
	printf(
		'<p class="fan-aside"><strong>%s</strong> %s</p>',
		esc_html__( 'Best read:', 'bookloversclub' ),
		esc_html( $conditions )
	);
}

$locations = blc_fan_get( $data, 'setting.real_locations', array() );
if ( $locations ) {
	printf(
		'<p class="fan-aside"><strong>%s</strong> %s</p>',
		esc_html__( 'You can visit:', 'bookloversclub' ),
		esc_html( implode( ' · ', $locations ) )
	);
}

blc_fan_section_close();
