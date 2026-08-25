<?php
/**
 * Reading Room card — a book you can open, and where you left off in it.
 *
 * The progress line is empty until assets/js/library.js fills it in from this
 * browser's stored position, so a first-time visitor sees a clean card.
 *
 * @package BookLoversClub
 */

$meta   = blc_reader_meta();
$byline = blc_reader_byline( $meta );
$slug   = get_post_field( 'post_name' );
$fan    = blc_reader_fan_page_for_book();
?>
<article class="card card-fan card-library">
	<?php if ( has_post_thumbnail() ) : ?>
		<a href="<?php the_permalink(); ?>" class="book-cover" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'blc-cover' ); ?>
		</a>
	<?php endif; ?>

	<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>

	<?php if ( $byline ) : ?>
		<p class="card-excerpt" style="margin-bottom: var(--spacing-xs);"><?php echo esc_html( $byline ); ?></p>
	<?php endif; ?>

	<?php if ( has_excerpt() ) : ?>
		<p class="card-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
	<?php endif; ?>

	<div class="blc-progress-track" hidden>
		<div class="blc-progress-fill" data-blc-progress-bar="<?php echo esc_attr( $slug ); ?>"></div>
	</div>
	<p class="blc-progress-readout" data-blc-progress-for="<?php echo esc_attr( $slug ); ?>" hidden></p>

	<p class="card-meta blc-card-actions">
		<a class="blc-read-link" href="<?php the_permalink(); ?>"
			data-blc-progress="<?php echo esc_attr( $slug ); ?>"
			data-continue-label="<?php esc_attr_e( 'Continue reading', 'bookloversclub' ); ?>">
			<?php esc_html_e( 'Read it free', 'bookloversclub' ); ?>
		</a>
		<?php if ( $fan ) : ?>
			<a class="blc-card-secondary" href="<?php echo esc_url( get_permalink( $fan ) ); ?>">
				<?php esc_html_e( 'Fan page', 'bookloversclub' ); ?>
			</a>
		<?php endif; ?>
	</p>
</article>
