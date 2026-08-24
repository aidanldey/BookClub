<?php
/**
 * "Before you start" — practical guidance for a first-time reader.
 *
 * @package BookLoversClub
 */

$data = isset( $args['data'] ) ? $args['data'] : array();

blc_fan_section_open( 'before-you-start', __( 'Before you start', 'bookloversclub' ), __( 'Before you start', 'bookloversclub' ) );

$for     = blc_fan_get( $data, 'before_you_start.who_its_for' );
$against = blc_fan_get( $data, 'before_you_start.who_bounces_off' );
if ( $for || $against ) {
	echo '<div class="fan-myths">';
	if ( $for ) {
		printf(
			'<div class="fan-myth-reality"><span class="fan-inline-label">%s</span><p>%s</p></div>',
			esc_html__( 'You\'ll love it if', 'bookloversclub' ),
			esc_html( $for )
		);
	}
	if ( $against ) {
		printf(
			'<div class="fan-myth-belief"><span class="fan-inline-label">%s</span><p>%s</p></div>',
			esc_html__( 'You may bounce if', 'bookloversclub' ),
			esc_html( $against )
		);
	}
	echo '</div>';
}

$wish = blc_fan_get( $data, 'before_you_start.wish_id_known', array() );
if ( $wish ) {
	printf( '<h3>%s</h3><ul class="fan-list fan-list-marked">', esc_html__( 'What readers wish they\'d known', 'bookloversclub' ) );
	foreach ( $wish as $item ) {
		printf( '<li>%s</li>', esc_html( $item ) );
	}
	echo '</ul>';
}

$commitment = blc_fan_get( $data, 'before_you_start.commitment_point' );
if ( $commitment ) {
	printf(
		'<div class="fan-note"><div class="fan-note-label">%s</div><p>%s</p></div>',
		esc_html__( 'Give it until', 'bookloversclub' ),
		esc_html( $commitment )
	);
}

$narrator = blc_fan_get( $data, 'before_you_start.audiobook.recommended_narrator' );
$verdict  = blc_fan_get( $data, 'before_you_start.audiobook.verdict' );
if ( $narrator || $verdict ) {
	printf( '<h3>%s</h3>', esc_html__( 'Print or audio', 'bookloversclub' ) );
	if ( $narrator ) {
		printf(
			'<p><span class="fan-inline-label">%s</span> %s</p>',
			esc_html__( 'Narrator', 'bookloversclub' ),
			esc_html( $narrator )
		);
	}
	if ( $verdict ) {
		printf( '<p>%s</p>', esc_html( $verdict ) );
	}
}

$companions = blc_fan_get( $data, 'before_you_start.companion_material', array() );
if ( $companions ) {
	printf(
		'<p class="fan-aside"><strong>%s</strong> %s</p>',
		esc_html__( 'Worth having alongside:', 'bookloversclub' ),
		esc_html( implode( ' · ', $companions ) )
	);
}

$adaptations = blc_fan_get( $data, 'before_you_start.adaptations', array() );
if ( $adaptations ) {
	printf( '<h3>%s</h3><ul class="fan-list fan-adaptations">', esc_html__( 'Adaptations', 'bookloversclub' ) );
	foreach ( $adaptations as $adaptation ) {
		if ( empty( $adaptation['title'] ) ) {
			continue;
		}
		$order_labels = array(
			'before' => __( 'watch first', 'bookloversclub' ),
			'after'  => __( 'read the book first', 'bookloversclub' ),
			'either' => __( 'either order', 'bookloversclub' ),
			'avoid'  => __( 'readers say skip it', 'bookloversclub' ),
		);
		$order = ! empty( $adaptation['before_or_after'] ) && isset( $order_labels[ $adaptation['before_or_after'] ] )
			? $order_labels[ $adaptation['before_or_after'] ]
			: '';
		$meta  = array_filter( array(
			! empty( $adaptation['medium'] ) ? $adaptation['medium'] : '',
			! empty( $adaptation['year'] ) ? $adaptation['year'] : '',
			$order,
		) );
		echo '<li>';
		printf( '<strong>%s</strong>', esc_html( $adaptation['title'] ) );
		if ( $meta ) {
			printf( ' <span class="fan-voice-meta">%s</span>', esc_html( implode( ' · ', $meta ) ) );
		}
		if ( ! empty( $adaptation['reader_verdict'] ) ) {
			printf( '<br>%s', esc_html( $adaptation['reader_verdict'] ) );
		}
		echo '</li>';
	}
	echo '</ul>';
}

// Content warnings sit last and stay collapsed — a reader opts in to seeing them.
$warnings = blc_fan_get( $data, 'before_you_start.content_warnings', array() );
if ( $warnings ) {
	echo '<details class="fan-warnings">';
	printf( '<summary>%s</summary><ul class="fan-chips">', esc_html__( 'Content warnings', 'bookloversclub' ) );
	foreach ( $warnings as $warning ) {
		printf( '<li>%s</li>', esc_html( $warning ) );
	}
	echo '</ul></details>';
}

blc_fan_section_close();
