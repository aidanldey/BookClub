<?php
/**
 * Fan page archive — the shelf of books with pages.
 *
 * @package BookLoversClub
 */

get_header();
?>
<header class="entry-header container">
	<div class="entry-kicker"><?php esc_html_e( 'Fan Pages', 'bookloversclub' ); ?></div>
	<h1><?php esc_html_e( 'The books we love', 'bookloversclub' ); ?></h1>
	<p class="fan-archive-intro">
		<?php esc_html_e( 'One page per book: the lines readers keep, the characters they argue about, and what the club actually said.', 'bookloversclub' ); ?>
	</p>
</header>

<div class="container">
	<?php if ( have_posts() ) : ?>
		<div class="grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/card', 'fan' );
			endwhile;
			?>
		</div>
		<?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No fan pages yet.', 'bookloversclub' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
