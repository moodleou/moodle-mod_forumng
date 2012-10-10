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
 * Lang strings.
 * Traduction par Pascal Maury et Luiggi Sansonetti
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
///pas trouv&eacute;
$string['forumtype_general'] = 'Forum standard pour utilisation g&eacute;n&eacute;rale'; // generalforum
$string['forumtype_studyadvice'] = 'Discussion personnelle (ne voit que ses propres discussions)';
///

$string['replytouser'] = 'Utiliser l\'adresse de l\'auteur dans le champ "R&eacute;pondre &agrave;"';
$string['configreplytouser'] = 'Lorsqu\'un message est envoy&eacute; par courriel, doit-il contenir l\'adresse de courriel de son auteur, afin que le destinataire puisse l\'atteindre personnellement. M&ecirc;me lorsque cette option est activ&eacute;e, les utilisateurs peuvent choisir dans leur profil de garder leur adresse secr&egrave;te.';
$string['disallowsubscribe'] = 'Les abonnements sont d&eacute;sactiv&eacute;s';
$string['forcesubscribe'] = 'Forcer tout le monde &agrave; &ecirc;tre (d&eacute;finitivement) abonn&eacute;';
$string['subscription'] = 'Mode d\'abonnement';
$string['subscription_help'] = 'Vous pouvez abonner tout le monde de fa&ccedil;on impos&eacute;e, ou de les abonner par d&eacute;faut, la diff&eacute;rence est que dans ce dernier cas, les utilisateurs peuvent choisir de se d&eacute;sabonner.<br>Ces options incluent tous les participants aux cours (&eacute;tudiants et enseignants). Les utilisateurs qui n\'appartiennent pas au cours (comme l\'administrateur) peuvent quand m&ecirc;me s\'abonner.';
$string['configtrackreadposts'] = 'Mettre sur \'Oui\' pour permettre &agrave; l\'utilisateur de suivre la lecture du message.';
$string['forums'] = 'Forums';
$string['digestmailheader'] = 'Ceci est votre r&eacute;sum&eacute; quotidien des nouveaux messages de forum de {$a->sitename}. Pour modifier vos pr&eacute;f&eacute;rences de notification, allez &agrave; {$a->userprefs}.';
$string['digestmailprefs'] = 'Votre profil';
$string['digestmailsubject'] = '{$a}: r&eacute;sum&eacute; du forum';
$string['unsubscribe'] = 'Se d&eacute;sabonner de ce forum';
$string['unsubscribeall'] = 'Se d&eacute;sabonner de tous les forums';
$string['postmailinfo'] = 'Ceci est la copie du message de forum post&eacute; sur le site {$a}.<br>Pour ajouter une r&eacute;ponse au message depuis {$a}, cliquez sur le lien suivant :';
$string['discussionoptions'] = 'Options de la discussion';
$string['forum'] = 'Forum';
$string['subscribed'] = 'Abonn&eacute;';
$string['subscribegroup'] = 'S\'abonner &agrave; ce groupe';
$string['subscribeshort'] = 'S\'abonner';
$string['subscribelong'] = 'S\'abonner &agrave; tout le forum';
$string['unsubscribegroup'] = 'Se d&eacute;sabonner de ce groupe';
$string['unsubscribegroup_partial'] = 'Se d&eacute;sabonner des discussions de ce groupe';
$string['unsubscribeshort'] = 'Se d&eacute;sabonner';
$string['unsubscribelong'] = 'Se d&eacute;sabonner du forum';
$string['subscribediscussion'] = 'S\'abonner &agrave; ce fil de discussions';
$string['unsubscribediscussion'] = 'Se d&eacute;sabonner de ce fil de discussions';
$string['subscribeddiscussionall'] = 'Toutes les discussions';
$string['subscribedthisgroup'] = 'Ce groupe';
$string['numberofdiscussions'] = 'Discussions {$a} ';
$string['numberofdiscussion'] = 'Discussion {$a}';
$string['discussions'] = 'Discussions';
$string['posts'] = 'Messages';
$string['subscribe'] = 'S\'abonner &agrave; ce forum';
$string['allsubscribe'] = 'S\'abonner &agrave; tous les forums';
$string['allunsubscribe'] = 'Se d&eacute;sabonner de tous les forums';
$string['forumname'] = 'Nom du forum';
$string['forumtype'] = 'Type de forum';
$string['forumtype_help'] = 'Diff&eacute;rents types de forums sont disponibles &agrave; des fins sp&eacute;cifiques ou &agrave; des m&eacute;thodes d\'enseignement. Le type de forum standard est appropri&eacute; pour toutes les conditions normales d\'utilisation.';
$string['forumtype_link'] = 'mod/forumng/forumtypes';
$string['forumintro'] = 'Introduction au forum';
$string['ratingtime'] = 'Restreindre l\'&eacute;valuation aux &eacute;l&eacute;ments dont les dates sont dans cet intervalle :';
$string['ratings'] = '&eacute;valuations';
$string['grading'] = 'Type de combinaison';
$string['grading_help'] = 'Si vous s&eacute;lectionnez cette option, une note pour ce forum sera ajout&eacute;e au carnet de notes du cours et sera calcul&eacute;e automatiquement. Laissez vide pour un forum non-&eacute;valu&eacute;, ou si vous pr&eacute;voyez de l\'&eacute;valuer manuellement.<br>Les diff&eacute;rentes mani&egrave;res de calculer sont assez explicites : dans chaque cas, la note pour chaque utilisateur est calcul&eacute;e sur la base de toutes les notes pour tous les messages qu\'il a post&eacute; dans le forum. Le classement est limit&eacute;s &agrave; l\'&eacute;chelle, par exemple si l\'&eacute;chelle est de 0-3, la m&eacute;thode de classement est sur «compter» et le classement des messages de l\'utilisateur ayant re&ccedil;u 17 votes sera de 3.';
$string['nodiscussions'] = 'Il n\'y a pas encore de discussion dans ce forum.';
$string['startedby'] = 'Lanc&eacute; par';
$string['discussion'] = 'Discussion';
$string['unread'] = 'Non lu';
$string['lastpost'] = 'Dernier message';
$string['group'] = 'Groupe';
$string['addanewdiscussion'] = 'Ajouter une discussion';
$string['subject'] = 'Sujet';
$string['message'] = 'Message';
$string['subscribestart'] = 'M\'envoyer des copies par courriel des message de ce forum';
$string['subscribestop'] = 'Je ne veux pas de copies par courriel des message de ce forum';
$string['mailnow'] = 'Envoyer maintenant';
$string['displayperiod'] = 'P&eacute;riode d\'affichage';
$string['subscriptions'] = 'Abonnements';
$string['nosubscribers'] = 'Il n\'y a pas encore d\'abonn&eacute; &agrave; ce forum.';
$string['subscribers'] = 'Abonn&eacute;s';
$string['numposts'] = '{$a} message(s)';
$string['noguestsubscribe'] = 'D&eacute;sol&eacute;, les visiteurs ne sont pas autoris&eacute;s &agrave; s\'abonner pour recevoir les messages des forums par courriel.';
$string['discussionsperpage'] = 'Discussions par page';
$string['configdiscussionsperpage'] = 'Nombre maximal de discussions affich&eacute;es sur une page';
$string['attachmentmaxbytes'] = 'Taille maximale de l\'annexe';
$string['attachmentmaxbytes_help'] = 'Il est possible de limiter la taille des annexes. Cette limite est fix&eacute;e par la personne qui met en place le forum.';
$string['configattachmentmaxbytes'] = 'Taille maximale des annexes des forums (cette taille d&eacute;pend par ailleurs des limites d&eacute;finies au niveau du cours et d\'autres r&eacute;glages locaux)';
$string['readafterdays'] = 'D&eacute;lai de lecture';
$string['configreadafterdays'] = 'Apr&egrave;s ce nombre de jours, les messages sont consid&eacute;r&eacute;s comme &eacute;tant lus par tous les usagers.';
$string['trackreadposts'] = 'Activer le suivi des messages';
$string['grading_average'] = 'Moyenne des &eacute;valuations';
$string['grading_count'] = 'Nombre d\'&eacute;valuations';
$string['grading_max'] = 'Evaluation maximale';
$string['grading_min'] = 'Evaluation minimale';
$string['grading_none'] = 'Pas d\'&eacute;valuation';
$string['grading_sum'] = 'Somme des &eacute;valuations';
$string['subscription_permitted'] = 'Abonnement facultatif';
$string['subscription_forced'] = 'Abonnement impos&eacute;';
$string['enableratings'] = 'Autoriser l\'&eacute;valuation des messages';
$string['enableratings_help'] = 'Si l\'option est activ&eacute;e, les messages du forum peuvent &ecirc;tre &eacute;valu&eacute;s en utilisant une &eacute;chelle num&eacute;rique par d&eacute;faut ou d&eacute;finie. Une ou plusieurs personnes peuvent &eacute;valuer le message et l\'&eacute;valuation affich&eacute;e est la moyenne de ces &eacute;valuations.<br>Si vous utilisez une &eacute;chelle num&eacute;rique jusqu\'&agrave; 5 (ou moins), une jolie «&eacute;toile» est affich&eacute;e. Sinon, c\'est une liste d&eacute;roulante.<br>Les r&ocirc;les contr&ocirc;lent qui peut &eacute;valuer et voir les &eacute;valuations. Par d&eacute;faut, seuls les enseignants peuvent &eacute;valuer les messages, et les &eacute;tudiants ne peuvent voir que les notes sur leurs propres messages.';
$string['markdiscussionread'] = 'Marquer tous les messages de ce fil de discusisons comme lus.';
$string['forumng:addinstance'] = 'Ajouter un nouveau ForumNG';
$string['forumng:createattachment'] = 'Annexe';
$string['forumng:deleteanypost'] = 'Supprimer chaque message';
$string['forumng:editanypost'] = 'Editer chaque message';
$string['forumng:managesubscriptions'] = 'G&eacute;rer les abonnements';
$string['forumng:movediscussions'] = 'D&eacute;placer les discussions';
$string['forumng:rate'] = 'Evaluer les messages';
$string['forumng:replypost'] = 'R&eacute;pondre aux messages';
$string['forumng:splitdiscussions'] = 'S&eacute;parer la discussion';
$string['forumng:startdiscussion'] = 'D&eacute;marrer une nouvelle discussion';
$string['forumng:viewanyrating'] = 'Voir toutes les &eacute;valuations';
$string['forumng:viewdiscussion'] = 'Voir les discussions';
$string['forumng:viewrating'] = 'Voir les &eacute;valuations de ses propores messages';
$string['forumng:viewsubscribers'] = 'Voir les abonn&eacute;s';
$string['forumng:copydiscussion'] = 'Copier la discussion';
$string['forumng:forwardposts'] = 'Transf&eacute;rer le(s) message(s) par courriel';
$string['pluginadministration'] = 'Administration ForumNG';
$string['modulename'] = 'ForumNG';
$string['pluginname'] = 'ForumNG';
$string['modulenameplural'] = 'ForumsNG';
$string['forbidattachments'] = 'Annexe impossible';
$string['configenablerssfeeds'] = 'Cette option permet l\'activation des flux RSS pour tous les forums. Il est en outre n&eacute;cessaire d\'activer manuellement les flux RSS dans les r&eacute;glages de chaque forum.';
$string['allowsubscribe'] = 'Abonnement facultatif';
$string['initialsubscribe'] = 'Abonnement automatique';
$string['perforumoption'] = 'A configurer s&eacute;par&eacute;ment pour chaque forum';
$string['configsubscription'] = 'Configuration des options de notification par courriel sur tous les forums du site.';
$string['feedtype']='Flux RSS de cette activit&eacute;';
$string['feedtype_help']='Cette option vous permet d\'activer le flux RSS de ce forum.<br>Vous pouvez choisir entre deux types de flux RSS :<br>Discussions : le flux g&eacute;n&eacute;r&eacute; comprendra les nouvelles discussions du forum avec leur message initial.<br>Messages : le flux g&eacute;n&eacute;r&eacute; comprendra tous les nouveaux messages post&eacute;s dans le forum..';
$string['configfeedtype']='S&eacute;lectionner l\'information &agrave; inclure dans tous les flux RSS du forum.';
$string['feedtype_none']='Flux RSS d&eacute;sactiv&eacute;';
$string['feedtype_discussions']='Discussions';
$string['feedtype_all_posts']='Messages';
$string['permanentdeletion']='Supprimer d&eacute;finitivement les donn&eacute;es obsol&egrave;tes';
$string['configpermanentdeletion']='Apr&egrave;s cette p&eacute;riode, les messages supprim&eacute;s et les anciennes versions de messages &eacute;dit&eacute;s seront d&eacute;finitivement effac&eacute;s de la base de donn&eacute;es.';
$string['permanentdeletion_never']='Jamais (ne pas supprimer les donn&eacute;es obsol&egrave;tes)';
$string['permanentdeletion_soon']='D&egrave;s que possible';
$string['usebcc']='Envoyer un courriel en copie invisible (\'Cci\')';
$string['configusebcc']='Laisser cette valeur &agrave; 0 pour utiliser les r&eacute;glages par d&eacute;faut de la messagerie Moodle (plus s&ucirc;r).<br>Indiquez un chiffre (par exemple 50) pour regrouper les messages du forum avec l\'option \'copie invisible\' (\'Cci\') pour que Moodle envoie un courriel unique depuis votre serveur de messagerie &agrave; de nombreux utilisateur.<br>Au lieu d\'envoyer 50 fois 1 message aux 50 personnes, cela envoie 1 message aux 50 personnes en copie invisible.';
$string['donotmailafter']='Ne pas envoyer de courriel apr&egrave;s (heure(s))';
$string['configdonotmailafter']='Pour &eacute;viter l\'envoi d\'un nombre trop important de messages si le cron de maintenance n\'a pas &eacute;t&eacute; ex&eacute;cut&eacute; r&eacute;cemment, le forum n\'enverra pas les courriels correspondants aux messages plus vieux que ce nombre d\'heures.';
$string['re']='Re: {$a}';
$string['discussionsunread']='Discussions (non lus)';
$string['feeds'] = 'Flux RSS';
$string['atom'] = 'Atom';
$string['subscribe_confirm'] = 'Vous avez &eacute;t&eacute; abonn&eacute;.';
$string['unsubscribe_confirm'] = 'Vous avez &eacute;t&eacute; d&eacute;sabonn&eacute;.';
$string['subscribe_confirm_group'] = 'Vous avez &eacute;t&eacute; abonn&eacute; au groupe.';
$string['unsubscribe_confirm_group'] = 'Vous avez &eacute;t&eacute; d&eacute;sabonn&eacute; du groupe.';
$string['subscribe_already'] = 'Vous &ecirc;tes d&eacute;j&agrave; abonn&eacute;.';
$string['subscribe_already_group'] = 'Vous &ecirc;tes d&eacute;j&agrave; abonn&eacute; &agrave; ce groupe.';
$string['unsubscribe_already'] = 'Vous &ecirc;tes d&eacute;j&agrave; d&eacute;sabonn&eacute;.';
$string['unsubscribe_already_group'] = 'Vous &ecirc;tes d&eacute;j&agrave; d&eacute;sabonn&eacute; de ce groupe.';
$string['subscription_initially_subscribed'] = 'Abonnement automatique';
$string['subscription_not_permitted'] = 'Abonnement d&eacute;sactiv&eacute;';
$string['feeditems'] = 'Nombre d\'articles RSS r&eacute;cents';
$string['feeditems_help'] = 'Nombre d\'articles inclus dans le flux Atom/RSS. Si ce param&egrave;tre est r&eacute;gl&eacute; trop bas, les utilisateurs qui ne v&eacute;rifient pas souvent le flux pourraient manquer des messages.';
$string['configfeeditems'] = 'Nombre de messages r&eacute;cents inclus dans un flux.';
$string['limitposts'] = 'Nombre maximal de messages';
$string['enablelimit'] = 'Nombre maximal de messages par utilisateur';
$string['enablelimit_help'] = 'Ce r&eacute;glage d&eacute;finit le nombre maximal de messages qu\'un participant peut poster durant une p&eacute;riode donn&eacute;e. Les utilisateurs ayant la capacit&eacute; <tt>mod/forumng:ignorethrottling</tt> ne sont pas touch&eacute;s par les limites de message.<br>Quand un utilisateur n\'est autoris&eacute; qu\'&agrave;  3 messages par exemple, un avertissement s\'affiche sous la forme d\'un message. Apr&egrave;s la limite, le syst&egrave;me affiche le moement auquel il sera en mesure de poster &agrave; nouveau un message dans le forum.';
$string['completiondiscussions'] = 'L\'utilisateur doit cr&eacute;er une discussion :';
$string['completiondiscussionsgroup'] = 'Nombre de discussions requises :';
$string['completiondiscussionsgroup_help'] = 'Si l\'option est activ&eacute;e, le forum sera marqu&eacute; complet pour un utilisateur une fois qu\'il a le nombre requis de nouvelles discussions (et a rempli une autre condition).';
$string['completionposts'] = 'L\'utilisateur doit lancer des discussions ou poster des messages :';
$string['completionpostsgroup'] = 'Nombre de discussions/messages requis';
$string['completionpostsgroup_help'] = 'Si l\'option est activ&eacute;e, le forum sera marqu&eacute; complet pour un utilisateur une fois qu\'il a le nombre requis de discussions et r&eacute;ponses, en comptant chaque discussion ou r&eacute;ponse pour 1 (et a rempli toutes les autres conditions).';
$string['completionreplies'] = 'L\'utilisateur doit r&eacute;pondre &agrave; des messages :';
$string['completionrepliesgroup'] = 'Nombre de r&eacute;ponses requises :';
$string['completionrepliesgroup_help'] = 'Si l\'option est activ&eacute;e, le forum sera marqu&eacute; complet pour un utilisateur une fois qu\'il a le nombre requis de r&eacute;ponses &agrave; des discussions existantes (et a rempli toutes les autres conditions).';
$string['ratingfrom'] = 'Evaluer les &eacute;l&eacute;ments &agrave; partir du ';
$string['ratinguntil'] = 'Evaluer les &eacute;l&eacute;ments jusqu\'au ';
$string['postingfrom'] = 'Poster des messages &agrave; partir du ';
$string['postinguntil'] = 'Poster des messages jusqu\'au ';
$string['postsper'] = 'Messages sur';
$string['alt_discussion_deleted'] = 'Discussion supprim&eacute;e';
$string['alt_discussion_timeout'] = 'Actuellement invisible pour les utilisateurs (limite de temps)';
$string['alt_discussion_sticky'] = 'Cette discussion appara&icirc;t toujours en t&ecirc;te de liste';
$string['alt_discussion_locked'] = 'Discussion en lecture seule';
$string['subscribestate_partiallysubscribed'] = 'Vous recevez des messages provenant de certaines discussions de ce forum par courriel &agrave; {$a}.';
$string['subscribestate_partiallysubscribed_thisgroup'] = 'Vous recevez des messages provenant de certaines discussions de ce groupe  par courriel &agrave; {$a}.';
$string['subscribestate_groups_partiallysubscribed'] = 'Vous recevez des messages provenant de certains groupes de ce forum par courriel &agrave; {$a}.';
$string['subscribestate_subscribed'] = 'Vous recevez des messages de ce forum par courriel &agrave; {$a}.';
$string['subscribestate_subscribed_thisgroup'] = 'Vous recevez des messages de ce groupe par courriel &agrave; {$a}.';
$string['subscribestate_subscribed_notinallgroup'] = 'Cliquer sur &lsquo;D&eacute;sabonnement&rsquo; pour se d&eacute;sabonner de ce forum.';
$string['subscribestate_unsubscribed'] = 'Vous ne recevez pas les messages de ce forum par courriel. Si vous le souhaitez, cliquez sur &lsquo;S\'abonner&rsquo;.';
$string['subscribestate_unsubscribed_thisgroup'] = 'Vous ne recevez pas les messages de ce groupe par courriel. Si vous le souhaitez, cliquez sur &lsquo;S\'abonner&rsquo;';
$string['subscribestate_not_permitted'] = 'Ce forum ne permet pas l\'abonnement par courriel.';
$string['subscribestate_forced'] = '(Le d&eacute;sabonnement n\'est pas possible.)';
$string['subscribestate_no_access'] = 'Vous n\'avez pas la possibilit&eacute; de vous abonner par courriel &agrave; ce forum.';
$string['subscribestate_discussionsubscribed'] = 'Vous recevez tous les messages de cette discussion par courriel &agrave; {$a}.';
$string['subscribestate_discussionunsubscribed'] = 'Vous ne recevez pas les messages de cette discussion par courriel. Si vous le souhaitez, cliquez sur &lsquo;S\'abonner&rsquo;.';
$string['replytopost'] = 'R&eacute;pondre au message : {$a}';
$string['editpost'] = 'Editer le message : {$a}';
$string['editdiscussionoptions'] = 'Editer les options de la discussions : {$a}';
$string['optionalsubject'] = 'Changer le sujet (facultatif)';
$string['attachmentnum'] = 'Annexe {$a}';
$string['sticky'] = 'Option de mise en avant de la discussion :';
$string['sticky_no'] = 'La discussion est tri&eacute;e normalement';
$string['sticky_yes'] = 'La discussion est palc&eacute;e en t&ecirc;te de liste';
$string['timestart'] = 'Afficher &agrave; partir de';
$string['timeend'] = 'Afficher jusqu\'&agrave; (inclus)';
$string['date_asc'] = 'les plus anciens';
$string['date_desc'] = 'les plus r&eacute;cents';
$string['numeric_asc'] = 'les plus nombreux';
$string['numeric_desc'] = 'les moins nombreux';
$string['sorted'] = 'tri&eacute; par {$a}';
$string['text_asc'] = 'A-Z';
$string['text_desc'] = 'Z-A';
$string['sortby'] = 'Trier par {$a}';
$string['rate'] = 'Evaluation';
$string['expand'] = 'D&eacute;velopper <span class=\'accesshide\'> le message {$a}</span>';
$string['postnum'] = 'Message {$a->num}';
$string['postnumreply'] = 'Message {$a->num}{$a->info} en r&eacute;ponse &agrave; {$a->parent}';
$string['postinfo_short'] = 'R&eacute;sum&eacute;';
$string['postinfo_unread'] = 'Non lu';
$string['postinfo_deleted'] = 'Effac&eacute;';
$string['split'] = 'S&eacute;parer<span class=\'accesshide\'> le message {$a}</span>';
$string['reply'] = 'R&eacute;pondre<span class=\'accesshide\'> au message {$a}</span>';
$string['directlink'] = 'Permalien<span class=\'accesshide\'> du message {$a}</span>';
$string['directlinktitle'] = 'Lien direct vers ce message';
$string['edit'] = 'Editer<span class=\'accesshide\'> le message {$a}</span>';
$string['delete'] = 'Effacer<span class=\'accesshide\'> le message {$a}</span>';
$string['undelete'] = 'Restaurer<span class=\'accesshide\'> le message {$a}</span>';
$string['deletedpost'] = 'Message effac&eacute;.';
$string['deletedbyauthor'] = 'Ce message a &eacute;t&eacute; supprim&eacute; par l\'auteur le {$a}.';
$string['deletedbymoderator'] = 'Ce message a &eacute;t&eacute; supprim&eacute; par un mod&eacute;rateur le {$a}.';
$string['deletedbyuser'] = 'Ce message a &eacute;t&eacute; supprim&eacute; par {$a->user} le {$a->date}.';
$string['expandall'] = 'D&eacute;velopper tous les messages';
$string['deletepost'] = 'Message effac&eacute; : {$a}';
$string['undeletepost'] = 'Message restaur&eacute; : {$a}';
$string['confirmdelete'] = 'Etes-vous s&ucirc;r de vouloir supprimer ce message ?';
$string['confirmdelete_notdiscussion'] = 'La suppression de ce message ne supprimera pas la discussion. Si vous souhaitez supprimer la discussion, utilisez les commandes au bas de la page de discussion.';
$string['confirmundelete'] = 'Etes-vous s&ucirc;r de vouloir restaurer ce message ?';
$string['splitpost'] = 'S&eacute;parer le message : {$a}';
$string['splitpostbutton'] = 'S&eacute;parer le message';
$string['splitinfo'] = 'S&eacute;parer ce message le supprimera, ainsi que toutes les r&eacute;ponses, de cette discussion. Une nouvelle discussion sera alors cr&eacute;&eacute;e (comme illustr&eacute; ci-dessous)';
$string['editbyself'] = 'Modifi&eacute; par l\'auteur le {$a}';
$string['editbyother'] = 'Modifi&eacute; par {$a->name} le {$a->date}';
$string['history'] = 'Historique';
$string['historypage'] = 'Historique : {$a}';
$string['currentpost'] = 'Version actuelle du message';
$string['olderversions'] = 'Anciennes versions (la plus r&eacute;cente en premier)';
$string['deleteemailpostbutton'] = 'Supprimer et notifier';
$string['deleteandemail'] = 'Supprimer et notifier l\'auteur par courriel';
$string['emailmessage'] = 'Message';
$string['emailcontentplain'] = 'Ceci est une notification pour vous informer que votre message sur le forum avec les d&eacute;tails suivants a &eacute;t&eacute; supprim&eacute; par \'{$a->firstname} {$a->lastname}\':

