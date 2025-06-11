<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// Output in osec/public is escaped by the file loader Theme/FileAbstract:render().
?>
<?php if ($activated) : ?>
    <div id="message2" class="updated">
        <p>
            <?php printf(
                    /* translators: home_url() */
                    __('New theme activated. <a href="%s">Visit site</a>', 'open-source-event-calendar'),
                    home_url('/')
            );?>
        </p>
    </div>
<?php elseif ($deleted) : ?>
    <div id="message3" class="updated">
        <p>
            <?php esc_html_e('Theme deleted.', 'open-source-event-calendar') ?>
        </p>
    </div>
<?php endif; ?>

<div class="wrap">

    <h2><?php echo esc_html($page_title); ?></h2>
    <h3><?php esc_html_e('Current Calendar Theme', 'open-source-event-calendar'); ?></h3>
    <div id="current-theme"<?php echo ($ct->screenshot) ? ' class="has-screenshot"' : '' ?>>
        <?php if ($ct->screenshot) : ?>
            <img src="<?php echo $ct->theme_root_uri . '/' . $ct->stylesheet . '/' . $ct->screenshot; ?>"
                 alt="<?php esc_attr_e('Current theme preview', 'open-source-event-calendar'); ?>"/>
        <?php endif; ?>
        <h4><?php
            /* translators: 1: theme title, 2: theme version, 3: theme author */
            printf(__('%1$s %2$s by %3$s', 'open-source-event-calendar'), $ct->title, $ct->version, $ct->author); ?></h4>
        <p class="theme-description"><?php echo $ct->description; ?></p>
        <div class="theme-options">
            <?php if ($ct->tags) : ?>
                <p><?php esc_html_e('Tags:', 'open-source-event-calendar'); ?><?php echo implode(', ', $ct->tags); ?></p>
            <?php endif; ?>
        </div>
        <?php theme_update_available($ct); ?>

    </div>

    <br class="clear"/>
    <?php
    if (
        ! current_user_can('switch_themes') &&
        ! current_user_can('switch_osec_themes')
    ) {
        echo '</div>';
        return false;
    }
    ?>
    <h3><?php esc_html_e('Available Calendar Themes', 'open-source-event-calendar'); ?></h3>
    <?php $wp_list_table->display(); ?>
    <br class="clear"/>
</div>
<?php // phpcs:enable ?>
