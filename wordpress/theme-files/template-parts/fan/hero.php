<?php
/**
 * Fan page hero — book cover, representative quote, and the at-a-glance rail.
 *
 * Expects $args['data'] (research data). Falls back gracefully: no cover renders
 * a typeset stand-in, no quote simply drops the block.
 *
 * @package BookLoversClub
 */

$data  = isset( $args['data'] ) ? $args['data'] : array();
$quote = blc_fan_hero_quote( $data );

$author = blc_fan_get( $data, 'book.author' );
$year   = blc_fan_get( $data, 'book.year_published' );
$pitch  = blc_fan_get( $data, 'book.one_line_pitch' );
$shelf  = blc_fan_get( $data, 'book.shelf_actual', blc_fan_get( $data, 'book.shelf' ) );

// The preferred_format enum reads as a label, not a raw value.
$format_labels = array(
	'print'  => __( 'Print', 'bookloversclub' ),
	'audio'  => __( 'Audio', 'bookloversclub' ),
	'either' => __( 'Print or audio', 'bookloversclub' ),
);
$format = blc_fan_get( $data, 'before_you_start.audiobook.preferred_format' );
$format = ( $format && isset( $format_labels[ $format ] ) ) ? $format_labels[ $format ] : null;

// Only flag the original language when it isn't the one we're reading in —
// "Originally in: English" is noise on an English-language site.
$language = blc_fan_get( $data, 'book.original_language' );
if ( $language && 0 === strcasecmp( $language, 'English' ) ) {
	$language = null;
}

// At-a-glance rail: only the facts we actually have.
$glance = array_filter( array(
	__( 'First published', 'bookloversclub' ) => $year,
	__( 'Pages', 'bookloversclub' )           => blc_fan_get( $data, 'book.page_count' ),
	__( 'Shelf', 'bookloversclub' )           => $shelf,
	__( 'Commitment', 'bookloversclub' )      => blc_fan_get( $data, 'book.reading_time_note' ),
	__( 'Originally in', 'bookloversclub' )   => $language,
	__( 'Best in', 'bookloversclub' )         => $format,
) );
?>
<header class="fan-hero">
	<div class="container">
		<div class="fan-hero-inner">

			<div class="fan-hero-cover">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="book-cover fan-cover">
						<?php the_post_thumbnail( 'blc-cover-large', array( 'alt' => esc_attr( sprintf( __( 'Cover of %s', 'bookloversclub' ), get_the_title() ) ) ) ); ?>
					</div>
				<?php else : ?>
					<div class="book-cover fan-cover fan-cover-placeholder" role="img"
						aria-label="<?php echo esc_attr( sprintf( __( 'No cover image for %s', 'bookloversclub' ), get_the_title() ) ); ?>">
						<span class="fan-cover-title"><?php echo esc_html( get_the_title() ); ?></span>
						<?php if ( $author ) : ?>
							<span class="fan-cover-author"><?php echo esc_html( $author ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="fan-hero-body">
				<div class="fan-kicker"><?php esc_html_e( 'Fan Page', 'bookloversclub' ); ?></div>

				<h1 class="fan-hero-title"><?php the_title(); ?></h1>

				<?php if ( $author || $year ) : ?>
					<p class="fan-hero-byline">
						<?php
						echo esc_html( $author ? $author : '' );
						if ( $author && $year ) {
							echo ' <span class="fan-dot" aria-hidden="true"></span> ';
						}
						echo esc_html( $year ? $year : '' );
						?>
					</p>
				<?php endif; ?>

				<?php if ( $quote && ! empty( $quote['text'] ) ) : ?>
					<figure class="fan-hero-quote">
						<blockquote><p><?php echo esc_html( $quote['text'] ); ?></p></blockquote>
						<?php if ( ! empty( $quote['attribution'] ) ) : ?>
							<figcaption><?php echo esc_html( $quote['attribution'] ); ?></figcaption>
						<?php endif; ?>
					</figure>
				<?php endif; ?>

				<?php if ( $pitch ) : ?>
					<p class="fan-hero-pitch"><?php echo esc_html( $pitch ); ?></p>
				<?php endif; ?>

				<?php if ( $glance ) : ?>
					<dl class="fan-glance">
						<?php foreach ( $glance as $label => $value ) : ?>
							<div class="fan-glance-item">
								<dt><?php echo esc_html( $label ); ?></dt>
								<dd><?php echo esc_html( $value ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>
			</div>

		</div>
	</div>
</header>
