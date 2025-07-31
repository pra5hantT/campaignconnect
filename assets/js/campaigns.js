/* Campaigns JS */
$(document).ready(function () {
    // Multi-step wizard for create campaign
    const steps = $('.campaign-step');
    let currentStep = 0;
    function showStep(index) {
        steps.hide();
        $(steps[index]).show();
        $('#stepIndicator').text('Step ' + (index + 1) + ' of ' + steps.length);
        $('.prev-step').toggle(index > 0);
        $('.next-step').toggle(index < steps.length - 1);
        $('.submit-campaign').toggle(index === steps.length - 1);
    }
    if (steps.length) {
        showStep(currentStep);
        $('.next-step').on('click', function (e) {
            e.preventDefault();
            if (currentStep < steps.length - 1) {
                currentStep++;
                showStep(currentStep);
            }
        });
        $('.prev-step').on('click', function (e) {
            e.preventDefault();
            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        });
    }
});