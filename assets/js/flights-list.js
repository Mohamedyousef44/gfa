var $ = jQuery
var flightDataStore = {}
var selectedPurchaseId;
let finalePrice = null;
let fareClass = null;
var extraBags = {}
var selectedBaggage = {};

jQuery(document).ready(function ($) {

    $('.selectInput').select2({
        placeholder: "Select City"
    })

    $(document).on('keydown', 'input[type="date"]', function (e) {
        e.preventDefault();
    });

    // Add a click event listener to the element with the "arrows" class
    $(document).on('click', '.arrows', function () {
        // Get the "from" and "to" select elements
        const fromSelect = $('.from input');
        const toSelect = $('.to input');

        // Swap their selected values
        const tempValue = fromSelect.val();
        fromSelect.val(toSelect.val()).trigger('change');
        toSelect.val(tempValue).trigger('change');
    });

    $(document).on('change', 'input[name="airTravelType"]', function () {

        // i need here to get the checked one val
        let val = $('input[name="airTravelType"]:checked').val();

        if (val === "RoundTrip") {
            $('.returnDate').show()
        } else {
            $('.returnDate').hide()
        }
    })

    $(document).on('click', '.search-flights', function (e) {
        e.preventDefault(); // Prevent default button behavior if inside a form

        // empty search notices
        $('.woocommerce-notices-wrapper').empty()

        // Get form values
        let airTravelType = $('input[name=airTravelType]:checked').val();
        let origin = $('input[name=origin]').val();
        let destination = $('input[name=destination]').val();
        let adultCount = $('input[name=adultCount]').val();
        let childCount = $('input[name=childCount]').val();
        let infantCount = $('input[name=infantCount]').val();
        let cabinClass = $('select[name=cabinClass]').val();
        let departureDateTime = $('input[name=departureDateTime]').val();
        let returnDateTime = ""

        if (airTravelType === "RoundTrip") {
            returnDateTime = $('input[name=returnDateTime]').val();
        }

        // Make the AJAX request
        $.ajax({
            url: flights_list.ajax_url, // The endpoint URL (admin-ajax.php)
            type: 'POST',
            data: {
                action: 'omdr_search_flights',
                airTravelType,
                origin,
                destination,
                departureDateTime,
                adultCount,
                childCount,
                infantCount,
                cabinClass,
                returnDateTime
            },
            beforeSend: function () {
                // Show the loader before the request
                $('#gfa-hub-loader').show();
                $('.flight-list').empty()
                $('.woocommerce-notices-wrapper').empty()
            },
            success: function (response) {
                // Hide loader and enable button
                $('#gfa-hub-loader').hide();
                if (response.success) {
                    // Handle successful response
                    renderWooNotice([response.message], 'message', '.search-box')

                    // save this inside localstorage to use it after that in revalidation
                    let searchTraceId = response.data.traceId
                    localStorage.setItem("flightSearchTraceId", searchTraceId)

                    let flights = response.data.flights
                    createFlightCards(flights)

                } else {
                    // Handle validation or processing errors
                    if (response.data.errors) {
                        let errors = getAPIValidationsErrors(response.data.errors)
                        renderWooNotice(errors, 'error', '.search-box');
                    } else {
                        renderWooNotice([response.data.message], 'error', '.search-box');
                    }
                }
            },
            error: function (xhr, status, error) {
                // Hide loader and enable button
                $('#gfa-hub-loader').hide();
                // Handle any AJAX errors
                renderWooNotice(["A server error occurred. Please try again later."], 'error', '.search-box');
            },
            complete: function () {
                $('#gfa-hub-loader').hide();
            }
        });
    });

    $(document).on('click', '.flight-details-link', function (e) {
        e.preventDefault();
        selectedPurchaseId = $(this).data('purchase-id');
        const flightDetails = flightDataStore[selectedPurchaseId];
        const indx = $(this).data('book-index-value');
        $("[data-book-index='" + indx + "']").data('purchase-id', selectedPurchaseId);
        finalePrice = flightDetails.price;
        fareClass = flightDetails.fareGroup.priceClass
        $(`[data-book-price-index=${indx}]`).closest('.fare-options').find('.finale-price').text(finalePrice);
        $(`[data-book-price-index=${indx}]`).closest('.fare-options').find('.fareClass').text(fareClass);

        renderOffcanvas(flightDetails)
    });

    $(document).on('click', '#add-flight-to-cart', function (e) {
        e.preventDefault();
        let searchTraceId = localStorage.getItem("flightSearchTraceId");
        let purchaseId = selectedPurchaseId;
        let flightDetails = flightDataStore[purchaseId];
        flightDetails.holdAction = false

        if (selectedBaggage[purchaseId]) {
            // Add the selected baggage information to the flight details
            flightDetails.selectedBags = selectedBaggage[purchaseId];
        } else {
            // Default to an empty object if no baggage is selected
            flightDetails.selectedBags = {};
        }

        addFlightToCart(searchTraceId, purchaseId, flightDetails)
    });

    $(document).on('click', '#hold-flight', function (e) {
        e.preventDefault();
        let searchTraceId = localStorage.getItem("flightSearchTraceId");
        let purchaseId = selectedPurchaseId;
        let flightDetails = flightDataStore[purchaseId];
        flightDetails.holdAction = true

        if (selectedBaggage[purchaseId]) {
            // Add the selected baggage information to the flight details
            flightDetails.selectedBags = selectedBaggage[purchaseId];
        } else {
            // Default to an empty object if no baggage is selected
            flightDetails.selectedBags = {};
        }

        addFlightToCart(searchTraceId, purchaseId, flightDetails)
    });

    // open modal of the extra bags
    $(document).on('click', '.add-more-bags', function (e) {

        let purchaseID = selectedPurchaseId;
        let traceID = localStorage.getItem("flightSearchTraceId");
        const flightDetails = flightDataStore[selectedPurchaseId];
        let passengerCount = flightDetails.passengersCount; // e.g., { ADT: 2, CHD: 2, INF: 0 }

        // Check if both purchaseID and traceID have data
        if (purchaseID && traceID) {
            $.ajax({
                url: wc_add_to_cart_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'omdr_revalidate_flights',
                    trace_id: traceID,
                    purchase_id: purchaseID,
                },
                beforeSend: function () {
                    // Show the loader before the request
                    $('#gfa-hub-loader').show();
                },
                complete: function () {
                    // Hide the loader after the request
                    $('#gfa-hub-loader').hide();
                },
                success: function (response) {
                    if (response.success) {
                        // Open the modal
                        $('#BaggageModal').modal('show');

                        // Select the container for extra bags
                        let $container = $('.extra-bags');
                        $container.empty(); // Clear old content to prevent duplication

                        let data = response.data;

                        // Loop through passenger types (ADT, CHD, INF)
                        Object.keys(passengerCount).forEach((type) => {
                            let count = passengerCount[type];
                            type = type.toLowerCase()
                            for (let i = 1; i <= count; i++) {
                                // Render a header for each passenger
                                $container.append(`<h3>${type} ${i} Baggage Options</h3>`);

                                let hasBaggage = false;

                                // Render baggage options for the current passenger
                                data.forEach((service) => {
                                    if (service.additionalServiceType === "Baggage") {
                                        hasBaggage = true;

                                        let cityPair = service.cityPair;
                                        let weight = service.serviceDescription;
                                        let weightID = service.freeText;
                                        let amount = service.flightFares[0].amount;

                                        if (weight && weightID && amount) {
                                            if (!extraBags[purchaseID]) {
                                                extraBags[purchaseID] = {};
                                            }
                                            if (!extraBags[purchaseID][`${type}_${i}`]) {
                                                extraBags[purchaseID][`${type}_${i}`] = [];
                                            }

                                            // Add baggage details for the specific passenger
                                            extraBags[purchaseID][`${type}_${i}`].push({
                                                weightID,
                                                amount
                                            });

                                            let element = `
                                                <div class="bag-item">
                                                    <p class="city-pair">City Pair: ${cityPair}</p>
                                                    <p class="weight-description">Weight: ${weight}</p>
                                                    <p class="weight-price">Amount: ${amount} SAR</p>
                                                    <input class="select-extra-bags" name="weight-id-${type}_${i}" type="radio" data-pairs="${cityPair}" data-amount="${amount}" value="${weightID}">
                                                </div>`;
                                            $container.append(element);
                                        }
                                    }
                                });

                                // If no baggage services were found for this passenger
                                if (hasBaggage) {
                                    $container.append(`
                                        <div class="bag-item">
                                            <p class="weight-description">None</p>
                                            <input class="select-extra-bags" name="weight-id-${type}_${i}" type="radio" value="none">
                                        </div>
                                    `);
                                } else {
                                    $container.append(`<p>No Extra bags for this flight</p>`);
                                }
                            }
                        });
                    } else {
                        // Handle error case
                        renderWooNotice([response.message], 'error');
                    }
                }
            });
        }
    });

    $(document).on('input', ".select-extra-bags", function (e) {
        // Get the selected value
        let weightID = $(this).val();
        let pairs = $(this).data("pairs");
        let name = $(this).attr("name").replace("weight-id-", ""); // Remove the prefix
        let amount = $(this).data("amount");

        // Ensure the selectedPurchaseID exists in the global variable
        if (!selectedBaggage[selectedPurchaseId]) {
            selectedBaggage[selectedPurchaseId] = {};
        }

        // Update or remove data based on the selection
        if (weightID !== "none") {
            // Store the selection data under the simplified name
            selectedBaggage[selectedPurchaseId][name] = {
                baggageRefNo: weightID,
                SegmentInfo: pairs,
                amount
            };
        } else {
            // Remove the baggage entry if "None" is selected
            delete selectedBaggage[selectedPurchaseId][name];
        }
    });
});

