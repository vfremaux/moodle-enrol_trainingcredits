<?php   /// $Id: enrol.php,v 1.1.2.1 2012/06/11 10:42:04 diml Exp $
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 2004  Martin Dougiamas  http://moodle.com               //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

require_once($CFG->dirroot.'/group/lib.php');

/**
* enrolment_plugin_trainingcredits
*
*/

class enrolment_plugin_trainingcredits {

	var $errormsg;

	/**
	* Prints the entry form/page for this enrolment
	*
	* This is only called from course/enrol.php
	* Most plugins will probably override this to print payment
	* forms etc, or even just a notice to say that manual enrolment
	* is disabled
	*
	* @param    course  current course object
	*/
	function print_entry($course) {
	    global $CFG, $USER, $SESSION, $THEME, $COURSE;
	
	    $strloginto = get_string('loginto', '', $course->shortname);
	    $strcourses = get_string('courses');
	
	/// Automatically enrol into courses without password
	
	    $context = get_context_instance(CONTEXT_SYSTEM);
	
	    $navlinks = array();
	    $navlinks[] = array('name' => $strcourses, 'link' => ".", 'type' => 'misc');
	    $navlinks[] = array('name' => $strloginto, 'link' => null, 'type' => 'misc');
	    $navigation = build_navigation($navlinks);
	
	    if ($course->password == '') {   // no password, so enrol
	    	
			// real trap for uncredited users	    	
	    	$usercredits = get_field('trainingcredits', 'coursecredits', 'userid', "$USER->id");
	    	if ($usercredits == 0 || ($usercredits < $course->cost)){
	    		$a->usercredits = 0 + $usercredits;
	    		$a->required = 0 + $course->cost - $usercredits;
	    		$creditstr =  ($usercredits) ? get_string('nocreditsleft', 'enrol_trainingcredits', $a) :
	    		get_string('nocredits', 'enrol_trainingcredits', $a) ;
	            print_header($strloginto, $course->fullname, $navigation);
	            echo '<br />';
	    		notify($creditstr);
	    		print_continue($CFG->wwwroot);
	    		if (!empty($CFG->trainingcredits_creditsinstructions)){
		    		print_box_start();
		    		print_heading(get_string('gettingcredits', 'enrol_trainingcredits'), 3);
		    		echo filter_string($CFG->trainingcredits_creditsinstructions);
		        }
	            print_footer();
	            exit;
	    	}
	    	
			// /real trap for uncredited users
	
	        if (has_capability('moodle/legacy:guest', $context, $USER->id, false)) {
	            add_to_log($course->id, 'course', 'guest', 'view.php?id='.$course->id, getremoteaddr());
	
	        } else if (empty($_GET['confirm']) && empty($_GET['cancel'])) {
	
	            print_header($strloginto, $course->fullname, $navigation);
	            echo '<br />';
	            $a->usercredits = $usercredits;
	            $a->cost = $course->cost;
	            $a->creditsleft = $usercredits - $course->cost;
	            notice_yesno(get_string('enrolmentconfirmation', 'enrol_trainingcredits', $a), "enrol.php?id=$course->id&amp;confirm=1&amp;sesskey=".sesskey(),
	                                                              "enrol.php?id=$course->id&amp;cancel=1");
	            print_footer();
	            exit;
	
	        } else if (!empty($_GET['confirm']) and confirm_sesskey()) {
	
	            if (!enrol_into_course($course, $USER, 'manual')) {
	                print_error('couldnotassignrole');
	            }
	            
	            // burn required credits
	            $oldcredits = get_field('trainingcredits', 'coursecredits', 'userid', $USER->id);
	            set_field('trainingcredits', 'coursecredits', $oldcredits - $course->cost, 'userid', $USER->id);

	            // force a refresh of mycourses
	            unset($USER->mycourses);
	
	            if (!empty($SESSION->wantsurl)) {
	                $destination = $SESSION->wantsurl;
	                unset($SESSION->wantsurl);
	            } else {
	                $destination = "$CFG->wwwroot/course/view.php?id=$course->id";
	            }
	
	            redirect($destination);
	
	        } else if (!empty($_GET['cancel'])) {
	            unset($SESSION->wantsurl);
	            if (!empty($SESSION->enrolcancel)) {
	                $destination = $SESSION->enrolcancel;
	                unset($SESSION->enrolcancel);
	            } else {
	                $destination = $CFG->wwwroot;
	            }
	            redirect($destination);
	        }
	    }
	
	    // if we get here we are going to display the form asking for the enrolment key
	    // and (hopefully) provide information about who to ask for it.
	    if (!isset($password)) {
	        $password = '';
	    }
		
	}

