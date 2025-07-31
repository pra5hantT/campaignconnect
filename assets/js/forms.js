/* Form helper functions */
$(document).ready(function () {
    // Show selected file name for file inputs
    $('input[type="file"]').on('change', function () {
        const fileName = this.files[0] ? this.files[0].name : '';
        $(this).next('.file-label').text(fileName);
    });
});