<?php
$airport_codes = get_airport_codes_from_csv();
?>
<div class="searchWrapper flex-column align-items-center justify-content-center gap-2">
    <div class="imgWrapper">
        <img src="https://omdrgate.com/wp-content/uploads/2024/12/c352f092-ba06-4ff1-9f4b-8d44aab9b1c4.jpg" class="img-fluid" />
    </div>
    <div class="search-box d-flex h-100 flex-column gap-2 container-fluid">
        <div class="trip-type gap-4 d-flex justify-content-start">
            <label>
                <input type="radio" name="airTravelType" value="OneWay" checked> <?php _e("One Way", TEXT_DOMAIN) ?>
            </label>
            <label>
                <input type="radio" name="airTravelType" value="RoundTrip"> <?php _e("Round Trip", TEXT_DOMAIN) ?>
            </label>
        </div>
        <div class="p-4 rounded-2 SearchDataWrapper gap-2 gap-md-0 rounded shadow-sm">
            <div class="row  gap-2 ">
                <div class="col-xl-3 col-12 p-0 m-0 d-flex  flex-fill gap-1 input-row flex-lg-row flex-column">
                    <div class="input-group col flex-fill w-100">
                        <span class="input-group-text"><?php _e("Departure", TEXT_DOMAIN) ?></span>
                        <input type="date" name="departureDateTime" id="dateOne" class="form-control  border-0">
                    </div>
                    <div class="input-group col flex-fill returnDate w-100" style="display: none;">
                        <span class="input-group-text"><?php _e("Return", TEXT_DOMAIN) ?></span>
                        <input type="date" name="returnDateTime" class="form-control border-0">
                    </div>
                </div>
                <div class="col-xl-3 col-12 p-0 m-0 d-flex align-items-end flex-fill gap-1 input-row">
                    <button type="button"
                        class="btn btn-dark w-100 travellersButton"
                        id="travellersButton"
                        data-bs-toggle="modal"
                        data-bs-target="#exampleModal"> Destinations ,
                        Travellers and cabin class
                    </button>
                </div>
            </div>
        </div>
        <div class="search-btn d-flex justify-content-end">
            <button class="btn btn-primary p-2  search-flights"><?php _e("Search Flights", TEXT_DOMAIN) ?></button>
        </div>

        <div class="woocommerce-notices-wrapper"></div>
    </div>
</div>
<!-- Button trigger modal -->


<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h1 class="modal-title fs-5 border-0" id="exampleModalLabel">Destinations ,
                    Travellers and cabin class
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row justif-content-center align-items-center gap-3 flex-column">
                <div class="col-10 input-row d-flex flex-column gap-3 p-0">
                    <div class="input-group from border rounded gap-1 p-1">
                        <!-- 'From' Input with dataList -->
                        <input list="from_airports" class="form-control border-0 w-100 rounded" name="origin" placeholder="From" />
                        <datalist id="from_airports">
                            <?php foreach ($airport_codes as $code) { ?>
                                <option value="<?php echo $code['iata_code']; ?>"><?php echo $code['name']; ?></option>
                            <?php } ?>
                        </datalist>
                    </div>
                    <div class="input-group to border rounded gap-1 p-1">
                        <!-- 'To' Input with dataList -->
                        <input list="to_airports" class="form-control border-0 w-100 rounded" name="destination" placeholder="To" />
                        <datalist id="to_airports">
                            <?php foreach ($airport_codes as $code) { ?>
                                <option value="<?php echo $code['iata_code']; ?>"><?php echo $code['name']; ?></option>
                            <?php } ?>
                        </datalist>
                    </div>
                </div>
                <div class=" col-10 input-row p-0">
                    <div class="input-group gap-1">
                        <input type="number" min="1" name="adultCount" id="adultCount" placeholder="Adults" class="form-control rounded" value="">
                    </div>
                </div>
                <div class="col-10 input-row p-0">
                    <div class="input-group gap-1">
                        <input type="number" min="0" name="childCount" placeholder="Children" class="form-control rounded" value="">
                    </div>
                </div>
                <div class="col-10 input-row p-0">
                    <div class="input-group gap-1">
                        <input type="number" min="0" name="infantCount" placeholder="Infants" class="form-control rounded" value="">
                    </div>
                </div>

                <div class="col-10 input-row p-0">
                    <div class="input-group gap-1">
                        <select class="form-select border rounded" name="cabinClass">
                            <option value="Economy"><?php _e("Economy", TEXT_DOMAIN) ?></option>
                            <option value="PremiumEconomy"><?php _e("Premium Economy", TEXT_DOMAIN) ?></option>
                            <option value="Business"><?php _e("Business", TEXT_DOMAIN) ?></option>
                            <option value="First"><?php _e("First", TEXT_DOMAIN) ?></option>
                            <option value="PremiumFirst"><?php _e("Premium First", TEXT_DOMAIN) ?></option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>