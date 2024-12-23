<?php
$passenger_num = $type . '_' . $i;
$nationalities = get_nationalities_code();

$type_names = array(
    'adt' => "Adult",
    'chd' => "Child",
    'inf' => "Infant"
);

?>
<h5><?php _e($type_names[$type] . " Number " . $i, TEXT_DOMAIN) ?></h5>
<div class="passengerForm">
    <div class="d-flex flex-column gap-3">
        <div class="PassengerData">
            <div class="row p-0 m-0 gap-0 gap-md-1">
                <div class="PassengerTitle mb-3 col-12 p-0  flex-fill">
                    <label for="FirstName" class="form-label mb-0"><?php _e("Title", TEXT_DOMAIN) ?></label>
                    <select class="form-select" name="passenger_title_<?php echo $passenger_num ?>" aria-label="Default select example" required>
                        <option value="Mr">MR</option>
                        <option value="MISS">MISS</option>
                        <option value="MS">MS</option>
                    </select>
                </div>
                <div class="mb-3 col-12 p-0 col-md-5 flex-fill">
                    <label for="FirstName" class="form-label mb-0"><?php _e("First Name", TEXT_DOMAIN) ?></label>
                    <input name="passenger_first_name_<?php echo $passenger_num ?>" type="text" class="form-control" id="FirstName" required>
                </div>
                <div class="mb-3 col-12 p-0 col-md-5 flex-fill">
                    <label for="LastName" class="form-label  mb-0"><?php _e("Last Name", TEXT_DOMAIN) ?></label>
                    <input name="passenger_last_name_<?php echo $passenger_num ?>" type="text" class="form-control" id="LastName" required>
                </div>
                <div class="mb-3 col-12 p-0">
                    <label for="email" class="form-label  mb-0"><?php _e("Email", TEXT_DOMAIN) ?></label>
                    <input name="passenger_email_<?php echo $passenger_num ?>" type="email" class="form-control" id="email" required>
                </div>

                <div class="mb-3 col-12 p-0 col-md-5 flex-fill">
                    <label for="dbo" class="form-label  mb-0"><?php _e("Gender", TEXT_DOMAIN) ?></label>
                    <select class="form-select" name="passenger_gender_<?php echo $passenger_num ?>" aria-label="Default select example" required>
                        <option value="M"><?php _e("Male", TEXT_DOMAIN) ?></option>
                        <option value="F"><?php _e("Female", TEXT_DOMAIN) ?></option>
                    </select>
                </div>

                <div class="mb-3 col-12 p-0 col-md-5 flex-fill">
                    <label for="dob" class="form-label mb-0"><?php _e("Birth Date", TEXT_DOMAIN) ?></label>
                    <input name="passenger_dob_<?php echo $passenger_num ?>" type="date" class="form-control" id="dob" required>
                </div>

                <input hidden name="passenger_pax_<?php echo $passenger_num ?>" value="<?php echo $type ?>" />

                <div class="mb-3 col-12  flex-fill p-0">
                    <label for="areaCode" class="form-label  mb-0"><?php _e("Area Code", TEXT_DOMAIN) ?></label>
                    <input name="passenger_area_code_<?php echo $passenger_num ?>" type="text" class="form-control" id="areaCode" required>
                </div>

                <div class="mb-3 col-12  flex-fill p-0">
                    <label for="mobile" class="form-label  mb-0"><?php _e("Mobile", TEXT_DOMAIN) ?></label>
                    <input name="passenger_mobile_<?php echo $passenger_num ?>" type="text" class="form-control" id="mobile" required>
                </div>

                <div class="mb-3 col-12 p-0 col-md-5 flex-fill">
                    <label for="nationality" class="form-label mb-0"><?php _e("Nationality", TEXT_DOMAIN) ?></label>
                    <select name="passenger_nationality_<?php echo $passenger_num ?>" class="form-control" id="nationality" required>
                        <?php foreach ($nationalities as $n) { ?>
                            <option value="<?php echo $n['Code'] ?>"> <?php echo $n['Name'] ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="mb-3 col-12 p-0 col-md-5 flex-fill">
                    <label for="passportNumber" class="form-label mb-0"><?php _e("Passport Number", TEXT_DOMAIN) ?></label>
                    <input name="passenger_passport_number_<?php echo $passenger_num ?>" type="text" class="form-control" id="passportNumber" required>
                </div>

                <div class="mb-3 col-12 p-0 col-md-5 flex-fill">
                    <label for="passportDOI"><?php _e("Passport Date of Issue:", TEXT_DOMAIN) ?></label>
                    <input name="passenger_passport_doi_<?php echo $passenger_num ?>" type="date" id="passportDOI" class="form-control" required>
                </div>
                <div class="mb-3 col-12 p-0 col-md-5 flex-fill">
                    <label for="passportDOE"><?php _e("Passport Date of Expired:", TEXT_DOMAIN) ?></label>
                    <input name="passenger_passport_doe_<?php echo $passenger_num ?>" type="date" id="passportDOE" class="form-control" required>
                </div>

                <div class="mb-3 col-12 p-0 col-md-5 flex-fill">
                    <label for="passportIC"><?php _e("Passport Issued Country:", TEXT_DOMAIN) ?></label>
                    <select name="passenger_passport_ic_<?php echo $passenger_num ?>" id="passportIC" class="form-control" required>
                        <?php foreach ($nationalities as $n) { ?>
                            <option value="<?php echo $n['Code'] ?>"> <?php echo $n['Name'] ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>