// helper functions
function renderWooNotice(msgsList, type, parentClass = "") {
    const noticeWrapperSelector = `${parentClass} .woocommerce-notices-wrapper`;

    // Remove any existing toasts or notices
    $('.toast-container').remove();

    // Determine toast class based on type (success, error, etc.)
    const toastClass = type === 'error' ? 'bg-danger text-white' : 'bg-success text-white';

    // Create the toast container and individual toast
    const toastContainer = $('<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1055;"></div>');

    msgsList.forEach(msg => {
        const toast = $(`
            <div class="toast ${toastClass}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true">
                <div class="toast-header">
                    <strong class="me-auto">${type === 'error' ? 'Error' : 'Success'}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${msg}
                </div>
            </div>
        `);

        toastContainer.append(toast);
    });

    // Append the toast container to the body
    $('body').append(toastContainer);

    // Initialize and show the toast
    $('.toast').each(function () {
        const toastInstance = new bootstrap.Toast(this);
        toastInstance.show();
    });
}


function getDuration(segment) {
    // Calculates the duration from each segment
    const totalDuration = segment.segs.reduce((acc, seg) => acc + seg.duration, 0);
    const hours = Math.floor(totalDuration / 60);
    const minutes = totalDuration % 60;
    return `${hours}h ${minutes}m`;
}

