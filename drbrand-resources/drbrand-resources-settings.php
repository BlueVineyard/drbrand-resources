<?php

/**
 * DrBrand Resources Settings Page
 * 
 * Admin settings page for configuring plugin options
 * 
 * @package DrBrand Resources
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add settings page to WordPress admin
 */
add_action('admin_menu', 'dbr_add_settings_page');

function dbr_add_settings_page()
{
    add_options_page(
        'DrBrand Resources Settings',
        'DrBrand Resources',
        'manage_options',
        'drbrand-resources-settings',
        'dbr_render_settings_page'
    );
}

/**
 * Register settings
 */
add_action('admin_init', 'dbr_register_settings');

function dbr_register_settings()
{
    register_setting(
        'dbr_settings_group',
        'dbr_settings',
        'dbr_sanitize_settings'
    );

    add_settings_section(
        'dbr_general_section',
        'Display Settings',
        'dbr_general_section_callback',
        'drbrand-resources-settings'
    );

    add_settings_field(
        'default_per_group',
        'Default Items Per Group',
        'dbr_per_group_field_callback',
        'drbrand-resources-settings',
        'dbr_general_section'
    );

    add_settings_field(
        'default_per_page',
        'Default Items Per Page',
        'dbr_per_page_field_callback',
        'drbrand-resources-settings',
        'dbr_general_section'
    );
}

/**
 * Section callback
 */
function dbr_general_section_callback()
{
    echo '<p>Configure default display settings for the resources shortcode.</p>';
}

/**
 * Field callbacks
 */
function dbr_per_group_field_callback()
{
    $options = get_option('dbr_settings');
    $per_group = isset($options['default_per_group']) ? $options['default_per_group'] : 3;
?>
<input type="number" name="dbr_settings[default_per_group]" value="<?php echo esc_attr($per_group); ?>" min="1" max="50"
    class="small-text">
<p class="description">Number of items to show per resource group on the main page (default: 3).</p>
<?php
}

function dbr_per_page_field_callback()
{
    $options = get_option('dbr_settings');
    $per_page = isset($options['default_per_page']) ? $options['default_per_page'] : 9;
?>
<input type="number" name="dbr_settings[default_per_page]" value="<?php echo esc_attr($per_page); ?>" min="1" max="100"
    class="small-text">
<p class="description">Number of items to show per page when viewing a single resource type (default: 9).</p>
<?php
}

/**
 * Sanitize settings
 */
function dbr_sanitize_settings($input)
{
    $sanitized = array();

    if (isset($input['default_per_group'])) {
        $sanitized['default_per_group'] = absint($input['default_per_group']);
        if ($sanitized['default_per_group'] < 1) {
            $sanitized['default_per_group'] = 3;
        }
    }

    if (isset($input['default_per_page'])) {
        $sanitized['default_per_page'] = absint($input['default_per_page']);
        if ($sanitized['default_per_page'] < 1) {
            $sanitized['default_per_page'] = 9;
        }
    }

    return $sanitized;
}

/**
 * Render settings page
 */
function dbr_render_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['settings-updated'])) {
        add_settings_error('dbr_messages', 'dbr_message', 'Settings Saved', 'success');
    }

    settings_errors('dbr_messages');
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form action="options.php" method="post">
        <?php
            settings_fields('dbr_settings_group');
            do_settings_sections('drbrand-resources-settings');
            submit_button('Save Settings');
            ?>
    </form>

    <hr style="margin: 40px 0;">

    <div class="dbr-settings-info" style="max-width: 800px;">
        <h2>Current Settings (Debugging)</h2>
        <table class="form-table">
            <tr>
                <th>Saved Settings:</th>
                <td>
                    <?php
                        $current_settings = get_option('dbr_settings', array());
                        echo '<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">';
                        print_r($current_settings);
                        echo '</pre>';
                        ?>
                </td>
            </tr>
        </table>

        <h2>Plugin Information</h2>
        <table class="form-table">
            <tr>
                <th>Version:</th>
                <td><?php echo esc_html(DBR_RESOURCES_VERSION); ?></td>
            </tr>
            <tr>
                <th>Shortcodes:</th>
                <td>
                    <code>[drbrand_resources]</code><br>
                    <code>[drbrand_resources_header]</code>
                </td>
            </tr>
        </table>

        <h3 style="margin-top: 30px;">Quick Links</h3>
        <p>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=resource')); ?>"
                class="button button-primary">
                Add New Resource
            </a>
        </p>
        <p>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=resource')); ?>" class="button">
                View All Resources
            </a>
        </p>
    </div>
</div>
<?php
}

/**
 * Add settings link to plugins page
 */
add_filter('plugin_action_links_drbrand-resources/drbrand-resources.php', 'dbr_add_settings_link');

function dbr_add_settings_link($links)
{
    $settings_link = '<a href="' . admin_url('options-general.php?page=drbrand-resources-settings') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}