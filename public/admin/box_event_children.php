<?php

use Osec\App\View\Event\EventTimeView;

if ( empty( $parent ) && empty( $children ) ) {
	return '';
}


// TODO seems a bit hacky here.
global $osec_app;

?>
<div class="ai1ec-panel-heading">
	<a data-toggle="ai1ec-collapse"
		data-parent="#osec-add-new-event-accordion"
		href="#osec-event-children-box">
		<i class="ai1ec-fa ai1ec-fa-retweet"></i> <?php
		if ( $parent ) {
			_e( 'Base recurrence event', OSEC_TXT_DOM );
		} else {
			_e( 'Modified recurrence events', OSEC_TXT_DOM );
		}
	?>
	</a>
</div>
<div id="osec-event-children-box" class="ai1ec-panel-collapse ai1ec-collapse">
	<div class="ai1ec-panel-body">
	<?php if ( $parent ) : ?>
	<?php _e( 'Edit parent:', OSEC_TXT_DOM ); ?>
	<a href="<?php echo get_edit_post_link( $parent->get( 'post_id' ) ); ?>"><?php
	echo apply_filters( 'the_title', $parent->get( 'post' )->post_title, $parent->get( 'post_id' ) );
	?></a>
	<?php else : /* children */ ?>
	<h4><?php _e( 'Modified Events', OSEC_TXT_DOM ); ?></h4>
	<ul>
		<?php foreach ( $children as $child ) : ?>
		<li>
			<?php _e( 'Edit:', OSEC_TXT_DOM ); ?>
			<a href="<?php echo get_edit_post_link( $child->get( 'post_id' ) ); ?>"><?php
			echo $child->get( 'post' )->post_title;
			?></a>, <?php echo EventTimeView::factory($osec_app)->get_timespan_html( $child, 'long' ); ?>
		</li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
	</div>
</div>
