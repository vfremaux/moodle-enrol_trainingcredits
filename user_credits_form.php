<?php

require_once $CFG->libdir.'/formslib.php';
require_once $CFG->dirroot.'/enrol/trainingcredits/locallib.php';

class User_Credits_Form extends moodleform{
    
    var $pagesize;
    var $offset;
    var $filter;
    var $url;
    
    function __construct($pagesize = 20, $offset = 0, $filter = ''){
        $this->pagesize = $pagesize;
        $this->offset = $offset;
        $this->filter = $filter;
        parent::__construct();
    }
    
    function definition(){
        global $COURSE, $CFG;
        global $SESSION;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'from', $this->offset);

        $group[0] = & $mform->createElement('text', 'namefilter');
        $group[1] = & $mform->createElement('submit', 'setfilter', get_string('setfilter', 'enrol_trainingcredits'));
        $group[2] = & $mform->createElement('submit', 'clearfilter', get_string('clearfilter', 'enrol_trainingcredits'));

        $mform->addGroup($group, 'filter', get_string('namefilter', 'enrol_trainingcredits'));
     	$mform->setHelpButton('filter', array('regexfilter', get_string('regexfilter', 'enrol_trainingcredits'), 'enrol_trainingcredits'));
		$mform->setDefault('namefilter', @$SESSION->namefilter);

        $attrs = array('maxlength' => 3, 'size' => 4);
        
    	$filteringclause = '';
    	if (!empty($SESSION->namefilter)){

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
				{$CFG->prefix}user u
			LEFT JOIN
				{$CFG->prefix}trainingcredits tc
			ON
				u.id = tc.userid
			$filteringclause
			ORDER BY
				lastname, firstname
		";
		$alluserscount = count_records_sql($sql);
		
		if ($alluserscount < $this->offset) $this->offset = 0;
     		
		$sql = "
			SELECT
				u.id,
				u.lastname,
				u.firstname,
				tc.coursecredits
			FROM
				{$CFG->prefix}user u
			LEFT JOIN
				{$CFG->prefix}trainingcredits tc
			ON
				u.id = tc.userid
			$filteringclause
			ORDER BY
				lastname, firstname
		";
 
        if ($users = get_records_sql($sql, $this->offset, $this->pagesize)){        
	        foreach($users as $utc){
	        	$utc->user = fullname($utc);
	            $mform->addElement('text', 'credit'.$utc->id, get_string('credits', 'enrol_trainingcredits', $utc), $attrs);
	            if (!$utc->coursecredits) $utc->coursecredits = '';
	            $mform->setDefault('credit'.$utc->id, $utc->coursecredits);
	        }
	    }
	    
	    $mform->addElement('html', $this->pager($this->offset, $this->pagesize, $alluserscount, $CFG->wwwroot.'/enrol/trainingcredits/usercredits.php'));

        $mform->addElement('submit', 'go_btn', get_string('submit'));
    }
    
    function validation($data){
    }
    
	function pager($offset, $page, $maxobjects, $url){
	    global $CFG;
	    
	    if ($maxobjects <= $page) return;
	    
	    $current = ceil(($offset + 1) / $page);
	    $pages = array();
	    $off = 0;    
	
	    for ($p = 1 ; $p <= ceil($maxobjects / $page) ; $p++){
	        if ($p == $current){
	            $pages[] = $p;
	        } else {
	            $pages[] = "<a href=\"{$url}?from={$off}\">{$p}</a>";
	        }
	        $off = $off + $page;    
	    }    
	    
	    return implode(' - ', $pages);
	}
}

?>