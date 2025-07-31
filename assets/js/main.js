/* Main JS for CampaignConnect */
$(document).ready(function () {
    // Initialize DataTables for tables with class 'datatable'
    if ($('.datatable').length) {
        $('.datatable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [],
        });
    }
    // Handle confirmation prompts for delete actions
    $('.confirm-delete').on('click', function (e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
});