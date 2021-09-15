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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/selector/lib.php');
require_once($CFG->dirroot . '/enrol/locallib.php');
require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * @package   enrol_trainingcredits
 * @category  enrol
 * @author    Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright 2015 Valery Fremaux {@link http://www.mylearningfactory.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function trainingcredits_notify($recipient, $message){
}

/**
 * A bulk operation for the manual enrolment plugin to edit selected users.
 *
 * @copyright 2011 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_trainingcredits_editselectedusers_operation extends enrol_bulk_enrolment_operation {

    /**
     * Returns the title to display for this bulk operation.
     *
     * @return string
     */
    public function get_title() {
        return get_string('editselectedusers', 'enrol_trainingcredits');
    }

    /**
     * Returns the identifier for this bulk operation. This is the key used when the plugin
     * returns an array containing all of the bulk operations it supports.
     */
    public function get_identifier() {
        return 'editselectedusers';
    }

    /**
     * Processes the bulk operation request for the given userids with the provided properties.
     *
     * @param course_enrolment_manager $manager
     * @param array $userids
     * @param stdClass $properties The data returned by the form.
     */
    public function process(course_enrolment_manager $manager, array $users, stdClass $properties) {
        global $DB, $USER;

        if (!has_capability("enrol/trainingcredits:manage", $manager->get_context())) {
            return false;
        }

        // Get all of the user enrolment id's.
        $ueids = array();
        $instances = array();
        foreach ($users as $user) {
            foreach ($user->enrolments as $enrolment) {
                $ueids[] = $enrolment->id;
                if (!array_key_exists($enrolment->id, $instances)) {
                    $instances[$enrolment->id] = $enrolment;
                }
            }
        }

        // Check that each instance is manageable by the current user.
        foreach ($instances as $instance) {
            if (!$this->plugin->allow_manage($instance)) {
                return false;
            }
        }

        // Collect the known properties.
        $status = $properties->status;
        $timestart = $properties->timestart;
        $timeend = $properties->timeend;

        list($ueidsql, $params) = $DB->get_in_or_equal($ueids, SQL_PARAMS_NAMED);

        $updatesql = array();
        if ($status == ENROL_USER_ACTIVE || $status == ENROL_USER_SUSPENDED) {
            $updatesql[] = 'status = :status';
            $params['status'] = (int)$status;
        }
        if (!empty($timestart)) {
            $updatesql[] = 'timestart = :timestart';
            $params['timestart'] = (int)$timestart;
        }
        if (!empty($timeend)) {
            $updatesql[] = 'timeend = :timeend';
            $params['timeend'] = (int)$timeend;
        }
        if (empty($updatesql)) {
            return true;
        }

        // Update the modifierid.
        $updatesql[] = 'modifierid = :modifierid';
        $params['modifierid'] = (int)$USER->id;

        // Update the time modified.
        $updatesql[] = 'timemodified = :timemodified';
        $params['timemodified'] = time();

        // Build the SQL statement.
        $updatesql = join(', ', $updatesql);
        $sql = "UPDATE {user_enrolments}
                   SET $updatesql
                 WHERE id $ueidsql";

        if ($DB->execute($sql, $params)) {
            foreach ($users as $user) {
                foreach ($user->enrolments as $enrolment) {
                    $enrolment->courseid  = $enrolment->enrolmentinstance->courseid;
                    $enrolment->enrol     = 'manual';
                    // Trigger event.
                    $event = \core\event\user_enrolment_updated::create(
                            array(
                                'objectid' => $enrolment->id,
                                'courseid' => $enrolment->courseid,
                                'context' => context_course::instance($enrolment->courseid),
                                'relateduserid' => $user->id,
                                'other' => array('enrol' => 'manual')
                                )
                            );
                    $event->trigger();
                }
            }
            // Delete cached course contacts for this course because they may be affected.
            cache::make('core', 'coursecontacts')->delete($manager->get_context()->instanceid);
            return true;
        }

        return false;
    }

    /**
     * Returns a enrol_bulk_enrolment_operation extension form to be used
     * in collecting required information for this operation to be processed.
     *
     * @param string|moodle_url|null $defaultaction
     * @param mixed $defaultcustomdata
     * @return enrol_manual_editselectedusers_form
     */
    public function get_form($defaultaction = null, $defaultcustomdata = null) {
        global $CFG;
        require_once($CFG->dirroot.'/enrol/trainingcredits/bulkchangeforms.php');
        return new enrol_trainingcredits_editselectedusers_form($defaultaction, $defaultcustomdata);
    }
}


