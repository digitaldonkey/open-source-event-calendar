<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// Output in osec/public is escaped by the file loader Theme/FileAbstract:render().
?>
<li>
    <a href="<?php echo esc_attr("#$id"); ?>" data-toggle="ai1ec-tab">
        <?php echo esc_html($title); ?>
    </a>
</li>
<?php // phpcs:enable ?>
