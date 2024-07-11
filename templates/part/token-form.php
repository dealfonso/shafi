<div class="input-group">
    <div class="input-group-text">
        <input class="form-check-input" type="checkbox" name="exp_time" id="exp_time">
        <span><?php _e('Expire in') ?></span>
    </div>
    <select class="form-select" id="hours" name="hours" disabled>
        <option value="1">1 <?php _e('hour') ?></option>
        <option value="2">2 <?php _e('hours') ?></option>
        <option value="3">3 <?php _e('hours') ?></option>
        <option value="6">6 <?php _e('hours') ?></option>
        <option value="12">12 <?php _e('hours') ?></option>
        <option value="24">1 <?php _e('day') ?> (24 <?php _e('hours') ?>)</option>
        <option value="48">2 <?php _e('days') ?></option>
        <option value="72">3 <?php _e('days') ?></option>
        <option value="168">1 <?php _e('week') ?></option>
        <option value="360">15 <?php _e('days') ?></option>
        <option value="720">1 <?php _e('month') ?> (30 <?php _e('days') ?>)</option>
        <option value="2160">3 <?php _e('months') ?> (90 <?php _e('days') ?>)</option>
        <option value="4320">6 <?php _e('months') ?></option>
        <option value="8640">1 <?php _e('year') ?></option>
        <option value="-1">-- <?php _e('custom period') ?></option>
    </select> 
</div>
<div class="input-group d-none" id="custom_expiry_time">
    <input type="number" min=1 class="form-control" name="seconds" id="seconds" value="60">
    <div class="input-group-text">
    <div class="btn-group btn-group-toggle" data-toggle="buttons">
        <input type="radio" class="btn-check" name="units" id="option1" autocomplete="off" value="s" checked>
        <label class="btn btn-outline-secondary" for="option1"><?php _e("sec."); ?></label>
        <input type="radio" class="btn-check" name="units" id="option2" autocomplete="off" value="m">
        <label class="btn btn-outline-secondary" for="option2"><?php _e("min."); ?></label>
        <input type="radio" class="btn-check" name="units" id="option3" autocomplete="off" value="h">
        <label class="btn btn-outline-secondary" for="option3"><?php _e("hours"); ?></label>
        <input type="radio" class="btn-check" name="units" id="option4" autocomplete="off" value="d">
        <label class="btn btn-outline-secondary" for="option4"><?php _e("days"); ?></label>
    </div>  
    </div>
</div>
<div class="input-group">
    <div class="input-group-text">
        <input class="form-check-input" type="checkbox" name="exp_hits" id="exp_hits">
        <span><?php _e('Expire after') ?></span>
    </div>
    <input type="number" min=1 id="hitcount" name="hitcount" class="form-control" value="10" disabled>
    <span class="input-group-text"><?php _e('hits') ?></span>
</div>  
<div class="input-group">
    <div class="input-group-text">
        <input class="form-check-input" type="checkbox" name="setpasswd" id="setpasswd">
        <span><?php _e('Set password') ?></span>
    </div>
    <input type="password" class="form-control"  maxlength="255" id="password" name="password" disabled>
</div>