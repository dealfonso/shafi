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
    <div class="collapsible closed" opened-text='<?php _e('simple') ?> <i class="fas fa-angle-up"></i>' closed-text='<?php _e('advanced') ?> <i class="fas fa-angle-down"></i>' >
        <div class="content">
            <form id="fileuploaded" action="<?php echo add_query_var(['op' => null ], get_root_url()); ?>" method="POST">
                <h3><?php _e('Create token') ?></h3>
                <p class="small">(*) <?php _e('A default token will be created if these values are not defined') ?></p>
                <?php
                include_once('templates/part/token-form.php');
                ?>
                <input type="hidden" id="compartir" name="compartir">
                <input type="hidden" id="resumableIdentifier" name="resumableIdentifier">
                <input type="hidden" id="resumableTotalChunks" name="resumableTotalChunks">
                <input type="hidden" id="resumableTotalSize" name="resumableTotalSize">
                <input type="hidden" id="resumableFilename" name="resumableFilename">
            </form>
        </div>
    </div>
    <div class="container">
        <button class="btn btn-primary btn-lg" id="resumable-send"><?php _e('Share') ?></button>
    </div>
    <br>
    <p class="small">
        <a href="javascript:showmodal_redeem();"><?php _e('i have a download token'); ?></a>
    </p>
</div>
<?php
} else {
    include_once('templates/part/upload-footer.php');
}
?>