<?php 
    global $storage_backend;
    global $current_user;

    $c_file = $handler->file;

    global $current_user;
    $c_tokens = $handler->file->get_tokens([], $current_user->is_admin());

    // Needs to re-check the state of the file and tokens, because it may have changed during the operation
    foreach ($c_tokens as $token)
        $token->expiration_check(true);
    $c_file->update_state(true);
?>
<?php 
      include('templates/part/modal-confirm.php');
?>
<script>
function showmodal_cancelall() {
    show_modal(
        '<?php _e('Please confirm token cancellation') ?>',
        '<?php _e('Please confirm that you want to cancel all tokens'); ?>',
        'cancelall', null
    );
}
function showmodal_delete(v, t) {
    show_modal(
        '<?php _e('Please confirm token cancellation') ?>',
        '<?php _e('Please confirm that you want to delete token'); ?>' + " " + t,
        'cancel', null,
        'token', v
    );
}
function showmodal_create(v) {
    $('#modal_create h5.modal-title').text('<?php _e('Create new token') ?>');
    $('#modal_create div.modal-body span.placeholder').text('');
    $('#modal_create #token').val('');
    $('#modal_create button#submitbutton').text('<?php _e('Create') ?>').attr('name', 'create')
    $('#modal_create').modal('show');
}
function showmodal_renew(v, t) {
    $('#modal_create h5.modal-title').text('<?php _e('Renew token') ?>');
    $('#modal_create div.modal-body span.placeholder').html('<?php _e('Renew token') ?> ' + t + "<br><div class='alert alert-warning' role='alert'><?php _e('These values are considered from now (they are not combined with the previous one)') ?></div>");
    $('#modal_create #token').val(v);
    $('#modal_create button#submitbutton').text('<?php _e('Renew') ?>').attr('name', 'renew')
    $('#modal_create').modal('show');
}
</script>

<div class="modal fade" id="modal_create" tabindex="-1" role="dialog" aria-hidden="true">
<form method="POST">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php _e('Create new token') ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <span class="placeholder"></span>
      <?php 
      include('templates/part/token-form.php');
      ?>
      </div>
      <div class="modal-footer">
        <form method="POST">
            <input type="hidden" id="token" name="token">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel') ?></button>
            <button type="submit" class="btn btn-primary" name="create" id="submitbutton"><?php _e('Create') ?></button>
        </form>
      </div>
    </div>
  </div>
  </form>
</div>  

<div class="col-md-6 offset-md-3">
    <div class="container text-center">
        <div class="filedesc">
            <div class="v-center">
                <ul>
                <li><span class="label"><?php _e('Name') ?>: </span><?php echo $c_file->get_field('name'); ?></li>
                <li><span class="label"><?php _e('Size') ?>: </span><?php echo human_filesize($c_file->get_field('size')); ?></li>
                <li><span class="label"><?php _e('Creation date') ?>: </span><?php echo SCPM_datetime_to_string($c_file->get_field('time')); ?></li>
                <li><span class="label"><?php _e('Status') ?>: </span><?php echo __STATE[$c_file->get_field('state')]; ?></li>
                <li><span class="label"><?php _e('Owner') ?>: </span><?php echo $c_file->get_field('owner'); ?></li>
                </ul>
            </div>
            <div class="v-center">
                <a class='download' href="<?php echo add_query_var(['op' => 'download', 'id' => $c_file->get_id() ], get_root_url()); ?>"><i class="fas fa-file-download"></i></a>
            </div>
        </div>
    </div>
