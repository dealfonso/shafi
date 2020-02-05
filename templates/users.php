<?php 
      include('templates/part/modal-confirm.php');
?>
<script>
function showmodal_delete(u) {
    show_modal(
        '<?php _e('Please confirm user deletion') ?>',
        '<?php _e('Please confirm that you want to delete user '); ?>' + u,
        'deluser', null,
        'username', u
    );
}
function showmodal_create() {
  let mu = $('#modal_user');
  mu.find('#username').val('').prop('readonly', false);
  mu.find('input[type="checkbox"]').prop('checked', false);
  mu.find('#userop').val('create').text("<?php _e('Create'); ?>");
  mu.modal('show');
  return mu;
}
function showmodal_update(username, perm_s) {
  let mu = showmodal_create();
  mu.find('#username').val(username).prop('readonly', true);
  Array.from(perm_s).forEach((p) =>mu.find(`input[type="checkbox"][value="${p}"]`).prop('checked', true))
  mu.find('#userop').val('update').text("<?php _e('Update'); ?>");
}
</script>
<div class="modal fade" id="modal_user" tabindex="-1" role="dialog" aria-hidden="true">
<form method="POST">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php _e('Create user') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method=post oninput='passwordm.setCustomValidity(password.value != passwordm.value ? "<?php _e('Passwords do not match') ?>" : "")'>
      <div class="modal-body">
        <p><input type="text" required="true" class="form-control" name='username' id="username" placeholder="<?php _e('User name') ?>"></p>
        <p><input type="password" validate-match="passwordm" class="form-control" name='password' id="password" placeholder="<?php _e('New password') ?>"></p>
        <p><input type="password" class="form-control" name='passwordm' id="passwordm" placeholder="<?php _e('Confirm password') ?>"></p>
        <p>
        <?php
          foreach (__PERMISSIONS as $p => $txt_p) {
            if (! in_array($p, ['l', 'o'])) {
        ?>
          <div class="form-check">
              <input type="checkbox" class="form-check-input" name="perm[]" value="<?php echo $p ?>">
              <label class="form-check-label" for="perm[]"><?php echo $txt_p ?></label>
          </div>
          <?php
            }
          }
          ?>
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php _e('Cancel') ?></button>
        <button type="submit" class="btn btn-primary" name="userop" id="userop" value="create"><?php _e('Create') ?></button>
      </div>
      </form>
    </div>
  </div>
  </form>
</div>  
            
<div class="col-md-12 text-center">
    <h3><?php _e('Users') ?></h3>
    <?php
    $users = SHAUser::search();
    $list = new DDN_List(
        $users, array(
            "username" => __('Username'),
            "permissions" => array (
                'title' => __('Permisions'), 
                'function' => function($o) { 
                    $perms = array();
                    $p_string = $o->get_field('permissions');
                    foreach (str_split($p_string) as $p) 
                        array_push($perms, __PERMISSIONS[$p] ?? $p);
                    return implode(', ', $perms); 
                }, 
            ),
            "_buttons" => function($o) {
                $username = $o->get_username();
                $perms_s = $o->get_field('permissions');
                
                // shafi user cannot be deleted (because he is the default admin)
                if ($username !== 'shafi') {
                    return 
                    '<a class="control" href="javascript:showmodal_update(\''. $username . '\', \''. $perms_s . '\')";><i class="fas fa-user-edit"></i></a>' . 
                    '<a class="control" href="javascript:showmodal_delete(\''. $username . '\')";><i class="fas fa-times"></i></a>';
                }
                return '';
            }                            
            )
    );
    echo $list->render('sortable');
    ?>
    <br/>
    <p>
        <a href="javascript:showmodal_create()" class="btn btn-secondary btn-lg" role="button">
        <i class="fas fa-plus"></i> <?php _e('Create user') ?>
        </a>
    </p>
</div>
