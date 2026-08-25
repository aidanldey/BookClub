<?php
/**
 * The reader — one public-domain book, read in the browser.
 *
 * The chrome is server-rendered so the page is complete before any JavaScript
 * runs: without JS a visitor still gets the edition, its provenance, and a
 * download link. assets/js/reader.js takes it from there, mounting foliate-js
 * into .blc-reader-viewport and wiring the controls that are already here.
 *
 * @package BookLoversClub
 */

get_header();

while ( have_posts() ) :
	the_post();

	$meta     = blc_reader_meta();
	$byline   = blc_reader_byline( $meta );
	$fan_page = blc_reader_fan_page_for_book();

	// A shared position: /read/<slug>/?loc=<cfi>. Handed to the reader, which
	// prefers it over wherever this browser left off.
	$loc = isset( $_GET['loc'] ) ? sanitize_text_field( wp_unslash( $_GET['loc'] ) ) : '';
	?>
	<article <?php post_class( 'library-book' ); ?>>

		<header class="blc-read-header">
			<div class="container">
				<div class="fan-kicker"><?php esc_html_e( 'Reading Room', 'bookloversclub' ); ?></div>
				<h1 class="blc-read-title"><?php the_title(); ?></h1>
				<?php if ( $byline ) : ?>
					<p class="blc-read-byline"><?php echo esc_html( $byline ); ?></p>
				<?php endif; ?>
				<p class="blc-read-links">
					<?php if ( $fan_page ) : ?>
						<a href="<?php echo esc_url( get_permalink( $fan_page ) ); ?>">
							<?php esc_html_e( 'What the club says about it', 'bookloversclub' ); ?>
						</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'library_book' ) ); ?>">
						<?php esc_html_e( 'All free books', 'bookloversclub' ); ?>
					</a>
				</p>
			</div>
		</header>

		<?php if ( ! $meta['epub_url'] ) : ?>

			<div class="container reading-column">
				<p class="blc-read-notice">
					<?php esc_html_e( 'This edition has not been uploaded yet — check back shortly.', 'bookloversclub' ); ?>
				</p>
			</div>

		<?php else : ?>

			<div class="blc-reader is-loading"
				data-epub="<?php echo esc_url( $meta['epub_url'] ); ?>"
				data-key="<?php echo esc_attr( get_post_field( 'post_name' ) ); ?>"
				data-loc="<?php echo esc_attr( $loc ); ?>">

				<div class="blc-reader-bar">
					<div class="blc-reader-bar-group">
						<button type="button" class="blc-reader-toc-button blc-reader-tool"
							aria-expanded="false" aria-controls="blc-reader-toc">
							<?php esc_html_e( 'Contents', 'bookloversclub' ); ?>
						</button>
						<button type="button" class="blc-reader-settings-button blc-reader-tool"
							aria-expanded="false" aria-controls="blc-reader-settings">
							<?php esc_html_e( 'Text', 'bookloversclub' ); ?>
						</button>
					</div>

					<p class="blc-reader-running-title"><?php the_title(); ?></p>

					<div class="blc-reader-bar-group">
						<button type="button" class="blc-reader-focus blc-reader-tool" aria-pressed="false">
							<?php esc_html_e( 'Focus', 'bookloversclub' ); ?>
						</button>
					</div>
				</div>

				<div class="blc-reader-panel blc-reader-toc" id="blc-reader-toc" hidden>
					<div class="blc-reader-panel-head">
						<h2><?php esc_html_e( 'Contents', 'bookloversclub' ); ?></h2>
						<button type="button" class="blc-reader-panel-close" aria-label="<?php esc_attr_e( 'Close contents', 'bookloversclub' ); ?>">&times;</button>
					</div>
					<nav class="blc-reader-toc-body" aria-label="<?php esc_attr_e( 'Table of contents', 'bookloversclub' ); ?>"></nav>
				</div>

				<div class="blc-reader-panel blc-reader-settings" id="blc-reader-settings" hidden>
					<div class="blc-reader-panel-head">
						<h2><?php esc_html_e( 'Text', 'bookloversclub' ); ?></h2>
						<button type="button" class="blc-reader-panel-close" aria-label="<?php esc_attr_e( 'Close text settings', 'bookloversclub' ); ?>">&times;</button>
					</div>

					<div class="blc-reader-setting">
						<span class="blc-reader-setting-label"><?php esc_html_e( 'Size', 'bookloversclub' ); ?></span>
						<div class="blc-reader-setting-controls">
							<button type="button" class="blc-reader-smaller blc-reader-chip" aria-label="<?php esc_attr_e( 'Smaller text', 'bookloversclub' ); ?>">&minus;</button>
							<span class="blc-reader-fontsize-value">100%</span>
							<button type="button" class="blc-reader-larger blc-reader-chip" aria-label="<?php esc_attr_e( 'Larger text', 'bookloversclub' ); ?>">+</button>
						</div>
					</div>

					<div class="blc-reader-setting">
						<span class="blc-reader-setting-label"><?php esc_html_e( 'Typeface', 'bookloversclub' ); ?></span>
						<div class="blc-reader-setting-controls">
							<button type="button" class="blc-reader-chip" data-setting="family" data-value="book"><?php esc_html_e( 'Edition', 'bookloversclub' ); ?></button>
							<button type="button" class="blc-reader-chip" data-setting="family" data-value="serif"><?php esc_html_e( 'Serif', 'bookloversclub' ); ?></button>
							<button type="button" class="blc-reader-chip" data-setting="family" data-value="sans"><?php esc_html_e( 'Sans', 'bookloversclub' ); ?></button>
						</div>
					</div>

					<div class="blc-reader-setting">
						<span class="blc-reader-setting-label"><?php esc_html_e( 'Spacing', 'bookloversclub' ); ?></span>
						<div class="blc-reader-setting-controls">
							<button type="button" class="blc-reader-chip" data-setting="lineHeight" data-value="1.4"><?php esc_html_e( 'Tight', 'bookloversclub' ); ?></button>
							<button type="button" class="blc-reader-chip" data-setting="lineHeight" data-value="1.6"><?php esc_html_e( 'Normal', 'bookloversclub' ); ?></button>
							<button type="button" class="blc-reader-chip" data-setting="lineHeight" data-value="1.9"><?php esc_html_e( 'Airy', 'bookloversclub' ); ?></button>
						</div>
					</div>

					<div class="blc-reader-setting">
						<span class="blc-reader-setting-label"><?php esc_html_e( 'Layout', 'bookloversclub' ); ?></span>
						<div class="blc-reader-setting-controls">
							<button type="button" class="blc-reader-chip" data-setting="flow" data-value="paginated"><?php esc_html_e( 'Pages', 'bookloversclub' ); ?></button>
							<button type="button" class="blc-reader-chip" data-setting="flow" data-value="scrolled"><?php esc_html_e( 'Scroll', 'bookloversclub' ); ?></button>
						</div>
					</div>

					<div class="blc-reader-setting">
						<span class="blc-reader-setting-label"><?php esc_html_e( 'Margins', 'bookloversclub' ); ?></span>
						<div class="blc-reader-setting-controls">
							<button type="button" class="blc-reader-chip" data-setting="justify" data-value="true"><?php esc_html_e( 'Justified', 'bookloversclub' ); ?></button>
							<button type="button" class="blc-reader-chip" data-setting="justify" data-value="false"><?php esc_html_e( 'Ragged', 'bookloversclub' ); ?></button>
						</div>
					</div>
				</div>

				<div class="blc-reader-stage">
					<button type="button" class="blc-reader-prev blc-reader-page-button"
						aria-label="<?php esc_attr_e( 'Previous page', 'bookloversclub' ); ?>">
						<span aria-hidden="true">&lsaquo;</span>
					</button>

					<div class="blc-reader-viewport"></div>

					<button type="button" class="blc-reader-next blc-reader-page-button"
						aria-label="<?php esc_attr_e( 'Next page', 'bookloversclub' ); ?>">
						<span aria-hidden="true">&rsaquo;</span>
					</button>

					<div class="blc-reader-loading" aria-live="polite">
						<p><?php esc_html_e( 'Opening the book…', 'bookloversclub' ); ?></p>
						<div class="blc-reader-loading-track"><div class="blc-reader-loading-bar"></div></div>
					</div>

					<div class="blc-reader-error" role="alert">
						<p><?php esc_html_e( 'This book would not open in your browser.', 'bookloversclub' ); ?></p>
						<p class="blc-reader-error-detail"></p>
						<p>
							<a class="btn btn-secondary" href="<?php echo esc_url( $meta['epub_url'] ); ?>" download>
								<?php esc_html_e( 'Download the EPUB instead', 'bookloversclub' ); ?>
							</a>
						</p>
					</div>
				</div>

				<div class="blc-reader-foot">
					<label class="blc-reader-slider-label">
						<span class="screen-reader-text"><?php esc_html_e( 'Position in the book', 'bookloversclub' ); ?></span>
						<input type="range" class="blc-reader-slider" min="0" max="1" step="0.001" value="0">
					</label>
					<div class="blc-reader-status">
						<span class="blc-reader-chapter"></span>
						<span class="blc-reader-percent">0%</span>
						<button type="button" class="blc-reader-share blc-reader-tool"
							data-copied="<?php esc_attr_e( 'Link copied', 'bookloversclub' ); ?>">
							<?php esc_html_e( 'Link to this spot', 'bookloversclub' ); ?>
						</button>
					</div>
				</div>
			</div>

		<?php endif; ?>

		<div class="blc-read-about">
			<div class="container reading-column">
				<?php if ( trim( get_the_content() ) ) : ?>
					<div class="entry-content"><?php the_content(); ?></div>
				<?php endif; ?>

				<h2><?php esc_html_e( 'About this edition', 'bookloversclub' ); ?></h2>
				<dl class="fan-glance blc-read-glance">
					<?php if ( $meta['year'] ) : ?>
						<div class="fan-glance-item">
							<dt><?php esc_html_e( 'First published', 'bookloversclub' ); ?></dt>
							<dd><?php echo esc_html( $meta['year'] ); ?></dd>
						</div>
					<?php endif; ?>
					<?php if ( $meta['translator'] ) : ?>
						<div class="fan-glance-item">
							<dt><?php esc_html_e( 'Translator', 'bookloversclub' ); ?></dt>
							<dd><?php echo esc_html( $meta['translator'] ); ?></dd>
						</div>
					<?php endif; ?>
					<?php if ( $meta['rights'] ) : ?>
						<div class="fan-glance-item">
							<dt><?php esc_html_e( 'Rights', 'bookloversclub' ); ?></dt>
							<dd><?php echo esc_html( $meta['rights'] ); ?></dd>
						</div>
					<?php endif; ?>
					<?php if ( $meta['source_name'] ) : ?>
						<div class="fan-glance-item">
							<dt><?php esc_html_e( 'Source', 'bookloversclub' ); ?></dt>
							<dd>
								<?php if ( $meta['source_url'] ) : ?>
									<a href="<?php echo esc_url( $meta['source_url'] ); ?>" rel="noopener"><?php echo esc_html( $meta['source_name'] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $meta['source_name'] ); ?>
								<?php endif; ?>
							</dd>
						</div>
					<?php endif; ?>
				</dl>

				<?php if ( $meta['epub_url'] ) : ?>
					<p class="blc-read-download">
						<a href="<?php echo esc_url( $meta['epub_url'] ); ?>" download>
							<?php esc_html_e( 'Download the EPUB', 'bookloversclub' ); ?>
						</a>
						<?php
						$size = blc_reader_filesize( $meta['epub_size'] );
						if ( $size ) {
							echo ' <span class="blc-read-filesize">' . esc_html( $size ) . '</span>';
						}
						?>
						<span class="blc-read-download-note">
							<?php esc_html_e( 'Yours to keep — it opens in any e-reader app.', 'bookloversclub' ); ?>
						</span>
					</p>
				<?php endif; ?>
			</div>
		</div>

	</article>
	<?php
endwhile;

get_footer();
