<div class="wrap">

    <h2><?php esc_html_e('Update Calendar Themes', 'open-source-event-calendar'); ?></h2>

    <?php echo $msg; ?>

    <?php if ($errors) : ?>
        <?php foreach ($errors as $error) : ?>
            <?php echo $error; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <p><a class="button" href="<?php echo OSEC_SETTINGS_BASE_URL; ?>"><?php esc_html_e(
                'Open Source Event Calendar Settings »',
                'open-source-event-calendar'
            ); ?></a></p>
</div>
