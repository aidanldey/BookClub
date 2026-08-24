<?php
/**
 * "What you've heard vs. what it is."
 *
 * @package BookLoversClub
 */

$data           = isset( $args['data'] ) ? $args['data'] : array();
$misconceptions = blc_fan_get( $data, 'misconceptions', array() );

blc_fan_section_open( 'misconceptions', __( 'What you\'ve heard', 'bookloversclub' ), __( 'What you\'ve heard vs. what it is', 'bookloversclub' ) );
?>
<div class="fan-myths">
	<?php foreach ( $misconceptions as $item ) : ?>
		<?php
		if ( empty( $item['belief'] ) || empty( $item['reality'] ) ) {
			continue;
		}
		?>
		<div class="fan-myth">
			<div class="fan-myth-belief">
				<span class="fan-inline-label"><?php esc_html_e( 'You\'ve heard', 'bookloversclub' ); ?></span>
				<p><?php echo esc_html( $item['belief'] ); ?></p>
			</div>
			<div class="fan-myth-reality">
				<span class="fan-inline-label"><?php esc_html_e( 'Actually', 'bookloversclub' ); ?></span>
				<p><?php echo esc_html( $item['reality'] ); ?></p>
				<?php if ( ! empty( $item['school_assignment_effect'] ) ) : ?>
					<p class="fan-myth-flag"><?php esc_html_e( 'Mostly an artefact of being assigned in school.', 'bookloversclub' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
<?php
blc_fan_section_close();
