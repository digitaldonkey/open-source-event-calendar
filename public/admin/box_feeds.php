<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// Output in osec/public is escaped by the file loader Theme/FileAbstract:render().
?>
<div class="timely">
    <ul class="ai1ec-nav ai1ec-nav-tabs">
        <?php echo $tab_headers ?>
    </ul>
    <div class="ai1ec-tab-content">
        <?php echo $tab_content ?>
    </div>
</div>
<?php // phpcs:enable ?>
