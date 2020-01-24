<?php
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
    <div class="row">
        <?php
        global $current_user;
        if ($current_user->is_logged_in()) {
            ?>
        <div class="col-md-3 offset-md-3 v-center text-right">
            <a class="clipboard" href="#" data-clipboard-text="<?php echo $link; ?>"><i class="bigicon far fa-copy"></i><br><?php _e('copy link') ?></a>
        </div>
        <div class="col-md-3 v-center text-left">
            <a href="<?php echo add_query_var(['op' => 'edit', 'id' => $handler->token_created->get_field('fileid')], get_root_url()) ?>"><i class="bigicon far fa-edit"></i><br><?php _e('edit file') ?></a>
        </div>
            <?php
        } else {
            ?>
            <div class="col-md-12 v-center text-center">
                <a class="clipboard" href="#" data-clipboard-text="<?php echo $link; ?>"><i class="bigicon far fa-copy"></i><br><?php _e('copy link') ?></a>
            </div>
            <?php
        }
            ?>
    </div>
    <div class="row text-center padding-top">
        <div class="col-md-12">
            <?php _e('upload other file')?><br>
            <a href="<?php echo get_root_url(); ?>"><i class="bigicon fas fa-cloud-upload-alt"></i></a>
        </div>
    </div>
</div>