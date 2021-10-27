jQuery(function ($) {
    $('#UseyourDrive .fileupload-box').on('useyourdrive-add-upload', function (e, file, data, self) {
        file.description = window.descriptionTemplate;
        self.queue[file.hash] = file;
    });
});