	/**
	* The other half to print_entry, this checks the form data
	*
	* This function checks that the user has completed the task on the
	* enrolment entry page and then enrolls them.
	*
	* @param    form    the form data submitted, as an object
	* @param    course  the current course, as an object
	*/
	function check_entry($form, $course) {
	    global $CFG, $USER, $SESSION, $THEME;
	
	    if (empty($form->password)) {
	        $form->password = '';
	    }
	
	    if (empty($course->password) or !confirm_sesskey()) {
	        // do not allow entry when no course password set
	        // automatic login when manual primary, no login when secondary at all!!
	        error('illegal enrolment attempted');
	    }
	
	    $groupid = $this->check_group_entry($course->id, $form->password);
	
	    if ((stripslashes($form->password) == $course->password) or ($groupid !== false) ) {
	
	        if (isguestuser()) { // only real user guest, do not use this for users with guest role
	            $USER->enrolkey[$course->id] = true;
	            add_to_log($course->id, 'course', 'guest', 'view.php?id='.$course->id, getremoteaddr());
	
	        } else {  /// Update or add new enrolment
	        	// this method will not burn credits, allowing password 
	        	// owners to get in anyway.
	            if (enrol_into_course($course, $USER, 'trainingcredits')) {
	                // force a refresh of mycourses
	                unset($USER->mycourses);
	                if ($groupid !== false) {
	                    if (!groups_add_member($groupid, $USER->id)) {
	                        print_error('couldnotassigngroup');
	                    }
	                }
	            } else {
	                print_error('couldnotassignrole');
	            }
	        }
	
	        if ($SESSION->wantsurl) {
	            $destination = $SESSION->wantsurl;
	            unset($SESSION->wantsurl);
	        } else {
	            $destination = "$CFG->wwwroot/course/view.php?id=$course->id";
	        }
	
	        redirect($destination);
	
	    } else if (!isset($CFG->enrol_manual_showhint) or $CFG->enrol_manual_showhint) {
	        $this->errormsg = get_string('enrolmentkeyhint', '', substr($course->password, 0, 1));
	
	    } else {
	        $this->errormsg = get_string('enrolmentkeyerror', 'enrol_manual');
	    }
	}
	
	
	/**
	* Check if the given enrolment key matches a group enrolment key for the given course
	*
	* @param    courseid  the current course id
	* @param    password  the submitted enrolment key
	*/
	function check_group_entry ($courseid, $password) {
	
	    if ($groups = groups_get_all_groups($courseid)) {
	        foreach ($groups as $group) {
	            if ( !empty($group->enrolmentkey) and (stripslashes($password) == $group->enrolmentkey) ) {
	                return $group->id;
	            }
	        }
	    }
	
	    return false;
	}
	
	
	/**
	* Prints a form for configuring the current enrolment plugin
	*
	* This function is called from admin/enrol.php, and outputs a
	* full page with a form for defining the current enrolment plugin.
	*
	* @param    frm  an object containing all the data for this page
	*/
	function config_form($frm) {
	    global $CFG;
		
	    include ("$CFG->dirroot/enrol/trainingcredits/config.html");
	}
	
	
	/**
	* Processes and stored configuration data for the enrolment plugin
	*
	* @param    config  all the configuration data as entered by the admin
	*/
	function process_config($config) {
	
	    $return = true;
	
	    foreach ($config as $name => $value) {
	        if (!set_config($name, $value)) {
	            $return = false;
	        }
	    }
	
	    return $return;
	}
	
	
	/**
	* Notify users about enrolments that are going to expire soon!
	* This function is run by admin/cron.php
	* @return void
	*/
	function cron() {
	    global $CFG, $USER, $SITE;
	
	}
	
	
	/**
	* Returns the relevant icons for a course
	*
	* @param    course  the current course, as an object
	*/
	function get_access_icons($course) {
	    global $CFG;
	
	    global $strallowguests;
	    global $strrequireskey;
	
	    if (empty($strallowguests)) {
	        $strallowguests = get_string('allowguests');
	        $strrequireskey = get_string('requireskey');
	    }
	
	    $str = '';
	
	    if (!empty($course->guest)) {
	        $str .= '<a title="'.$strallowguests.'" href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">';
	        $str .= '<img class="accessicon" alt="'.$strallowguests.'" src="'.$CFG->pixpath.'/i/guest.gif" /></a>&nbsp;&nbsp;';
	    }
	    if (!empty($course->password)) {
	        $str .= '<a title="'.$strrequireskey.'" href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">';
	        $str .= '<img class="accessicon" alt="'.$strrequireskey.'" src="'.$CFG->pixpath.'/i/key.gif" /></a>';
	    }
	
	    return $str;
	}
	
