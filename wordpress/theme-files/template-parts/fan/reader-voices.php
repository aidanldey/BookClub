<?php
/**
 * "From the club" — real, linkable reader quotes.
 *
 * Every voice carries its handle and a permalink; see research/SOURCING.md.
 * A voice without a permalink is not rendered — an unverifiable quote on the
 * page is worse than one fewer voice.
 *
 * @package BookLoversClub
 */

$data   = isset( $args['data'] ) ? $args['data'] : array();
$voices = blc_fan_get( $data, 'reader_voices', array() );

blc_fan_section_open( 'voices', __( 'From the club', 'bookloversclub' ), __( 'What readers say', 'bookloversclub' ), '' );
?>
<div class="grid fan-grid fan-voices">
	<?php foreach ( $voices as $voice ) : ?>
		<?php
		if ( empty( $voice['quote'] ) || empty( $voice['permalink'] ) ) {
			continue;
		}
		$handle   = ! empty( $voice['username'] ) ? $voice['username'] : __( 'a reader', 'bookloversclub' );
		$platform = ! empty( $voice['platform'] ) ? $voice['platform'] : '';
		$critical = isset( $voice['angle'] ) && 'critical' === $voice['angle'];
		?>
		<figure class="card fan-voice<?php echo $critical ? ' is-critical' : ''; ?>">
			<?php if ( ! empty( $voice['angle'] ) ) : ?>
				<div class="fan-voice-angle"><?php echo esc_html( blc_fan_angle_label( $voice['angle'] ) ); ?></div>
			<?php endif; ?>

			<blockquote>
				<?php echo wp_kses_post( blc_fan_spoiler( '<p>' . esc_html( $voice['quote'] ) . '</p>', ! empty( $voice['spoiler'] ) ) ); ?>
			</blockquote>

			<figcaption class="fan-voice-source">
				<a href="<?php echo esc_url( $voice['permalink'] ); ?>" rel="nofollow ugc noopener" target="_blank">
					<span class="fan-voice-handle"><?php echo esc_html( $handle ); ?></span>
				</a>
				<span class="fan-voice-meta">
					<?php
					$meta = array_filter( array( $platform, ! empty( $voice['date'] ) ? $voice['date'] : '' ) );
					echo esc_html( implode( ' · ', $meta ) );
					?>
				</span>
			</figcaption>
		</figure>
	<?php endforeach; ?>
</div>

<p class="fan-aside fan-voices-note">
	<?php esc_html_e( 'Quotes are reproduced as written and linked to their source. If one of these is yours and you would like it removed, tell us and it comes down.', 'bookloversclub' ); ?>
</p>
<?php
blc_fan_section_close();
