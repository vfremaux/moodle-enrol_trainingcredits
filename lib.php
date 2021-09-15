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
 * Manual enrolment plugin main library file.
 *
 * @package    enrol_trainingcredits
 * @copyright  2016 Valery Fremaux {http://www.mylearningfactory.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/enrol/manual/lib.php');

class enrol_trainingcredits_plugin extends enrol_manual_plugin {

    public function allow_enrol(stdClass $instance) {
        // As a first approach, admins or teachers may NOT spend credits of users.
        return false;
    }

    public function allow_unenrol(stdClass $instance) {
        // Users may not unenrol people having spent the credits.
        return false;
    }

    public function allow_manage(stdClass $instance) {
        // Users with manage cap may tweak cost, period and other params.
        return true;
    }

    /**
     * Returns link to manual enrol UI if exists.
     * Does the access control tests automatically.
     *
     * @param stdClass $instance
     * @return moodle_url
     */
    public function get_manual_enrol_link($instance) {
        $name = $this->get_name();
        if ($instance->enrol !== $name) {
            throw new coding_exception('invalid enrol instance!');
        }

        if (!enrol_is_enabled($name)) {
            return null;
        }

        $context = context_course::instance($instance->courseid, MUST_EXIST);

        if (!has_capability('enrol/trainingcredits:enrol', $context)) {
            // Note: manage capability not used here because it is used for editing
            // of existing enrolments which is not possible here.
            return null;
        }

        return new moodle_url('/enrol/trainingcredits/manage.php', array('enrolid' => $instance->id, 'id' => $instance->courseid));
    }

    /**
     * Returns enrolment instance manage link.
     *
     * By defaults looks for manage.php file and tests for manage capability.
     *
     * @param navigation_node $instancesnode
     * @param stdClass $instance
     * @return moodle_url;
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        if ($instance->enrol !== 'trainingcredits') {
             throw new coding_exception('Invalid enrol instance type!');
        }

        $context = context_course::instance($instance->courseid);
        if (has_capability('enrol/trainingcredits:config', $context)) {
            $params = ['courseid' => $instance->courseid, 'id' => $instance->id, 'type' => 'trainingcredits'];
            $managelink = new moodle_url('/enrol/editinstance.php', $params);
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
        }
    }

    /**
     * Returns edit icons for the page with list of instances.
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT, $COURSE;

        if ($instance->enrol !== 'trainingcredits') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);

        $icons = array();

        if (has_capability('enrol/trainingcredits:managecredits', $context)) {
            $managelink = new moodle_url("/enrol/trainingcredits/usercredits.php", array('returnid' => $COURSE->id));
            $icons[] = $OUTPUT->action_icon($managelink, new pix_icon('t/enrolusers', get_string('backmanageusercredits', 'enrol_trainingcredits'), 'core', array('class'=>'iconsmall')));
        }
        if (has_capability('enrol/trainingcredits:config', $context)) {
            $editlink = new moodle_url("/enrol/editinstance.php", array('courseid' => $instance->courseid, 'id' => $instance->id, 'type' => 'trainingcredits'));
            $icons[] = $OUTPUT->action_icon($editlink, new pix_icon('t/edit', get_string('edit'), 'core',
                    array('class' => 'iconsmall')));
        }

        return $icons;
    }


    /**
     * Add new instance of enrol plugin with default settings.
     * @param stdClass $course
     * @return int id of new instance, null if can not be created
     */
    public function add_default_instance($course) {
        $expirynotify = $this->get_config('expirynotify', 0);
        if ($expirynotify == 2) {
            $expirynotify = 1;
            $notifyall = 1;
        } else {
            $notifyall = 0;
        }
        $fields = array(
            'status'          => $this->get_config('status'),
            'roleid'          => $this->get_config('roleid', 0),
            'enrolperiod'     => $this->get_config('enrolperiod', 0),
            'expirynotify'    => $expirynotify,
            'notifyall'       => $notifyall,
            'expirythreshold' => $this->get_config('expirythreshold', 86400),
            'creditcost'      => $this->get_config('creditcost', 1),
        );
        return $this->add_instance($course, $fields);
    }

    /**
     * Return true if we can add a new instance to this course.
     *
     * @param int $courseid
     * @return boolean
     */
    public function can_add_instance($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/trainingcredits:config', $context)) {
            return false;
        }

        return true;
    }

    /**
     * Add elements to the edit instance form.
     *
     * @param stdClass $instance
     * @param MoodleQuickForm $mform
     * @param context $context
     * @return bool
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
        global $CFG, $COURSE, $DB;

        $coursecontext = context_course::instance($COURSE->id);

        $enrol = enrol_get_plugin('trainingcredits');

        $groups = array(0 => get_string('none'));
        foreach (groups_get_all_groups($COURSE->id) as $group) {
            $groups[$group->id] = format_string($group->name, true, array('context' => $coursecontext));
        }

        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'), 'size="80"');
        $mform->setType('name', PARAM_TEXT);

        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        $mform->addElement('select', 'status', get_string('status', 'enrol_trainingcredits'), $options);

        if ($instance->id) {
            if ($cohort = $DB->get_record('cohort', array('id' => $instance->customint1))) {
                $cohorts = array($instance->customint1=>format_string($cohort->name, true, array('context' => context::instance_by_id($cohort->contextid))));
            } else {
                $cohorts = array($instance->customint1 => get_string('error'));
            }
            $mform->addElement('select', 'customint1', get_string('cohort', 'cohort'), $cohorts);
            $mform->setConstant('customint1', $instance->customint1);
            $mform->hardFreeze('customint1', $instance->customint1);

        } else {
            $cohorts = array('' => get_string('choosedots'));
            list($sqlparents, $params) = $DB->get_in_or_equal($context->get_parent_context_ids());
            $sql = "SELECT id, name, idnumber, contextid
                      FROM {cohort}
                     WHERE contextid $sqlparents
                  ORDER BY name ASC, idnumber ASC";
            $rs = $DB->get_recordset_sql($sql, $params);

            foreach ($rs as $c) {
                $cohortcontext = context::instance_by_id($c->contextid);
                if (!has_capability('moodle/cohort:view', $cohortcontext)) {
                    continue;
                }
                $cohorts[$c->id] = format_string($c->name);
            }
            $rs->close();
            $mform->addElement('select', 'customint1', get_string('cohort', 'cohort'), $cohorts);
        }

        $roles = get_assignable_roles($context);
        $roles[0] = get_string('none');
        $roles = array_reverse($roles, true); // Descending default sortorder.
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_trainingcredits'), $roles);
        $mform->setDefault('roleid', $enrol->get_config('roleid'));
        if ($instance->id and !isset($roles[$instance->roleid])) {
            if ($role = $DB->get_record('role', array('id' => $instance->roleid))) {
                $roles = role_fix_names($roles, $coursecontext, ROLENAME_ALIAS, true);
                $roles[$instance->roleid] = role_get_name($role, $context);
            } else {
                $roles[$instance->roleid] = get_string('error');
            }
        }

        $mform->addElement('select', 'customint2', get_string('addgroup', 'enrol_trainingcredits'), $groups);

        $mform->addElement('text', 'customint3', get_string('creditcost', 'enrol_trainingcredits'));
        $mform->setType('customint3', PARAM_INT);

        $mform->addElement('text', 'customint4', get_string('maxenroled', 'enrol_trainingcredits'), array('optional' => true));
        $mform->setType('customint4', PARAM_INT);

        $mform->addelement('checkbox', 'customint5', get_string('sendwelcomemessage', 'enrol_trainingcredits'));
        $mform->setType('customint5', PARAM_BOOL);

    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     * @return void
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        global $DB;

        $errors = array();

        $params = array('roleid' => $data['roleid'], 'customint1' => $data['customint1'], 'courseid' => $data['courseid'], 'id' => $data['id']);
        $select = "roleid = :roleid AND customint1 = :customint1 AND courseid = :courseid AND enrol = 'trainingcredits' AND id <> :id";
        if ($DB->record_exists_select('enrol', $select, $params)) {
            $errors['roleid'] = get_string('instanceexists', 'enrol_trainingcredits');
        }

        return $errors;
    }

    /**
     * Add new instance of enrol plugin.
     * @param stdClass $course
     * @param array instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = null) {
        global $DB;

        if ($DB->record_exists('enrol', array('courseid' => $course->id, 'enrol' => 'trainingcredits'))) {
            // only one instance allowed, sorry
            return null;
        }

        return enrol_plugin::add_instance($course, $fields);
    }

    /**
     * Update instance of enrol plugin.
     * @param stdClass $instance
     * @param stdClass $data modified instance fields
     * @return boolean
     */
    public function update_instance($instance, $data) {
        global $DB;

        // Delete all other instances, leaving only one.
        if ($instances = $DB->get_records('enrol', array('courseid' => $instance->courseid, 'enrol' => 'trainingcredits'), 'id ASC')) {
            foreach ($instances as $anotherinstance) {
                if ($anotherinstance->id != $instance->id) {
                    $this->delete_instance($anotherinstance);
                }
            }
        }
        return enrol_plugin::update_instance($instance, $data);
    }

    /**
     * Returns a button to manually enrol users through the manual enrolment plugin.
     *
     * By default the first manual enrolment plugin instance available in the course is used.
     * If no manual enrolment instances exist within the course then false is returned.
     *
     * This function also adds a quickenrolment JS ui to the page so that users can be enrolled
     * via AJAX.
     *
     * @param course_enrolment_manager $manager
     * @return enrol_user_button
     */
    public function get_manual_enrol_button(course_enrolment_manager $manager) {
        global $CFG;

        $instance = null;
        $instances = array();
        foreach ($manager->get_enrolment_instances() as $tempinstance) {
            if ($tempinstance->enrol == 'trainingcredits') {
                if ($instance === null) {
                    $instance = $tempinstance;
                }
                $instances[] = array('id' => $tempinstance->id, 'name' => $this->get_instance_name($tempinstance));
            }
        }
        if (empty($instance)) {
            return false;
        }

        if (!$manuallink = $this->get_manual_enrol_link($instance)) {
            return false;
        }

        $button = new enrol_user_button($manuallink, get_string('enrolusers', 'enrol_trainingcredits'), 'get');
        $button->class .= ' enrol_trainingcredits_plugin';

        $startdate = $manager->get_course()->startdate;
        $startdateoptions = array();
        $timeformat = get_string('strftimedatefullshort');
        if ($startdate > 0) {
            $startdateoptions[2] = get_string('coursestart') . ' (' . userdate($startdate, $timeformat) . ')';
        }
        $today = time();
        $today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);
        $startdateoptions[3] = get_string('today') . ' (' . userdate($today, $timeformat) . ')' ;
        $defaultduration = $instance->enrolperiod > 0 ? $instance->enrolperiod / 86400 : '';

        $modules = array('moodle-enrol_trainingcredits-quickenrolment', 'moodle-enrol_trainingcredits-quickenrolment-skin');
        $arguments = array(
            'instances'           => $instances,
            'courseid'            => $instance->courseid,
            'ajaxurl'             => '/enrol/manual/ajax.php',
            'url'                 => $manager->get_moodlepage()->url->out(false),
            'optionsStartDate'    => $startdateoptions,
            'defaultRole'         => $instance->roleid,
            'defaultDuration'     => $defaultduration,
            'creditCost'          => 1,
            'disableGradeHistory' => $CFG->disablegradehistory,
            'recoverGradesDefault'=> ''
        );

        if ($CFG->recovergradesdefault) {
            $arguments['recoverGradesDefault'] = ' checked="checked"';
        }

        $function = 'M.enrol_trainingcredits.quickenrolment.init';
        $button->require_yui_module($modules, $function, array($arguments));
        $button->strings_for_js(array(
            'ajaxoneuserfound',
            'ajaxxusersfound',
            'ajaxnext25',
            'enrol',
            'enrolmentoptions',
            'enrolusers',
            'errajaxfailedenrol',
            'errajaxsearch',
            'none',
            'usersearch',
            'unlimitedduration',
            'startdatetoday',
            'durationdays',
            'enrolperiod',
            'finishenrollingusers',
            'recovergrades'), 'enrol');
        $button->strings_for_js('assignroles', 'role');
        $button->strings_for_js('startingfrom', 'moodle');
        $button->strings_for_js('creditcost', 'enrol_trainingcredits');

        return $button;
    }

    /**
     * Sync all meta course links.
     *
     * @param progress_trace $trace
     * @param int $courseid one course, empty mean all
     * @return int 0 means ok, 1 means error, 2 means plugin disabled
     */
    public function sync(progress_trace $trace, $courseid = null) {
        global $DB;

        if (!enrol_is_enabled('trainingcredits')) {
            $trace->finished();
            return 2;
        }

        // Unfortunately this may take a long time, execution can be interrupted safely here.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);

        $trace->output('Verifying trainingcredits enrolment expiration...');

        $params = array('now' => time(), 'useractive' => ENROL_USER_ACTIVE, 'courselevel' => CONTEXT_COURSE);
        $coursesql = "";
        if ($courseid) {
            $coursesql = "AND e.courseid = :courseid";
            $params['courseid'] = $courseid;
        }

        // Deal with expired accounts.
        $action = $this->get_config('expiredaction', ENROL_EXT_REMOVED_KEEP);

        if ($action == ENROL_EXT_REMOVED_UNENROL) {
            $instances = array();
            $sql = "SELECT ue.*, e.courseid, c.id AS contextid
                      FROM {user_enrolments} ue
                      JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'trainingcredits')
                      JOIN {context} c ON (c.instanceid = e.courseid AND c.contextlevel = :courselevel)
                     WHERE ue.timeend > 0 AND ue.timeend < :now
                           $coursesql";
            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $ue) {
                if (empty($instances[$ue->enrolid])) {
                    $instances[$ue->enrolid] = $DB->get_record('enrol', array('id'=>$ue->enrolid));
                }
                $instance = $instances[$ue->enrolid];
                // Always remove all manually assigned roles here, this may break enrol_self roles but we do not want hardcoded hacks here.
                role_unassign_all(array('userid' => $ue->userid, 'contextid' => $ue->contextid, 'component' => '', 'itemid' => 0), true);
                $this->unenrol_user($instance, $ue->userid);
                $trace->output("unenrolling expired user $ue->userid from course $instance->courseid", 1);
            }
            $rs->close();
            unset($instances);

        } else if ($action == ENROL_EXT_REMOVED_SUSPENDNOROLES or $action == ENROL_EXT_REMOVED_SUSPEND) {
            $instances = array();
            $sql = "SELECT ue.*, e.courseid, c.id AS contextid
                      FROM {user_enrolments} ue
                      JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'trainingcredits')
                      JOIN {context} c ON (c.instanceid = e.courseid AND c.contextlevel = :courselevel)
                     WHERE ue.timeend > 0 AND ue.timeend < :now
                           AND ue.status = :useractive
                           $coursesql";
            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $ue) {
                if (empty($instances[$ue->enrolid])) {
                    $instances[$ue->enrolid] = $DB->get_record('enrol', array('id' => $ue->enrolid));
                }
                $instance = $instances[$ue->enrolid];
                if ($action == ENROL_EXT_REMOVED_SUSPENDNOROLES) {
                    // Remove all manually assigned roles here, this may break enrol_self roles but we do not want hardcoded hacks here.
                    role_unassign_all(array('userid' => $ue->userid, 'contextid' => $ue->contextid, 'component' => '', 'itemid' => 0), true);
                    $this->update_user_enrol($instance, $ue->userid, ENROL_USER_SUSPENDED);
                    $trace->output("suspending expired user $ue->userid in course $instance->courseid, roles unassigned", 1);
                } else {
                    $this->update_user_enrol($instance, $ue->userid, ENROL_USER_SUSPENDED);
                    $trace->output("suspending expired user $ue->userid in course $instance->courseid, roles kept", 1);
                }
            }
            $rs->close();
            unset($instances);

        } else {
            // ENROL_EXT_REMOVED_KEEP means no changes.
        }

        $trace->output('...trainingcredits enrolment updates finished.');
        $trace->finished();

        return 0;
    }

    /**
     * Returns the user who is responsible for manual enrolments in given instance.
     *
     * Usually it is the first editing teacher - the person with "highest authority"
     * as defined by sort_by_roleassignment_authority() having 'enrol/manual:manage'
     * capability.
     *
     * @param int $instanceid enrolment instance id
     * @return stdClass user record
     */
    protected function get_enroller($instanceid) {
        global $DB;

        if ($this->lasternollerinstanceid == $instanceid and $this->lasternoller) {
            return $this->lasternoller;
        }

        $instance = $DB->get_record('enrol', array('id'=>$instanceid, 'enrol' => $this->get_name()), '*', MUST_EXIST);
        $context = context_course::instance($instance->courseid);

        if ($users = get_enrolled_users($context, 'enrol/trainingcredits:manage')) {
            $users = sort_by_roleassignment_authority($users, $context);
            $this->lasternoller = reset($users);
            unset($users);
        } else {
            $this->lasternoller = parent::get_enroller($instanceid);
        }

        $this->lasternollerinstanceid = $instanceid;

        return $this->lasternoller;
    }

    /**
     * Gets an array of the user enrolment actions.
     *
     * @param course_enrolment_manager $manager
     * @param stdClass $ue A user enrolment object
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
        $actions = array();
        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;
        if ($this->allow_unenrol_user($instance, $ue) && has_capability("enrol/trainingcredits:unenrol", $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'), $url, array('class'=>'unenrollink', 'rel' => $ue->id));
        }
        if ($this->allow_manage($instance) && has_capability("enrol/trainingcredits:manage", $context)) {
            $url = new moodle_url('/enrol/editenrolment.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/edit', ''), get_string('edit'), $url, array('class'=>'editenrollink', 'rel'=>$ue->id));
        }
        return $actions;
    }

    /**
     * The trainingcredits plugin has several bulk operations that can be performed.
     * @param course_enrolment_manager $manager
     * @return array
     */
    public function get_bulk_operations(course_enrolment_manager $manager) {
        global $CFG;
        require_once($CFG->dirroot.'/enrol/trainingcredits/locallib.php');

        $context = $manager->get_context();
        $bulkoperations = array();
        if (has_capability("enrol/trainingcredits:manage", $context)) {
            $bulkoperations['editselectedusers'] = new enrol_trainingcredits_editselectedusers_operation($manager, $this);
        }
        if (has_capability("enrol/trainingcredits:unenrol", $context)) {
            $bulkoperations['deleteselectedusers'] = new enrol_trainingcredits_deleteselectedusers_operation($manager, $this);
        }
        if (has_capability("enrol/trainingcredits:unenrol", $context)) {
            $bulkoperations['deleteandcreditbackselectedusers'] = new enrol_trainingcredits_deleteselectedusers_operation($manager, $this);
            $bulkoperations['deleteandcreditbackselectedusers']->set_creditback_option(true);
        }
        return $bulkoperations;
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        global $DB;
        // There is only I manual enrol instance allowed per course.
        if ($instances = $DB->get_records('enrol', array('courseid' => $data->courseid, 'enrol' => 'trainingcredits'), 'id')) {
            $instance = reset($instances);
            $instanceid = $instance->id;
        } else {
            $instanceid = $this->add_instance($course, (array)$data);
        }
        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Self enrol user to course
     *
     * @param stdClass $instance enrolment instance
     * @param stdClass $data data needed for enrolment.
     * @return bool|array true if enroled else eddor code and messege
     */
    public function enrol_self(stdClass $instance, $data = null) {
        global $DB, $USER, $CFG;

        $timestart = time();
        if ($instance->enrolperiod) {
            $timeend = $timestart + $instance->enrolperiod;
        } else {
            $timeend = 0;
        }

        $this->enrol_user($instance, $USER->id, $instance->roleid, $timestart, $timeend);

        // Burn as many credits as required.
        $credits = $DB->get_record('enrol_trainingcredits', array('userid' => $USER->id));
        $credits->coursecredits -= $instance->customint3;
        $DB->update_record('enrol_trainingcredits', $credits);

        // Send welcome message.
        if ($instance->customint5) {
            $this->email_welcome_message($instance, $USER);
        }
    }

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $instance) {
        global $CFG, $OUTPUT, $USER;

        require_once("$CFG->dirroot/enrol/trainingcredits/locallib.php");

        $enrolstatus = $this->can_self_enrol($instance);

        // Don't show enrolment instance form, if user can't enrol using it.
        if (true === $enrolstatus) {
            $form = new enrol_trainingcredits_enrol_form(null, $instance);
            $instanceid = optional_param('instance', 0, PARAM_INT);
            if ($instance->id == $instanceid) {
                if ($data = $form->get_data()) {
                    $this->enrol_self($instance, $data);
                }
            }

            ob_start();
            $form->display();
            $output = ob_get_clean();
            return $OUTPUT->box($output);
        } else {
            return $OUTPUT->box($enrolstatus);
        }
    }

    /**
     * Checks if user can self enrol usingcredits.
     *
     * @param stdClass $instance enrolment instance
     * @param bool $checkuserenrolment if true will check if user enrolment is inactive.
     *             used by navigation to improve performance.
     * @return bool|string true if successful, else error message or false.
     */
    public function can_self_enrol(stdClass $instance, $checkuserenrolment = true) {
        global $CFG, $DB, $OUTPUT, $USER;

        if ($checkuserenrolment) {
            if (isguestuser()) {
                // Can not enrol guest.
                return get_string('noguestaccess', 'enrol') . $OUTPUT->continue_button(get_login_url());
            }
            // Check if user is already enroled.
            if ($DB->get_record('user_enrolments', array('userid' => $USER->id, 'enrolid' => $instance->id))) {
                return get_string('canntenrol', 'enrol_trainingcredits');
            }
        }

        if ($instance->status != ENROL_INSTANCE_ENABLED) {
            return get_string('canntenrol', 'enrol_trainingcredits');
        }

        if ($instance->enrolstartdate != 0 and $instance->enrolstartdate > time()) {
            return get_string('canntenrol', 'enrol_trainingcredits');
        }

        if ($instance->enrolenddate != 0 and $instance->enrolenddate < time()) {
            return get_string('canntenrol', 'enrol_trainingcreditds');
        }

        // Already enrolled
        if ($DB->record_exists('user_enrolments', array('userid' => $USER->id, 'enrolid' => $instance->id))) {
            return get_string('canntenrol', 'enrol_trainingcredits');
        }

        // Check cohort restriction
        if ($instance->customint1) {
            require_once("$CFG->dirroot/cohort/lib.php");
            if (!cohort_is_member($instance->customint1, $USER->id)) {
                $cohort = $DB->get_record('cohort', array('id' => $instance->customint1));
                if (!$cohort) {
                    return null;
                }
                $a = format_string($cohort->name, true, array('context' => context::instance_by_id($cohort->contextid)));
                return markdown_to_html(get_string('cohortnonmemberinfo', 'enrol_trainingcredits', $a));
            }
        }

        // Check credit restrictions
        $credits = $DB->get_record('enrol_trainingcredits', array('userid' => $USER->id));

        if (!$instance->customint3 > $credits->coursecredits) {
            // Not enough credits in account.
            return get_string('canntenrol', 'enrol_trainingcredits');
        }

        // Check max enrollimit restriction.
        if ($instance->customint4 > 0) {
            // Max enrol limit specified.
            $count = $DB->count_records('user_enrolments', array('enrolid' => $instance->id));
            if ($count >= $instance->customint4) {
                // Bad luck, no more self enrolments here.
                return get_string('maxenroledreached', 'enrol_trainingcredits');
            }
        }

        return true;
    }

    public function show_enrolme_link(stdClass $instance) {

        if (true !== $this->can_self_enrol($instance, false)) {
            return false;
        }

        return true;
    }

}