function getDepartureAndArrivalTimes(segment) {
    const firstSeg = segment.segs[0];
    const lastSeg = segment.segs[segment.segs.length - 1];

    const departureTime = new Date(firstSeg.departureOn).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const arrivalTime = new Date(lastSeg.arrivalOn).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    // Check if arrival is next day
    const nextDay = new Date(firstSeg.departureOn).getDate() !== new Date(lastSeg.arrivalOn).getDate() ? "+1" : "";
    return { departureTime, arrivalTime, nextDay };
}

function getRouteInfo(segment) {
    const origin = segment.origin;
    const destination = segment.destination;
    return { origin, destination };
}

function createFlightCards(flights) {
    const container = $('.flight-list');

    // Handle empty flight list
    if (flights.length === 0) {
        renderWooNotice(["No flights found. Please try again with different criteria."], 'error', '.search-box')
        // container.html('<p class="text-center">No Flights Found</p>');
        return;
    }
    // Clear the container before appending
    container.empty();
    // Loop through filtered flights and create cards
    flights.forEach((flight, indx) => {
        // Extract segment and fare group details
        console.log(flight.vendor)
        let isPaidBags = flight.vendor == "AT";
        const segment = flight.segGroups[0];
        const fareGroup = flight.fareGroups[0];
        const cabinClass = fareGroup.segInfos[0].cabinClass;
        const { departureTime, arrivalTime, nextDay } = getDepartureAndArrivalTimes(segment);
        const duration = getDuration(segment);
        const { origin, destination } = getRouteInfo(segment);
        const isHolding = flight.isHold;
        const passengersCount = {
            ADT: flight.adtNum,
            CHD: flight.chdNum,
            INF: flight.infNum
        };

        const totalBase = getFlightTotalPrice(flight, passengersCount);
        // Determine the class based on flight.isHold
        const refundClass = flight.isHold ? 'text-success ' : 'text-danger';
        const refundBorderClass = flight.isHold ? 'border-success ' : 'border-danger';
        let flightDetails = {
            price: totalBase,
            duration,
            cabinClass,
            fareGroup,
            currency: flight.currency,
            airline: flight.airline,
            segGroups: flight.segGroups,
            baggages: fareGroup.baggages,
            miniRules: fareGroup.miniRules,
            departureTime,
            arrivalTime,
            adtNum: flight.adtNum,
            chdNum: flight.chdNum,
            infNum: flight.infNum,
            passengersCount,
            isHold: isHolding,
            isPaidBags
        };
        // Store flight details for later retrieval
        flightDataStore[fareGroup.purchaseId] = flightDetails;
        const LastFare = flight.fareGroups.length;
        let displaybtn = 'd-block';
        if (LastFare == 1) {
            displaybtn = 'd-none';
        }
        finalePrice = flightDetails.price;
        // Create the card element
        const cardELE = $(`
    <div class="p-4 shadow AirWayParent">
        <div class="flight-item justify-content-between row">
            <div class="flight-info col-lg-5 col-12 p-3 border shadow-sm rounded-3">
                <div class="flight-time d-flex justify-content-between">
                    <h5>Airline: ${flight.airline}</h5>
                    <div class="flightTimes align-items-center justify-content-center d-flex flex-column mainFontSize">
                        <h4 class="departure-time">${departureTime}</h4>
                        <h4 class="departure-location">${origin}</h4>
                    </div>
                    <div class="d-flex justify-content-center align-items-center">
                        <span><i class="fa-solid fa-arrow-right-long"></i></span>
                    </div>
                    <div class="flightTimes airline-logo-parent d-flex align-items-center justify-content-center flex-column">
                        <h4 class="airline-logo text-center">✈️</h4>
                        <h4 class="duration text-center mainFontSize text-secondary">${duration}</h4>
                    </div>
                    <div class="d-flex justify-content-center align-items-center">
                        <span><i class="fa-solid fa-arrow-right-long"></i></span>
                    </div>
                    <div class="flightTimes d-flex align-items-center justify-content-center flex-column mainFontSize">
                        <h4 class="arrival-location">${destination}</h4>
                        <h4 class="arrival-time">${arrivalTime}<span class="next-day">${nextDay}</span></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-12 d-none d-lg-flex row justify-content-center m-0 p-0 gap-3 RefundMessageParent">
                <div class="fare-options col border ${refundBorderClass} rounded-3 shadow-sm">
                    <div class="d-flex gap-3 justify-content-between px-2 align-items-end">
                        <div class="fare d-flex flex-column gap-2 ">
                            <h4 class="fare-class fareClass">${cabinClass}</h4>

                            <h4 class="fare-price mainFontSize">
                                <span class="text-secondary" data-book-price-index=${indx}>${flight.currency}</span><span class="finale-price">      ${finalePrice.toFixed(2)}</span>
                            </h4>
                        </div>
                        <div class="d-flex justify-content-center gap-3 align-items-end flex-fill">
				<button type="button" class="btn ${displaybtn} btn-outline-primary " data-bs-toggle="modal" data-bs-target="#FlightDetails${indx}">
				FlightDetails</button>

			<div class="modal fade" id="FlightDetails${indx}" tabindex="-1" aria-labelledby="FlightDetailsLabel" aria-hidden="true">
 					 <div class="modal-dialog modal-dialog-centered FlightDetailsModal">
 						   <div class="modal-content">
   								    <div class="modal-header border-0">
   										     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
     								</div>
      								<div class="modal-body" >
     								 <section class="lis-bg-light">
										<div class="container">
      								          <div class="row justify-content-center gap-2">
         								           <div class="col-12 col-md-10 text-center">
         								               <div class="heading pb-4">
          									                  <h2>Choose Your Plan</h2>
                        								</div>
                 								   </div>
												</div>
               							 <div class="row p-0 m-0 gap-1 flex-wrap" id="classContainer${indx}"></div>
      	   							     </div>
       									 </section>
 									     </div>     
    									 </div>
   									</div>
								</div>
                            <a href="#" class="flight-details-link btn btn-primary text-white" 
                                data-purchase-id=${fareGroup.purchaseId} 
                                data-bs-toggle="offcanvas" 
                                data-bs-target="#offcanvasRight" 
                                aria-controls="offcanvasRight"  
								data-book-index=${indx}
>
                                Book Flight 
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4 col-12 justify-content-between d-flex d-lg-none">
                <span class="fare-price d-flex flex-column flex-md-row mainFontSize w-100 align-items-stretch justify-content-between gap-3">
                    <a href="#" class=" flight-details-link btn btn-primary text-white" 
                        data-purchase-id=${fareGroup.purchaseId} 
                        data-bs-toggle="offcanvas" 
                        data-bs-target="#offcanvasRight" 
                        aria-controls="offcanvasRight"
>
                        Book Flight
                    </a>
	<button type="button" class="btn btn-outline-primary ${displaybtn} " data-bs-toggle="modal" data-bs-target="#FlightDetails${indx}">
				FlightDetails</button>
                    <span class="text-secondary text-center fw-bold ">${flight.currency}<span class="finale-price"> ${finalePrice.toFixed(2)}</span></span> 
                </span>
            </div>
        </div>
    </div>
`);
        const classContainer = cardELE.find(`#classContainer${indx}`);

        flight.fareGroups.forEach((fareGroup, ind) => {
            const totalBase = getFlightTotalPriceSingleFare(fareGroup, passengersCount);
            let layout = 'col-md-6';
            let miniRulesData = fareGroup.miniRules || [];
            let changeValue = 'N/A';
            let cancelValue = 'N/A';
            let badgeColor = "N/A";
            flightDetails = {
                price: totalBase,
                duration,
                cabinClass,
                fareGroup,
                currency: flight.currency,
                airline: flight.airline,
                segGroups: flight.segGroups,
                baggages: fareGroup.baggages,
                miniRules: fareGroup.miniRules,
                departureTime,
                arrivalTime,
                adtNum: flight.adtNum,
                chdNum: flight.chdNum,
                infNum: flight.infNum,
                passengersCount,
                isHold: flight.isHold,
                isPaidBags
            };

            flightDataStore[fareGroup.purchaseId] = flightDetails;
            switch (fareGroup.priceClass) {
                case "ECONOMY BASIC":
                    badgeColor = "bg-success";
                    break;
                case "ECONOMY VALUE":
                    badgeColor = "bg-primary";
                    break;
                case "ECONOMY COMFORT":
                    badgeColor = "bg-info";
                    break;
                case "ECONOMY DELUXE":
                    badgeColor = "bg-danger";
                    break;
            }
            miniRulesData.forEach(item => {
                if (item.changeAllowed && !item.cancelAllowed && item.paxType === "ADT") {
                    changeValue = item.exgAmt;
                } else if (!item.changeAllowed && item.cancelAllowed) {
                    cancelValue = item.canAmt;
                }
            });
            if (LastFare <= 2) {
                layout = 'col-md-4 flex-fill';
            } else if (LastFare >= 3) {
                layout = 'col-md-2 flex-fill';
            }
            const fareGroupElement = $(`
                <div class="fare-group  border col-12 d-flex flex-column justify-content-center gap-2 align-items-center ${layout}   p-3 mb-3">
                    <h5 class="fare-title text-center fw-bold m-0 p-0 ">${fareGroup.priceClass}</h5>
					<div class="cardBadge badge-${ind} ${badgeColor}"></div>
					<div class="cardBadge badge-${ind} cardBadgeTwo ${badgeColor}"></div>
                    <p class="fare-title text-center fw-bold m-0 p-0 d-flex justify-content-between align-items-center gap-2">Price: ${totalBase.toFixed(2)} SAR</p>
                    <p class="fare-title text-center fw-bold m-0 p-0 d-flex justify-content-between align-items-center gap-2"> <i class="fa-solid fa-bag-shopping"></i> 					Baggage : ${fareGroup.baggages[0].checkInBag}</p>
                    <p class="fare-title text-center fw-bold m-0 p-0 d-flex justify-content-between align-items-center gap-2"><i class="fa-solid fa-bag-shopping"></i> 						Cabin baggage:${fareGroup.baggages[0].cabinBag}					</p>
         			<p class="fare-title text-center fw-bold m-0 p-0 d-flex justify-content-between align-items-center gap-2"><i class="fa-solid fa-money-bill"></i> 						Change Fee :${changeValue}</p>
        			<p class="fare-title text-center fw-bold m-0 p-0 d-flex justify-content-between align-items-center gap-2"><i class="fa-solid fa-money-bill"></i> 						Refund Fee :${cancelValue}</p>
                    <div class="d-flex justify-content-center"><a class="btn btn-outline-primary flight-details-link"     
								        data-bs-dismiss="modal" aria-label="Close"      
								data-purchase-id=${fareGroup.purchaseId}
data-book-index-value=${indx}
>Select</a>
					</div>
                </div>
            `);
            classContainer.append(fareGroupElement);
        });
        // Append the card to the container
        container.append(cardELE);
    });
}

