$(function () {
    new ClipboardJS('.clipboard');
    $('.fa-share-alt').addClass('faa-tada-wait animated');
    function enable_hours() {
        var checked = $('#exp_time').prop('checked');
        if ((checked)&&($('#hours').val()=='-1'))
            $('#custom_expiry_time').removeClass('d-none').show();
        else
            $('#custom_expiry_time').addClass('d-none').hide();
    }
    $('#hours').on('change', function() {
        enable_hours();
    });
    $('#exp_time').on('change', function() {
        var checked = $(this).prop('checked');
        $('#hours').prop('disabled', ! checked);
        enable_hours();
    });
    $('#exp_hits').on('change', function() {
        var checked = $(this).prop('checked');
        $('#hitcount').prop('disabled', ! checked);
    });
    $(".custom-file-input").on("change", function() {
        var fileName = $(this).val().split("\\").pop();
        $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });
    $('#setpasswd').on('change', function() {
        var checked = $(this).prop('checked');
        $('#password').prop('disabled', ! checked);
    });
    enable_hours();
});
$(function() {
    $('input[type="password"]').each(function() {
        let pw = $(this);
        let pwm_id = pw.attr('validate-match');
        if (pwm_id !== undefined) {
            let pwm = $('#' + pwm_id);
            if (pwm.length == 1) {
                pwm = $(pwm[0]);
                pwm.on('keyup', function() {
                    if (pw.val() !== pwm.val()) {
                        pwm.removeClass('is-valid').addClass('is-invalid');
                        return;
                    }
                    pwm.removeClass('is-invalid').addClass('is-valid');
                });

                pw.on('keyup', function() {
                    if (pwm.val() !== "") {
                        if (pw.val() !== pwm.val()) {
                            pwm.removeClass('is-valid').addClass('is-invalid');
                            return;
                        }
                        pwm.removeClass('is-invalid').addClass('is-valid');
                    } else
                        pwm.removeClass('is-valid').removeClass('is-invalid');
                })
            }
        }
    })
});