<?php
if ( ! defined('ABSPATH') ) {
    exit;
}
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// Output in osec/public is escaped by the file loader Theme/FileAbstract:render().
?>
<div class="timely ai1ec-panel-group ai1ec-form-inline"
     id="osec-add-new-event-accordion">
    <?php echo $nonce; ?>
    <?php foreach ($boxes as $i => $box) : ?>
        <div class="ai1ec-panel ai1ec-panel-default
        <?php echo 0 === $i ? 'ai1ec-overflow-visible' : '' ?>">
            <?php echo $box; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php // phpcs:enable ?>
