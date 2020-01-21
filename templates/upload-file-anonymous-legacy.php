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
    $oid = $handler->token_created->get_field('oid');
    $link = rtrim(__SERVER_NAME, '/') . get_root_url() . $oid;

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