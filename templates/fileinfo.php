<?php
    $c_file = $handler->file;
    custom_layout();
    hide_toolbar();
    refresh_background();
?>
<div class="container h-100 w-100 d-flex">
    <div class="card my-auto ms-1 me-auto w-50 rounded rounded-lg shadow-lg">
        <div class="card-body text-center">
            <?php show_title(); ?>
            <h5 class="card-title mt-5"><?php _e('you are about to download a file') ?></h5>
            <h6 class="card-subtitle mb-2 text-muted"><?php _e('Name') ?></h6>
            <p class="card-text"><?php echo $c_file->get_field('name'); ?></p>
            <h6 class="card-subtitle mb-2 text-muted"><?php _e('Size') ?></h6>
            <p class="card-text"><?php echo human_filesize($c_file->get_field('size')); ?></p>
            <form action="<?php echo add_query_var(['d' => '' ]); ?>" method="POST">
                <div class="w-100 d-flex">
                    <input type="hidden" name="passwd" value="<?php echo($_POST["passwd"]??""); ?>">
                    <button type="submit" class="mx-auto btn btn-primary" id="download" name="download"><?php _e('Download') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>