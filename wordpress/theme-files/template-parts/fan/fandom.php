<?php
/**
 * "The fandom" — where the readers actually are.
 *
 * @package BookLoversClub
 */

$data = isset( $args['data'] ) ? $args['data'] : array();

blc_fan_section_open( 'fandom', __( 'The fandom', 'bookloversclub' ), __( 'Where its readers gather', 'bookloversclub' ) );

$communities = blc_fan_get( $data, 'fandom.communities', array() );
if ( $communities ) {
	echo '<ul class="fan-list fan-communities">';
	foreach ( $communities as $community ) {
		if ( empty( $community['name'] ) ) {
			continue;
		}
		$meta = array_filter( array(
			! empty( $community['platform'] ) ? $community['platform'] : '',
			! empty( $community['size_note'] ) ? $community['size_note'] : '',
		) );
		echo '<li>';
		if ( ! empty( $community['url'] ) ) {
			printf(
				'<a href="%s" rel="nofollow ugc noopener" target="_blank">%s</a>',
				esc_url( $community['url'] ),
				esc_html( $community['name'] )
			);
		} else {
			echo esc_html( $community['name'] );
		}
		if ( $meta ) {
			printf( ' <span class="fan-voice-meta">%s</span>', esc_html( implode( ' · ', $meta ) ) );
		}
		echo '</li>';
	}
	echo '</ul>';
}

$blocks = array(
	'rituals'       => __( 'Rituals', 'bookloversclub' ),
	'artifacts'     => __( 'Artefacts', 'bookloversclub' ),
	'running_jokes' => __( 'Running jokes', 'bookloversclub' ),
	'pilgrimages'   => __( 'Pilgrimages', 'bookloversclub' ),
);
foreach ( $blocks as $key => $label ) {
	$items = blc_fan_get( $data, 'fandom.' . $key, array() );
	if ( ! $items ) {
		continue;
	}
	printf( '<h3>%s</h3><ul class="fan-list fan-list-marked">', esc_html( $label ) );
	foreach ( $items as $item ) {
		printf( '<li>%s</li>', esc_html( $item ) );
	}
	echo '</ul>';
}

$stance = blc_fan_get( $data, 'fandom.adaptation_stance' );
if ( $stance ) {
	$labels = array(
		'protective'  => __( 'Protective of the book — adaptations are viewed with suspicion.', 'bookloversclub' ),
		'welcoming'   => __( 'Welcoming — adaptations are treated as a way in.', 'bookloversclub' ),
		'split'       => __( 'Split — the adaptation question reliably starts an argument.', 'bookloversclub' ),
		'indifferent' => __( 'Largely indifferent to adaptations.', 'bookloversclub' ),
	);
	printf(
		'<p class="fan-aside"><strong>%s</strong> %s</p>',
		esc_html__( 'On adaptations:', 'bookloversclub' ),
		esc_html( isset( $labels[ $stance ] ) ? $labels[ $stance ] : $stance )
	);
}

blc_fan_section_close();
