<div class="wrap">

    <h2><?php _e('Update Calendar Themes', OSEC_TXT_DOM); ?></h2>

    <?php echo $msg; ?>

    <?php if ($errors) : ?>
        <?php foreach ($errors as $error) : ?>
            <?php echo $error; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <p><a class="button" href="<?php echo OSEC_SETTINGS_BASE_URL; ?>"><?php _e(
                'Open Source Event Calendar Settings »',
                OSEC_TXT_DOM
            ); ?></a></p>
</div>
