<?php

function get_airport_codes_from_csv()
{
    $file_paths = [
        GFA_HUB_FLIGHTS_PATH . '/assets/flights-data/airport-codes-1.csv',
        GFA_HUB_FLIGHTS_PATH . '/assets/flights-data/airport-codes-2.csv'
    ];

    $filtered_airports = [];

    foreach ($file_paths as $file_path) {
        if (!file_exists($file_path)) {
            return [];
        }

        // Open the current CSV file
        if (($handle = fopen($file_path, 'r')) !== false) {
            // Skip the header row
            fgetcsv($handle);

            // Process each row
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) >= 4 && !empty($data[0])) {
                    $iata_code = $data[2];
                    $airport_name = $data[3];
                    $country = $data[4];
                    $city = $data[1];

                    $filtered_airports[] = [
                        'name' => "$country - $city - $airport_name",
                        'iata_code' => $iata_code
                    ];
                }
            }
            // Close the file
            fclose($handle);
        }
    }

    return $filtered_airports;
}

function get_nationalities_code()
{
    $file_path = GFA_HUB_FLIGHTS_PATH . '/assets/flights-data/passenger-nationality.json';
    if (!file_exists($file_path)) {
        return [];
    }

    // Read and decode the JSON file
    $json_data = file_get_contents($file_path);
    $nationalities = json_decode($json_data, true);

    return $nationalities ?: [];
}

function get_flight_status($order_id)
{
    return get_post_meta($order_id, 'flight_status', true) ?: "";
}

function format_date_of_birth($dateString)
{
    $date = new DateTime($dateString);

    // Get the day with ordinal suffix
    $day = $date->format('j');
    $suffix = date('S', strtotime($dateString)); // Get the ordinal suffix (st, nd, rd, th)

    // Format the month and year
    $month = $date->format('M'); // Short month name
    $year = $date->format('Y');

    return "{$day}{$suffix} {$month} {$year}";
}

function convert_mins_to_hours($mins)
{
    $hours = intdiv($mins, 60);
    $minutes = $mins % 60;

    return sprintf("%dh %02dm", $hours, $minutes);
}

function format_city_pairs($code)
{
    if (strlen($code) === 6) {
        return substr($code, 0, 3) . '-' . substr($code, 3, 3);
    }
    return $code;
}

function calculate_total_base_price($fares, $passengerCounts)
{
    $totalBasePrice = 0;

    foreach ($fares as $fare) {
        $paxType = $fare['paxType'];
        $baseFare = $fare['baseFare'];
        $passengerCount = $passengerCounts[$paxType] ?? 0;

        // Add to total base price
        $totalBasePrice += $baseFare * $passengerCount;
    }

    return round($totalBasePrice, 2);
}

function calculate_total_tax_price($fares, $passengerCounts)
{
    $totalTaxPrice = 0;

    foreach ($fares as $fare) {
        $paxType = $fare['paxType'];
        $passengerCount = $passengerCounts[$paxType] ?? 0;

        // Sum up all taxes for this fare
        $taxSum = array_reduce($fare['taxes'], function ($carry, $tax) {
            return $carry + $tax['amt'];
        }, 0);

        // Add to total tax price
        $totalTaxPrice += $taxSum * $passengerCount;
    }

    return round($totalTaxPrice, 2);
}

function calculate_total_tax($taxes)
{
    $total = array_reduce($taxes, function ($carry, $tax) {
        return $carry + $tax['amt'];
    }, 0);

    // Round the total to two decimal places
    return round($total, 2);
}
