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
 * Adds new instance of type trainingcredits to specified course.
 *
 * @package   enrol_trainingcredits
 * @category  enrol
 * @author    Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright 2015 Valery Fremaux {@link http://edunao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/enrol/trainingcredits/edit_form.php');
require_once($CFG->dirroot.'/enrol/trainingcredits/locallib.php');

$courseid = required_param('courseid', PARAM_INT);
$instanceid = optional_param('id', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);
$cohortid = optional_param('cohortid', 0, PARAM_INT);
$return = optional_param('return', '', PARAM_TEXT);

// Security.

require_login($course);
require_capability('moodle/course:enrolconfig', $context);
require_capability('enrol/trainingcredits:config', $context);

$PAGE->set_url('/enrol/trainingcredits/edit.php', array('courseid' => $course->id, 'id' => $instanceid));
$PAGE->set_pagelayout('admin');

$returnurl = new moodle_url('/enrol/instances.php', array('id' => $course->id));

if (!enrol_is_enabled('trainingcredits')) {
    redirect($returnurl);
}

$enrol = enrol_get_plugin('trainingcredits');

if ($instanceid) {
    $instance = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'trainingcredits', 'id' => $instanceid), '*', MUST_EXIST);

} else {
    // No instance yet, we have to add new instance.
    if (!$enrol->get_newinstance_link($course->id)) {
        redirect($returnurl);
    }
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', array('id' => $course->id)));
    $instance = new stdClass();
    $instance->id         = null;
    $instance->courseid   = $course->id;
    $instance->enrol      = 'trainingcredits';
    $instance->customint1 = ''; // Cohort id restriction.
    $instance->customint2 = 0;  // Optional group id.
    $instance->customint3 = 1;  // course cost
    $instance->customint4 = 0;  // Max enrolled
    $instance->customint5 = 0;  // Welcome message
}

// Try and make the manage instances node on the navigation active.
$courseadmin = $PAGE->settingsnav->get('courseadmin');
if ($courseadmin && $courseadmin->get('users') && $courseadmin->get('users')->get('manageinstances')) {
    $courseadmin->get('users')->get('manageinstances')->make_active();
}

$mform = new enrol_trainingcredits_edit_form(null, array($instance, $enrol, $course));

if ($mform->is_cancelled()) {
    redirect($returnurl);

} elseif ($data = $mform->get_data()) {
    if ($data->id) {
        if ($data->roleid != $instance->roleid) {
            // The sync script can only add roles, for perf reasons it does not modify them.
            role_unassign_all(array('contextid' => $context->id, 'roleid' => $instance->roleid, 'component' => 'enrol_trainingcredits', 'itemid' => $instance->id));
        }
        $instance->name         = $data->name;
        $instance->status       = $data->status;
        $instance->roleid       = $data->roleid;
        $instance->customint1   = $data->customint1; // cohort restriction
        $instance->customint2   = $data->customint2; // optional groupid
        $instance->customint3   = $data->customint3; // course cost
        $instance->customint4   = $data->customint4; // max enrolled
        $instance->customint5   = $data->customint5; // Welcome message
        $instance->timemodified = time();
        $DB->update_record('enrol', $instance);
    }  else {
        $enrolid = $enrol->add_instance($course, array('name' => $data->name, 'status' => $data->status, 'customint1' => $data->customint1, 'roleid' => $data->roleid, 'customint2' => $data->customint2, 'customint3' => $data->customint3, 'customint4' => $data->customint4, 'customint5' => $data->customint5));

        $params = array(
            'context' => context_course::instance($course->id),
            'objectid' => $enrolid,
            'other' => array(
                'courseid' => $course->id,
            ),
        );
        $event = \enrol_trainingcredits\event\trainingcredits_created::create($params);
        $event->trigger();
    }
    redirect($returnurl);
}

$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('pluginname', 'enrol_trainingcredits'));

$data = new StdClass();
$data->return = $return;

if ($cohortid) {
    $data->customint1 = $cohortid;
    $cohortname = $DB->get_field('cohort', 'name', array('id' => $cohortid));
    $data->name = $course->shortname.' - '.$cohortname;
}
$mform->set_data($data);

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
