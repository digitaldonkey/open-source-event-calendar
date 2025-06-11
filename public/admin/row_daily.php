<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// Output in osec/public is escaped by the file loader Theme/FileAbstract:render().
?>
<div class="ai1ec-form-group">
    <label for="osec_daily_count" class="ai1ec-control-label ai1ec-col-sm-3">
        <?php esc_html_e('Every', 'open-source-event-calendar'); ?>:
    </label>
    <div class="ai1ec-col-sm-9">
        <?php echo $count; ?>
    </div>
</div>
<?php // phpcs:enable ?>
