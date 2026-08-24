<?php
/**
 * Fan page card (dog-ear on hover), for the /books/ archive.
 *
 * @package BookLoversClub
 */

$data   = blc_fan_data();
$author = blc_fan_get( $data, 'book.author' );
$pitch  = blc_fan_get( $data, 'book.one_line_pitch' );
$voices = blc_fan_get( $data, 'reader_voices', array() );
?>
<article class="card card-fan">
	<?php if ( has_post_thumbnail() ) : ?>
		<a href="<?php the_permalink(); ?>" class="book-cover" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'blc-cover' ); ?>
		</a>
	<?php endif; ?>

	<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>

	<?php if ( $author ) : ?>
		<p class="card-excerpt" style="margin-bottom: var(--spacing-xs);"><?php echo esc_html( $author ); ?></p>
	<?php endif; ?>

	<?php if ( $pitch ) : ?>
		<p class="card-excerpt"><?php echo esc_html( $pitch ); ?></p>
	<?php endif; ?>

	<?php if ( $voices ) : ?>
		<div class="card-meta">
			<?php
			printf(
				/* translators: %d: number of reader quotes */
				esc_html( _n( '%d reader voice', '%d reader voices', count( $voices ), 'bookloversclub' ) ),
				count( $voices )
			);
			?>
		</div>
	<?php endif; ?>
</article>
