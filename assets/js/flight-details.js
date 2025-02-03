var $ = jQuery

jQuery(document).ready(function ($) {

    const countdownElement = $("#countdown-timer");
    const ticketTimeLimit = new Date(countdownElement.data("ticket-limit"))
    const now = new Date()
    const timeOffset = ticketTimeLimit - now

    startCountdown(countdownElement, timeOffset)

    $(document).on('click', '#cancel-flight', function (e) {
        e.preventDefault();
        let flightOrderId = $('#flight-order-id').val()
        let orderId = $('#order-id').val()
        let guestToken = $('#guest-token').val()

        // Make the AJAX request
        $.ajax({
            url: flights_details.ajax_url,
            type: 'POST',
            data: {
                action: 'omdr_cancel_ticket',
                flightOrderId,
                orderId,
                guestToken
            },
            beforeSend: function () {
                // Show the loader before the request
                $('#gfa-hub-loader').show();
                $('.woocommerce-notices-wrapper').empty()
            },
            success: function (response) {
                if (response.success) {
                    renderWooNotice([response.message], 'message', '.flight-actions')
                } else {
                    renderWooNotice([response.message], 'error', '.flight-actions')
                }
            },
            error: function (xhr, status, error) {
                // Handle any AJAX errors
                renderWooNotice(["A server error occurred. Please try again later."], 'error', '.flight-actions');
            },
            complete: function () {
                $('#gfa-hub-loader').hide();
            }
        });
    });

    $(document).on('click', '#confirm-flight', function (e) {
        e.preventDefault();
        let flightOrderId = $('#flight-order-id').val()
        let orderId = $('#order-id').val()
        let guestToken = $('#guest-token').val()
        console.log({ flightOrderId, orderId, guestToken })

        // Make the AJAX request
        $.ajax({
            url: flights_details.ajax_url,
            type: 'POST',
            data: {
                action: 'omdr_issue_ticket',
                flightOrderId,
                orderId,
                guestToken
            },
            beforeSend: function () {
                // Show the loader before the request
                $('#gfa-hub-loader').show();
                $('.woocommerce-notices-wrapper').empty()
            },
            success: function (response) {
                if (response.success) {
                    renderWooNotice([response.message], 'message', '.flight-actions')
                } else {
                    renderWooNotice([response.message], 'error', '.flight-actions')
                }
            },
            error: function (xhr, status, error) {
                // Handle any AJAX errors
                renderWooNotice(["A server error occurred. Please try again later."], 'error', '.flight-actions');
            },
            complete: function () {
                $('#gfa-hub-loader').hide();
            }
        });
    });

    $(document).on('click', '#flight-fares', function (e) {
        e.preventDefault();
        let $container = $('#flight-cat16');
        if ($container.children().length > 0) {
            // If it has children, toggle visibility
            $container.children().toggle();
        } else {
            let orderId = $(this).data('orderid')

            // Make the AJAX request
            $.ajax({
                url: flights_details.ajax_url,
                type: 'POST',
                data: {
                    action: 'omdr_get_fares',
                    orderId,
                    is_order_created: true
                },
                beforeSend: function () {
                    // Show the loader before the request
                    $('#gfa-hub-loader').show();
                    $('.woocommerce-notices-wrapper').empty()
                },
                success: function (response) {
                    if (response.success) {

                        $container.empty(); // Clear existing content if needed

                        response.data.forEach((seg) => {
                            seg.fareBasis.forEach((fare) => {
                                fare.detailedRules.forEach((rule) => {
                                    // Create the HTML structure
                                    let $ruleDiv = $('<div>', { class: 'rule-item' });
                                    let $typeParagraph = $('<p>').text(rule.type);
                                    let $freeTextParagraph = $('<p>').text(rule.freeText);

                                    // Append paragraphs to the div
                                    $ruleDiv.append($typeParagraph, $freeTextParagraph);

                                    // Append the div to the container
                                    $container.append($ruleDiv);
                                });
                            });
                        });
                    } else {
                        renderWooNotice([response.message], 'error', '.flight-actions');
                    }
                },
                error: function (xhr, status, error) {
                    // Handle any AJAX errors
                    renderWooNotice(["A server error occurred. Please try again later."], 'error', '.flight-actions');
                },
                complete: function () {
                    $('#gfa-hub-loader').hide();
                }
            });
        }
    });
});

// helper functions
function renderWooNotice(msgsList, type, parentClass = "") {
    const noticeWrapperSelector = `${parentClass} .woocommerce-notices-wrapper`;

    if ($(noticeWrapperSelector).length) {
        // Empty the wrapper before appending the new ul
        $(noticeWrapperSelector).empty();

        const role = type === 'error' ? "role='alert'" : "";
        const ulEl = $(`<ul class="woocommerce-${type}" ${role}></ul>`);

        msgsList.forEach(msg => {
            ulEl.append(`<li>${msg}</li>`);
        });
        $(noticeWrapperSelector).append(ulEl);
    }
}

function startCountdown(element, timeRemaining) {
    function updateCountdown() {
        if (timeRemaining <= 0) {
            element.text("Flight has been cancelled automatically.");
            clearInterval(interval);
            return;
        }

        const days = Math.floor(timeRemaining / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeRemaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeRemaining % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeRemaining % (1000 * 60)) / 1000);

        element.text(`${days}d ${hours}h ${minutes}m ${seconds}s`);

        // Decrease timeRemaining for the next update
        timeRemaining -= 1000;
    }

    // Run the countdown every second
    const interval = setInterval(updateCountdown, 1000);
    updateCountdown();
}
