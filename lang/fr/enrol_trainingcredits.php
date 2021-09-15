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
$string['privacy:metadata:trainingcredits'] = "Information sur les crédits d\'inscription détenus par l\'utilisateur";
$string['privacy:metadata:userid'] = "L\'identifiant utilisateur";
$string['privacy:metadata:coursecredits'] = "Le montant de crédits détenus";

$string['trainingcredits:config'] = 'Peut configurer l\'inscripion par crédit';
$string['trainingcredits:managecredits'] = 'Peut gérer les crédits';
$string['trainingcredits:manage'] = 'Gérer les inscriptions';
$string['trainingcredits:enrol'] = 'Inscrire les utilisateurs';
$string['trainingcredits:unenrol'] = 'Désincrire les utilisateurs';

// Events
$string['event_trainingcredits_creditedback'] = 'Crédits restitués';
$string['event_trainingcredits_enrolled'] = 'Inscription par crédit';
$string['event_trainingcredits_created'] = 'Méthode d\'inscription par crédits ajouée';
$string['event_trainingcredits_deleted'] = 'Méthode d\'inscription par cr"édits supprimée';

$string['addgroup'] = 'Ajouter au groupe';
$string['assignrole'] = 'Rôle à assigner';
$string['back'] = 'Revenir';
$string['backmanageusercredits'] = 'Gérer les crédits utilisateur';
$string['cantenrol'] = 'Vous ne pouvez pas vous inscrire dans ce cours (pas de crédits disponible)';
$string['clearfilter'] = 'Supprimer le filtre';
$string['creditcost'] = 'Credits nécessaires';
$string['creditinstructions'] = 'Texte d\'instructions pour obtenir des crédits';
$string['credits'] = 'Crédits de <b>{$a->user}</b> : ';
$string['creditsmanagement'] = 'Gestion des crédits';
$string['description'] = 'Cette méthode permet une inscription directe si l\'utilisateur dispose de suffisamment de credits à consommer dans son compte';
$string['editusercredits'] = 'Editer les crédits des utilisateurs';
$string['editselectedusers'] = 'Editer les utilisateurs sélectionnés';
$string['deleteselectedusers'] = 'Supprimer les utilisateurs sélectionnés';
$string['deleteandcreditbackselectedusers'] = 'Supprimer les utilisateurs sélectionnés et les recréditer';
$string['enrolme'] = 'Confimer mon inscription';
$string['enrolmentconfirmation'] = 'Votre inscription consommera <b>{$a->cost}</b> crédits sur votre compte. Vous avez actuellement <b>{$a->usercredits}</b> crédits disponibles.<br/> Après votre inscription, il vous restera <b>{$a->creditsleft}</b> crédits. Voulez vous continuer ?';
$string['gettingcredits'] = 'Comment obtenir des crédits ?';
$string['maxenroled'] = 'Nombre d\'inscrits maximum dans cette instance';
$string['maxenroledreached'] = 'Trop d\'utilisateurs ont été inscrits dans ce cours.';
$string['namefilter'] = 'Filtre';
$string['nocredits'] = 'Ce cours n\'est accessible que si votre compte est chargé en crédits pédagogiques. Vous aurez besoin de <b>{$a->required}</b> credits pour pouvoir vous inscrire à ce cours. <br/><br/>Vous avez actuellement </b>{$a->usercredits}</b> crédits disponibles !';
$string['nocreditsleft'] = 'Désolé, il vous manque <b class="error">{$a->required}</b> crédits pour pouvoir vous inscrire à ce cours. Vous avez actuellement {$a->usercredits} crédits disponibles';
$string['nopassword'] = 'L\'inscription à ce cours est disponible sans clef.';
$string['pluginname'] = 'Inscription par crédits pédagogiques';
$string['regexfilter'] = 'Filtre a expressions régulières';
$string['sendwelcomemessage'] = 'Envoyer un message de bienvenue';
$string['setfilter'] = 'Enregistrer le filtre';
$string['setusercredits'] = 'Modification des crédits utilisateur';
$string['status'] = 'Activer l\'inscription par crédits';


$string['regexfilter_help'] = 'Vous pouvez utiliser ici des marqueurs d\'expression régulères tels que ^ come "début de" $ comme "fin de" ou .* comme n\'importe quelle chaine, etc.';

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
