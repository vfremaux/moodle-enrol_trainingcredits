<?php // $Id: access.php,v 1.1.2.1 2012/06/11 10:42:05 diml Exp $

$enrol_trainingcredits_capabilities = array(

    'enrol/trainingcredits:manage' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'admin' => CAP_ALLOW
        )
    ),

);

?>