function validateDateTimes(departureDateTime, returnDateTime) {
    // Initialize an error message container
    let errors = [];

    // Validation for departureDateTime: Must be >= today
    let today = new Date();
    let departureDate = new Date(departureDateTime);

    if (departureDate < today) {
        errors.push("Departure date must be greater than or equal to today's date.");
    }

    // Validation for returnDateTime: Must be greater than departureDateTime
    if (returnDateTime) {
        let returnDate = new Date(returnDateTime);
        if (returnDate <= departureDate) {
            errors.push("Return date must be greater than departure date.");
        }
    }

    return errors
}

function getAPIValidationsErrors(errorObj) {
    let errors = []
    // Iterate over each key in the errors object
    for (let key in errorObj) {
        if (errorObj.hasOwnProperty(key)) {
            errors.push(errorObj[key])
        }
    }

    return errors
}

function renderOffcanvas(flightDetails) {
    let fareGroup = flightDetails.fareGroup
    let passengersCount = flightDetails.passengersCount
    let segGroups = flightDetails.segGroups
    let currency = flightDetails.currency

    // display the hold now button
    if (flightDetails.isHold) {
        $('#hold-flight').show()
    } else {
        $('#hold-flight').hide()
    }

    // add more bags button
    if (flightDetails.isPaidBags) {
        $('.add-more-bags').show()
    } else {
        $('.add-more-bags').hide()
    }

    // Select and clear the container
    const flightInfoContainer = $("#flight-info-container");
    flightInfoContainer.empty(); // Clear existing content

    segGroups.forEach((segGroup, groupIndex) => {
        const { segs } = segGroup;

        segs.forEach((seg) => {
            const {
                origin,
                destination,
                departureOn,
                arrivalOn,
                duration,
            } = seg;

            const departureTime = new Date(departureOn).toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit",
            });
            const arrivalTime = new Date(arrivalOn).toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit",
            });
            const arrivalNextDay = new Date(arrivalOn).getDate() > new Date(departureOn).getDate() ? "+1" : "";

            // Reuse the flightCard structure
            const flightCard = `
                <div class="flight-info my-2 p-3 border shadow-sm rounded-3">
                    <div class="flight-time d-flex justify-content-between">
                        <div class="flightTimes align-items-center d-flex flex-column">
                            <span class="departure-time">${departureTime}</span>
                            <span class="departure-location">${origin}</span>
                        </div>
                        <div class="d-flex justify-content-center align-items-center">
                            <span><i class="fa-solid fa-arrow-right-long"></i></span>
                        </div>
                        <div class="flightTimes airline-logo-parent d-flex my-2 align-items-center flex-column">
                            <span class="airline-logo text-center">✈️</span>
                            <span class="duration text-center text-secondary">${calculateTotalDuration(duration)} mins</span>
                        </div>
                        <div class="d-flex justify-content-center align-items-center">
                            <span><i class="fa-solid fa-arrow-right-long"></i></span>
                        </div>
                        <div class="flightTimes align-items-center d-flex flex-column">
                            <span class="arrival-time">${arrivalTime}<span class="next-day">${arrivalNextDay}</span></span>
                            <span class="arrival-location">${destination}</span>
                        </div>
                    </div>
                </div>
            `;

            // Append the card to the container
            flightInfoContainer.append(flightCard);
        });

        // Add a separator for return trips
        if (groupIndex < segGroups.length - 1) {
            flightInfoContainer.append(`<hr>`);
        }
    });

    // Render Pricing Section
    const pricingDetailsContainer = $("#pricing-details-container");
    pricingDetailsContainer.empty()  // Clear existing content

    const priceDetails = getFlightPriceDetails(fareGroup, passengersCount);
    let totalPrice = 0;

    var priceLiEl = [];
    priceDetails.forEach(({ type, basePrice, tax, num }) => {
        const passengerTotal = ((basePrice + tax) * num);
        totalPrice += passengerTotal;
        // Initialize an array to store list items
        priceLiEl.push(`
            <li class="list-group-item gap-3 d-flex flex-column justify-content-between align-items-center">
			 <div class="d-flex justify-content-between align-items-center w-100">
     		 <b>${type}(s):</b>
   			 <span>${num} x ${basePrice} </span>
			 <span> = </span>
			 <b>${num * basePrice} ${currency}</b>
			</div>
                <div class="w-100 d-flex justify-content-between"><b>Taxes</b> = <span>${tax} ${currency}</span></div>
                <div class="w-100 d-flex justify-content-between"><b>Total</b> = <span>${passengerTotal.toFixed(2)} ${currency}</span></div>
            </li>`
        )
    })

    priceLiEl.push(`
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <b>Total Price:</b>
            <span>=</span>
            <span>${totalPrice.toFixed(2)} ${currency}</span>
        </li>`
    )
    pricingDetailsContainer.append(priceLiEl.join(''));


    // Render Baggages Section
    const baggageDetailsContainer = $("#baggage-list");
    baggageDetailsContainer.empty(); // Clear existing content

    // Initialize an array to store list items
    let liEl = [];

    // Loop through baggageData and create list items
    fareGroup.baggages.forEach(bag => {
        liEl.push(
            `<li class="list-group-item flex-column gap-3 d-flex justify-content-between align-items-center">
                <div class="d-flex w-100 justify-content-between"><b>City Pair:</b> ${bag?.cityPair || "N/A"}</div>
                <div class="d-flex w-100 justify-content-between"><b>Pax Type: </b><b>${bag?.paxType || "N/A"}</b></div>
                <div class="d-flex w-100 justify-content-between"><b>Check In Bag:</b><b> ${bag?.checkInBag || "N/A"}</b></div>
                <div class="d-flex w-100 justify-content-between"><b>Cabin Bag:</b><b> ${bag?.cabinBag || "N/A"}</b></div>
            </li>`
        );
    }
    );

    // Append all the list items to the container
    baggageDetailsContainer.append(liEl.join(''));
}

