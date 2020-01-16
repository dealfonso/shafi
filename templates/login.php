<div class="col-md-4 offset-md-4 text-center">
    <i class="bigicon fas fa-user-lock"></i>
    <h3><?php _e('Log in') ?></h3>
    <form method=post>
        <p><input type="text" required="true" class="form-control" name='username' id="username" placeholder="<?php _e('Username') ?>"></p>
        <p><input type="password" required="true" class="form-control" name='password' id="password" placeholder="<?php _e('Password') ?>"></p>
        <p><button type="submit" class="btn btn-primary btn-lg" id="login" name="login"><?php _e('Log in') ?></button></p>
    </form>
</div>
