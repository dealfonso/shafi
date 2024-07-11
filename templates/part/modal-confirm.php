<div class="modal fade" id="modalbox" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      </div>
      <form method="POST" class="w-100">
        <div class="modal-footer justify-content-between">
          <input type="hidden" id="datafield" name="token">
          <button type="submit" class="btn btn-primary" name="submitbutton" id="submitbutton"><?php _e('Confirm') ?></button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function show_modal(title, body, confirmid, confirmtxt = null, idfield = 'datafield', idval = '', url = null) {
    $('#modalbox h5.modal-title').html(title);
    $('#modalbox div.modal-body').html(body);
    let sb = $('#modalbox button#submitbutton').attr('name', confirmid);
    if (confirmtxt !== null) sb.text(confirmtxt);
    $('#modalbox #datafield').val(idval).attr('name', idfield);
    $('#modalbox form').attr('action', '');
    if (url !== null)
      $('#modalbox form').attr('action', url);
    $('#modalbox').modal('show');
}
function showmodal_cancelfile(n, f, dest) {
    show_modal(
        '<?php _e('Please confirm file cancellation') ?>',
        '<?php _e('Please confirm that you want to cancel file '); ?> ' + n + '. <?php _e('This action may imply file deletion.'); ?>',
        'deletefile', null, 'fileid', f, dest
    );
}
</script>
