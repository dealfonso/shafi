<!-- needed by resumable-link.js -->
<div class="modal fade" id="modal_error" tabindex="-1" role="dialog" aria-hidden="false">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background: #e85e6c;">
        <h5 class="modal-title"><?php _e('An error has occurred') ?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <i class="bigicon fas fa-bomb"></i>
        <p class="error_text"><?php _e('An error has occurred in the system.'); echo " "; _e('Please contact with the admins'); ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><?php _e('Accept') ?></button>
      </div>
    </div>
  </div>
</div>
<script>
function error_modal(text = null) {
    if (text !== null)
        $('#modal_error p.error_text').html(text);
    $('#modal_error').modal('show');
}
</script>