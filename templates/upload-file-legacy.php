<?php include_once('templates/part/error-modal.php'); ?>
<?php
if ($handler->token_created === null) {
?>
<div class="col-md-6 offset-md-3 v-center text-center">
    <form action="<?php echo add_query_var(['op' => null ], '/up'); ?>" method="POST" enctype="multipart/form-data">
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
    </form>             
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
    <div class="row">
        <div class="col-md-3 offset-md-3 v-center text-right">
            <a class="clipboard" href="#" data-clipboard-text="<?php echo $link; ?>"><i class="bigicon far fa-copy"></i><br><?php _e('copy link') ?></a>
        </div>
        <div class="col-md-3 v-center text-left">
            <a href="<?php echo add_query_var(['op' => 'edit', 'id' => $handler->token_created->get_field('fileid')], '/') ?>"><i class="bigicon far fa-edit"></i><br><?php _e('edit file') ?></a>
        </div>
    </div>
</div>
<?php
}
?>