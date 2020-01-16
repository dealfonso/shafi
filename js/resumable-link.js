$(function() {
    function toHuman(size) {
        let suffix = [ 'bytes', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb' ];
        let isuffix = 0;
        while ((size/1024).toFixed(0) > 0) {
            size = size/1024;
            isuffix++;
        }
        return "" + size.toFixed(2) + suffix[isuffix];
    }
    
    function ProgressBar(target) {
        this.target = $(target);
        this.fileAdded = function(text) {
            (this.target).removeClass('d-none').css('width','100%').find('.progress-bar').addClass('inactive-progress-bar').text(text);
        };
        this.uploading = function(progress) {
            progress = progress.toFixed(0);
            (this.target).css('width',"" + progress + '%').find('.progress-bar').removeClass('inactive-progress-bar').text("" + progress + "%");
        };
        this.finish = function() {
            (this.target).addClass('d-none').find('.progress-bar').css('width','100%');
        };
    }
    
    var resumable_browse_button = document.getElementById('resumable-select-file');
    if (resumable_browse_button !== null) {
        var resumableUpload = new Resumable({
            target: '/upload',
            fileParameterName: 'uploadchunkedfile',
            chunkRetryInterval: 1000,
            testChunks: false,
            maxFiles: 1,
            chunkSize: 1*1024*1024
        });
    
        if (!resumableUpload.support) 
            location.href = '/up';
    
        resumableUpload.assignBrowse(resumable_browse_button);
    
        var progressBar = new ProgressBar($('#upload-progress'));
    
        $('#resumable-send').click(function(){
            resumableUpload.upload();
        });
    
        resumableUpload.on('fileAdded', function(file) {
            $('#filename').val(file.fileName);
            progressBar.fileAdded(toHuman(file.size));
        });
    
        resumableUpload.on('fileSuccess', function(file) {
            progressBar.finish();
            $('#resumableIdentifier').val(file.uniqueIdentifier);
            $('#resumableTotalChunks').val(file.chunks.length);
            $('#resumableTotalSize').val(file.size);
            $('#resumableFilename').val(file.fileName);
            $('#fileuploaded').submit();
        });
    
        resumableUpload.on('progress', function() {
            progressBar.uploading(resumableUpload.progress()*100);
        });

        resumableUpload.on('error', function() {
            error_modal('Ha ocurrido un error mientras se subia el fichero. Por favor reintente más tarde.');
        });
    }
})
