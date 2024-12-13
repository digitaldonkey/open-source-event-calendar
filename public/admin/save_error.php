<div class="error">
    <?php foreach ($msgs as $msg) : ?>
        <p>
            <strong>
                <?php echo wp_kses(
                    $msg,
                    'post'
                ); ?>
            </strong>
        </p>
    <?php endforeach; ?>
</div>
