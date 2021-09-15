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

// Privacy.
$string['privacy:metadata:trainingcredits'] = "Information about owned training credits to be spent into new enrolments";
$string['privacy:metadata:userid'] = "The user id";
$string['privacy:metadata:coursecredits'] = "Owned course credit amount";

// Capabilities
$string['trainingcredits:config'] = 'Configure training credits';
$string['trainingcredits:managecredits'] = 'Manage user credits';
$string['trainingcredits:enrol'] = 'Enrol other users';
$string['trainingcredits:unenrol'] = 'Disenrol other users';
$string['trainingcredits:unenrolself'] = 'Disenrol self';
$string['trainingcredits:manage'] = 'Manage enrolments';

// Events
$string['event_trainingcredits_creditedback'] = 'Training credits back credited';
$string['event_trainingcredits_enrolled'] = 'Enroled by training credits';
$string['event_trainingcredits_created'] = 'Training credits enrolment added';
$string['event_trainingcredits_deleted'] = 'Training credits enrolment deleted';

$string['addgroup'] = 'Add to group';
$string['assignrole'] = 'Role to assign';
$string['back'] = 'Browse back';
$string['backmanageusercredits'] = 'Manage user credits';
$string['cantenrol'] = 'You cannot enrol in this course (no available learning credits)';
$string['clearfilter'] = 'Erase filter';
$string['creditcost'] = 'Credits needed';
$string['creditinstructions'] = 'Instructions text for getting credits';
$string['credits'] = 'Training credits for <b>{$a->user}</b>: ';
$string['creditsmanagement'] = 'Credits management';
$string['description'] = 'This method allows self-enrolment if the user has enough credits on account';
$string['editusercredits'] = 'Edit user credits';
$string['enrolmentconfirmation'] = 'Your enrolment will use <b>{$a->cost}</b> training credits on your account. You have <b>{$a->usercredits}</b> credits left.<br/><br/> After enrolment, you will have <b>{$a->creditsleft}</b> credits. Confirm enrolment?';
$string['enrolme'] = 'Enrol me';
$string['enrolusers'] = 'Enrol users';
$string['deleteselectedusers'] = 'Delete selected users';
$string['deleteandcreditbackselectedusers'] = 'Delete selected users and credit them back';
$string['editselectedusers'] = 'Edit selected users';
$string['gettingcredits'] = 'How did you get credits?';
$string['maxenroled'] = 'Max enrolled users in this instance';
$string['maxenrolledreached'] = 'There are too many users enrolled in this course';
$string['namefilter'] = 'Filter';
$string['nocredits'] = 'This course can only be accessed if you have enough training credits on your account. You need <b class="error"<{$a->required}</b> training credits for this course. <br/><br/>You currently have {$a->usercredits} credits';
$string['nocreditsleft'] = 'Sorry, you need <bclass="error">{$a->required}</b> credits to be enrolled in this course. You only have <b>{$a->usercredits}</b> credits on your account';
$string['nopassword'] = 'This course needs bo password to enrol.';
$string['pluginname'] = 'User Credit Based Enrol';
$string['regexfilter'] = 'Regexp filter';
$string['sendwelcomemessage'] = 'Send welcome message';
$string['setfilter'] = 'Save filter';
$string['setusercredits'] = 'Set user credits';
$string['status'] = 'Enable training credit enrolments';

$string['regexfilter_help'] = 'You can use regexp expressions such as ^ as "start of" $ as "end of" or .* for any string, etc.';

$string['creditused_mail'] = '
<%%SITE%%> courseware
-------------------------------------

User <%%USERNAME%%> has applied for course <%%COURSE%%>.

using <%%COST%%> training credits.

Credits left: <%%CREDITSLEFT%%>

--------------------------------------
';

$string['creditused_mail_html'] = '
<h2><%%SITE%%> courseware</h2>
<hr/>

<p>User <%%USERNAME%%> has applied for course <%%COURSE%%>.</p>

<p>using <%%COST%%> training credits.</p>

<p>Credits left: <%%CREDITSLEFT%%> credits.</p>

<hr/>
';
