<?php

$ADMIN->add('users', new admin_externalpage('enroltrainingcreditssettings', get_string('settings'),
        new moodle_url('/enrol/trainingcredits/usercredits.php'), 'enrol/trainingcredits:managecredits'));
