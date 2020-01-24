<?php include_once('templates/part/error-modal.php'); ?>
<?php include_once('templates/part/redeem-token.php'); ?>
<?php
if ($handler->token_created === null) {
?>
<div class="col-md-6 offset-md-3 v-center text-center">
    <form action="<?php echo add_query_var(['op' => null ], __LEGACY_UPLOAD_URL); ?>" method="POST" enctype="multipart/form-data">
        <div class="input-group">
            <div class="custom-file">
                <input required="true" type="file" class="custom-file-input" id="fichero" name="fichero">
                <label class="custom-file-label" for="customFile"><?php _e('Select file') ?></label>
            </div>                        
        </div>
        <div class="collapsible closed" opened-text='<?php _e('simple') ?> <i class="fas fa-angle-up"></i>' closed-text='<?php _e('advanced') ?> <i class="fas fa-angle-down"></i>' >
            <div class="content">
                <h3><?php _e('Create token') ?></h3>
                <p class="small">(*) <?php _e('A default token will be created if these values are not defined') ?></p>
                <?php
                include_once('templates/part/token-form.php');
                ?>
            </div>
        </div>
        <div class="container">
            <button type="submit" class="btn btn-primary btn-lg" id="compartir" name="compartir"><?php _e('Share') ?></button>
        </div>
        <div class="container small">
            <a href="javascript:showmodal_redeem();"><?php _e('i have a download token'); ?></a>
        </div>
    </form>             
</div>
<?php
} else {
    include_once('templates/part/upload-footer.php');
}
?>