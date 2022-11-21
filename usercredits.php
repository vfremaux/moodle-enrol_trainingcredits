<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Adds instance form
 *
 * @package   enrol_trainingcredits
 * @category  enrol
 * @author    Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright 2015 Valery Fremaux {@link http://www.mylearningfactory.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/enrol/trainingcredits/locallib.php');
require_once($CFG->dirroot.'/enrol/trainingcredits/user_credits_form.php');

$returnid = optional_param('returnid', 0, PARAM_INT);

$context = context_system::instance();
$url = new moodle_url('/enrol/trainingcredits/usercredits.php');
$PAGE->set_url($url);
$PAGE->set_context($context);

// remember the current time as the time any responses were submitted
// (so as to make sure students don't get penalized for slow processing on this page)
$timestamp = time();

// Security.

require_login();
require_capability('enrol/trainingcredits:managecredits', $context);

/// Get user limitations

$PAGE->set_heading(get_string('editusercredits', 'enrol_trainingcredits'));
$PAGE->navbar->add(get_string('editusercredits', 'enrol_trainingcredits'));

echo $OUTPUT->header();

$currenttab = 'edit';
$mode = 'setattempts';

// include('tabs.php');

$mform = new User_Credits_Form(20, optional_param('from', 0, PARAM_INT), ['returnid' => $returnid]);

if (!$mform->is_cancelled() && $data = $mform->get_data()) {
    if (isset($data->filter['setfilter'])) {
        $SESSION->namefilter = stripslashes($data->filter['namefilter']);
        $mform = new User_Credits_Form(20, optional_param('from', 0, PARAM_INT), $CFG->wwwroot.'/enrol/trainingcredits/usercredits.php');
    } else if (isset($data->filter['clearfilter'])) {
        unset($SESSION->namefilter);
        $data->filter['namefilter'] = '';
        $_POST['filter']['namefilter'] = '';
        $mform = new User_Credits_Form(20, optional_param('from', 0, PARAM_INT));
    } else {
        $selection = preg_grep('/^credit/', array_keys((array)$data));
        $selection = preg_replace('/^credit/', '', $selection);

        if (!empty($selection)) {
            foreach ($selection as $userid) {
                $credit = new StdClass();
                $credit->userid = $userid;
                $datakey = 'credit'.$userid;
                $credit->coursecredits = $data->$datakey;
                if ($rec = $DB->get_record_select('enrol_trainingcredits', " userid = ?  ", array($userid))) {
                    $DB->update_record('enrol_trainingcredits', $credit);
                } else {
                    $DB->insert_record('enrol_trainingcredits', $credit);
                }
            }
        }
    }
}

echo $OUTPUT->heading(get_string('setusercredits', 'enrol_trainingcredits'));
echo $OUTPUT->box_start();

$mform->display();
echo $OUTPUT->box_end();

if (!empty($returnid)) {
    echo '<center>';
    $returnurl = new moodle_url('/course/view.php', ['id' => $returnid]);
    echo '<a href="'.$returnurl.'" class="btn btn-primary">'.get_string('back', 'enrol_trainingcredits').'</a>';
    echo '</center>';
}

echo $OUTPUT->footer();
