<?php
/**
 * "Run it in your club" — the facilitator's kit.
 *
 * @package BookLoversClub
 */

$data = isset( $args['data'] ) ? $args['data'] : array();

blc_fan_section_open( 'club-kit', __( 'Run it in your club', 'bookloversclub' ), __( 'Run it in your club', 'bookloversclub' ) );

$split = blc_fan_get( $data, 'club_kit.session_split' );
if ( $split ) {
	printf(
		'<div class="fan-note"><div class="fan-note-label">%s</div><p>%s</p></div>',
		esc_html__( 'How to split it', 'bookloversclub' ),
		esc_html( $split )
	);
}

$questions = blc_fan_get( $data, 'club_kit.discussion_questions', array() );
if ( $questions ) {
	printf( '<h3>%s</h3><ol class="fan-questions">', esc_html__( 'Discussion questions', 'bookloversclub' ) );
	foreach ( $questions as $question ) {
		if ( empty( $question['question'] ) ) {
			continue;
		}
		echo '<li>';
		echo wp_kses_post( blc_fan_spoiler( esc_html( $question['question'] ), ! empty( $question['spoiler'] ) ) );
		if ( ! empty( $question['splits_the_room'] ) ) {
			printf( ' <span class="badge badge-seasonal">%s</span>', esc_html__( 'Splits the room', 'bookloversclub' ) );
		}
		echo '</li>';
	}
	echo '</ol>';
}

$passages = blc_fan_get( $data, 'club_kit.read_aloud_passages', array() );
if ( $passages ) {
	printf( '<h3>%s</h3><ul class="fan-list fan-list-marked">', esc_html__( 'Read these aloud', 'bookloversclub' ) );
	foreach ( $passages as $passage ) {
		printf( '<li>%s</li>', esc_html( $passage ) );
	}
	echo '</ul>';
}

$notes = blc_fan_get( $data, 'club_kit.facilitator_notes', array() );
if ( $notes ) {
	printf( '<h3>%s</h3><ul class="fan-list fan-list-marked">', esc_html__( 'Facilitator notes', 'bookloversclub' ) );
	foreach ( $notes as $note ) {
		printf( '<li>%s</li>', esc_html( $note ) );
	}
	echo '</ul>';
}

$pairing = blc_fan_get( $data, 'club_kit.pairing' );
if ( $pairing ) {
	printf(
		'<p class="fan-aside"><strong>%s</strong> %s</p>',
		esc_html__( 'Pair it with:', 'bookloversclub' ),
		esc_html( $pairing )
	);
}

blc_fan_section_close();