Sujet : {$a->subject}
Forum : {$a->forum}
Espace de cours : {$a->course}

Cliquez sur {$a->deleteurl} pour voir la discussion';
$string['emailcontenthtml'] = 'Ceci est une notification pour vous informer que votre message sur le forum avec les d&eacute;tails suivants a &eacute;t&eacute; supprim&eacute; par \'{$a->firstname} {$a->lastname}\':<br />
<br />
Sujet : {$a->subject}<br />
Forum : {$a->forum}<br />
Espace de cours : {$a->course}<br/>
<br/>
<a href="{$a->deleteurl}" title="voir le message supprim&eacute;">Voir la discussion</a>';
$string['copytoself'] = 'S\'envoyer une copie';
$string['deletedforumpost'] = 'Votre message a &eacute;t&eacute; supprim&eacute;';
$string['emailerror'] = 'Il y a eu une erreur lors de l\envoi du courriel';
$string['sendanddelete'] = 'Envoyer et effacer';
$string['deletepostbutton'] = 'Effacer';
$string['undeletepostbutton'] = 'Restaurer le message';
$string['averagerating'] = 'Moyenne des &eacute;valuations : {$a->avg} (de {$a->num})';
$string['yourrating'] = 'Votre &eacute;valuation :';
$string['ratingthreshold'] = 'Nombre d\'&eacute;valuations requises avant affichage de la note';
$string['ratingthreshold_help'] = 'Si vous d&eacute;finissez cette option &agrave; 3, alors la note pour un message ne sera pas visible avant que 3 personnes n\'&eacute;valuent le message.<br>Cela peut aider &agrave; r&eacute;duire l\'effet d\'une note unique sur la moyenne.';
$string['saveallratings'] = 'Enregistrer toutes les &eacute;valuations';
$string['js_nratings'] = '(# &eacute;valuations)';
$string['js_nratings1'] = '(1 &eacute;valuation)';
$string['js_publicrating'] = 'Moyenne des &eacute;valuations : #.';
$string['js_nopublicrating'] = 'Pas encore &eacute;valu&eacute;.';
$string['js_userrating'] = 'Votre &eacute;valuation : #.';
$string['js_nouserrating'] = 'Vous n\'avez pas encore &eacute;valu&eacute; cet &eacute;l&eacute;ment.';
$string['js_outof'] = '(Hors de #)';
$string['js_clicktosetrating'] = 'Cliquer pour attribuer # &eacute;toiles &agrave; ce message.';
$string['js_clicktosetrating1'] = 'Cliquer pour attribuer 1 &eacute;toile &agrave; ce message.';
$string['js_clicktoclearrating'] = 'Cliquer pour supprimer votre &eacute;valuation.';
$string['undelete'] = 'Restaurer';
$string['exportword'] = 'Exportation au format Word';
$string['exportedtitle'] = 'Discussions &lsquo;{$a->subject}&rsquo; du forum export&eacute;e le {$a->date}';
$string['set'] = 'R&eacute;glages';
$string['showusername'] = 'Afficher les noms d\'utilisateurs';
$string['configshowusername'] = 'Inclut les noms d\'utilisateur dans les rapports li&eacute;s au forum (peut &ecirc;tre vu par les mod&eacute;rateurs mais pas les &eacute;tudiants)';
$string['showidnumber'] = 'Afficher les num&eacute;ros d\'identification';
$string['configshowidnumber'] = 'Inclut les num&eacute;ros d\'identification dans les rapports li&eacute;s au forum (peut &ecirc;tre vu par les mod&eacute;rateurs mais pas par les &eacute;tudiants)';
$string['hidelater'] = 'Ne plus montrer ces instructions';
$string['existingattachments'] = 'Annexe(s) existante(s)';
$string['deleteattachments'] = 'Supprimer l\'annexe existante';
$string['attachments'] = 'Annexes';
$string['attachment'] = 'Annexe';
$string['choosefile'] = '1. Choisir le fichier';
$string['clicktoadd'] = '2. Ajouter';
$string['readdata'] = 'Lecture des donn&eacute;es';
$string['search_update_count'] = '{$a} forums &agrave; traiter.';
$string['searchthisforum'] = $string['searchthisforumlink'] = 'Rechercher dans ce forum';
$string['viewsubscribers'] = 'Voir les abonn&eacute;s';
$string['inreplyto'] = 'En r&eacute;ponse &agrave;';
$string['forumng:view'] = 'Voir les forums';
$string['forumng:ignorepostlimits'] = 'Ignorer la limitation du nombre de messages';
$string['forumng:mailnow'] = 'Notifier par courriel avant la fin du d&eacute;lai d\'&eacute;dition';
$string['forumng:setimportant'] = 'Marquer les messages comme <strong>Important</strong>';
$string['forumng:managediscussions'] = 'G&eacute;rer les options de la discussion';
$string['forumng:viewallposts'] = 'Voir les messgaes cach&eacute;s et effac&eacute;s';
$string['forumng:viewreadinfo'] = 'Voir qui a lu un message';
$string['editlimited'] = 'Attention : enregistrez toutes les modifications apport&eacute;es &agrave; ce message avant {$ a}. Apr&egrave;s, toute modification vous sera impossible.';
$string['badbrowser'] = '<h3>Fonctionnalit&eacute;s du forum sont r&eacute;duites</h3>&nbsp;<p>Vous utilisez $a. Si vous souhaitez enrichir l\'exp&eacute;rience dans l\'utilisation des forums, vous devez mettre &agrave; jour votre version d\'<a href=\'http://www.microsoft.com/windows/internet-explorer/\'>Internet Explorer</a> ou <a href=\'http://www.mozilla.com/firefox/\'>de Firefox</a>.</p>';
$string['nosubscribersgroup'] = 'Personne du groupe n\'est encore abonn&eacute; &agrave; ce forum.';
$string['hasunreadposts'] = '(Messages non lus)';
$string['postdiscussion'] = 'Envoyer';
$string['postreply'] = 'Envoyer une r&eacute;ponse';
$string['confirmbulkunsubscribe'] = 'Etes-vous s&ucirc;r de vouloir d&eacute;sinscrire les utilisateurs s&eacute;lectionn&eacute;s dans la liste ci-dessous (l\'op&eacute;ration ne peut &ecirc;tre annul&eacute;e.)';
$string['savedraft'] = 'Enregistrer comme brouillon';
$string['draftexists'] = 'Une version de ce brouillon a &eacute;t&eacute; sauvegard&eacute; le $a. Si vous ne terminez pas la r&eacute;daction de ce message maintenant, vous le retrouverez en tant que brouillon sur la page principale de ce forum.';
$string['draft_inreplyto'] = '(en r&eacute;ponse &agrave; {$a})';
$string['draft_newdiscussion'] = '(nouvelle discussion)';
$string['drafts'] = 'Brouillons inachev&eacute;s';
$string['deletedraft'] = 'Supprimer le brouillon';
$string['confirmdeletedraft'] = 'Etes-vous s&ucirc;r de vouloir supprimer les brouillons de la liste ci-dessous ?';
$string['draft'] = 'Brouillon';
$string['collapseall'] = 'R&eacute;duire tous les messages';
$string['selectlabel'] = 'S&eacute;lectionner le message {$a}';
$string['selectintro'] = 'Cocher chaque message que vous souhaitez inclure dans la s&eacute;lection. Lorsque que votre s&eacute;lection est termin&eacute;e, cliquer sur \'Confirmer la s&eacute;lection\' en bas de la page.';
$string['confirmselection'] = 'Confirmer la s&eacute;lection';
$string['selectedposts'] = 'Messages s&eacute;lectionn&eacute;s';
$string['selectorall'] = 'Voulez-vous inclure la discussion compl&egrave;te ou seulement les messages s&eacute;lectionn&eacute;s ?';
$string['setimportant'] = 'Marquer ce message comme \'Important\'';//used by moderators, highlight important posts
$string['important'] = 'Message important'; // alt text for important icon
$string['flaggedposts'] = 'Messages marqu&eacute;s';
$string['flaggedpostslink'] = '{$a} message(s) marqu&eacute;(s) comme Important';
$string['post'] = 'Message';
$string['author'] = 'Auteur';
$string['clearflag'] = 'Retirer le marqueur';
$string['setflag'] = 'Marquer ce message pour une r&eacute;f&eacute;rence ult&eacute;rieure';
$string['flagon'] = 'Vous avez marqu&eacute; ce message';
$string['flagoff'] = 'Non marqu&eacute;';
$string['postby'] = '(par {$a})';
$string['quotaleft_plural'] = 'Vous ne pouvez publier plus que <strong>$a->posts</strong> messages au cours de ces $a->period -ci.';
$string['quotaleft_singular'] = 'Vous ne pouvez publier plus que <strong>$a->posts</strong> messages au cours de ce $a->period -ci.';
$string['studyadvice_noyourquestions'] = 'Vous n\'avez pas encore commenc&eacute; de discussion dans ce forum d\'apprentissage.';
$string['studyadvice_noquestions'] = 'Personne n\'a commenc&eacute; de discussion actuellement dans ce forum d\'apprentissage.';
$string['jumpto'] = 'Aller &agrave; :';
$string['jumpnext'] = 'Message non-lu suivant';
$string['jumpprevious'] = 'Message non-lu pr&eacute;c&eacute;dent';
$string['jumppreviousboth'] = 'Pr&eacute;c&eacute;dent';
$string['skiptofirstunread'] = 'Passer au premier message non lu';
$string['enableadvanced'] = 'Activer les fonctions avanc&eacute;es';
$string['configenableadvanced'] = 'Cette option active les fonctionnalit&eacute;s avanc&eacute;es du forum qui peuvent &ecirc;tre inutilement complexes pour l\'utilisation standard du forum.<br>Actuellement, il ne s\'agit que du partage de forum mais d\'autres fonctionnalit&eacute;s seront ajout&eacute;es par la suite.';
$string['shared'] = 'Autoriser le partage de ce forum';
$string['shared_help'] = 'Cochez cette case et d&eacute;finissez le num&eacute;ro d\'identification dans le champ ci-dessous, afin de permettre le partage de ce forum.<br>Ce forum va devenir le forum d\'origine. Vous pouvez ensuite cr&eacute;er un ou plusieurs exemplaires de ce forum en choisissant <strong>Forum partag&eacute; existant</strong>, et en indiquant le m&ecirc;me num&eacute;ro d\'identification lors de la cr&eacute;ation de chaque copie.';
$string['sharing'] = 'Partage de forum';
$string['useshared'] = 'Utiliser un forum partag&eacute; existant';
$string['useshared_help'] = 'Si vous voulez partager un forum existant, cochez cette case et indiquez le num&eacute;ro d\'identification du forum d\'origine (qui doit autoriser le partage).<br>Lorsque cette option est activ&eacute;e, la plupart des autres options sur ce formulaire seront ignor&eacute;es car vous n\'&ecirc;tes pas vraiment dans la cr&eacute;ation d\'un nouveau forum, mais vous activez un lien vers un forum existant. La seule exception est la disponibilit&eacute; et les options d\'ach&egrave;vement (manuelle seulement).';
$string['sharedinfo'] = 'Il s\'agit d\'un forum partag&eacute;. Les param&egrave;tres d\'acc&egrave;s ici pr&eacute;sents ne sont pas partag&eacute;s, et s\'appliquent exclusivement aux &eacute;tudiants qui acc&egrave;dent au forum partag&eacute; de ce cours. Si vous souhaitez &eacute;diter d\'autres param&egrave;tres pour le forum, merci <a href=\'$a\'> d\'&eacute;diter les param&egrave;tres du forum d\'origine.</a>.';
$string['sharedviewinfooriginal'] = '<strong>Ce forum est partag&eacute;</strong> sous l\'identifiant <strong>$a</strong> pour une utilisation dans d\'autres cours.';
$string['sharedviewinfonone'] = 'Il n\'est actuellement utilis&eacute; dans aucun autre cours.';
$string['sharedviewinfolist'] = 'Il est utilis&eacute; dans le(s) cours suivant(s) : {$a}.';
$string['sharedviewinfoclone'] = '<strong>Il s\'agit d\'un forum partag&eacute;</strong>. Le <a href=\'{$a->url}\'>forum d\'origine</a> se trouve dans le cours {$a->shortname}.';
$string['jumpparent'] = 'Parent';
$string['savetoportfolio'] = 'Sauvegarder dans "Mon dossier"';
$string['savedposts_all'] = '{$a}';
$string['savedposts_selected'] = '{$a} (messages s&eacute;lectionn&eacute;s)';
$string['savedposts_one'] = '{$a->name}: {$a->subject}';
$string['savedposts_all_tag'] = 'Discussion du forum';
$string['savedposts_selected_tag'] = 'Messages du forum';
$string['savedposts_one_tag'] = 'Message du forum';
$string['savedposts_original'] = 'Source originale de la discussion';
$string['savedtoportfolio'] = 'Les informations ont &eacute;t&eacute; sauv&eacute;es dans "Mon dossier".';
$string['offerconvert'] = 'Si vous voulez cr&eacute;er un nouveau ForumNG comme copie de l\'ancienne-version, veillez ne pas utiliser ce formulaire. Utilisez plut&ocirc;t, <a href=\'{$a}\'>Convertir les forums.';
$string['convert_title'] = 'Convertir les forums';
$string['convert_info'] = 'La conversion peut fonctionner sur un ou plusieurs forums \'anciens\'; actuellement seuls les forums de type \'g&eacute;n&eacute;ral\' sont support&eacute;s. Pour s&eacute;lectionner plusieurs forums dans la liste, utiliser la touche Ctrl.';
$string['convert_warning'] = '<p>Lorsque vous cliquez sur \'Convertir\', le forum s&eacute;lectionn&eacute; sera converti. <br>Tous les messages et discussions
qu\'il contient seront trait&eacute;s, et cela peut prendre quelques minutes. Pendant la conversion les forums seront alors indisponibles.</p>
<ul>
<li>les anciens forums convertis seront cach&eacute;s durant l\'ex&eacute;cution du processus de conversion. Cela vous garantit qu\'aucun nouveau message ne soit publi&eacute; et \'exclus\' de la conversion</li>
<li>les nouveaux forums en cr&eacute;ation restent cach&eacute;s durant toute la dur&eacute;e de la conversion. Ils ne seront seulement r&eacute;v&eacute;l&eacute;s qu\'apr&egrave;s la fin du processus.</li>
</ul>';
$string['convert_hide'] = 'Laisser les nouveaux forums cr&eacute;&eacute;s invisibles';
$string['convert_nodata'] = 'Ne pas inclure les donn&eacute;es li&eacute;es aux utilisateurs (messages, abonnements, etc.)';
$string['convert_process_init'] = 'Cr&eacute;ation de la structure du forum...';
$string['convert_process_state_done'] = 'Termin&eacute;.';
$string['convert_process_show'] = 'Rendre le forum visible...';
$string['convert_process_subscriptions_normal'] = 'Conversion des abonnements normaux...';
$string['convert_process_subscriptions_initial'] = 'Conversion des abonnements initiaux...';
$string['convert_process_discussions'] = 'Conversions des discussions...';
$string['convert_process_dashboard'] = 'Conversion de vos pr&eacute;f&eacute;rences de tableaux de bord...';
$string['convert_process_dashboard_done'] = 'Valid&eacute; (OK {$a->yay}, Echec {$a->nay}).';
$string['convert_process_assignments'] = 'Mise &agrave; jour de l\'attribution des r&ocirc;les...';
$string['convert_process_overrides'] = 'Mise &agrave; jour des d&eacute;rogations des r&ocirc;les...';
$string['convert_process_search'] = 'Recalcul des donn&eacute;es de recherche...';
$string['convert_process_update_subscriptions'] = 'Conversion des abonnements de groupe...';
$string['convert_process_complete'] = 'Conversion effectu&eacute;e en {$a->seconds}s (voir {$a->link}).';
$string['convert_newforum'] = 'Nouveau forum';
$string['convert_noneselected'] = 'Aucun forum n\'est s&eacute;lectionn&eacute; pour la conversion ! S&eacute;lectionner un ou plusieurs forum(s).';
$string['convert_noforums'] = 'Il n\'y aucun ancien forum attach&eacute; &agrave; ce cours &agrave; convertir.';
$string['pastediscussion']='Coller la discussion';
$string['switchto_simple_text']='La vue standard ne fonctionne pas toujours avec les outils d\'assistance technologique. Nous proposons aussi une vue simple qui permet l\'utilisation de toutes les fonctionnalit&eacute;s.';
$string['switchto_standard_text']='Vous utilisez la vue simple pour ce forum, qui devrait mieux fonctionner avec la technologie d\'assistance.';
$string['switchto_simple_link']='Basculer en vue simple.';
$string['switchto_standard_link']='Basculer en vue standard.';
$string['displayversion'] = 'Version de ForumNG : <strong>{$a}</strong>';
// OU only
$string['externaldashboardadd'] = 'Ajouter le forum au tableau de bord';
$string['externaldashboardremove'] = 'Supprimer le forum du tableau de bord';
// New error strings
$string['error_fileexception'] = 'Une erreur de traitement de fichier s\'est produite. C\'est susceptible d\'&ecirc;tre caus&eacute; par un probl&egrave;me du syst&egrave;me. Merci de r&eacute;essayer plus tard.';
$string['error_subscribeparams'] = 'Param&egrave;tre incorrect: n&eacute;cessite un identifiant ou un cours associ&eacute;.';
$string['error_nopermission'] = 'Vous n\'&ecirc;tes pas autoris&eacute; &agrave; effectuer cette demande.';
$string['error_exception'] = 'Une erreur s\'est produite sur le forum. Veuillez r&eacute;essayer plus tard ou effectuer une autre action.<div class=\'forumng-errormessage\'>Message d\'erreur : {$a}</div>';
$string['error_cannotchangesubscription'] = 'Vous n\'&ecirc;tes pas autoris&eacute; &agrave; vous abonner ou vous d&eacute;sabonner de ce forum.';
$string['error_cannotchangediscussionsubscription'] = 'Vous n\'&ecirc;tes pas autoris&eacute; &agrave; vous abonner ou vous d&eacute;sabonner &agrave; cette discussion.';
$string['error_cannotchangegroupsubscription'] = 'Vous n\'&ecirc;tes pas autoris&eacute; &agrave; vous abonner ou vous d&eacute;sabonner du groupe s&eacute;lectionn&eacute;.';
$string['error_cannotsubscribetogroup'] = 'Vous n\'&ecirc;tes pas autoris&eacute; &agrave; vous abonner au groupe s&eacute;lectionn&eacute;.';
$string['error_cannotunsubscribefromgroup'] = 'Vous n\'&ecirc;tes pas autoris&eacute; &agrave; vous d&eacute;sabonner du groupe s&eacute;lectionn&eacute;.';
$string['error_invalidsubscriptionrequest'] = 'Votre demande d\'abonnement n\'est pas valide.';
$string['error_unknownsort'] = 'Crit&egrave;re de tri inconnu.';
$string['error_ratingthreshold'] = 'Le seuil doit &ecirc;tre un nombre positif.';
$string['error_duplicate'] = 'Vous avez d&eacute;j&agrave; r&eacute;dig&eacute; un message en utilisant le formulaire pr&eacute;c&eacute;dent. (Cette erreur appara&icirc;t parfois si vous cliquez deux fois sur le bouton d\'envoi du message. Dans ce cas, votre message est sauvegard&eacute;)';
$string['edit_notcurrentpost'] = 'Vous ne pouvez pas &eacute;diter les messages supprim&eacute;s ou les versions ant&eacute;rieures des messages.';
$string['edit_timeout'] = 'Vous n\'&ecirc;tes plus autoris&eacute; &agrave; &eacute;diter ce message; le temps requis pour l\'&eacute;dition est &eacute;puis&eacute;.';
$string['edit_notyours'] = 'Vous ne pouvez pas &eacute;diter le message d\'un autre utilisateur.';
$string['edit_nopermission'] = 'Vous n\'&ecirc;tes pas autoris&eacute; &agrave; &eacute;diter ce type de message.';
$string['edit_readonly'] = 'Ce forum est en lecture seule, les messages ne peuvent &ecirc;tre &eacute;dit&eacute;s.';
$string['edit_notdeleted'] = 'Vous ne pouvez pas restaurer un message qui n\'a pas &eacute;t&eacute; supprim&eacute;.';
$string['edit_rootpost'] = 'Cette action ne peut pas s\'appliquer &agrave; un message qui d&eacute;bute une discussion.';
$string['edit_locked'] = 'Cette discussion est actuellement verrouill&eacute;e.';
$string['edit_notlocked'] = 'Cette discussion n\'est pas verrouill&eacute;e.';
$string['edit_wronggroup'] = 'Vous ne pouvez pas effectuer de changements &agrave; vos messages en dehors de votre groupe.';
$string['reply_notcurrentpost'] = 'Vous ne pouvez pas r&eacute;pondre aux messages supprim&eacute;s ou aux versions ant&eacute;rieures de ce message.';
$string['reply_nopermission'] = 'Vous ne disposez pas des droits n&eacute;cessaires pour r&eacute;pondre.';
$string['reply_readonly'] = 'Ce forum est en lecture seule, aucune r&eacute;ponses ne peuvent &ecirc;tre ajout&eacute;es.';
$string['reply_typelimit'] = 'En raison du format sp&eacute;cifique de ce forum, vous ne pouvez pas r&eacute;pondre &agrave; ce message.';
$string['reply_wronggroup'] = 'Vous ne pouvez pas r&eacute;pondre aux messages de cette discussion, car vous n\'&ecirc;tes pas dans le bon groupe.';
$string['reply_postquota'] = 'Vous ne pouvez pas r&eacute;pondre actuellement aux messages car vous avez atteint la limite maximale d\'envoi.';
$string['reply_missing'] = 'Vous ne pouvez pas r&eacute;pondre &agrave; ce message car il est introuvable.';
$string['startdiscussion_nopermission'] = 'Vous n\'avez pas la possibilit&eacute; de d&eacute;marrer de nouvelle discussion ici.';
$string['startdiscussion_groupaccess'] = 'Vous n\'avez pas la possibilit&eacute; de d&eacute;marrer de nouvelle discussion dans ce groupe.';
$string['startdiscussion_postquota'] = 'Vous ne pouvez pas commencer de nouvelle discussion car vous avez atteint la limite d\envoi.';
$string['error_markreadparams'] = 'Param&egrave;tre incorrect: n&eacute;cessite un identifiant ou un cours.';
$string['error_cannotmarkread'] = 'Vous n\'&ecirc;tes pas autoris&eacute; marquer les discussions comme "lues" dans ce forum.';
$string['error_cannotviewdiscussion'] = 'Vous n\'&ecirc;tes pas autoris&eacute; a acc&eacute;der &agrave; cette discussion.';
$string['error_cannotmanagediscussion'] = 'Vous n\'avez pas la possibilit&eacute; de g&eacute;rer cette discussion.';
$string['error_draftnotfound'] = 'Impossible de trouver le brouillon du message. Le brouillon est peut &ecirc;tre d&eacute;j&agrave; post&eacute; ou a &eacute;t&eacute; supprim&eacute;.';
$string['jserr_load'] = 'Il y a eu une erreur lors du chargement de ce message.<br>Rechargez la page et essayez &agrave; nouveau.';
$string['jserr_save'] = 'Il y a eu une erreur pendant la sauvegarde ce message.<br>Copiez le texte dans un autre programme afin de ne pas perdre son contenu, rechargez la page et essayez &agrave; nouveau.';
$string['jserr_alter'] = 'Il y a eu une erreur endommageant le contenu de votre message. <br>Rechargez la page et essayez &agrave; nouveau.';
$string['jserr_attachments'] = 'Il y avait une erreur de chargement de l\'annexe dans l\'&eacute;diteur.<br>Rechargez cette page et r&eacute;essayez.';
$string['rate_nopermission'] = 'Vous n\'avez pas la possibilit&eacute; d\'&eacute;valuer ce message ($a).';
$string['subscribers_nopermission'] = 'Vous n\'avez pas la possibilit&eacute; de voir la liste des abonn&eacute;s.';
$string['feed_nopermission'] = 'Vous n\'avez pas la permission d\'acc&eacute;der &agrave; ce fil.';
$string['feed_notavailable'] = 'Ce fil n\'est pas disponible.';
$string['crondebugdesc'] = 'Uniquement &agrave; des fins de test -- Cocher cette option pour inclure les donn&eacute;es de d&eacute;bogage dans les rapports du cron';
$string['crondebug'] = 'Donn&eacute;es de d&eacute;bogage du cron';
$string['unsubscribeselected'] = 'D&eacute;sinscrire les utilisateurs s&eacute;lectionn&eacute;s';
$string['unsubscribe_nopermission'] = 'Vous n\'avez pas l\'autorisation de d&eacute;sabonner les autres utilisateurs.';
$string['draft_noedit'] = 'L\'option  "brouillon" ne peut &ecirc;tre utilis&eacute;e pendant l\'&eacute;dition des messages.';
$string['draft_mismatch'] = 'Une erreur s\'est produite pendant l\'acc&egrave;s au brouillon du message (il se peut que vous n\'en soyez pas l\'auteur, ou bien qu\'il ne fasse pas partie de la discussion en cours).';
$string['draft_cannotreply'] = '<p>Il n\'est pas possible d\'ajouter une r&eacute;ponse pour le message faisant r&eacute;f&eacute;rence &agrave;. $a</p><p>Vous pouvez utiliser le bouton X &agrave; cot&eacute; de ce brouillon sur la page principale du forum, pour voir le contenu complet de votre brouillon (vous pouvez alors le copier/coller &agrave; destination d\'un autre emplacement) et pouvoir le supprimer.</p>';
$string['invalidemail'] = 'Cette adresse mail n\'est pas valide. Veuillez entrer une adresse mail unique.';
$string['invalidemails'] = 'Cette adresse mail n\'est pas valide. Veuillez entrez une ou plusieurs adresses, s&eacute;par&eacute;es par des espaces ou des points-virgules.';
$string['error_forwardemail'] = 'Il y a eu une erreur lors de l\'envoi du courriel &agrave;  <strong>$a</strong>. Le courriel n\'a pu &ecirc;tre envoy&eacute;.';
$string['alert_link'] = 'Signaler';
$string['alert_linktitle'] = 'Marquer ce message comme inacceptable';
$string['reportunacceptable'] = 'Courriel de contact pour le signalement de messages offensants';
$string['reportingemail'] = 'Courriel de contact pour le signalement de messages offensants';
$string['reportingemail_help'] = 'Si cette adresse mail est fournie, alors un lien "Signaler" appara&icirc;t &agrave; c&ocirc;t&eacute; de chaque message. Les utilisateurs peuvent cliquer sur le lien pour rapporter des messages offensifs. Les informations seront envoy&eacute;es &agrave; cette adresse.<br>Si cette adresse mail n\'est pas renseign&eacute;e, la fonction de rapport ne sera pas disponible (&agrave; moins qu\'une adresse sp&eacute;cifique au niveau du site a &eacute;t&eacute; fournie).';
$string['configreportunacceptable'] = 'Adresse mail pour le rapport des messages signal&eacute;s comme offensants. Si aucune adresse e-mail n\'est renseign&eacute;e, la fonction "Signaler" sera d&eacute;sactiv&eacute;e, &agrave; moins qu\'elle ne le soit au niveau d\'un forum.';
$string['alert_info'] = 'La fonction "Signaler" permet d\'envoyer ce message au mod&eacute;rateur qui pourra juger de son contenu. <strong>Veuillez utiliser ce lien uniquement si vous pensez que le message enfreint les r&egrave;gles relatives &agrave; l\'utilisation du forum</strong>.';
$string['alert_reasons'] = 'Raison du signalement';
$string['alert_condition1'] = 'Contenu abusif';
$string['alert_condition2'] = 'Contenu assimil&eacute; &agrave; du harc&egrave;lement moral';
$string['alert_condition3'] = 'Contenu choquant (pornographie, ...)';
$string['alert_condition4'] = 'Contenu calomnieux ou diffamatoire';
$string['alert_condition5'] = 'Contenu en contradiction avec les droits d\'auteurs';
$string['alert_condition6'] = 'Contenu sortant du cadre des r&egrave;gles du forum pour une autre raison';
$string['alert_conditionmore'] = 'Informations compl&eacute;mentaires (facultatif)';
$string['alert_reporterinfo'] = "<strong>D&eacute;tails sur le rapporteur</strong>:";
$string['alert_reporterdetail'] = '{$a->fullname} ({$a->username}; {$a->email}; {$a->ip})';
$string['invalidalert'] = 'Vous devez pr&eacute;ciser la raison pour laquelle vous avez signal&eacute; ce message.';
$string['invalidalertcheckbox'] = 'Vous devez cochez au moins une case.';
$string['alert_submit'] = "Envoyer le rapport";
$string['error_sendalert'] = 'Une erreur s\'est produite lors de l\'envoi de votre rapport {$a}. Le rapport n\'a pas &eacute;t&eacute; envoy&eacute;.';
$string['error_portfoliosave'] = 'Une erreur s\'est produite pendant la sauvegarde vers Mon dossier.';
$string['alert_pagename'] = 'Signaler un message comme incorrect';
$string['alert_emailsubject'] = 'Alerte F{$a->postid}: {$a->coursename} {$a->forumname}';
$string['alert_emailpreface'] = 'Un message sur le forum a &eacute;t&eacute; signal&eacute; par {$a->fullname} ({$a->username},
{$a->email}) {$a->url}';
$string['alert_feedback'] = 'Votre rapport a &eacute;t&eacute; envoy&eacute; avec succ&egrave;s. Il va &ecirc;tre trait&eacute; par un membre de l\'&eacute;quipe.';
$string['alert_emailappendix'] = 'Vous recevez cette notification suite a une utilisation de votre adresse mail sur le forumNG pour signaler un courriel inadapt&eacute;.';
$string['alert_note'] = 'Note : Ce courriel a &eacute;galement &eacute;t&eacute; envoy&eacute; &agrave; $a';
$string['alert_notcurrentpost'] = 'Ce message a d&eacute;j&agrave; &eacute;t&eacute; supprim&eacute;.';
$string['alert_turnedoff'] = 'La fonction "Signaler" n\'est pas disponible.';
$string['move_notselected'] = 'Vous devez s&eacute;lectionner un forum cible dans le menu d&eacute;roulant, avant de cliquer sur le bouton \'d&eacute;placer\'.';
$string['partialsubscribed'] = 'Partiel';
$string['move_nogroups'] = 'Vous n\'avez pas acc&egrave;s &agrave; certains groupes dans le forum s&eacute;lectionn&eacute;.';
$string['beforestartdate'] = 'Vous pouvez consulter les messages de ce forum, mais ne pouvez publier vos propres messages. L\'ouverture de ce forum aux publications est pr&eacute;vu le {$a}.';
$string['beforestartdatecapable'] = 'Les &eacute;tudiants peuvent consulter tous les messages de ce forum, mais ne pourront envoyer leur propres publications jusqu\'au : {$a}. Vous avez acc&egrave;s aux messages envoy&eacute;s avant cette date.';
$string['beforeenddate'] = 'Ce forum est ferm&eacute;, pour recevoir les nouveaux messages sur {$a}.';
$string['beforeenddatecapable'] = 'Ce forum ferme pour les nouveaux messages d\'utilisateur le {$a}.';
$string['afterenddate'] = 'Vous pouvez lire tous les messages de ce forum, mais vous ne pouvez pas publier de messages. Ce forum est verrouill&eacute; depuis le {$a}.';
$string['afterenddatecapable'] = 'Les &eacute;tudiants peuvent lire tous les messages de ce forum mais ne peuvent plus publier de messages depuis la fermeture du forum le $a. Vous avez toujours acc&egrave;s aux messages publi&eacute;s.';
$string['removeolddiscussions'] = 'Nettoyer le forum';
$string['removeolddiscussions_help'] = 'Le syst&egrave;me peut supprimer automatiquement les discussions si elles n\'ont pas eu de nouvelles r&eacute;ponses pendant un certain laps de temps.';
$string['removeolddiscussionsafter'] = 'Supprimer les discussions apr&egrave;s (mois)';
$string['removeolddiscussionsdefault'] = 'Ne jamais supprimer';
$string['withremoveddiscussions'] = 'D&eacute;placer les discussions vers';
$string['onemonth'] = '1 mois';
$string['withremoveddiscussions_help'] = 'Vous avez 2 options pour le traitement des discussions supprim&eacute;es :
<ul><li>Les supprimer d&eacute;finitivement (contrairement &agrave; la suppression standard, cette action ne permet pas de restauration).
Cette option permet d\'&eacute;conomiser de l\'espace dans la base de donn&eacute;es.</li>
<li>Les d&eacute;placer vers un autre forum (par exemple un forum d\'archives.
Vous pouvez s&eacute;lectinoner n\'importe quel forum du m&ecirc;me espace de cours.</li></ul>';
$string['deletepermanently'] = 'Supprimer d&eacute;finitivement';
$string['housekeepingstarthour']='Heure de d&eacute;but de l\'archivage';
$string['housekeepingstophour']='Heure de fin d\'archivage';
$string['confighousekeepingstarthour']='Les t&acirc;ches d\'archivage, telle que la suppression des anciennes discussions, commencera chaque jour &agrave; l\'heure d&eacute;finie ici.';
$string['confighousekeepingstophour']='La t&acirc;che d\'archivage s\'arr&ecirc;tera &agrave; l\'heure d&eacute;finie ici.';
$string['invalidforum']='Ce forum n\'existe plus';
$string['errorinvalidforum'] = 'Le forum cible pour l\'archivage d\'anciennes discussions n\'existe plus. Veuillez choisir un autre forum.';
$string['archive_errorgrouping']='Le forum o&ugrave; doivent &ecirc;tre d&eacute;plac&eacute;es les discussions a un r&eacute;glage de groupes diff&eacute;rent. Merci de modifier les param&egrave;tres de l\'option <strong>Nettoyer le forum</strong>.';
$string['archive_errortargetforum']='Le forum o&ugrave; doivent &ecirc;tre d&eacute;plac&eacute;es les discussions n\'existe plus. Merci de modifier les param&egrave;tres de l\'option <strong>Nettoyer le forum</strong>.';
$string['error_notwhensharing'] = 'Cette option n\'est pas disponible lorsque le forum est partag&eacute;.';
$string['error_sharingrequiresidnumber'] = 'Lorsque vous partagez ce forum, vous devez entrer un num&eacute;ro d\'identification unique pour tout le site.';
$string['error_sharingidnumbernotfound'] = 'Lorsque vous utilisez un forum partag&eacute;, vous devez entrer un num&eacute;ro d\'identification qui corresponde exactement &agrave; un num&eacute;ro pr&eacute;c&eacute;demment entr&eacute; dans un forum partag&eacute;.';
$string['error_sharinginuse'] = 'Vous ne pouvez pas d&eacute;sactiver le partage de ce forum car il y a d&eacute;j&agrave; des forums qui le partagent. Si n&eacute;cessaire, supprimer d\'abord ces autres forums.';
$string['error_nosharedforum'] = 'Forum <strong>{$a->name}</strong> : impossible de le restaurer en tant que forum partag&eacute; ; num&eacute;ro d\'identification {$a->idnumber} introuvable. Le forum a &eacute;t&eacute; restaur&eacute; en tant que forum ind&eacute;pendant.';
$string['advancedsearch'] = 'Recherche avanc&eacute;e';
$string['words'] = 'Rechercher par mots';
$string['words_help'] = 'Tapez le terme recherch&eacute; ici.

Utilisez les guillemets pour rechercher des expressions exactes.

Pour exclure un mot, ins&eacute;rez un trait d\'union imm&eacute;diatement avant celui-ci.

Par exemple: la recherche <tt> picasso -sculpture "premi&egrave;res œuvres" </ tt> renverra des r&eacute;sultats pour le terme "Picasso" ou l\'expression "premi&egrave;res œuvres" mais exclura les &eacute;l&eacute;ments contenant le terme "sculpture".

Si vous laissez cette zone vide, tous les messages qui correspondent &agrave; l\'auteur et/ou &agrave; des crit&egrave;res de date seront retourn&eacute;s, ind&eacute;pendamment de leur contenu.';
$string['authorname'] = 'Nom de l\'auteur';
$string['authorname_help'] = 'Vous pouvez taper un pr&eacute;nom (Michel), un nom de famille (Dupont), le nom complet (Michel Dupont), ou la premi&egrave;re partie de l\'un de ces &eacute;l&eacute;ments (Mic, dup, Michel D). La recherches est insensible &agrave; la casse.

Vous pouvez &eacute;galement taper un nom d\'utilisateur (d&eacute;pend de votre syst&egrave;me d\'authentification et de cr&eacute;ation de compte).

Si vous laissez ce champ vide, les messages de tous les auteurs seront inclus.';
$string['daterangefrom'] = 'Date &agrave; partir de';
$string['daterangefrom_help'] = 'Utilisez les dates pour restreindre votre recherche pour inclure uniquement les messages dans la p&eacute;riode donn&eacute;e.

Si vous ne sp&eacute;cifiez pas les dates, les messages de n\'importe quelle date seront inclus dans les r&eacute;sultats.';
$string['daterangeto'] = 'Date jusqu\'&agrave;';
$string['searchresults'] = 'R&eacute;sultats de la recherche : <strong>{$a}</strong>';
$string['searchtime'] = 'Rechercher par date : {$a} s';
$string['nothingfound'] = 'Aucun r&eacute;sultat ne correspond &agrave; votre recherche. Veuillez tenter une autre requ&ecirc;te.';
$string['previousresults'] = 'Retour au r&eacute;sultats {$a}';
$string['nextresults'] = 'Plus de r&eacute;sultats';
$string['author'] = ' auteur : "{$a}"';
$string['from'] = ' de : {$a}';
$string['to'] = ' jusqu\'&agrave; : {$a}';
$string['inappropriatedateortime'] = 'La <strong>&eacute;riode de fin</strong> est ult&eacute;rieure &agrave; la date du jour. Veuillez v&eacute;rifier et essayer &agrave; nouveau.';
$string['daterangemismatch'] = 'Erreur de p&eacute;riode : la <strong>Date &agrave; partir de</strong> est post&eacute;rieure &agrave; la <strong>Date jusqu\'&agrave;</strong>.';
$string['nosearchcriteria'] = 'Ce crit&egrave;re de recherche n\'est pas valide : veuillez utiliser un ou plusieurs des crit&egrave;res ci-dessous et r&eacute;essayez.';
$string['searchallforums'] = 'Rechercher dans tous les forums';
$string['replies'] = 'R&eacute;ponses';
$string['newdiscussion'] = 'Nouvelle discussion';
$string['nothingtodisplay'] = '<h3>Rien &agrave; afficher</h3>';
$string['re'] = 'Re: {$a}';
// pas s&ucirc;r
$string['error_feedlogin'] = 'Erreur chargement de l\'utilisateur';
//
$string['error_makebig'] = 'Le cours ne contient que {$a->users} utilisateurs et vous avez demand&eacute; &agrave; {$a->readusers} lecteurs de lire chaque discussion. Merci de cr&eacute;er ou d\'inscrire plus d\'utilisateurs.';
$string['error_system'] = 'Une erreur syst&egrave;me est survenue : {$a}';
$string['modulename_help'] = 'ForumNG est un rempla&ccedil;ant du forum standard de Moodle avec la plupart des caract&eacute;ristiques du forum standard, mais contenant &eacute;galement beaucoup d\'autres options et une autre interface utilisateur.

NG signifie \'Nouvelle G&eacute;n&eacute;ration\'.';
$string['mailnow_help'] = 'Envoyer votre message par courriel aux abonn&eacute;s plus rapidement.

Sauf si vous choisissez cette option, le syst&egrave;me attend pendant un certain temps avant d\'envoyer le message de telle sorte que toutes les modifications que vous pourriez faire peuvent &ecirc;tre inclus dans le courriel.';
$string['displayperiod_help'] = 'Vous pouvez masquer cette discussion aux &eacute;tudiants jusqu\'&agrave;, ou &agrave; partir, d\'une
certaine date.

Les &eacute;tudiants ne voient pas la discussion masqu&eacute;e. Pour les mod&eacute;rateurs, la liste de discussion est affich&eacute;e en gris avec l\'ic&ocirc;ne de l\'horloge.';
$string['sticky_help'] = 'Cette option permet de placer la discussion en t&ecirc;te de la liste, m&ecirc;me si d\'autres discussions sont cr&eacute;&eacute;es apr&egrave;s.

Les discussions mises en t&ecirc;te de liste sont affich&eacute;es avec une ic&ocirc;ne de fl&egrave;che pointant vers le haut. Il peut y avoir plusieurs discussions mises en t&ecirc;te de liste.';
$string['errorfindinglastpost'] = 'Erreur de calcul pour le dernier message (incoh&eacute;rence de la base de donn&eacute;es ?)';
$string['drafts_help'] = 'Lorsque vous enregistrer un brouillon, il appara&icirc;t dans cette liste. Cliquez sur le brouillon pour reprendre le travail.

Si vous souhaitez supprimer le brouillon, cliquez sur l\'ic&ocirc;ne de suppression. il y aura un message de confirmation.

Dans certains cas, il peut ne pas &ecirc;tre possible de continuer votre brouillon (par exemple si elle est en r&eacute;ponse &agrave; une discussion qui a depuis &eacute;t&eacute; supprim&eacute;e). Dans cette situation, vous pouvez r&eacute;cup&eacute;rer le contenu de votre brouillon en cliquant sur l\'ic&ocirc;ne de suppression.';
$string['flaggedposts_help'] = 'Les messages marqu&eacute;s d\'un drapeau apparaissent dans cette liste. Pour acc&eacute;der &agrave; un message marqu&eacute;,
cliquez dessus.

Pour enlever le drapeau d\'un message, cliquez sur l\'ic&ocirc;ne du drapeau (ici ou dans le message).';

$string['searchthisforum_help'] = 'Tapez le terme recherch&eacute; ici.

Utilisez les guillemets pour rechercher des expressions exactes.

Pour exclure un mot, ins&eacute;rez un trait d\'union imm&eacute;diatement avant celui-ci.

Par exemple: la recherche <tt> picasso -sculpture "premi&egrave;res œuvres" </ tt> renverra des r&eacute;sultats pour le terme "Picasso" ou l\'expression "premi&egrave;res œuvres" mais exclura les &eacute;l&eacute;ments contenant le terme "sculpture".

Si vous laissez cette zone vide, tous les messages qui correspondent &agrave; l\'auteur et/ou &agrave; des crit&egrave;res de date seront retourn&eacute;s, ind&eacute;pendamment de leur contenu.';
$string['searchthisforumlink_help'] = 'Tapez le terme recherch&eacute; ici.

Utilisez les guillemets pour rechercher des expressions exactes.

Pour exclure un mot, ins&eacute;rez un trait d\'union imm&eacute;diatement avant celui-ci.

Par exemple: la recherche <tt> picasso -sculpture "premi&egrave;res œuvres" </ tt> renverra des r&eacute;sultats pour le terme "Picasso" ou l\'expression "premi&egrave;res œuvres" mais exclura les &eacute;l&eacute;ments contenant le terme "sculpture".

Si vous laissez cette zone vide, tous les messages qui correspondent &agrave; l\'auteur et/ou &agrave; des crit&egrave;res de date seront retourn&eacute;s, ind&eacute;pendamment de leur contenu.';

$string['notext'] = '(pas de texte)';
