<?php
/**
 * "Reader favorites" — the character cards.
 *
 * @package BookLoversClub
 */

$data       = isset( $args['data'] ) ? $args['data'] : array();
$characters = blc_fan_get( $data, 'characters', array() );

// Fan-favorite rank orders the grid; unranked characters fall to the back.
usort( $characters, function ( $a, $b ) {
	$rank_a = isset( $a['fan_favorite_rank'] ) ? (int) $a['fan_favorite_rank'] : PHP_INT_MAX;
	$rank_b = isset( $b['fan_favorite_rank'] ) ? (int) $b['fan_favorite_rank'] : PHP_INT_MAX;
	return $rank_a <=> $rank_b;
} );

blc_fan_section_open( 'characters', __( 'Characters', 'bookloversclub' ), __( 'Who readers carry with them', 'bookloversclub' ), '' );
?>
<div class="grid fan-grid">
	<?php foreach ( $characters as $character ) : ?>
		<article class="card fan-character">
			<header class="fan-character-head">
				<h3><?php echo esc_html( isset( $character['name'] ) ? $character['name'] : '' ); ?></h3>
				<?php if ( ! empty( $character['role'] ) ) : ?>
					<p class="fan-character-role"><?php echo esc_html( $character['role'] ); ?></p>
				<?php endif; ?>
			</header>

			<?php if ( ! empty( $character['divisive'] ) || ! empty( $character['heartbreak'] ) ) : ?>
				<p class="fan-character-badges">
					<?php if ( ! empty( $character['divisive'] ) ) : ?>
						<span class="badge badge-seasonal"><?php esc_html_e( 'Divisive', 'bookloversclub' ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $character['heartbreak'] ) ) : ?>
						<span class="badge badge-featured"><?php esc_html_e( 'Heartbreak', 'bookloversclub' ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $character['why_readers_love_them'] ) ) : ?>
				<p><?php echo esc_html( $character['why_readers_love_them'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $character['signature_moment'] ) ) : ?>
				<p class="fan-character-moment">
					<span class="fan-inline-label"><?php esc_html_e( 'Signature moment', 'bookloversclub' ); ?></span>
					<?php
					// Signature moments are plot; hide them behind the spoiler shell.
					echo wp_kses_post( blc_fan_spoiler( esc_html( $character['signature_moment'] ), true ) );
					?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $character['the_case_against'] ) ) : ?>
				<p class="fan-character-against">
					<span class="fan-inline-label"><?php esc_html_e( 'The case against', 'bookloversclub' ); ?></span>
					<?php echo esc_html( $character['the_case_against'] ); ?>
				</p>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>
</div>
<?php
blc_fan_section_close();
