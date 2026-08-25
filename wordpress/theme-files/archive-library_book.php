<?php
/**
 * The Reading Room — every book the club can hand you outright.
 *
 * Public domain only, by design: an EPUB served over HTTP is downloadable by
 * anyone who opens it, so this shelf holds the books whose copyright has
 * expired and nothing else. Books still in copyright get a fan page and a link
 * to a bookshop or a library, not a file.
 *
 * @package BookLoversClub
 */

get_header();
?>
<header class="entry-header container">
	<div class="entry-kicker"><?php esc_html_e( 'Reading Room', 'bookloversclub' ); ?></div>
	<h1><?php esc_html_e( 'Books you can start right now', 'bookloversclub' ); ?></h1>
	<p class="fan-archive-intro">
		<?php esc_html_e( 'Out of copyright, free to read, and open in the browser — no account, no app, no download unless you want one. Your place in each book is remembered on this device.', 'bookloversclub' ); ?>
	</p>
</header>

<div class="container">
	<?php if ( have_posts() ) : ?>
		<div class="grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/card', 'library' );
			endwhile;
			?>
		</div>
		<?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'The shelf is still being built. Check back soon.', 'bookloversclub' ); ?></p>
	<?php endif; ?>

	<div class="reading-column blc-library-note">
		<hr class="ornament">
		<p>
			<?php esc_html_e( 'Not finding a favourite? Most of what the club reads is still in copyright, which means we can point you to it but not hand it over. Those books have fan pages instead — start there, then borrow from your library.', 'bookloversclub' ); ?>
		</p>
		<p>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'fan_page' ) ); ?>">
				<?php esc_html_e( 'Browse the fan pages', 'bookloversclub' ); ?>
			</a>
		</p>
	</div>
</div>
<?php
get_footer();
