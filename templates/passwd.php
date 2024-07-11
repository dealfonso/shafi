<?php
    $c_file = $handler->file;
    custom_layout();
    hide_toolbar();
?>
<div class="container h-100 w-100 d-flex">
    <div class="card my-auto ms-1 me-auto w-50 rounded rounded-lg shadow-lg">
        <div class="card-body text-center">
            <?php show_title(); ?>
            <h5 class="card-title mt-5"><?php _e('this file is password protected') ?></h5>
            <i class="bigicon fas fa-lock my-3"></i>
            <form method="POST">
                <div class="form-group">
                    <input type="password" class="form-control" id="passwd" name="passwd" placeholder="<?php _e('Password') ?>">
                </div>
                <div class="container">
                    <button type="submit" class="btn btn-primary btn-lg" id="download" name="download"><?php _e('Unlock') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>