<?php
    global $current_user;
    $email = $current_user->get_field('email');
?>
<div class="col-md-4 offset-md-4 text-center">
    <h3><?php _e('Update information') ?></h3>
    <form method=post oninput='passwordm.setCustomValidity(password.value != passwordm.value ? "<?php _e('Passwords do not match') ?>" : "")'>
        <p><input type="email" class="form-control" name="email" id="email" placeholder="<?php _e('e-mail') ?>" value="<?php echo($email); ?>"></p>
        <span class="text-muted small">(*) <?php _e("Leave empty to not to change the password"); ?></span>
        <p><input type="password" validate-match='passwordm' class="form-control" name='password' id="password" placeholder="<?php _e('New password') ?>"></p>
        <p><input type="password" class="form-control" name='passwordm' id="passwordm" placeholder="<?php _e('Confirm password') ?>"></p>
        <p>
            <div class="input-text">
                <input type="checkbox" id="removepassword" name="removepassword" value="true">
                <label for="removepassword"><?php _e('Remove password') ?></label>
            </div>
        </p>
        <p><button type="submit" class="btn btn-primary btn-lg" id="updateprofile" name="updateprofile"><?php _e('Change') ?></button></p>
    </form>
</div>
<script>
    document.getElementById('removepassword').addEventListener('change', function() {
        if (this.checked) {
            // Disable and clear password fields
            document.getElementById('password').disabled = true;
            document.getElementById('password').value = '';
            document.getElementById('passwordm').disabled = true;
            document.getElementById('passwordm').value = '';
        } else {
            document.getElementById('password').disabled = false;
            document.getElementById('passwordm').disabled = false;
        }
    });
</script>
