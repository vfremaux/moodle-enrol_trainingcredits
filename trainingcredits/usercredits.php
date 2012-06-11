<?php

    require_once("../../config.php");
    require_once('locallib.php');
    require_once('user_credits_form.php');

    // remember the current time as the time any responses were submitted
    // (so as to make sure students don't get penalized for slow processing on this page)
    $timestamp = time();

    require_capability('enrol/trainingcredits:manage', get_context_instance(CONTEXT_SYSTEM));

/// Get user limitations

    $streditinguserattempts = get_string('editusercredits', 'enrol_trainingcredits');
    $navigation = build_navigation($streditinguserattempts);
    print_header_simple($streditinguserattempts, '', $navigation, "", "");

    $currenttab = 'edit';
    $mode = 'setattempts';

    // include('tabs.php');

    $mform = new User_Credits_Form(20, optional_param('from', 0, PARAM_INT), $CFG->wwwroot.'/enrol/trainingcredits/usercredits.php');

    if (!$mform->is_cancelled() && $data = $mform->get_data()){
        if (isset($data->filter['setfilter'])){
        	$SESSION->namefilter = stripslashes($data->filter['namefilter']);
    		$mform = new User_Credits_Form(20, optional_param('from', 0, PARAM_INT), $CFG->wwwroot.'/enrol/trainingcredits/usercredits.php');
        } else if (isset($data->filter['clearfilter'])){
        	unset($SESSION->namefilter);
        	$data->filter['namefilter'] = '';
        	$_POST['filter']['namefilter'] = '';
    		$mform = new User_Credits_Form(20, optional_param('from', 0, PARAM_INT), $CFG->wwwroot.'/enrol/trainingcredits/usercredits.php');
        } else {
	        $selection = preg_grep('/^credit/', array_keys((array)$data));
	        $selection = preg_replace('/^credit/', '', $selection);

	        if (!empty($selection)){
	            foreach($selection as $userid){
	                $credit = new StdClass();
	                $credit->userid = $userid;
	                $datakey = 'credit'.$userid;
	                $credit->coursecredits = $data->$datakey;
	                if ($rec = get_record_select('trainingcredits', " userid = $userid  ")){
		                update_record('trainingcredits', $credit);
		            } else {
		                insert_record('trainingcredits', $credit);
		            }
	            }
	        }
	    }
    }
    
    print_heading(get_string('setusercredits', 'enrol_trainingcredits'));
    print_box_start();

    $mform->display();
    print_box_end();

    print_footer();
?>