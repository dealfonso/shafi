<div class="modal fade" id="redeem_modal" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"></h5>
        <?php _e('Redeem a token') ?>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="text" id="tokentr" class="form-control" placeholder="<?php _e('Token to redeem') ?>">
      </div>
        <div class="modal-footer text-center">
          <button type="submit" class="btn btn-primary" id="redeem"><?php _e('Redeem') ?></button>
        </div>
    </div>
  </div>
</div>
<script>
function showmodal_redeem() {
    $('#redeem_modal').modal('show');
}
$(function() {
    $('#redeem').on('click', function() {
        location.href = "<?php echo get_root_url(); ?>" + $('#tokentr').val();
    })
})
</script>
