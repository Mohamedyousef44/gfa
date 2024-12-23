<div class="current-status">
    <?php echo $ticket_status ?>
</div>
<!-- segments -->
<div class="container">
    <div class="row">
        <div class="col-md-12 p-0 m-0">
            <div class="table-responsive">
                <table class="table table-collapse">
                    <caption><?php _e("Itineray Details", TEXT_DOMAIN) ?></caption>
                    <thead>
                        <tr>
                            <th><?php _e("AirLine / FlightNo", TEXT_DOMAIN) ?></th>
                            <th><?php _e("Departure AirPort / Terminal", TEXT_DOMAIN) ?></th>
                            <th><?php _e("Departure Date & Time", TEXT_DOMAIN) ?></th>i
                            <th><?php _e("Arrival AirPort / Terminal", TEXT_DOMAIN) ?></th>
                            <th><?php _e("Arrival Date & Time", TEXT_DOMAIN) ?></th>
                            <th><?php _e("Duration", TEXT_DOMAIN) ?></th>
                            <th><?php _e("Cabin Class", TEXT_DOMAIN) ?></th>
                            <th><?php _e("Equipment Type", TEXT_DOMAIN) ?></th>
                            <th><?php _e("PNR", TEXT_DOMAIN) ?></th>
                        </tr>
                    </thead>
                    <tbody id="handlebarsFundTable">
                        <?php foreach ($seg_groups as $seg) {
                            $segments = $seg['segments'];
                            foreach ($segments as $segment) { ?>
                                <tr class="collapse-control collapsed  " data-toggle="collapse"
                                    data-target="#collaspe{{code}}" aria-expanded="false">
                                    <td><a><?php echo ($segment['opAirline'] . " / " . $segment['flightNum']) ?></a></td>
                                    <td class="">
                                        <div class=" text-center">
                                            <b><?php echo $segment['origin'] ?? "N/A" ?></b>
                                            <br>
                                            <b><?php echo $segment['depTerminal'] ?? "N/A" ?></b>
                                        </div>
                                    </td>
                                    <td class="">
                                        <div class=" text-center">
                                            <?php
                                            $dateTime = new DateTime($segment['departureOn']);
                                            ?>
                                            <b><?php echo $dateTime->format('Y-m-d') ?></b>
                                            <br>
                                            <b><?php echo $dateTime->format('H:i:s'); ?></b>
                                        </div>
                                    </td>
                                    <td class="">
                                        <div class=" text-center">
                                            <b><?php echo $segment['destination'] ?? "N/A" ?></b>
                                            <br>
                                            <b><?php echo $segment['arrTerminal'] ?? "N/A" ?></b>
                                        </div>
                                    </td>
                                    <td class="">
                                        <div class=" text-center">
                                            <?php
                                            $dateTime = new DateTime($segment['arrivalOn']);
                                            ?>
                                            <b><?php echo $dateTime->format('Y-m-d') ?></b>
                                            <br>
                                            <b><?php echo $dateTime->format('H:i:s'); ?></b>
                                        </div>
                                    </td>
                                    <td><?php echo convert_mins_to_hours($segment['duration']) ?></td>
                                    <td><?php echo $segment['cabinClass'] ?? "N/A" ?></td>
                                    <td><?php echo $segment['eqpType'] ?? "N/A" ?></td>
                                    <td><?php echo $segment['pnr'] ?? "N/A" ?></td>
                                </tr>
                        <?php }
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- passenger details -->
<div class="container">
    <div class="row">
        <div class="col-md-12 p-0 m-0">
            <div class="table-responsive">
                <table class="table table-collapse">
                    <caption><?php _e("Passenger Detalis", TEXT_DOMAIN) ?></caption>
                    <thead>
                        <tr>
                            <th><?php _e("PaxType / Title / First / Last / Gender", TEXT_DOMAIN) ?></th>
                            <th><?php _e("Date of Birth", TEXT_DOMAIN) ?></th>
                            <th><?php _e("Passport", TEXT_DOMAIN) ?></th>
                            <th><?php _e("Passenger Nationality", TEXT_DOMAIN) ?></th>
                            <th><?php _e("Passport DOE", TEXT_DOMAIN) ?></th>
                            <th><?php _e("Seat", TEXT_DOMAIN) ?></th>
                        </tr>
                    </thead>
                    <tbody id="handlebarsFundTable">
                        <?php foreach ($passengers as $passenger_arr) { ?>
                            <tr class="collapse-control collapsed  " data-toggle="collapse"
                                data-target="#collaspe{{code}}" aria-expanded="false">
                                <td><a> <?php echo ($passenger_arr['paxType'] . ' / ' . $passenger_arr['title'] . ' / ' . $passenger_arr['firstName'] . ' / ' . $passenger_arr['lastName'] . ' / ' . $passenger_arr['genderType']) ?></a></td>
                                <td class=" ">
                                    <div class=" text-center">
                                        <b><?php echo format_date_of_birth($passenger_arr['dob']) ?></b>
                                    </div>
                                </td>
                                <td class="">
                                    <div class=" text-center">
                                        <b><?php echo $passenger_arr['passportNumber'] ?></b>
                                    </div>
                                </td>
                                <td class="">
                                    <div class=" text-center">
                                        <b><?php echo $passenger_arr['passengerNationality'] ?></b>
                                    </div>
                                </td>
                                <td class="">
                                    <div class=" text-center">
                                        <b><?php echo format_date_of_birth($passenger_arr['passportDOE']) ?></b>
                                    </div>
                                </td>
                                <td class="">
                                    <div class=" text-center">
                                        <b><?php echo $passenger_arr['seatPref'] ?? "N/A"  ?></b>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- price -->
<div class="container">
    <div class="row">
        <div class="col-md-12 p-0 m-0">
            <div class="table-responsive">
                <table class="table table-collapse">
                    <caption><?php _e("Client Fare", TEXT_DOMAIN) ?></caption>
                    <thead>
                        <tr>
                            <th><?php _e("PaxType", TEXT_DOMAIN) ?></th>
                            <th><?php _e("Base Fare", TEXT_DOMAIN) ?></th>
                            <th><?php _e("Tax", TEXT_DOMAIN) ?></th>
                            <th><?php _e("Client Total", TEXT_DOMAIN) ?></th>
                        </tr>
                    </thead>
                    <tbody id="handlebarsFundTable">
                        <?php foreach ($flight_fares as $fare) { ?>
                            <tr class="collapse-control collapsed" data-toggle="collapse"
                                data-target="#collaspe{{code}}" aria-expanded="false">
                                <td><a><?php echo ($fare['paxType'] . " * " . $passenger_counts[$fare['paxType']]) ?></a></td>
                                <td class="">
                                    <?php $total_pax_base =  round($fare['baseFare']) * $passenger_counts[$fare['paxType']] ?>
                                    <div class=" text-center">
                                        <b><?php echo $total_pax_base ?></b>
                                    </div>
                                </td>
                                <td class="">
                                    <div class=" text-center">
                                        <?php $total_pax_tax = calculate_total_tax($fare['taxes']) * $passenger_counts[$fare['paxType']] ?>
                                        <b><?php echo $total_pax_tax ?></b>
                                    </div>
                                </td>
                                <td class="">
                                    <div class=" text-center">
                                        <b><?php echo ($total_pax_tax + $total_pax_base) ?></b>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                        <tr class="collapse-control collapsed" data-toggle="collapse"
                            data-target="#collaspe{{code}}" aria-expanded="false">
                            <th><b>Total</b></th>
                            <td class="">
                                <div class=" text-center">
                                    <?php $total_base =  calculate_total_base_price($flight_fares, $passenger_counts) ?>
                                    <b><?php echo $total_base ?></b>
                                </div>
                            </td>
                            <td class="">
                                <div class=" text-center">
                                    <?php $total_tax =  calculate_total_tax_price($flight_fares, $passenger_counts) ?>
                                    <b><?php echo $total_tax ?></b>
                                </div>
                            </td>
                            <td class="">
                                <div class=" text-center">
                                    <b><?php echo ($total_base + $total_tax) ?></b>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- baggaes -->
<div class="container">
    <div>
        <h2><?php _e("Baggage", TEXT_DOMAIN) ?></h2>
        <?php foreach ($baggages as $bag) { ?>
            <div class="row">
                <div class="col-md-12 p-0 m-0">
                    <div class="table-responsive">
                        <table class="table table-collapse">
                            <caption class="">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><?php echo format_city_pairs($bag['cityPair']) ?></span>
                                    <img class="bagImg" src="<?php echo GFA_HUB_FLIGHTS_ASSETS_IMAGES . '1573537-200.png' ?>" width="40px" alt="baggage icon">
                                </div>
                            </caption>
                            <thead>
                                <tr>
                                    <th><b><?php _e("Baggage", TEXT_DOMAIN) ?></b></th>
                                    <th><b><?php _e("Check In", TEXT_DOMAIN) ?></b></th>
                                    <th><b><?php _e("Cabin", TEXT_DOMAIN) ?></b></th>
                                </tr>
                            </thead>
                            <tbody id="handlebarsFundTable">
                                <tr class="collapse-control collapsed  " data-toggle="collapse"
                                    data-target="#collaspe{{code}}" aria-expanded="false">
                                    <th><?php echo $bag['paxType'] ?></th>
                                    <td class="">
                                        <div class=" text-center">
                                            <b><?php echo $bag['checkInBag'] ?? "N/A" ?></b>
                                        </div>
                                    </td>
                                    <td class="">
                                        <div class=" text-center">
                                            <b><?php echo $bag['cabinBag'] ?? "N/A" ?></b>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<?php if (!in_array(get_flight_status($order_id), ['cancelled', 'confirmed'])) { ?>
    <div class="container">
        <button class="btn" data-orderid="<?php echo $order_id ?>" id="flight-fares">Get Fares</button>
        <div id="flight-cat16"></div>
    </div>
    <div class="flight-actions">
        <div class="woocommerce-notices-wrapper"></div>
        <div id="gfa-hub-loader" class="loader" style="display: none;"></div>
        <div class="cancel-flight">
            <button class="btn btn-outline-danger" id="cancel-flight"><?php _e("Cancel", TEXT_DOMAIN) ?></button>
            <?php if ($ticket_time_limit) { ?> <!-- Example: 2024-11-26T15:30:00 -->
                <?php $date = new DateTime($ticket_time_limit); ?>
                <?php if ($date > new DateTime()) { ?>
                    <p>
                        <?php _e("Flight will be cancelled automatically after ", TEXT_DOMAIN); ?>
                    <div id="countdown-timer" data-ticket-limit="<?php echo esc_attr($ticket_time_limit); ?>"></div>
                    </p>
                <?php } ?>
            <?php } ?>
        </div>
        <div class="confirm-flight">
            <button class="btn btn-outline-primary" id="confirm-flight"><?php _e('Confirm', TEXT_DOMAIN) ?></button>
            <span><?php _e("Please if you confirm the flight, you will not be able to cancel it.", TEXT_DOMAIN) ?></span>
        </div>
    </div>
    <input id="flight-order-id" hidden value="<?php echo $booking_id ?>" />
    <input id="order-id" hidden value="<?php echo $order_id ?>" />
    <input id="guest-token" hidden value="<?php echo $token ?>" />
<?php } ?>