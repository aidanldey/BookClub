<?php
/**
 * "Lines readers keep" — quotes from the book itself.
 *
 * @package BookLoversClub
 */

$data   = isset( $args['data'] ) ? $args['data'] : array();
$quotes = blc_fan_get( $data, 'book_quotes', array() );

blc_fan_section_open( 'quotes', __( 'Lines', 'bookloversclub' ), __( 'Lines readers keep', 'bookloversclub' ) );
?>
<div class="fan-quotes">
	<?php foreach ( $quotes as $quote ) : ?>
		<?php
		if ( empty( $quote['text'] ) ) {
			continue;
		}
		$attribution = blc_fan_quote_attribution( $quote, $data );
		?>
		<figure class="fan-quote<?php echo ! empty( $quote['tattoo_tier'] ) ? ' is-tattoo-tier' : ''; ?>">
			<?php if ( ! empty( $quote['tattoo_tier'] ) ) : ?>
				<span class="fan-quote-flag"><?php esc_html_e( 'The one people get tattooed', 'bookloversclub' ); ?></span>
			<?php endif; ?>

			<blockquote>
				<?php echo wp_kses_post( blc_fan_spoiler( '<p>' . esc_html( $quote['text'] ) . '</p>', ! empty( $quote['spoiler'] ) ) ); ?>
			</blockquote>

			<?php if ( $attribution || ! empty( $quote['translation'] ) ) : ?>
				<figcaption>
					<?php echo esc_html( $attribution ); ?>
					<?php if ( ! empty( $quote['translation'] ) ) : ?>
						<span class="fan-quote-translator">
							<?php
							/* translators: %s: translator name */
							printf( esc_html__( 'trans. %s', 'bookloversclub' ), esc_html( $quote['translation'] ) );
							?>
						</span>
					<?php endif; ?>
				</figcaption>
			<?php endif; ?>

			<?php if ( ! empty( $quote['why_it_lands'] ) ) : ?>
				<p class="fan-quote-gloss"><?php echo esc_html( $quote['why_it_lands'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $quote['commonly_misread'] ) ) : ?>
				<p class="fan-quote-misread">
					<span class="fan-inline-label"><?php esc_html_e( 'Commonly misread', 'bookloversclub' ); ?></span>
					<?php echo esc_html( $quote['commonly_misread'] ); ?>
				</p>
			<?php endif; ?>
		</figure>
	<?php endforeach; ?>
</div>
<?php
blc_fan_section_close();
