<?php
/**
 * Plugin Name: DrBrand Resources
 * Description: Resources filter and listing for the Resource post type.
 * Version: 1.0.0
 * Author: DrBrand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DBR_RESOURCES_VERSION', '1.0.0' );
define( 'DBR_RESOURCES_DIR', plugin_dir_path( __FILE__ ) );
define( 'DBR_RESOURCES_URL', plugin_dir_url( __FILE__ ) );

add_action( 'wp_enqueue_scripts', 'dbr_resources_register_assets' );
function dbr_resources_register_assets() {
	wp_register_style(
		'dbr-resources',
		DBR_RESOURCES_URL . 'assets/resources.css',
		array(),
		DBR_RESOURCES_VERSION
	);

	wp_register_script(
		'dbr-resources',
		DBR_RESOURCES_URL . 'assets/resources.js',
		array(),
		DBR_RESOURCES_VERSION,
		true
	);
}

add_shortcode( 'drbrand_resources', 'dbr_resources_shortcode' );
function dbr_resources_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'per_group' => 3,
		),
		$atts,
		'drbrand_resources'
	);

	wp_enqueue_style( 'dbr-resources' );
	wp_enqueue_script( 'dbr-resources' );

	$gsap_handle = 'gsap';
	if ( ! wp_script_is( $gsap_handle, 'registered' ) ) {
		wp_register_script(
			$gsap_handle,
			'https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js',
			array(),
			'3.12.5',
			true
		);
	}
	wp_enqueue_script( $gsap_handle );

	$archive_url = get_post_type_archive_link( 'resource' );
	if ( ! $archive_url ) {
		$archive_url = home_url( '/resources/' );
	}

	$search_query = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$resource_type = isset( $_GET['resource-type'] ) ? sanitize_text_field( wp_unslash( $_GET['resource-type'] ) ) : 'all';
	$sort_by = isset( $_GET['sort'] ) ? sanitize_text_field( wp_unslash( $_GET['sort'] ) ) : 'newest';

	$sort_args = dbr_resources_sort_args( $sort_by );
	$terms = dbr_resources_get_resource_terms();

	ob_start();
	?>
	<div class="dbr-resources" data-archive-url="<?php echo esc_url( $archive_url ); ?>">
		<form class="dbr-resources__filters" method="get" action="<?php echo esc_url( $archive_url ); ?>">
			<label class="dbr-resources__label">
				<span>Search</span>
				<input type="search" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="Search resources">
			</label>
			<label class="dbr-resources__label">
				<span>Content Type</span>
				<select name="resource-type">
					<option value="all"<?php selected( $resource_type, 'all' ); ?>>All</option>
					<?php foreach ( $terms as $term ) : ?>
						<option value="<?php echo esc_attr( $term->slug ); ?>"<?php selected( $resource_type, $term->slug ); ?>>
							<?php echo esc_html( $term->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="dbr-resources__label">
				<span>Sort By</span>
				<select name="sort">
					<option value="newest"<?php selected( $sort_by, 'newest' ); ?>>Newest</option>
					<option value="oldest"<?php selected( $sort_by, 'oldest' ); ?>>Oldest</option>
					<option value="title-asc"<?php selected( $sort_by, 'title-asc' ); ?>>Title (A–Z)</option>
					<option value="title-desc"<?php selected( $sort_by, 'title-desc' ); ?>>Title (Z–A)</option>
				</select>
			</label>
			<button type="submit" class="dbr-resources__submit">Apply</button>
		</form>

		<?php if ( 'all' !== $resource_type ) : ?>
			<?php echo dbr_resources_render_single_term( $resource_type, $search_query, $sort_args ); ?>
		<?php else : ?>
			<?php echo dbr_resources_render_grouped( $terms, (int) $atts['per_group'], $search_query, $sort_args, $archive_url ); ?>
		<?php endif; ?>
	</div>
	<?php

	return ob_get_clean();
}

function dbr_resources_get_resource_terms() {
	$term_slugs = array( 'free-downloads', 'book-purchase', 'video' );
	$terms = array();
	foreach ( $term_slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'resource-type' );
		if ( $term && ! is_wp_error( $term ) ) {
			$terms[] = $term;
		}
	}
	return $terms;
}

function dbr_resources_sort_args( $sort_by ) {
	switch ( $sort_by ) {
		case 'oldest':
			return array( 'orderby' => 'date', 'order' => 'ASC' );
		case 'title-asc':
			return array( 'orderby' => 'title', 'order' => 'ASC' );
		case 'title-desc':
			return array( 'orderby' => 'title', 'order' => 'DESC' );
		case 'newest':
		default:
			return array( 'orderby' => 'date', 'order' => 'DESC' );
	}
}

function dbr_resources_query_args( $search_query, $sort_args, $term_slug, $posts_per_page ) {
	$args = array(
		'post_type' => 'resource',
		'posts_per_page' => $posts_per_page,
		's' => $search_query,
		'orderby' => $sort_args['orderby'],
		'order' => $sort_args['order'],
	);

	if ( $term_slug ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'resource-type',
				'field' => 'slug',
				'terms' => $term_slug,
			),
		);
	}

	return $args;
}

function dbr_resources_render_grouped( $terms, $per_group, $search_query, $sort_args, $archive_url ) {
	if ( empty( $terms ) ) {
		return '<p>No resource types found.</p>';
	}

	ob_start();
	foreach ( $terms as $term ) {
		$query = new WP_Query( dbr_resources_query_args( $search_query, $sort_args, $term->slug, $per_group ) );
		?>
		<section class="dbr-resources__group">
			<header class="dbr-resources__group-header">
				<h2><?php echo esc_html( $term->name ); ?></h2>
			</header>
			<div class="dbr-resources__grid">
				<?php
				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						$query->the_post();
						echo dbr_resources_render_card( get_the_ID(), $term->slug );
					}
					wp_reset_postdata();
				} else {
					echo '<p class="dbr-resources__empty">No resources found.</p>';
				}
				?>
			</div>
			<div class="dbr-resources__view-more">
				<a class="dbr-resources__button" href="<?php echo esc_url( add_query_arg( 'resource-type', $term->slug, $archive_url ) ); ?>">
					View more
				</a>
			</div>
		</section>
		<?php
	}
	return ob_get_clean();
}

function dbr_resources_render_single_term( $term_slug, $search_query, $sort_args ) {
	$term = get_term_by( 'slug', $term_slug, 'resource-type' );
	if ( ! $term || is_wp_error( $term ) ) {
		return '<p>Invalid resource type.</p>';
	}

	$query = new WP_Query( dbr_resources_query_args( $search_query, $sort_args, $term_slug, -1 ) );

	ob_start();
	?>
	<section class="dbr-resources__group">
		<header class="dbr-resources__group-header">
			<h2><?php echo esc_html( $term->name ); ?></h2>
		</header>
		<div class="dbr-resources__grid">
			<?php
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					echo dbr_resources_render_card( get_the_ID(), $term_slug );
				}
				wp_reset_postdata();
			} else {
				echo '<p class="dbr-resources__empty">No resources found.</p>';
			}
			?>
		</div>
	</section>
	<?php

	return ob_get_clean();
}

function dbr_resources_render_card( $post_id, $term_slug ) {
	$permalink = get_permalink( $post_id );
	$redirection_url = function_exists( 'get_field' ) ? get_field( 'redirection_url', $post_id ) : '';
	$link = $redirection_url ? $redirection_url : $permalink;

	$thumb_url = get_the_post_thumbnail_url( $post_id, 'large' );
	$title = get_the_title( $post_id );
	$excerpt = get_the_excerpt( $post_id );

	ob_start();
	switch ( $term_slug ) {
		case 'free-downloads':
			?>
			<a class="dbr-card dbr-card--free" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener">
				<?php if ( $thumb_url ) : ?>
					<img class="dbr-card__image" src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>">
				<?php endif; ?>
				<h3 class="dbr-card__title"><?php echo esc_html( $title ); ?></h3>
				<p class="dbr-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
			</a>
			<?php
			break;
		case 'book-purchase':
			?>
			<a class="dbr-card dbr-card--book" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener">
				<div class="dbr-card__media" style="<?php echo $thumb_url ? 'background-image:url(' . esc_url( $thumb_url ) . ');' : ''; ?>">
					<?php if ( $thumb_url ) : ?>
						<img class="dbr-card__media-img" src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>">
					<?php endif; ?>
				</div>
				<h3 class="dbr-card__title"><?php echo esc_html( $title ); ?></h3>
			</a>
			<?php
			break;
		case 'video':
			$video_url = function_exists( 'get_field' ) ? get_field( 'video_url', $post_id ) : '';
			?>
			<div class="dbr-card dbr-card--video" data-video-url="<?php echo esc_url( $video_url ); ?>">
				<?php if ( $thumb_url ) : ?>
					<img class="dbr-card__image" src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>">
				<?php endif; ?>
				<button class="dbr-card__play" type="button" aria-label="Play video">
					<span></span>
				</button>
				<h3 class="dbr-card__title"><?php echo esc_html( $title ); ?></h3>
			</div>
			<?php
			break;
		default:
			?>
			<a class="dbr-card" href="<?php echo esc_url( $link ); ?>">
				<h3 class="dbr-card__title"><?php echo esc_html( $title ); ?></h3>
			</a>
			<?php
	}

	return ob_get_clean();
}

