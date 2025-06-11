<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// Output in osec/public is escaped by the file loader Theme/FileAbstract:render().
?>
<div class="wrap">
    <h1>DB options unserialized </h1>
    <p>The options and descriptions are ´distributed´ into DB among multiple wp_options rows.</p>
    <button onclick="document.body.querySelectorAll('details').forEach((e) => {(e.hasAttribute('open')) ?e.removeAttribute('open') : e.setAttribute('open',true);
  })">Toggle all
    </button>
    <?php foreach ($all_options as $group) {
        echo '<h3>' . $group['title'] . '</h3>';
        foreach ($group['options'] as $optionKey => $option) {
            echo '<details style="background: lightgray; padding: .1em .5em">';
            echo '<summary>' . $optionKey . '</summary>';
            echo '<pre style="width: inherit; overflow: scroll; background: #fcfcfc; padding: .1em .3em; min-height: 2.5em">';
            echo esc_html(print_r($option, true), ENT_HTML5);
            echo '</pre>';
            echo '</details>';
        }
    } ?>
    <br class="clear"/>
</div>
<?php // phpcs:enable ?>
