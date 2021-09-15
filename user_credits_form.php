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
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/enrol/trainingcredits/locallib.php');

class User_Credits_Form extends moodleform {

    var $pagesize;
    var $offset;
    var $filter;
    var $url;

    function __construct($pagesize = 20, $offset = 0, $customdata = []) {
        $this->pagesize = $pagesize;
        $this->offset = $offset;
        parent::__construct(null, $customdata);
    }
    
    function definition() {
        global $COURSE, $CFG, $SESSION, $DB;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'from', $this->offset);
        $mform->setType('from', PARAM_INT);

        $mform->addElement('hidden', 'returnid', $this->_customdata['returnid']);
        $mform->setType('returnid', PARAM_INT);

        $group[0] = & $mform->createElement('text', 'namefilter');
        $group[1] = & $mform->createElement('submit', 'setfilter', get_string('setfilter', 'enrol_trainingcredits'));
        $group[2] = & $mform->createElement('submit', 'clearfilter', get_string('clearfilter', 'enrol_trainingcredits'));
        $mform->setType('filter[namefilter]', PARAM_TEXT);

        $mform->addGroup($group, 'filter', get_string('namefilter', 'enrol_trainingcredits'));
        $mform->addHelpButton('filter', 'regexfilter', 'enrol_trainingcredits');
        $mform->setDefault('namefilter', @$SESSION->namefilter);

        $attrs = array('maxlength' => 3, 'size' => 4);

        $filteringclause = '';
        if (!empty($SESSION->namefilter)) {

            $namefilter = str_replace('*', '.*', $SESSION->namefilter);

            $filteringclause = "
                WHERE
                    lastname REGEXP '$namefilter' OR
                    firstname REGEXP '$namefilter'
            ";
        }

        $sql = "
            SELECT
                COUNT(*)
            FROM
                {user} u
            LEFT JOIN
                {enrol_trainingcredits} tc
            ON
                u.id = tc.userid
                $filteringclause
        ";
        $alluserscount = $DB->count_records_sql($sql);

        if ($alluserscount < $this->offset) {
            $this->offset = 0;
        }

        $sql = "
            SELECT
                u.id,
                u.idnumber,
                u.username,
                ".get_all_user_name_fields(true, 'u')."
                ,tc.coursecredits
            FROM
                {user} u
            LEFT JOIN
                {enrol_trainingcredits} tc
            ON
                u.id = tc.userid
                $filteringclause
            ORDER BY
                u.lastname, u.firstname
        ";
 
        if ($users = $DB->get_records_sql($sql, array(), $this->offset, $this->pagesize)) {
            foreach ($users as $utc) {
                $utc->user = fullname($utc);
                $label = get_string('credits', 'enrol_trainingcredits', $utc).' ('.$utc->username.') ['.$utc->idnumber.']';
                $mform->addElement('text', 'credit'.$utc->id, $label, $attrs);
                $mform->setType('credit'.$utc->id, PARAM_INT);
                if (!$utc->coursecredits) {
                    $utc->coursecredits = '';
                }
                $mform->setDefault('credit'.$utc->id, $utc->coursecredits);
            }
        }

        $mform->addElement('html', $this->pager($this->offset, $this->pagesize, $alluserscount, new moodle_url('/enrol/trainingcredits/usercredits.php')));

        $mform->addElement('submit', 'go_btn', get_string('submit'));
    }

    function validation($data, $files) {
    }

    function pager($offset, $page, $maxobjects, $url) {
        global $CFG;

        if ($maxobjects <= $page) {
            return;
        }

        $current = ceil(($offset + 1) / $page);
        $pages = array();
        $off = 0;    
    
        for ($p = 1 ; $p <= ceil($maxobjects / $page) ; $p++) {
            if ($p == $current) {
                $pages[] = $p;
            } else {
                $pages[] = "<a href=\"{$url}?from={$off}\">{$p}</a>";
            }
            $off = $off + $page;    
        }

        return implode(' - ', $pages);
    }
}
