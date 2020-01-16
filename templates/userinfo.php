<div class="col-md-4 offset-md-4 text-center">
    <h3><?php _e('Change password') ?></h3>
    <form method=post oninput='passwordm.setCustomValidity(password.value != passwordm.value ? "<?php _e('Passwords do not match') ?>" : "")'>
        <p><input type="password" validate-match='passwordm' required="true" class="form-control" name='password' id="password" placeholder="<?php _e('New password') ?>"></p>
        <p><input type="password" required="true" class="form-control" name='passwordm' id="passwordm" placeholder="<?php _e('Confirm password') ?>"></p>
        <p><button type="submit" class="btn btn-primary btn-lg" id="setpass" name="setpass"><?php _e('Change') ?></button></p>
    </form>
</div>