function calculateTotalDuration(duration) {
    const hours = Math.floor(duration / 60)
    const minutes = duration % 60;

    return `${hours}h ${minutes}m`;
}

function getDuration(segment) {
    // Calculates the duration from each segment
    const totalDuration = segment.segs.reduce((acc, seg) => acc + seg.duration, 0);
    const hours = Math.floor(totalDuration / 60);
    const minutes = totalDuration % 60;
    return `${hours}h ${minutes}m`;
}

function getFlightPriceDetails(fareGroup, passengersCount) {
    const fares = fareGroup.fares;

    const paxTypes = fares.map(fare => {
        const paxType = fare.paxType;
        const passengerCount = passengersCount[paxType] || 0;
        const basePrice = fare.base;
        const taxes = fare.taxes.reduce((acc, tax) => acc + tax.amt, 0);

        return {
            type: paxType,
            num: passengerCount,
            basePrice,
            tax: taxes
        };
    });

    return paxTypes;
}

function getFlightTotalPrice(flight, passengersCount) {
    let fareGroup = flight.fareGroups[0]
    const priceDetails = getFlightPriceDetails(fareGroup, passengersCount);

    let totalPrice = 0;
    priceDetails.forEach(({ basePrice, tax, num }) => {
        totalPrice += (basePrice + tax) * num;
    });

    return totalPrice;
}

