<?php
/**
 * "Contested" — the arguments readers are still having.
 *
 * Both sides get the same visual weight; see the README on steelmanning.
 *
 * @package BookLoversClub
 */

$data    = isset( $args['data'] ) ? $args['data'] : array();
$debates = blc_fan_get( $data, 'debates', array() );

blc_fan_section_open( 'debates', __( 'Contested', 'bookloversclub' ), __( 'What readers argue about', 'bookloversclub' ) );
?>
<div class="fan-debates">
	<?php foreach ( $debates as $debate ) : ?>
		<?php
		if ( empty( $debate['question'] ) ) {
			continue;
		}
		$spoiler = ! empty( $debate['spoiler'] );
		?>
		<article class="fan-debate">
			<h3 class="fan-debate-question">
				<?php echo esc_html( $debate['question'] ); ?>
				<?php if ( ! empty( $debate['settled'] ) ) : ?>
					<span class="badge badge-level"><?php esc_html_e( 'Mostly settled', 'bookloversclub' ); ?></span>
				<?php endif; ?>
			</h3>

			<div class="fan-debate-sides">
				<?php if ( ! empty( $debate['side_a'] ) ) : ?>
					<div class="fan-debate-side">
						<span class="fan-inline-label"><?php esc_html_e( 'One camp', 'bookloversclub' ); ?></span>
						<?php echo wp_kses_post( blc_fan_spoiler( '<p>' . esc_html( $debate['side_a'] ) . '</p>', $spoiler ) ); ?>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $debate['side_b'] ) ) : ?>
					<div class="fan-debate-side">
						<span class="fan-inline-label"><?php esc_html_e( 'The other', 'bookloversclub' ); ?></span>
						<?php echo wp_kses_post( blc_fan_spoiler( '<p>' . esc_html( $debate['side_b'] ) . '</p>', $spoiler ) ); ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $debate['discussion_prompt'] ) ) : ?>
				<p class="fan-debate-prompt"><?php echo esc_html( $debate['discussion_prompt'] ); ?></p>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>
</div>
<?php
blc_fan_section_close();
