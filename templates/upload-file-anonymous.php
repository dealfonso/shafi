<?php include_once('templates/part/error-modal.php'); ?>
<?php
if ($handler->token_created === null) {
?>
<div class="col-md-6 offset-md-3 v-center text-center">
        <div class="input-group">
            <input type="text" id="filename" disabled class="form-control" placeholder="<?php _e('Filename') ?>">
            <div class="input-group-append">
                <button id="resumable-select-file" class="btn btn-outline-secondary" type="button"><?php _e('Select file') ?></button>
            </div>
        </div>
        <p>
            <div id="upload-progress" class="progress d-none">
                <div class="progress-bar inactive-progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"><span></span></div>
            </div>
        </p>
        <p class="small">(*) <?php _e('A new link will be created. It will expire in a few days.') ?></p>    
        <form id="fileuploaded" action="<?php echo add_query_var(['op' => null ], '/'); ?>" method="POST">
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
</div>
<?php
} else {
    $oid = $handler->token_created->get_field('oid');
    $link = __SERVER_NAME . __ROOT_FOLDER . $oid;

    $s=$handler->token_created->get_field('exp_secs'); $t=clone $handler->token_created->get_field('time'); $exp_date = $s===null?null:SCPM_datetime_to_string($t->add(new DateInterval('PT' . $s . 'S')));
    $h=$handler->token_created->get_field('exp_hits');
    $message_a = array();
    array_push($message_a, __('Here you have a link to your file'));
    if ($s !== null) {
        $phrase = _s('It will expire on %s', $exp_date);
        if ($h !== null) $phrase .= _s(' or in %d hits', $h);
        array_push($message_a, $phrase);
    } else
        if ($h !== null) array_push($message_a, _s('It will expire in %d hits', $h));
    array_push($message_a, __('Thank you for using SHAFI.')); 
?>
<div class="v-center text-center">
    <p><?php echo implode(". ", $message_a); ?></p>
    <p><a target=_blank href="<?php echo $link; ?>"><?php echo $link; ?></a></p>
    <p><a class="clipboard" href="#" data-clipboard-text="<?php echo $link; ?>"><i class="bigicon far fa-copy"></i><br><?php _e('copy link') ?></a></p>
</div>
<?php
}
?>