	/**
	 * Prints the message telling you were to get the enrolment key
	 * appropriate for the prevailing circumstances
	 * A bit clunky because I didn't want to change the standard strings
	 *This function still uses the manual enroll parameters to operate.
	 */
	function print_enrolmentkeyfrom($course) {
	    global $CFG;
	    global $USER;
	
	    $context = get_context_instance(CONTEXT_SYSTEM);
	    $guest = has_capability('moodle/legacy:guest', $context, $USER->id, false);
	
	    // if a keyholder role is defined we list teachers in that role (if any exist)
	    $contactslisted = false;
	    $canseehidden = has_capability('moodle/role:viewhiddenassigns', $context);
	    if (!empty($CFG->enrol_manual_keyholderrole)) {
	        if ($contacts = get_role_users($CFG->enrol_manual_keyholderrole, get_context_instance(CONTEXT_COURSE, $course->id),true,'','u.lastname ASC',$canseehidden  )) {
	            // guest user has a slightly different message
	            if ($guest) {
	                print_string('enrolmentkeyfromguest', '', ':<br />' );
	            }
	            else {
	                print_string('enrolmentkeyfrom', '', ':<br />');
	            }
	            foreach ($contacts as $contact) {
	                $contactname = "<a href=\"../user/view.php?id=$contact->id&course=".SITEID."\">".fullname($contact)."</a>.";
	                echo "$contactname<br />";
	            }
	            $contactslisted = true;
	        }
	    }
	
	    // if no keyholder role is defined OR nobody is in that role we do this the 'old' way
	    // (show the first person with update rights)
	    if (!$contactslisted) {
	        if ($teachers = get_users_by_capability(get_context_instance(CONTEXT_COURSE, $course->id), 'moodle/course:update',
	            'u.*', 'u.id ASC', 0, 1, '', '', false, true)) {
	            $teacher = array_shift($teachers);
	        }
	        if (!empty($teacher)) {
	            $teachername = "<a href=\"../user/view.php?id=$teacher->id&course=".SITEID."\">".fullname($teacher)."</a>.";
	        } else {
	            $teachername = strtolower( get_string('defaultcourseteacher') ); //get_string('yourteacher', '', $course->teacher);
	        }
	
	        // guest user has a slightly different message
	        if ($guest) {
	            print_string('enrolmentkeyfromguest', '', $teachername );
	        }
	        else {
	            print_string('enrolmentkeyfrom', '', $teachername);
	        }
	    }
	}

} /// end of class

?>
