jQuery(document).ready(function ($) {
    var ajaxurl = frontendajax.ajaxurl;
    // Focus on the input field when the page loads
    $('#qr_code_input').focus();

    //-------------------------------------------------------------
    // Handle form submission
    $('#qr_validation_form').submit(function (event) {
        event.preventDefault();
        
        clearValidationResults();

        // Get the QR code from the input field
        var qrCode = $('#qr_code_input').val();

        // AJAX request to validate the QR code on the server
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'search_qr_code',
                qrCode: qrCode,
            },
            success: function (response) {
                // Display the validation results in the table
                displayValidationResult(response);

                // Clear the input field after validation
                $('#qr_code_input').val('');

                // Focus on the input field again
                $('#qr_code_input').focus();

                $('#qr_code_input_validate').val(qrCode);
                
                var result = JSON.parse(response);

                if(!result.isUsed){
                    $('#validate_button').css('display', 'block');
                }

                $('#clear_results').css('display', 'block');
                
                $('#validation_results_table').css('display', 'block');

                $('#qr_validation_form').css('display', 'none');

            },
            error: function (error) {
                console.error('Error validating QR code: ', error);
            },
        });
    });
    //-------------------------------------------------------------
    // Handle form submission
    $('#qr_validate_form').submit(function (event) {
        event.preventDefault();
        
        $('#validate_button').css('display', 'none');
        clearValidationResults();

        // Get the QR code from the input field
        var qrCode = $('#qr_code_input_validate').val();

        // AJAX request to validate the QR code on the server
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'validate_qr_code',
                qrCode: qrCode,
            },
            success: function (response) {
                // Display the validation results in the table
                displayValidationResult(response);
            },
            error: function (error) {
                console.error('Error validating QR code: ', error);
            },
        });
    });
    //-------------------------------------------------------------

    // Function to display validation results in the table
// Function to display validation results in the table
function displayValidationResult(response) {
    console.log("Prueba");
    // Parse the JSON response
    var result = JSON.parse(response);

    // Add a new row to the table
    var newRow = $('<tr>');
    newRow.append($('<td>').text(result.name));
    newRow.append($('<td>').text(result.email));
    newRow.append($('<td>').text(result.product));
    newRow.append($('<td>').text(result.code));

    // Check if the QR code is valid
    if (result.isValid) {
        // If valid, display the additional information
        newRow.append($('<td>').text(result.creationDate));
        newRow.append($('<td>').text(result.expirationDate));

        // Display "USADO" or "NO USADO" based on the isUsed value
        if (result.isUsed) {
            newRow.append($('<td>').text(result.validate_day));
            newRow.append($('<td class="red">').text('REDIMIDO'));
        } else {
            newRow.append($('<td>').text('-'));
            newRow.append($('<td class="green">').text('NO REDIMIDO'));
        }

        // Display "VALIDO" or "VENCIDO" based on the expiration date
        var currentDate = new Date();

        var expirationDateString = result.expirationDate;

        var dateParts = expirationDateString.split("/");

        var expirationDateTime = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);

        if (result.isUsed) {
            newRow.append($('<td class="red">').text('INVALIDO'));
        } else if (expirationDateTime > currentDate) {
            newRow.append($('<td class="green">').text('VALIDO'));
        } else if (expirationDateTime < currentDate) {
            newRow.append($('<td class="red">').text('VENCIDO'));
        }
    } else {
        // If not valid, display an error message
        var errorMessage = result.error || 'No existe en la base de datos';
        newRow.append($('<td colspan="5">').text('Código QR no válido: ' + errorMessage));
    }

    // Append the new row to the table
    $('#validation_results_table tbody').prepend(newRow);
}


    // Handle clear button click
    $('#clear_results').click(function () {
        // Clear the table
        $('#clear_results').css('display', 'none');
        $('#qr_validation_form').css('display', 'block');
        $('#validation_results_table').css('display', 'none');
        $('#qr_code_input_validate').val('');
        $('#validate_button').css('display', 'none');
        clearValidationResults();
    });

    // Function to clear validation results from the table
    function clearValidationResults() {
        $('#validation_results_table tbody').empty();
    }
});
