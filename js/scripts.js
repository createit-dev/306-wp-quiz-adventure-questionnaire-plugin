(function($) {
    $(document).ready(function() {
        var currentStep = 1;
        var totalSteps = $('.qp-step').length;

        // Function to update the active class
        function updateActiveStep(step) {
            // Remove the "active" class from all steps
            $('.cq-step').removeClass('active');

            // Add the "active" class to the specified step
            $('.cq-step-' + step).addClass('active');
        }

        // Update the active class for the initial step
        updateActiveStep(currentStep);

        function goToStep(step) {
            $('.qp-step').hide();
            $('#qp-step-' + step).show();
            currentStep = step;
            updateActiveStep(currentStep);
        }

        function validateStep(step) {
            var allAnswered = true;
            $('#qp-step-' + step + ' .qp-question').each(function() {
                if (!$(this).find('input[type="radio"]:checked').length) {
                    allAnswered = false;
                }
            });
            return allAnswered;
        }

        $('.qp-next-step').on('click', function() {
            if (!validateStep(currentStep)) {
                alert('Please answer all questions before proceeding.');
                return;
            }

            if (currentStep < totalSteps) {
                goToStep(currentStep + 1);
            }
        });

        $('.qp-prev-step').on('click', function() {
            if (currentStep > 1) {
                goToStep(currentStep - 1);
            }
        });



        // Code for form submission and handling of results will go here.
        $('.qp-submit').on('click', function() {
            if (!validateStep(currentStep)) {
                alert('Please answer all questions before submitting.');
                return;
            }

            // Serialize the form data
            var formData = $('#qp-questionnaire input[type="radio"]:checked').serialize();

            // Send the data via AJAX
            $.ajax({
                url: cq_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'submit_questionnaire',
                    form_data: formData,
                },
                beforeSend: function() {
                    $('#qp-questionnaire').css('opacity', '0.5');
                },
                success: function(response) {
                    if (response.success) {
                        // Redirect to the results page with the hash as a query parameter
                        window.location.href =  response.data.url;
                    } else {
                        // Handle the error
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    $('#qp-questionnaire').css('opacity', '1');
                },
            });

        });

        $('#send-results-form').on('submit', function(e) {
            e.preventDefault();

            var emailAddress = $('#email-address').val();
            var resultsHash = $('#results-hash').val();
            var ajaxNonce = cq_ajax_object.nonce;

            $.ajax({
                url: cq_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'cq_send_results_email',
                    nonce: ajaxNonce,
                    email: emailAddress,
                    hash: resultsHash,
                },
                success: function(response) {
                    if (response.success) {
                        // Show a success message
                        $('#email-sent-message').show();
                    } else {
                        // Handle the error
                        alert('There was an error sending the email. Please try again.');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // Handle the error
                    alert('There was an error sending the email. Please try again.');
                },
            });
        });

    });
})(jQuery);
