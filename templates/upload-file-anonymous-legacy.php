<?php include_once('templates/part/redeem-token.php'); ?>
<?php
if ($handler->token_created === null) {
?>
<div class="col-md-6 offset-md-3 v-center text-center">
    <form id="fileuploaded" action="<?php echo add_query_var(['op' => null ], __LEGACY_UPLOAD_URL); ?>" method="POST" enctype="multipart/form-data">
        <div class="custom-file">
            <input required="true" type="file" class="custom-file-input" id="fichero" name="fichero">
            <label class="custom-file-label" for="customFile"><?php _e('Select file') ?></label>
        </div>                    
        <p></p>
        <p class="small">(*) <?php _e('A new link will be created. It will expire in a few days.') ?></p>
        <?php if (__ANONYMOUS_PASSWORDS) { ?>
            <div class="collapsible closed" opened-text='<?php _e('simple') ?> <i class="fas fa-angle-up"></i>' closed-text='<?php _e('advanced') ?> <i class="fas fa-angle-down"></i>' >
                <div class="content">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <input type="checkbox" name="setpasswd" id="setpasswd">
                                <span><?php _e('Set password') ?></span>
                            </div>
                        </div>
                        <input type="password" class="form-control"  maxlength="255" id="password" name="password" disabled>
                    </div>
                </div>
            </div>
        <?php } ?>
        <div class="container">
            <button type="submit" class="btn btn-primary btn-lg" id="compartir" name="compartir"><?php _e('share') ?></button>
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