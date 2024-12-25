<div class="flights-list  ">
    <div class='loaderParent' id="gfa-hub-loader" style="display: none;">
        <div class="loader"></div>
    </div>
    <?php include GFA_HUB_FLIGHTS_TEMPLATES_FRONTEND . '/flight-search.php'; ?>
    <div class="flight-list">
    </div>
    <div class="offcanvas offcanvas-start" data-bs-scroll="true" tabindex="-1" id="offcanvasRight"
        aria-labelledby="offcanvasRightLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasRightLabel"><?php _e("Flight Details", TEXT_DOMAIN) ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column gap-3">
            <!-- Flight Information -->
            <div id="flight-info-container"></div>

            <!-- Pricing Details -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><?php _e("Pricing", TEXT_DOMAIN) ?></h4>
                    <img src="<?php echo GFA_HUB_FLIGHTS_ASSETS_IMAGES . 'cash2.png' ?>" width="50px" alt="pricing">
                </div>
                <ul class="list-group list-group-flush" id="pricing-details-container"></ul>
            </div>

            <!-- Baggage Information -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><?php _e("Baggage", TEXT_DOMAIN) ?></h4>
                    <img src="<?php echo GFA_HUB_FLIGHTS_ASSETS_IMAGES . '1573537-200.png' ?>" width="50px" alt="baggage">
                </div>
                <ul id="baggage-list" class="list-group list-group-flush"></ul>
            </div>

            <!-- Hidden Inputs -->
            <input hidden name="traceId" value="" />
            <input hidden name="purchaseIds" value="" />
            <input hidden name="flightDetails" value="" />
            <!-- Button trigger modal -->
            <button style="display:none;" type="button" class="add-more-bags btn btn-outline-secondary">
                Add More Baggage
            </button>

            <!-- Modal -->
            <div class="modal fade" id="BaggageModal" tabindex="-1" aria-labelledby="BaggageModal" aria-hidden="true">
                <div class="modal-dialog modal-dialog-baggage modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header flex-column BaggageHeader align-items-start">
                            <div class="d-flex justify-content-between w-100 align-items-center">
                                <h1 class="modal-title fs-5 text-white" id="exampleModalLabel">Baggage</h1>
                                <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>

                            </div>
                            <h6 class=" text-white">Need To Carry a bit Extra?</h6>
                        </div>
                        <div class="modal-body row gap-4 justify-content-between">
                            <div class="extra-bags">
                                <div class="bag-item">
                                    <p class="city-pair">City Pair: CAIJED</p>
                                    <p class="weight-description">Weight: 10Kg</p>
                                    <p class="weight-price">Amount: 18.52 SAR</p>
                                    <input type="radio" value="SCI_0KG&1_CAIJED">
                                </div>
                                <div class="bag-item">
                                    <p class="city-pair">City Pair: CAIJED</p>
                                    <p class="weight-description">Weight: 30Kg</p>
                                    <p class="weight-price">Amount: 28.52 SAR</p>
                                    <input type="radio" value="SCI_0KG&1_CAIJED">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer flex-column ">
                            <div class="d-flex align-items-center w-100 justify-content-between">
                                <h2 class="m-0 p-0">Total :</h2>
                                <h5 class="m-0 p-0" id="totalBaggage">0 Sar</h5>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="btn-group" role="group" aria-label="Basic outlined example">
                <button type="button" class="btn btn-outline-primary" id="add-flight-to-cart"><?php _e("BOOK NOW", TEXT_DOMAIN) ?></button>
                <button style="display:none;" type="button" class="btn btn-outline-warning border border-start-0" id="hold-flight"><?php _e("Hold Ticket", TEXT_DOMAIN) ?></button>
            </div>
        </div>
    </div>
</div>