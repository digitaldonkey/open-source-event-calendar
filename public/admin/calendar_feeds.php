<div class="wrap">

    <h2><?php echo $title; ?></h2>

    <div id="poststuff">

        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false); ?>
            <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false); ?>

            <div class="metabox-holder">
                <div class="post-box-container column-1-ai1ec left-side timely">
                    <?php do_meta_boxes($settings_page, 'left', null); ?>
                    <?php
                    // Show the submit button only in the settings page and not in the Feeds page.
                    if ($calendar_settings) {
                        submit_button(esc_attr__('Update Settings', 'open-source-event-calendar'), 'primary', 'osec_save_settings');
                    }
                    ?>
                </div>
                <div class="post-box-container column-2-ai1ec right-side timely"><?php do_meta_boxes(
                        $settings_page,
                        'right',
                        null
                    ); ?></div>
            </div>
        </form>

    </div>

</div>