</div>
<div class="col-md-12">
    <div class="container text-center">
        <h3><?php _e('Tokens for the file') ?></h3>
        <?php
        $list = new DDN_List(
            $c_tokens, array(
                "pass" => function($o) {
                    if ($o->get_field('password') !== null) return '<i class="fas fa-lock"></i>';
                    return '';
                },
                "oid" => [
                    'function' => function ($o) { 
                        $oid = $o->get_field('oid');
                        if ($o->is_active())
                        return  '<a class="control" href="' . get_root_url() . $oid . '" target=_blank>' . $oid . '<i class="openext fas fa-external-link-alt"></i></a>' . 
                                '<a class="control clipboard" href="#" data-clipboard-text="'. rtrim(__SERVER_NAME, '/') . get_root_url() . $oid .'"><i class="far fa-copy"></i></a>';
                        else
                        return $oid;

                    },
                    'value' => function($o) { 
                        return $o->get_field('oid');
                    },
                    'title' => 'ID'
                ],
                "exp_date" => array (
                    'title' => __('Validity'), 
                    'function' => function($o) { $s=$o->get_field('exp_secs'); $t=clone $o->get_field('time'); return $s===null?'N/A':SCPM_datetime_to_string($t->add(new DateInterval('PT' . $s . 'S'))); }, 
                    'value' => function($o) { 
                        $s=$o->get_field('exp_secs'); $t=clone $o->get_field('time'); return $s===null?0:$t->add(new DateInterval('PT' . $s . 'S'))->getTimestamp();
                    }
                ),
                "max_usos" => array (
                    'title' => __('Max. Hits'), 
                    'function' => function($o) { $u = $o->get_field('exp_hits'); return $u===null?'N/A':$u; }, 
                ),
                "estado" => array (
                    'title' => __('Status'), 
                    'function' => function($o) { return __STATE[$o->get_field('state')]; }, 
                ),
                "hits" => __('Hits'),
                "_buttons" => function($o) use ($c_file) {
                    // If it is not active, we won't put any action button
                    if (! $c_file->is_active()) return "";
                    if ($o->is_deleted()) return;

                    $result = "";
                    $oid = $o->get_field('oid');
                    $id = $o->get_id();
                    if ($o->is_active()) {
                        $result .= '<a class="control" href="javascript:showmodal_renew(\''. $id .'\', \'' . $oid . '\')";><i class="far fa-edit"></i></a>';
                        $result .= '<a class="control" href="javascript:showmodal_delete(\''. $id .'\', \'' . $oid . '\')";><i class="fas fa-times"></i></a>';
                    }
                    else
                        $result .= '<a class="control" href="javascript:showmodal_renew(\''. $id .'\', \'' . $oid . '\')";><i class="fas fa-redo"></i></a>';

                    // TODO: support stats
                    // $result .= '<a class="control" href="' . add_query_var(['op' => 'stats', 'tid' => $id ]) . '"><i class="far fa-chart-bar"></i></a>';
                    return $result;
                }                            
            )
        );
        $list->sort('oid', 'asc');
        echo $list->render('sortable');
        ?>

        <?php
        if ($c_file->is_active()) {
        ?>
        <p>
        <a href="javascript:showmodal_create()" class="btn btn-info btn-lg" role="button">
            <i class="fas fa-plus"></i> <?php _e('Create token') ?>
        </a>
        <a href="javascript:showmodal_cancelall()" class="btn btn-warning btn-lg" role="button">
            <i class="fas fa-trash"></i> <?php _e('Cancel all tokens') ?>
        </a>
        <a href="javascript:showmodal_cancelfile('<?php echo htmlspecialchars($c_file->get_field('name')); ?>', '<?php echo $c_file->get_id(); ?>', '<?php echo add_query_var(['op' => 'del', 'id' => $c_file->get_id() ], __ADMIN_URL); ?>')" class="btn btn-danger btn-lg" role="button">
            <i class="fas fa-trash"></i> <?php _e('Cancel file') ?>
        </a>
        </p>
        <?php
        } else {
            if (! $c_file->is_deleted()) {
        ?>
            <form method="POST">
                <button type="submit" class="btn btn-info btn-lg" name="reactivate" id="submitbutton">
                    <i class="fas fa-play"></i> <?php _e('Activate file') ?></button>
            </form>
        <?php
            }
        }
        ?>
    </div>
</div>
       