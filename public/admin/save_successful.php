<?php
if ( ! defined('ABSPATH') ) {
    exit;
}
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// Output in osec/public is escaped by the file loader Theme/FileAbstract:render().
?>
<div class="updated">
    <p>
        <strong>
            <?php echo $msg; ?>
        </strong>
    </p>
</div>
<?php // phpcs:enable ?>
