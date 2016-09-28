<?php

$string['trainingcredits:config'] = 'Peut configurer l\'inscripion par crédit';
$string['trainingcredits:managecredits'] = 'Peut gérer les crédits';

$string['backmanageusercredits'] = 'Gérer les crédits utilisateur';
$string['clearfilter'] = 'Supprimer le filtre';
$string['creditinstructions'] = 'Texte d\'instructions pour obtenir des crédits';
$string['credits'] = 'Crédits de <b>$a->user</b> : ';
$string['creditsmanagement'] = 'Gestion des crédits';
$string['description'] = 'Cette méthode permet une inscription directe si l\'utilisateur dispose de suffisamment de credits à consommer dans son compte';
$string['editusercredits'] = 'Editer les crédits des utilisateurs';
$string['enrolmentconfirmation'] = 'Votre inscription consommera $a->cost crédits sur votre compte. Vous avez actuellement $a->usercredits disponibles.<br/><br/> Après votre inscription, il vous restera $a->creditsleft crédits. Confirmez-vous votre inscription ?';
$string['gettingcredits'] = 'Comment obtenir des crédits ?';
$string['namefilter'] = 'Filtre';
$string['nocredits'] = 'Ce cours n\'est accessible que si votre compte est chargé en crédits pédagogiques. Vous aurez besoin de $a->required credits pour pouvoir vous inscrire à ce cours. <br/><br/>Vous avez actuellement $a->usercredits crédits disponibles !';
$string['nocreditsleft'] = 'Désolé, il vous manque $a->required crédits pour pouvoir vous inscrire à ce cours. Vous avez actuellement $a->usercredits crédits disponibles';
$string['pluginname'] = 'Inscription par crédits pédagogiques';
$string['setfilter'] = 'Enregistrer le filtre';
$string['setusercredits'] = 'Modification des crédits utilisateur';
$string['creditused_mail'] = '
<%%SITE%%> courseware
-------------------------------------

L\'utilisateur <%%USERNAME%%> s\'est inscrit
sur le cours <%%COURSE%%>.

Il a utilisé <%%COST%%> crédits.

Son solde de crédits est de <%%CREDITSLEFT%%>

--------------------------------------
';

$string['creditused_mail_html'] = '
<h2><%%SITE%%> courseware</h2>
<hr/>

<p>L\'utilisateur <%%USERNAME%%> s\'est inscrit
sur le cours <%%COURSE%%>.</p>

<p>Il a utilisé <%%COST%%> crédits.</p>

<p>Son solde de crédits est de <%%CREDITSLEFT%%> credits.</p>

<hr/>
';
