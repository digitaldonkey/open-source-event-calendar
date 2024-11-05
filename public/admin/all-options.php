<div class="wrap">

	<h1>Ai1EC DB Options Summary page</h1>
  <p>The options and descriptions are ´distributed´ into DB among multiple wp_options rows.</p>
  <button onclick="document.body.querySelectorAll('details').forEach((e) => {(e.hasAttribute('open')) ?e.removeAttribute('open') : e.setAttribute('open',true);
  })">Toggle all</button>
  <?php foreach( $all_options as $group) {
    echo '<h3>' . $group['title'] . '</h3>';
    foreach ($group['options'] as $optionKey => $option) {
      echo '<details style="background: lightgray; padding: .1em .5em">';
      echo   '<summary>' . $optionKey . '</summary>';
      echo '<pre style="width: inherit; overflow: scroll; background: #fcfcfc; padding: .1em .3em; min-height: 2.5em">';
      echo        esc_html(print_r($option, TRUE), ENT_HTML5);
      echo     "</pre>";
      echo '</details>';
    }
  } ?>
	<br class="clear" />
</div>
