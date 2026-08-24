<?php
/**
 * Single fan page — book cover and representative quote up top, then the
 * research sections in reading order.
 *
 * Everything below the hero renders from the `_blc_fan_data` research JSON.
 * Sections in the "cherry-pick" tier only appear when they carry content, so a
 * thin page stays short rather than rendering empty headings.
 *
 * @package BookLoversClub
 */

get_header();

while ( have_posts() ) :
	the_post();

	$data     = blc_fan_data();
	$sections = blc_fan_sections( $data );
	?>
	<article <?php post_class( 'fan-page' ); ?>>

		<?php get_template_part( 'template-parts/fan/hero', null, array( 'data' => $data ) ); ?>

		<?php if ( count( $sections ) > 1 ) : ?>
			<nav class="fan-nav" aria-label="<?php esc_attr_e( 'Page sections', 'bookloversclub' ); ?>">
				<div class="container">
					<ul>
						<?php foreach ( $sections as $id => $label ) : ?>
							<li><a href="#<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</nav>
		<?php endif; ?>

		<?php if ( trim( get_the_content() ) ) : ?>
			<div class="fan-section">
				<div class="container reading-column">
					<div class="entry-content has-drop-cap-auto">
						<?php the_content(); ?>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php
		// Anchor id => template part. Only the sections blc_fan_sections()
		// selected are rendered, and in this order.
		$parts = array(
			'resonance'        => 'resonance',
			'craft'            => 'craft',
			'characters'       => 'characters',
			'quotes'           => 'book-quotes',
			'voices'           => 'reader-voices',
			'setting'          => 'setting',
			'misconceptions'   => 'misconceptions',
			'debates'          => 'debates',
			'reception'        => 'reception',
			'author'           => 'author',
			'fandom'           => 'fandom',
			'before-you-start' => 'before-you-start',
			'club-kit'         => 'club-kit',
			'if-you-loved-this'=> 'if-you-loved-this',
		);
		foreach ( $parts as $id => $part ) {
			if ( isset( $sections[ $id ] ) ) {
				get_template_part( 'template-parts/fan/' . $part, null, array( 'data' => $data ) );
			}
		}
		?>

		<?php if ( comments_open() || get_comments_number() ) : ?>
			<div class="fan-section" id="talk">
				<div class="container reading-column">
					<hr class="ornament">
					<h2><?php esc_html_e( 'Add your voice', 'bookloversclub' ); ?></h2>
					<p class="fan-aside">
						<?php esc_html_e( 'Tell us what this book did to you — the best comments end up on this page.', 'bookloversclub' ); ?>
					</p>
					<?php comments_template(); ?>
				</div>
			</div>
		<?php endif; ?>

	</article>
	<?php
endwhile;

get_footer();
