<?php include_once('templates/part/error-modal.php'); ?>
<?php include_once('templates/part/redeem-token.php'); ?>
<?php
if ($handler->token_created === null) {
?>
<div class="col-md-6 offset-md-3 v-center text-center">
        <div class="input-group">
            <input type="text" id="filename" disabled class="form-control" placeholder="<?php _e('Filename') ?>">
            <div class="input-group-append">
                <button id="resumable-select-file" resumable-url="<?php echo __UPLOAD_URL ?>" resumable-legacy-url="<?php echo __LEGACY_UPLOAD_URL ?>" class="btn btn-outline-secondary" type="button"><?php _e('Select file') ?></button>
            </div>
        </div>
        <p>
            <div id="upload-progress" class="progress d-none">
                <div class="progress-bar inactive-progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"><span></span></div>
            </div>
        </p>
        <p class="small">(*) <?php _e('A new link will be created. It will expire in a few days.') ?></p>    
        <form id="fileuploaded" action="<?php echo add_query_var(['op' => null ], get_root_url()); ?>" method="POST">
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
            <input type="hidden" id="compartir" name="compartir">
            <input type="hidden" id="resumableIdentifier" name="resumableIdentifier">
            <input type="hidden" id="resumableTotalChunks" name="resumableTotalChunks">
            <input type="hidden" id="resumableTotalSize" name="resumableTotalSize">
            <input type="hidden" id="resumableFilename" name="resumableFilename">
        </form>
        <div class="container">
            <button class="btn btn-primary btn-lg" id="resumable-send">compartir</button>
        </div>
        <div class="container small">
            <a href="javascript:showmodal_redeem();"><?php _e('i have a download token'); ?></a>
        </div>
</div>
<?php
} else {
    include_once('templates/part/upload-footer.php');
}
?>