function getFlightTotalPriceSingleFare(fare, passengersCount) {

    const priceDetails = getFlightPriceDetails(fare, passengersCount);

    let totalPrice = 0;
    priceDetails.forEach(({ basePrice, tax, num }) => {
        totalPrice += (basePrice + tax) * num;
    });

    return totalPrice;
}

function addFlightToCart(searchTraceId, purchaseId, flightDetails) {
    $.ajax(
        {
            url: wc_add_to_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'flights_ajax_add_to_cart',
                trace_id: searchTraceId,
                purchase_id: purchaseId,
                flight_details: flightDetails,
            },
            success: function (response) {
                if (response.success) {
                    window.location.href = response.data.checkout_url;
                } else {
                    $('#offcanvasRight').offcanvas('hide');
                    renderWooNotice([response.data.message], 'error', '.search-box')
                }
            }
        });
}

//Baggage JS 
const totalBaggage = document.getElementById('totalBaggage');
const counters = {}; // Store counters for each baggage type
let totalPrice = 0;

// Function to update total price
function updateTotalPrice() {
    totalPrice = Object.keys(counters).reduce((sum, key) => {
        return sum + counters[key].count * counters[key].price;
    }, 0);
    totalBaggage.innerText = `${totalPrice} Sar`;
}

// Event listener for increment and decrement
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('increment') || e.target.classList.contains('decrement')) {
        const baggageId = e.target.getAttribute('data-id');
        const price = parseInt(e.target.getAttribute('data-price'), 10);

        if (!counters[baggageId]) {
            counters[baggageId] = { count: 0, price };
        }
        const counterElement = document.querySelector(`.counter[data-id="${baggageId}"]`);

        if (e.target.classList.contains('increment')) {
            counters[baggageId].count++;
        } else if (e.target.classList.contains('decrement') && counters[baggageId].count > 0) {
            counters[baggageId].count--;
        }

        counterElement.innerText = counters[baggageId].count; // Update counter display
        updateTotalPrice(); // Update total price display
    }
});