/**
 * A bulk operation for the manual enrolment plugin to delete selected users enrolments.
 *
 * @copyright 2011 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_trainingcredits_deleteselectedusers_operation extends enrol_bulk_enrolment_operation {

    protected $creditbackoption = false;

    /**
     * Returns the title to display for this bulk operation.
     *
     * @return string
     */
    public function get_identifier() {
        if ($this->creditbackoption) {
            return 'deleteandcreditbackselectedusers';
        }
        return 'deleteselectedusers';
    }

    /**
     * Returns the identifier for this bulk operation. This is the key used when the plugin
     * returns an array containing all of the bulk operations it supports.
     *
     * @return string
     */
    public function get_title() {
        if ($this->creditbackoption) {
            return get_string('deleteandcreditbackselectedusers', 'enrol_trainingcredits');
        }
        return get_string('deleteselectedusers', 'enrol_trainingcredits');
    }

    /**
     * Returns a enrol_bulk_enrolment_operation extension form to be used
     * in collecting required information for this operation to be processed.
     *
     * @param string|moodle_url|null $defaultaction
     * @param mixed $defaultcustomdata
     * @return enrol_manual_editselectedusers_form
     */
    public function get_form($defaultaction = null, $defaultcustomdata = null) {
        global $CFG;
        require_once($CFG->dirroot.'/enrol/trainingcredits/bulkchangeforms.php');
        if (!array($defaultcustomdata)) {
            $defaultcustomdata = array();
        }
        $defaultcustomdata['title'] = $this->get_title();
        $defaultcustomdata['message'] = get_string('confirmbulkdeleteenrolment', 'enrol_trainingcredits');
        $defaultcustomdata['button'] = get_string('unenrolusers', 'enrol_trainingcredits');
        return new enrol_trainingcredits_deleteselectedusers_form($defaultaction, $defaultcustomdata);
    }

    /**
     * Processes the bulk operation request for the given userids with the provided properties.
     *
     * @global moodle_database $DB
     * @param course_enrolment_manager $manager
     * @param array $userids
     * @param stdClass $properties The data returned by the form.
     */
    public function process(course_enrolment_manager $manager, array $users, stdClass $properties) {
        global $DB, $COURSE;

        if (!has_capability("enrol/trainingcredits:unenrol", $manager->get_context())) {
            return false;
        }
        foreach ($users as $user) {
            foreach ($user->enrolments as $enrolment) {
                $plugin = $enrolment->enrolmentplugin;
                $instance = $enrolment->enrolmentinstance;
                if ($plugin->allow_unenrol_user($instance, $enrolment)) {
                    $plugin->unenrol_user($instance, $user->id);
                }
                if ($instance->enrol == 'trainingcredits' && $this->creditbackoption) {
                    // Credit the user back with the course cost.
                    $credit = $DB->get_record('enrol_trainingcredits', ['userid' => $user->id]);
                    if ($credit) {
                        $credit->coursecredit += $instance->customint3;
                        $DB->update_record('enrol_trainingcredits', $credit);
                    } else {
                        $credit = new StdClass;
                        $credit->userid = $user->id;
                        $credit->coursecredits = $instance->customint3;
                        $DB->insert_record('enrol_trainingcredits', $credit);
                    }

                    $context = context_course::instance($COURSE->id);
                    $event = \enrol_trainingcredits\event\trainingcredits_creditedback::create(
                            array(
                                'objectid' => $instance->id,
                                'courseid' => $COURSE->id,
                                'context' => $context,
                                'relateduserid' => $user->id
                                )
                            );
                    $event->trigger();
                }
            }
        }
        return true;
    }

    public function set_creditback_option($option) {
        $this->creditbackoption = $option;
    }
}

