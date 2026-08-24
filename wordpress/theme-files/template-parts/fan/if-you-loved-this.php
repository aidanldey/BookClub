<?php
/**
 * "If you loved this" — where to go next.
 *
 * @package BookLoversClub
 */

$data       = isset( $args['data'] ) ? $args['data'] : array();
$next_reads = blc_fan_get( $data, 'if_you_loved_this', array() );

blc_fan_section_open( 'if-you-loved-this', __( 'Read next', 'bookloversclub' ), __( 'If you loved this', 'bookloversclub' ), '' );
?>
<div class="grid fan-grid">
	<?php foreach ( $next_reads as $book ) : ?>
		<?php
		if ( empty( $book['title'] ) ) {
			continue;
		}
		?>
		<article class="card fan-next">
			<h3><?php echo esc_html( $book['title'] ); ?></h3>
			<?php if ( ! empty( $book['author'] ) ) : ?>
				<p class="card-excerpt"><?php echo esc_html( $book['author'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $book['same_author'] ) ) : ?>
				<p><span class="badge badge-author"><?php esc_html_e( 'Same author', 'bookloversclub' ); ?></span></p>
			<?php endif; ?>
			<?php if ( ! empty( $book['reason'] ) ) : ?>
				<p><?php echo esc_html( $book['reason'] ); ?></p>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>
</div>
<?php
blc_fan_section_close();
