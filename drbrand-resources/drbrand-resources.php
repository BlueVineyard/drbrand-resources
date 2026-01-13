<?php

/**
 * Plugin Name: DrBrand Resources
 * Description: Resources filter and listing for the Resource post type.
 * Version: 1.4.8
 * Author: Rohan T George
 */

if (! defined('ABSPATH')) {
	exit;
}

define('DBR_RESOURCES_VERSION', '1.4.8');
define('DBR_RESOURCES_DIR', plugin_dir_path(__FILE__));
define('DBR_RESOURCES_URL', plugin_dir_url(__FILE__));

// Load Settings Page
require_once DBR_RESOURCES_DIR . 'drbrand-resources-settings.php';

add_action('wp_enqueue_scripts', 'dbr_resources_register_assets');
function dbr_resources_register_assets()
{
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

add_shortcode('drbrand_resources', 'dbr_resources_shortcode');
function dbr_resources_shortcode($atts)
{
	// Get settings from database
	$settings = get_option('dbr_settings', array());
	$default_per_group = isset($settings['default_per_group']) ? (int) $settings['default_per_group'] : 3;
	$default_per_page = isset($settings['default_per_page']) ? (int) $settings['default_per_page'] : 9;

	$atts = shortcode_atts(
		array(
			'per_group' => $default_per_group,
			'per_page' => $default_per_page,
			'free_downloads_heading' => '',
			'book_purchase_heading' => '',
			'video_heading' => '',
		),
		$atts,
		'drbrand_resources'
	);

	wp_enqueue_style('dbr-resources');
	wp_enqueue_script('dbr-resources');

	$gsap_handle = 'gsap';
	if (! wp_script_is($gsap_handle, 'registered')) {
		wp_register_script(
			$gsap_handle,
			'https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js',
			array(),
			'3.12.5',
			true
		);
	}
	wp_enqueue_script($gsap_handle);

	$archive_url = get_post_type_archive_link('resource');
	if (! $archive_url) {
		$archive_url = home_url('/resources/');
	}

	$search_query = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
	$resource_type = isset($_GET['resource-type']) ? sanitize_text_field(wp_unslash($_GET['resource-type'])) : 'all';
	$sort_by = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : 'newest';

	$sort_args = dbr_resources_sort_args($sort_by);
	$terms = dbr_resources_get_resource_terms();
	$group_headings = dbr_resources_group_headings($atts);

	ob_start();
?>
<div class="dbr-resources" data-archive-url="<?php echo esc_url($archive_url); ?>">
    <form class="dbr-resources__filters" method="get" action="<?php echo esc_url($archive_url); ?>">
        <label class="dbr-resources__label">
            <span>Search</span>
            <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="Search resources">
        </label>
        <div class="dbr-resources__label">
            <span>Content Type</span>
            <div class="dbr-custom-select" data-name="resource-type">
                <button type="button" class="dbr-custom-select__trigger">
                    <span class="dbr-custom-select__value">
                        <?php
							if ('all' === $resource_type) {
								echo 'All';
							} else {
								$selected_term = get_term_by('slug', $resource_type, 'resource-type');
								echo $selected_term ? esc_html($selected_term->name) : 'All';
							}
							?>
                    </span>
                    <svg class="dbr-custom-select__arrow" width="12" height="8" viewBox="0 0 12 8" fill="none">
                        <path d="M1 1L6 6L11 1" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>
                <ul class="dbr-custom-select__dropdown">
                    <li class="dbr-custom-select__option<?php echo 'all' === $resource_type ? ' is-selected' : ''; ?>"
                        data-value="all">All</li>
                    <?php foreach ($terms as $term) : ?>
                    <li class="dbr-custom-select__option<?php echo $resource_type === $term->slug ? ' is-selected' : ''; ?>"
                        data-value="<?php echo esc_attr($term->slug); ?>">
                        <?php echo esc_html($term->name); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <select name="resource-type" class="dbr-custom-select__hidden">
                    <option value="all" <?php selected($resource_type, 'all'); ?>>All</option>
                    <?php foreach ($terms as $term) : ?>
                    <option value="<?php echo esc_attr($term->slug); ?>"
                        <?php selected($resource_type, $term->slug); ?>>
                        <?php echo esc_html($term->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="dbr-resources__label">
            <span>Sort By</span>
            <div class="dbr-custom-select" data-name="sort">
                <button type="button" class="dbr-custom-select__trigger">
                    <span class="dbr-custom-select__value">
                        <?php
							$sort_labels = array(
								'newest' => 'Newest',
								'oldest' => 'Oldest',
								'title-asc' => 'Title (A–Z)',
								'title-desc' => 'Title (Z–A)',
							);
							echo isset($sort_labels[$sort_by]) ? esc_html($sort_labels[$sort_by]) : 'Newest';
							?>
                    </span>
                    <svg class="dbr-custom-select__arrow" width="12" height="8" viewBox="0 0 12 8" fill="none">
                        <path d="M1 1L6 6L11 1" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>
                <ul class="dbr-custom-select__dropdown">
                    <li class="dbr-custom-select__option<?php echo 'newest' === $sort_by ? ' is-selected' : ''; ?>"
                        data-value="newest">Newest</li>
                    <li class="dbr-custom-select__option<?php echo 'oldest' === $sort_by ? ' is-selected' : ''; ?>"
                        data-value="oldest">Oldest</li>
                    <li class="dbr-custom-select__option<?php echo 'title-asc' === $sort_by ? ' is-selected' : ''; ?>"
                        data-value="title-asc">Title (A–Z)</li>
                    <li class="dbr-custom-select__option<?php echo 'title-desc' === $sort_by ? ' is-selected' : ''; ?>"
                        data-value="title-desc">Title (Z–A)</li>
                </ul>
                <select name="sort" class="dbr-custom-select__hidden">
                    <option value="newest" <?php selected($sort_by, 'newest'); ?>>Newest</option>
                    <option value="oldest" <?php selected($sort_by, 'oldest'); ?>>Oldest</option>
                    <option value="title-asc" <?php selected($sort_by, 'title-asc'); ?>>Title (A–Z)</option>
                    <option value="title-desc" <?php selected($sort_by, 'title-desc'); ?>>Title (Z–A)</option>
                </select>
            </div>
        </div>
        <button type="submit" class="dbr-resources__submit">Apply</button>
    </form>

    <?php if ('all' !== $resource_type) : ?>
    <?php echo dbr_resources_render_single_term($resource_type, $search_query, $sort_args, (int) $atts['per_page'], $archive_url); ?>
    <?php else : ?>
    <?php echo dbr_resources_render_grouped($terms, (int) $atts['per_group'], $search_query, $sort_args, $archive_url, $group_headings); ?>
    <?php endif; ?>
</div>
<?php

	return ob_get_clean();
}

add_shortcode('drbrand_resources_header', 'dbr_resources_header_shortcode');
function dbr_resources_header_shortcode($atts)
{
	$atts = shortcode_atts(
		array(
			'default_h1' => '',
			'default_description' => '',
			'free_downloads_h1' => '',
			'free_downloads_description' => '',
			'book_purchase_h1' => '',
			'book_purchase_description' => '',
			'video_h1' => '',
			'video_description' => '',
		),
		$atts,
		'drbrand_resources_header'
	);

	$resource_type = isset($_GET['resource-type']) ? sanitize_text_field(wp_unslash($_GET['resource-type'])) : 'all';
	$copy = dbr_resources_header_copy($atts);

	$h1 = $copy['default_h1'];
	$description = $copy['default_description'];

	if ('free-downloads' === $resource_type && ($copy['free_downloads_h1'] || $copy['free_downloads_description'])) {
		$h1 = $copy['free_downloads_h1'] ? $copy['free_downloads_h1'] : $h1;
		$description = $copy['free_downloads_description'] ? $copy['free_downloads_description'] : $description;
	} elseif ('book-purchase' === $resource_type && ($copy['book_purchase_h1'] || $copy['book_purchase_description'])) {
		$h1 = $copy['book_purchase_h1'] ? $copy['book_purchase_h1'] : $h1;
		$description = $copy['book_purchase_description'] ? $copy['book_purchase_description'] : $description;
	} elseif ('video' === $resource_type && ($copy['video_h1'] || $copy['video_description'])) {
		$h1 = $copy['video_h1'] ? $copy['video_h1'] : $h1;
		$description = $copy['video_description'] ? $copy['video_description'] : $description;
	}

	ob_start();
?>
<div class="dbr-resources__header">
    <?php if ($h1) : ?>
    <h1 class="dbr-resources__title"><?php echo esc_html($h1); ?></h1>
    <?php endif; ?>
    <?php if ($description) : ?>
    <p class="dbr-resources__description"><?php echo esc_html($description); ?></p>
    <?php endif; ?>
</div>
<?php
	return ob_get_clean();
}

function dbr_resources_header_copy($atts)
{
	return array(
		'default_h1' => $atts['default_h1'],
		'default_description' => $atts['default_description'],
		'free_downloads_h1' => $atts['free_downloads_h1'],
		'free_downloads_description' => $atts['free_downloads_description'],
		'book_purchase_h1' => $atts['book_purchase_h1'],
		'book_purchase_description' => $atts['book_purchase_description'],
		'video_h1' => $atts['video_h1'],
		'video_description' => $atts['video_description'],
	);
}

function dbr_resources_get_resource_terms()
{
	$term_slugs = array('free-downloads', 'book-purchase', 'video');
	$terms = array();
	foreach ($term_slugs as $slug) {
		$term = get_term_by('slug', $slug, 'resource-type');
		if ($term && ! is_wp_error($term)) {
			$terms[] = $term;
		}
	}
	return $terms;
}

function dbr_resources_group_headings($atts)
{
	return array(
		'free-downloads' => $atts['free_downloads_heading'],
		'book-purchase' => $atts['book_purchase_heading'],
		'video' => $atts['video_heading'],
	);
}

function dbr_resources_sort_args($sort_by)
{
	switch ($sort_by) {
		case 'oldest':
			return array('orderby' => 'date', 'order' => 'ASC');
		case 'title-asc':
			return array('orderby' => 'title', 'order' => 'ASC');
		case 'title-desc':
			return array('orderby' => 'title', 'order' => 'DESC');
		case 'newest':
		default:
			return array('orderby' => 'date', 'order' => 'DESC');
	}
}

function dbr_resources_query_args($search_query, $sort_args, $term_slug, $posts_per_page)
{
	$args = array(
		'post_type' => 'resource',
		'posts_per_page' => $posts_per_page,
		's' => $search_query,
		'orderby' => $sort_args['orderby'],
		'order' => $sort_args['order'],
	);

	if ($term_slug) {
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

function dbr_resources_render_grouped($terms, $per_group, $search_query, $sort_args, $archive_url, $group_headings)
{
	if (empty($terms)) {
		return '<p>No resource types found.</p>';
	}

	ob_start();
	foreach ($terms as $term) {
		$query = new WP_Query(dbr_resources_query_args($search_query, $sort_args, $term->slug, $per_group));
		$heading = isset($group_headings[$term->slug]) && $group_headings[$term->slug]
			? $group_headings[$term->slug]
			: $term->name;
	?>
<section class="dbr-resources__group">
    <header class="dbr-resources__group-header">
        <h2><?php echo esc_html($heading); ?></h2>
    </header>
    <div class="dbr-resources__grid">
        <?php
				if ($query->have_posts()) {
					while ($query->have_posts()) {
						$query->the_post();
						echo dbr_resources_render_card(get_the_ID(), $term->slug);
					}
					wp_reset_postdata();
				} else {
					echo '<p class="dbr-resources__empty">No resources found.</p>';
				}
				?>
    </div>
    <div class="dbr-resources__view-more">
        <a class="dbr-resources__button"
            href="<?php echo esc_url(add_query_arg('resource-type', $term->slug, $archive_url)); ?>">
            View more
        </a>
    </div>
</section>
<?php
	}
	return ob_get_clean();
}

function dbr_resources_render_single_term($term_slug, $search_query, $sort_args, $per_page, $archive_url)
{
	$term = get_term_by('slug', $term_slug, 'resource-type');
	if (! $term || is_wp_error($term)) {
		return '<p>Invalid resource type.</p>';
	}

	$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
	$args = dbr_resources_query_args($search_query, $sort_args, $term_slug, $per_page);
	$args['paged'] = $paged;
	$query = new WP_Query($args);

	ob_start();
	?>
<section class="dbr-resources__group">
    <header class="dbr-resources__group-header">
        <h2><?php echo esc_html($term->name); ?></h2>
    </header>
    <div class="dbr-resources__grid dbr-resources__grid--single">
        <?php
			if ($query->have_posts()) {
				$post_count = 0;
				while ($query->have_posts()) {
					$query->the_post();
					echo dbr_resources_render_card(get_the_ID(), $term_slug);
					$post_count++;
					// Add hr after every 3 items (one row), but not after the last item
					if ($post_count % 3 === 0 && $query->current_post + 1 < $query->post_count) {
						echo '<hr class="dbr-resources__separator">';
					}
				}
				wp_reset_postdata();
			} else {
				echo '<p class="dbr-resources__empty">No resources found.</p>';
			}
			?>
    </div>
    <?php if ($query->max_num_pages > 1) : ?>
    <div class="dbr-resources__pagination">
        <?php
				$base = add_query_arg('paged', '%#%', $archive_url);
				$pagination_args = array(
					'base' => $base,
					'format' => '',
					'current' => $paged,
					'total' => (int) $query->max_num_pages,
					'prev_text' => 'Previous',
					'next_text' => 'Next',
					'add_args' => array_filter(
						array(
							'resource-type' => $term_slug,
							's' => $search_query,
							'sort' => isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : '',
						),
						'strlen'
					),
				);
				echo paginate_links($pagination_args);
				?>
    </div>
    <?php endif; ?>
</section>
<?php

	return ob_get_clean();
}

function dbr_resources_render_card($post_id, $term_slug)
{
	$permalink = get_permalink($post_id);
	$thumb_url = get_the_post_thumbnail_url($post_id, 'large');
	$title = get_the_title($post_id);
	$excerpt = get_the_excerpt($post_id);

	ob_start();
	switch ($term_slug) {
		case 'free-downloads':
			// Use pdf_file ACF field for free downloads
			$pdf_file = function_exists('get_field') ? get_field('pdf_file', $post_id) : '';
			$link = $pdf_file ? $pdf_file : $permalink;
	?>
<a class="dbr-card dbr-card--free" href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener">
    <?php if ($thumb_url) : ?>
    <img class="dbr-card__image" src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr($title); ?>">
    <?php endif; ?>
    <h3 class="dbr-card__title"><?php echo esc_html($title); ?></h3>
    <p class="dbr-card__excerpt"><?php echo esc_html($excerpt); ?></p>
</a>
<?php
			break;
		case 'book-purchase':
			// Use redirection_url ACF field for book purchases
			$redirection_url = function_exists('get_field') ? get_field('redirection_url', $post_id) : '';
			$link = $redirection_url ? $redirection_url : $permalink;
		?>
<a class="dbr-card dbr-card--book" href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener">
    <div class="dbr-card__media"
        style="<?php echo $thumb_url ? 'background-image:url(' . esc_url($thumb_url) . ');' : ''; ?>">
        <?php if ($thumb_url) : ?>
        <img class="dbr-card__media-img" src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr($title); ?>">
        <?php endif; ?>
    </div>
    <h3 class="dbr-card__title"><?php echo esc_html($title); ?></h3>
</a>
<?php
			break;
		case 'video':
			$video_url = function_exists('get_field') ? get_field('video_url', $post_id) : '';
			// Use placeholder if no featured image
			if (!$thumb_url) {
				$thumb_url = DBR_RESOURCES_URL . 'video_placeholder.png';
			}
		?>
<div class="dbr-card dbr-card--video" data-video-url="<?php echo esc_url($video_url); ?>">
    <div class="dbr-card__video-wrapper">
        <div class="dbr-card__overlay-img">
            <img class="dbr-card__image" src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr($title); ?>">
            <span class="dbr-card__title dbr-card__title--overlay"><?php echo esc_html($title); ?></span>
        </div>
        <button class="dbr-card__play" type="button" aria-label="Play video">
            <span></span>
        </button>
        <h3 class="dbr-card__title"><?php echo esc_html($title); ?></h3>
        <p class="dbr-card__excerpt"><?php echo esc_html($excerpt); ?></p>
    </div>
</div>
<?php
			break;
		default:
			// Default to permalink
			$link = $permalink;
		?>
<a class="dbr-card" href="<?php echo esc_url($link); ?>">
    <h3 class="dbr-card__title"><?php echo esc_html($title); ?></h3>
</a>
<?php
	}

	return ob_get_clean();
}