class enrol_trainingcredits_enrol_form extends moodleform {
    protected $instance;
    protected $toomany = false;

    /**
     * Overriding this function to get unique form id for multiple self enrolments.
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $formid = $this->_customdata->id.'_'.get_class($this);
        return $formid;
    }

    public function definition() {
        global $USER, $OUTPUT, $CFG, $DB;

        $mform = $this->_form;
        $instance = $this->_customdata;
        $this->instance = $instance;
        $plugin = enrol_get_plugin('trainingcredits');

        $heading = $plugin->get_instance_name($instance);
        $mform->addElement('header', 'selfheader', $heading);

        if ($instance->password) {
            // Change the id of self enrolment key input as there can be multiple self enrolment methods.
            $mform->addElement('password', 'enrolpassword', get_string('password', 'enrol_trainingcredits'),
                    array('id' => 'enrolpassword_'.$instance->id));
            $context = context_course::instance($this->instance->courseid);
            $keyholders = get_users_by_capability($context, 'enrol/self:holdkey', user_picture::fields('u'));
            $keyholdercount = 0;
            foreach ($keyholders as $keyholder) {
                $keyholdercount++;
                if ($keyholdercount === 1) {
                    $mform->addElement('static', 'keyholder', '', get_string('keyholder', 'enrol_self'));
                }
                $keyholdercontext = context_user::instance($keyholder->id);
                if ($USER->id == $keyholder->id || has_capability('moodle/user:viewdetails', context_system::instance()) ||
                        has_coursecontact_role($keyholder->id)) {
                    $profilelink = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $keyholder->id . '&amp;course=' .
                    $this->instance->courseid . '">' . fullname($keyholder) . '</a>';
                } else {
                    $profilelink = fullname($keyholder);
                }
                $profilepic = $OUTPUT->user_picture($keyholder, array('size' => 35, 'courseid' => $this->instance->courseid));
                $mform->addElement('static', 'keyholder'.$keyholdercount, '', $profilepic . $profilelink);
            }

        } else {
            $mform->addElement('static', 'nokey', '', get_string('nopassword', 'enrol_trainingcredits'));
        }

        $credits = $DB->get_record('enrol_trainingcredits', ['userid' => $USER->id]);
        if ($credits && $credits->coursecredits >= $instance->customint3) {
            $a = new StdClass;
            $a->cost = $instance->customint3;
            $a->usercredits = $credits->coursecredits;
            $a->creditsleft = $credits->coursecredits - $instance->customint3;
            $mform->addElement('static', 'credit_help', '', get_string('enrolmentconfirmation', 'enrol_trainingcredits', $a));

            $this->add_action_buttons(false, get_string('enrolme', 'enrol_trainingcredits'));
        } else {
            $a = new StdClass;
            $a->required = $instance->customint3 - @$credits->coursecredits;
            $a->usercredits = 0 + @$credits->coursecredits;
            $mform->addElement('static', 'credit_help', '', get_string('nocreditsleft', 'enrol_trainingcredits', $a));
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $instance->courseid);

        $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
        $mform->setDefault('instance', $instance->id);
    }

    public function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);
        $instance = $this->instance;

        if ($this->toomany) {
            $errors['notice'] = get_string('error');
            return $errors;
        }

        if ($instance->password) {
            if ($data['enrolpassword'] !== $instance->password) {
                if ($instance->customint1) {
                    // Check group enrolment key.
                    if (!enrol_self_check_group_enrolment_key($instance->courseid, $data['enrolpassword'])) {
                        // We can not hint because there are probably multiple passwords.
                        $errors['enrolpassword'] = get_string('passwordinvalid', 'enrol_self');
                    }

                } else {
                    $plugin = enrol_get_plugin('self');
                    if ($plugin->get_config('showhint')) {
                        $hint = core_text::substr($instance->password, 0, 1);
                        $errors['enrolpassword'] = get_string('passwordinvalidhint', 'enrol_self', $hint);
                    } else {
                        $errors['enrolpassword'] = get_string('passwordinvalid', 'enrol_self');
                    }
                }
            }
        }

        return $errors;
    }
}
