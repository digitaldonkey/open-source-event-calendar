<div class="wrap">

    <h2><?php echo $page->title; ?></h2>

    <div id="poststuff">

        <form method="post" action="">
            <?php wp_nonce_field('update', 'simple_page_nonce'); ?>

            <div class="metabox-holder">
                <div class="post-box-container left-side timely">
                    <?php $template_adapter->display_meta_box($page->meta_box_id, 'left-side', null); ?>
                </div>
            </div>
        </form>

    </div>

</div>
