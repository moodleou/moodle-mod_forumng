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
 * @package mod
 * @subpackage forumng
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['replytouser'] = 'Utilizar la dirección de correo en las respuestas';
$string['configreplytouser'] = 'Cuando un tema del foro se envía por correo, ¿debe contener la dirección
de correo del usuario para que los receptores puedan responderle directamente en vez de a
través del foro? Incluso si se selecciona \'Sí\', los usuarios pueden elegir en su
perfil mantener secreta su dirección de correo.';
$string['disallowsubscribe'] = 'La suscripción no está permitida';
$string['forcesubscribe'] = 'Forzar que todo el mundo esté (y permanezca) suscrito';
$string['subscription'] = 'Suscripción';
$string['subscription_help'] =
'Puedes obligar a todo el mundo a estar suscrito, o hacer que estén suscritos
inicialmente; la diferencia es que, en el segundo caso, los usuarios pueden cancelar la suscripción.

Estas opciones incluyen a todos los usuarios (estudiantes y profesores) que están inscritos al curso. Los
usuarios que no pertenecen al curso (como los administradores) pueden de todas formas suscribirse de forma manual.' ;
$string['configtrackreadposts'] = 'Selecciona \'sí\' si quieres gestionar las entradas leídas/no leídas para cada usuario.';
$string['forums'] = 'Forums';
$string['digestmailheader'] = 'Este es el resumen diario de los nuevos temas del foro {$a->sitename}.
Para cambiar tus preferencias de correo en este foro, puedes ir a {$a->userprefs}.';
$string['digestmailprefs'] = 'tu perfil de usuario';
$string['digestmailsubject'] = '{$a}: resumen del foro';
$string['unsubscribe'] = 'Cancelar la suscripción de este foro';
$string['unsubscribeall'] = 'Cancelar la suscripción de todos los foros';
$string['postmailinfo'] =
'Esta es una copia del mensaje publicado en la web de {$a}

Para responder a través de la web, pulsa en este enlace:';
$string['discussionoptions'] = 'Opciones del tema';
$string['forum'] = 'Forum';
$string['subscribed'] = 'Suscrito';
$string['subscribegroup'] = 'Suscribirse a este grupo';
$string['subscribeshort'] = 'Suscribirse';
$string['subscribelong'] = 'Suscribirse a todo el foro';
$string['unsubscribegroup'] = 'Cancelar la suscripción a este grupo';
$string['unsubscribegroup_partial'] = 'Cancelar la suscripción a las discusiones de este grupo';
$string['unsubscribeshort'] = 'Cancelar la suscripción';
$string['unsubscribelong'] = 'Cancelar la suscripción a este foro';
$string['subscribediscussion'] = 'Suscribirse a este tema';
$string['unsubscribediscussion'] = 'Cancelar la suscripción a este tema';
$string['subscribeddiscussionall'] = 'Todo';
$string['subscribedthisgroup'] = 'Este grupo';
$string['numberofdiscussions'] = '{$a} temas';
$string['numberofdiscussion'] = '{$a} temas';
$string['discussions'] = 'Temas';
$string['posts'] = 'Temas';
$string['subscribe'] = 'Suscribirse a este foro';
$string['allsubscribe'] = 'Suscribirse a todos los foros';
$string['allunsubscribe'] = 'Cancelar la suscripción a todos los foros';
$string['forumname'] = 'Nombre del foro';
$string['forumtype'] = 'Tipo del foro';
$string['forumtype_help'] = 'Hay diferentes tipos de foro disponibles para propósitos específicos o diferentes
métodos de enseñanza. El foro de tipo estándar es apropiado para un uso normal de los foros.';
$string['forumtype_link'] = 'mod/forumng/forumtypes';
$string['forumintro'] = 'Descripción del foro';
$string['ratingtime'] = 'Restringir las valoraciones a entradas entre las fechas de este rango:';
$string['ratings'] = 'Valoraciones';
$string['grading'] = 'Calificación';
$string['grading_help'] = 'Si seleccionas esta opción, se calculará de forma automática una calificación de este foro y
se añadirá al libro de calificaciones del curso. Deja esto en blanco para foros que no se
califiquen, o que se vayan a calificar manualmente.

Las diferentes formas de calcular la calificación son autoexplicatorias; en cada caso, la calificación
para cada estudiante se basa en todas las calificaciones de todos los post de ese estudiante. Las
calificaciones están limitadas a la escala; por ejemplo, si la escala es 0-3, el método de calificación
es &lsquo;número&rsquo; y las entradas del estudiante han recibido 17 valoraciones, su calificación será 3.';
$string['nodiscussions'] = 'Todavía no hay entradas en este foro';
$string['startedby'] = 'Creado por';
$string['discussion'] = 'Tema';
$string['unread'] = 'No leídos';
$string['lastpost'] = 'Última entrada';
$string['group'] = 'Grupo';
$string['addanewdiscussion'] = 'Crear un nuevo tema';
$string['subject'] = 'Asunto';
$string['message'] = 'Mensaje';
$string['subscribestart'] = 'Enviarme copias de las entradas de este foro al correo';
$string['subscribestop'] = 'No quiero que se envíen a mi correo copias de las entradas de este foro';
$string['mailnow'] = 'Enviar YA un correo electrónico';
$string['displayperiod'] = 'Periodo de visualización';
$string['subscriptions'] = 'Suscripciones';
$string['nosubscribers'] = 'Todavía no hay suscriptores en este foro.';
$string['subscribers'] = 'Suscriptores';
$string['numposts'] = '{$a} tema(s)';
$string['noguestsubscribe'] = 'Lo siento, no esta permitido que los invitados se suscriban para recibir
las entradas de los foros por correo.';

$string['discussionsperpage'] = 'Entradas por página';
$string['configdiscussionsperpage'] = 'Máximo número de entradas que se muestran en una página del foro';
$string['attachmentmaxbytes'] = 'Tamaño máximo de los anexos';
$string['attachmentmaxbytes_help'] = 'Este es el máximo tamaño <i>total</i> para todos los anexos
de una entrada';
$string['configattachmentmaxbytes'] = 'Tamaño máximo por defecto para todos los anexos de los foros en el sitio web
(sujeto a los límites del curso y otras opciones locales)';
$string['readafterdays'] = 'Días para marcar como leído';
$string['configreadafterdays'] = 'Transcurridos este número de días, se considera que las entradas han sido leídas
por todos los usuarios.';
$string['trackreadposts'] = 'Vigilar entradas no leídas';
$string['teacher_grades_students'] = 'El profesor califica a los estudiantes';
$string['grading_average'] = 'Media de valoraciones';
$string['grading_count'] = 'Cuenta de valoraciones';
$string['grading_max'] = 'Máxima valoración';
$string['grading_min'] = 'Mínima valoración';
$string['grading_none'] = 'Sin calificación';
$string['grading_sum'] = 'Suma de valoraciones';
$string['subscription_permitted'] = 'Cualquiera puede suscribirse';
$string['subscription_forced'] = 'Obligar a que todo el mundo esté suscrito';
$string['enableratings'] = 'Permitir valorar las entradas';
$string['enableratings_help'] =  'Si se activa, las entradas pueden ser valoradas utilizando una escala numérica o
definida por Moodle. Una o más personas pueden valorar la entrada y la valoración mostrada
será la media aritmética de esas entradas.

Si utilizas una escala numérica hasta 5 (o menos) entonces se visualiza utilizando &lsquo;estrellas&rsquo;.
En cualquier otro caso será una lista desplegable.

El sistema de permisos controla quien puede valorar entradas y ver valoraciones. Por defecto, sólo los
profesores pueden valorar las entradas, y los estudiantes sólo pueden ver las valoraciones de sus propias entradas.';
$string['markdiscussionread'] = 'Marcar como leídos todos los temas en esta discusión.';
$string['forumng:addinstance'] = 'Añadir un nuevo ForumNG';
$string['forumng:createattachment'] = 'Crear adjuntos';
$string['forumng:deleteanypost'] = 'Borrar cualquier tema';
$string['forumng:editanypost'] = 'Editar cualquier tema';
$string['forumng:managesubscriptions'] = 'Gestionar suscripciones';
$string['forumng:movediscussions'] = 'Mover temas';
$string['forumng:rate'] = 'Valorar entradas';
$string['forumng:grade'] = 'Calificar entradas';
$string['forumng:replypost'] = 'Contestar a los temas';
$string['forumng:splitdiscussions'] = 'Partir temas';
$string['forumng:startdiscussion'] = 'Comenzar un nuevo tema';
$string['forumng:viewanyrating'] = 'Ver todas las valoraciones';
$string['forumng:viewdiscussion'] = 'Ver temas';
$string['forumng:viewrating'] = 'Ver valoraciones de las entradas propias';
$string['forumng:viewsubscribers'] = 'Ver suscriptores';
$string['forumng:copydiscussion'] = 'Copiar tema';
$string['forumng:forwardposts'] = 'Reenviar entradas';

$string['pluginadministration'] = 'Administración de ForumNG';
$string['modulename'] = 'ForumNG';
$string['pluginname'] = 'ForumNG';
$string['modulenameplural'] = 'ForumNGs';
$string['forbidattachments'] = 'No se permiten anexos';
$string['configenablerssfeeds'] =
'Esta opción habilita la posibilidad de tener feeds RSS para todos
los foros. Hay que habilitar de forma manual los feeds en las opciones de cada foro.';
$string['allowsubscribe'] = 'Permitir la suscripción';
$string['initialsubscribe'] = 'Suscribir automáticamente a todo el mundo';
$string['perforumoption'] = 'Configuración en cada foro';
$string['configsubscription'] = 'Controla las opciones de la suscripción por correo en todos los foros del sitio.';
$string['feedtype']='Contenidos del feed';
$string['feedtype_help']='Si está habilitado, los usuarios se pueden suscribir al foro utilizando un lector de
feeds RSS o Atom. Se puede configurar para incluir sólo los temas principales y no las respuestas, o
para incluir todas las entradas.';
$string['configfeedtype']='Selecciona la información a incluir en los feed RSS de todos los foros.';
$string['feedtype_none']='Feed deshabilitado';
$string['feedtype_discussions']='Contiene sólo los temas principales';
$string['feedtype_all_posts']='Contiene todas las entradas';
$string['permanentdeletion']='Eliminación de datos obsoletos';
$string['configpermanentdeletion']= 'Tras este periodo de tiempo, las entradas eliminadas y las versiones antiguas
de las entradas editadas se eliminan permanentemente de la base de datos.';
$string['permanentdeletion_never']='Nunca (no eliminar datos obsoletos)';
$string['permanentdeletion_soon']='Eliminar lo antes posible';
$string['usebcc']='Enviar correos con copia oculta';
$string['configusebcc']= 'Deja este valor a cero para utilizar la gestión de correo por defecto
de Moodle (más seguro). Selecciona un número (por ejemplo 50) para agrupar juntos los correos del foro
utilizando copia oculta para que Moodle sólo tenga que enviar un correo que el servidor de correo repartirá
entre los suscriptores. Esto puede mejorar el rendimiento del correo en el cron del foro, pero no tiene
algunas utilidades de los correos estándar de Moodle como las de juego de caracteres o la gestión de
correo devuelto.';
$string['donotmailafter']='No enviar correo después de (horas)';
$string['configdonotmailafter']= 'Para evitar causar una avalancha de correos si el cron del servidor no se ha
ejecutado durante un tiempo, el foro no enviará correos de entradas que sean de más antigüedad que estas horas.';
$string['re']='Re: {$a}'; // I made a new string because I like it better with {$a}
$string['discussionsunread']='Temas (no leídos)';
$string['feeds'] = 'Feeds';
$string['atom'] = 'Atom';
$string['subscribe_confirm'] = 'Te has suscrito.';
$string['unsubscribe_confirm'] = 'Has cancelado la suscripción.';
$string['subscribe_confirm_group'] = 'Has sido suscrito al grupo.';
$string['unsubscribe_confirm_group'] = 'Tu suscripción al grupo ha sido cancelada.';
$string['subscribe_already'] = 'Ya estás suscrito.';
$string['subscribe_already_group'] = 'Ya estás suscrito a este grupo.';
$string['unsubscribe_already'] = 'Tu suscripción ya estaba cancelada.';
$string['unsubscribe_already_group'] = 'Tu suscripción a este grupo ya estaba cancelada.';
$string['subscription_initially_subscribed'] = 'Todo el mundo está suscrito inicialmente';
$string['subscription_not_permitted'] = 'No se permite la suscripción';
$string['feeditems'] = 'Número de ítems en el feed';
$string['feeditems_help'] = 'El número de ítems que se incluirán en el feed RSS/Atom. Si el número es muy
bajo, los usuarios que no chequeen el feed frecuentemente pueden perder algunos mensajes.';
$string['configfeeditems'] = 'Número de mensajes que se incluirán en el feed.';
$string['limitposts'] = 'Limitar entradas';
$string['enablelimit'] = 'Limitar creación de temas';
$string['enablelimit_help'] =  'Esta opción limita los temas y entradas que pueden crear los estudiantes
(más concreatemten, todos los usuarios que no tengan la capability <tt>mod/forumng:ignorethrottling</tt>).

Cuando al estudiante sólo se le permiten tres entradas más, se muestra un aviso en el formulario de la entrada. Cuando ha
sobrepasado el límite, el sistema muestra en qué momento podrá volver a crear una entrada.';
$string['completiondiscussions'] = 'El usuario puede crear temas:';
$string['completiondiscussionsgroup'] = 'Temas requeridos';
$string['completiondiscussionsgroup_help'] = 'Si se marca esta opción, el foro aparecerá "completado" para un estudiante una
vez que haya creado el número requerido requerido de temas (y cumpla con las
condiciones adicionales que se hayan configurado).';
$string['completionposts'] = 'El usuario debe crear temas o entradas:';
$string['completionpostsgroup'] = 'Entradas requeridas';
$string['completionpostsgroup_help'] = 'Si se selecciona, el foro se marcará como completado
una vez que los estudiantes hayan creado el número requerido de temas/entradas, donde cada
tema nuevo o cada entrada cuenta como una (siempre que cumpla las otras condiciones configuradas).';
$string['completionreplies'] = 'El usuario debe crear respuestas:';
$string['completionrepliesgroup'] = 'Respuestas requeridas';
$string['completionrepliesgroup_help'] = 'Si se selecciona, el foro se marcará como completado por el estudiante
una vez que haya enviado el número requerido de respuestas a temas existentes (siempre
que cumpla las otras condiciones configuradas)';
$string['ratingfrom'] = 'Valorar sólo las entradas desde';
$string['ratinguntil'] = 'Valorar sólo las entradas hasta';
$string['postingfrom'] = 'Creación de nuevas entradas habilitada desde';
$string['postinguntil'] = 'Creación de nuevas entradas habilitada hasta';
$string['postsper'] = 'entradas por';
$string['alt_discussion_deleted'] = 'Tema borrado';
$string['alt_discussion_timeout'] = 'No visible para los usuarios (límite de tiempo)';
$string['alt_discussion_sticky'] = 'Este tema siempre aparecerá al principio de la lista';
$string['alt_discussion_locked'] = 'El tema es de sólo lectura';
$string['subscribestate_partiallysubscribed'] = 'Recibes mensajes de algunos temas de este foro en {$a}.';
$string['subscribestate_partiallysubscribed_thisgroup'] = 'Recibes mensajes de algunos temas de este grupo en {$a}.';
$string['subscribestate_groups_partiallysubscribed'] = 'Recibes mensajes de algunos grupos de este foro en {$a}.';
$string['subscribestate_subscribed'] = 'Recibes mensajes de este foro en {$a}.';
$string['subscribestate_subscribed_thisgroup'] = 'Recibes mensajes de este grupo en {$a}.';
$string['subscribestate_subscribed_notinallgroup'] = 'Pulsa &lsquo;Cancelar suscripción&rsquo; para cancelar la suscripción al foro';
$string['subscribestate_unsubscribed'] = 'Actualmente no recibes mensajes de este foro en el correo. Si quieres
recibirlos, debes pulsar en &lsquo;Suscribirse a este foro&rsquo;.';
$string['subscribestate_unsubscribed_thisgroup'] = 'Actualmente no recibes mensajes de este grupo en el correo. Si quieres
recibirlos, debes pulsar en &lsquo;Suscribirse a este grupo&rsquo;.';
$string['subscribestate_not_permitted'] = 'Este foro no permite la suscripción.';
$string['subscribestate_forced'] = '(Este foro no permite cancelar la suscripción.)';
$string['subscribestate_no_access'] = 'No tienes permisos para suscribirte a este foro.';
$string['subscribestate_discussionsubscribed'] = 'Recibirás mensajes de este tema por correo en {$a}.';
$string['subscribestate_discussionunsubscribed'] = 'Actualmente no recibes mensajes de este tema en el correo. Si quieres
recibirlos, debes pulsar en &lsquo;Suscribirse a este tema&rsquo;.';
$string['replytopost'] = 'Responder al tema: {$a}';
$string['editpost'] = 'Editar tema: {$a}';
$string['editdiscussionoptions'] = 'Editar opciones del tema: {$a}';
$string['optionalsubject'] = 'Nuevo asunto (opcional)';
$string['attachmentnum'] = 'Adjunto {$a}';
$string['sticky'] = '¿Tema de tipo post-it?';
$string['sticky_no'] = 'El tema se ordena de forma normal';
$string['sticky_yes'] = 'El tema aparecerá siempre al principio de la lista';
$string['timestart'] = 'Mostrar sólo desde';
$string['timeend'] = 'Mostrar sólo hasta';
$string['date_asc'] = 'antiguos primero';
$string['date_desc'] = 'nuevos primero';
$string['numeric_asc'] = 'más pequeño primero';
$string['numeric_desc'] = 'más alto primero';
$string['sorted'] = 'ordenado {$a}';
$string['text_asc'] = 'A-Z';
$string['text_desc'] = 'Z-A';
$string['sortby'] = 'Ordenado por {$a}';
$string['rate'] = 'Valorar';
$string['expand'] = 'Expandir<span class=\'accesshide\'> entrada {$a}</span>';
$string['postnum'] = 'Entrada {$a->num}';
$string['postnumreply'] = 'Entrada {$a->num}{$a->info} en respuesta a {$a->parent}';
$string['postinfo_short'] = 'resumido';
$string['postinfo_unread'] = 'no leído';
$string['postinfo_deleted'] = 'eliminado';
$string['split'] = 'Dividir<span class=\'accesshide\'> entrada {$a}</span>';
$string['reply'] = 'Responder<span class=\'accesshide\'> al tema {$a}</span>';
$string['directlink'] = 'Permalink<span class=\'accesshide\'> al tema {$a}</span>';
$string['directlinktitle'] = 'Enlace directo a esta entrada';
$string['edit'] = 'Editar<span class=\'accesshide\'> entrada {$a}</span>';
$string['delete'] = 'Borrar<span class=\'accesshide\'> entrada {$a}</span>';
$string['undelete'] = 'Recuperar<span class=\'accesshide\'> entrada {$a}</span>';
$string['deletedpost'] = 'Entrada eliminada.';
$string['deletedbyauthor'] = 'Esta entrada fue eliminada por el autor el {$a}.';
$string['deletedbymoderator'] = 'Esta entrada fue eliminada por un moderador el {$a}.';
$string['deletedbyuser'] = 'Esta entrada fue eliminada por {$a->user} el {$a->date}.';
$string['expandall'] = 'Expandir todas las entradas';
$string['deletepost'] = 'Borrar entrada: {$a}';
$string['undeletepost'] = 'Recuperar entrada: {$a}';
$string['confirmdelete'] = '¿Estás seguro de querer borrar esta entrada?';
$string['confirmdelete_notdiscussion'] = 'Borrar esta entrada no eliminará el tema. Si quieres eliminar el tema, utiliza los
botones que hay debajo del tema';
$string['confirmundelete'] = '¿Estás seguro de que quieres recuperar esta entrada?';
$string['splitpost'] = 'Dividir entrada: {$a}';
$string['splitpostbutton'] = 'Dividir entrada como nuevo tema';
$string['splitinfo'] = 'Dividir esta entrada la eliminará, junto con todas sus respuestas, del tema actual. Se creará
un nuevo tema (mostrado debajo).';
$string['editbyself'] = 'Editado por el autor el {$a}';
$string['editbyother'] = 'Editado por {$a->name} el {$a->date}';
$string['history'] = 'Historial';
$string['historypage'] = 'Historial: {$a}';
$string['currentpost'] = 'Versión actual de la entrada';
$string['olderversions'] = 'Versiones anteriores (más reciente primero)';
$string['deleteemailpostbutton'] = 'Borrar y enviar correo';
$string['deleteandemail'] = 'Borrar y enviar correo al autor';
$string['emailmessage'] = 'Mensaje';
$string['emailcontentplain'] = 'Este es un mensaje para avisarle de que la entrada del foro que se detalla a continuación '.
'ha sido eliminada por \'{$a->firstname} {$a->lastname}\':

Asunto: {$a->subject}
Foro: {$a->forum}
Módulo: {$a->course}

Para ver esta discusión, puede visitar  {$a->deleteurl}';
$string['emailcontenthtml'] = 'Este es un mensaje para avisarle de que la entrada del foro que se detalla a continuación '.
'ha sido eliminada por \'{$a->firstname} {$a->lastname}\':<br />
<br />
Asunto: {$a->subject}<br />
Foro: {$a->forum}<br />
Módulo: {$a->course}<br />
<br />
<a href="{$a->deleteurl}" title="ver tema eliminad">Ver el tema</a>';
$string['copytoself'] = 'Enviar una copia a tu correo';
$string['deletedforumpost'] = 'Se ha borrado tu entrada';
$string['emailerror'] = 'Ocurrió un error enviando el correo';
$string['sendanddelete'] = 'Enviar y eliminar';
$string['deletepostbutton'] = 'Eliminar';
$string['undeletepostbutton'] = 'Restaurar entrada';
$string['averagerating'] = 'Media de valoraciones: {$a->avg} (desde {$a->num})';
$string['yourrating'] = 'Tu valoración:';
$string['ratingthreshold'] = 'Valoraciones requeridas';
$string['ratingthreshold_help'] = 'Si selecciona 3 en este campo, las valoraciones de la entrada
no se mostrarán hasta que al menos 3 personas la hayan valorado.

Esto puede ayudar a reducir el efecto de una sola valoración en la media.';
$string['saveallratings'] = 'Guardar todas las valoraciones';
$string['js_nratings'] = '(# valoraciones)';
$string['js_nratings1'] = '(1 valoración)';
$string['js_publicrating'] = 'Valoración media: #.';
$string['js_nopublicrating'] = 'Sin valoraciones.';
$string['js_userrating'] = 'Tu valoración: #.';
$string['js_nouserrating'] = 'No ha sido valorado por ti.';
$string['js_outof'] = '(de #.)';
$string['js_clicktosetrating'] = 'Pulsa para dar a esta entrada # estrellas.';
$string['js_clicktosetrating1'] = 'Pulsa para dar a esta entrada una estrella.';
$string['js_clicktoclearrating'] = 'Pulsa para eliminar tu valoración.';
$string['undelete'] = 'Restaurar';
$string['exportword'] = 'Exportar a word';
$string['exportedtitle'] = 'Tema &lsquo;{$a->subject}&rsquo; exportado el {$a->date}';
$string['set'] = 'Set'; //¿Se utiliza?
$string['showusername'] = 'Mostrar nombres de usuario';
$string['configshowusername'] = 'Incluir los nombres de usuario en los informes relacionados con el foro,
que son visibles para los moderadores [pero no para los estudiantes normales]';
$string['showidnumber'] = 'Mostrar ID de usuarios';
$string['configshowidnumber'] = 'Incluir los ID de usuario en los informes relacionados con el foro,
que son visibles para los moderadores [pero no para los estudiantes normales]';
$string['hidelater'] = 'No mostrar de nuevo este mensaje';
$string['existingattachments'] = 'Adjuntos actuales';
$string['deleteattachments'] = 'Eliminar adjuntos actuales';
$string['attachments'] = 'Adjuntos';
$string['attachment'] = 'Adjunto';
$string['choosefile'] = '1. Elige el fichero';
$string['clicktoadd'] = '2. Añádelo';
$string['readdata'] = 'Leer datos';
$string['search_update_count'] = '{$a} foros por procesar.';
$string['searchthisforum'] = 'Buscar en este foro';
$string['searchthisforumlink'] = 'Buscar en este foro';
$string['viewsubscribers'] = 'Ver suscriptores';
$string['inreplyto'] = 'En respuesta a';
$string['forumng:view'] = 'Ver foro';
$string['forumng:ignorepostlimits'] = 'Ignorar los límites de envío de entradas';
$string['forumng:mailnow'] = 'Enviar manualmente notificaciones de entradas por correo';
$string['forumng:setimportant'] = 'Marcar entradas como importantes';
$string['forumng:managediscussions'] = 'Editar las opciones del tema';
$string['forumng:viewallposts'] = 'Ver entradas ocultas y eliminadas';
$string['forumng:viewreadinfo'] = 'Ver quien ha leído una entrada';
$string['editlimited'] = 'Atención: debes guardar los cambios de esta entrada antes de {$a}. Después de
ese momento ya no podrás editar la entrada.';
$string['badbrowser'] = '<h3>Foro con características reducidas</h3>&nbsp;<p>Estás utilizando {$a}.
Si quieres tener una mejor experiencia utilizando estos foros, por favor, actualiza a
una versión más reciente de <a href=\'http://www.microsoft.com/windows/internet-explorer/\'>Internet Explorer</a>
o <a href=\'http://www.mozilla.com/firefox/\'>Firefox</a>.</p>';
$string['nosubscribersgroup'] = 'Todavía nadie del grupo está suscrito a este foro.';
$string['hasunreadposts'] = '(Entradas sin leer)';
$string['postdiscussion'] = 'Enviar tema';
$string['postreply'] = 'Enviar respuesta';
$string['confirmbulkunsubscribe'] = '¿Estás seguro de querer cancelar la suscripción de los usuarios de la lista?
(esta acción no puede deshacerse.)';
$string['savedraft'] = 'Guardar como borrador';
$string['draftexists'] = 'Se ha guardado un borrador de esta entrada ({$a}). Si no quieres terminar
de redactar la entrada ahora, puedes recuperar el borrador más adelante desde la página principal del foro';
$string['draft_inreplyto'] = '(respuesta a {$a})';
$string['draft_newdiscussion'] = '(nuevo tema)';
$string['drafts'] = 'Borradores';
$string['deletedraft'] = 'Eliminar borradores';
$string['confirmdeletedraft'] = '¿Estás seguro de querer eliminar este borrador? (se muestra en la parte inferior)';
$string['draft'] = 'Borrador';
$string['collapseall'] = 'Plegar todas las entradas';
$string['selectlabel'] = 'Seleccionar entrada {$a}';
$string['selectintro'] = 'Marca el check que hay junto a cada entrada que desees incluir. Cuando hayas
finalizado, avanza hasta el final y pulsa &lsquo;Confirmar selección&rsquo;.';
$string['confirmselection'] = 'Confirmar selección';
$string['selectedposts'] = 'Seleccionar entradas';
$string['selectorall'] = '¿Quieres incluir todo el tema, o sólo las entradas seleccionadas?';
$string['selectoralldisc'] = 'Todas las entradas que se muestran';
$string['selectorselecteddisc'] = 'Seleccionar entrada';
$string['selectorselectdisc'] = 'Entrada seleccionada';
$string['selectordiscall'] = 'Quieres incluir todos los temas que se muestran en esta página, o sólo los temas seleccionados?';
$string['selectdiscintro'] = 'Marca el check que hay junto a cada tema que desees incluir. Cuando hayas
finalizado, avanza hasta el final y pulsa &lsquo;Confirmar selección&rsquo;.';
$string['setimportant'] = 'Marcar entrada como "importante"';//used by moderators, highlight important posts
$string['important'] = 'Entrada importante'; // alt text for important icon
$string['flaggedposts'] = 'Entradas marcadas';
$string['flaggedpostslink'] = '{$a} entradas marcadas';
$string['post'] = 'Entrada';
$string['author'] = 'Autor';
$string['clearflag'] = 'Eliminar marca';
$string['setflag'] = 'Marcar esta entrada para futura referencia';
$string['flagon'] = 'Has marcado esta entrada';
$string['flagoff'] = 'Sin marcar';
$string['postby'] = '(por {$a})';
$string['quotaleft_plural'] = 'Sólo puedes escribir <strong>{$a->posts}</strong> entradas más en la {$a->period} actual.';
$string['quotaleft_singular'] = 'Sólo puedes escribir <strong>{$a->posts}</strong> entrada más en la {$a->period} actual.';
$string['studyadvice_noyourquestions'] = 'Todavía no has creado ningún tema en este foro de ayudas de estudio';
$string['studyadvice_noquestions'] = 'Nadie ha creado todavía ningún tema en este foro de ayudas de estudio';
$string['jumpto'] = 'Ir a:';
$string['jumpnext'] = 'Siguiente no leído';
$string['jumpprevious'] = 'Anterior no leído';
$string['jumppreviousboth'] = 'anteriores';
$string['skiptofirstunread'] = 'Saltar a la primera entrada no leída';
$string['enableadvanced'] = 'Habilitar opciones avanzadas';
$string['configenableadvanced'] = 'Esta opción habilita características avanzadas del foro que pueden resultar inncesariamente complejos para
la mayoría de las instalaciones. Actualmente sólo se habilita la compartición de foros, pero podrían añadirse
otras características en el futuro.';
$string['shared'] = 'Permitir que se comparta el foro';
$string['shared_help'] = 'Marca esta opción e informa el número ID del campo inferior para
habilitar la compartición de este foro.

Este foro se convertirá en el foro original. Podrás crear una o más copias de este foro eligiendo
<strong>Usar foro compartido existente</strong>, e indicando el el mismo número ID al crear cada copia.';
$string['sharing'] = 'Compartición de foro';
$string['useshared'] = 'Clonar un foro existente';
$string['useshared_help'] = 'Si quieres crear un clon de un foro existente, marca esta opción e indica el número ID
del foro original (el cual debe tener habilitada la compartición de foro).

Cuando se selecciona esta opción, muchas otras opciones serán ignoradas ya que realmente no
se está creando un foro sino un enlace a un foro existente. La excepción es la disponibilidad y
(sólo manualmente) las opciones de finalización.';
$string['sharedinfo'] = 'Este es un foro compartido. Las opciones de acceso no se comparten, y
se aplican únicamente a los estudiantes que acceden a este foro desde este curso en particular. Si quieres
editar otras opciones de este foro, por favor, <a href=\'{$a}\'>edita las opciones del foro original</a>.';
$string['sharedviewinfooriginal'] = '<strong>Este foro está compartido</strong> bajo el nombre
<strong>{$a}</strong> para su uso en otros cursos.';
$string['sharedviewinfonone'] = 'No está incluido actualmente en ningún otro curso.';
$string['sharedviewinfolist'] = 'Está incluido aquí: {$a}.';
$string['sharedviewinfoclone'] = '<strong>Este es un foro compartido</strong>. El
<a href=\'{$a->url}\'>foro original</a> está en {$a->shortname}.';
$string['jumpparent'] = 'Padre';
$string['savetoportfolio'] = 'Guardar en tu portafolio';
$string['savedposts_all'] = '{$a}';
$string['savedposts_selected'] = '{$a} (entradas seleccionadas)';
$string['savedposts_one'] = '{$a->name}: {$a->subject}';
$string['savedposts_all_tag'] = 'Tema del foro';
$string['savedposts_selected_tag'] = 'Entradas del foro';
$string['savedposts_one_tag'] = 'Entrada del foro';
$string['savedposts_original'] = 'Tema original';
$string['savedtoportfolio'] = 'La información seleccionada ha sido guardada en tu portafolio.';
$string['offerconvert'] = 'Si quieres crear un nuevo ForumNG que sea una copia de un foro de estilo antiguo,
no utilices este formulario. En vez de eso, <a href=\'{$a}\'>convierte el foro</a>.';
$string['convert_title'] = 'Convertir foros';
$string['convert_info'] = 'El proceso de conversión se puede ejecutar en uno o más foros de estilo antiguo;
de momento sólo está soportado el foro de tipo \'general\'. Utiliza la tecla Ctrl
para seleccionar más de un foro de la lista si lo necesitas.';
$string['convert_warning'] = '<p>Cuando pulses Convertir, se convertirán los foros seleccionados.
Esto incluye todas las entradas y temas, y puede llevar un tiempo. Los foros no estarán
disponibles durante la conversión.</p><ul>
<li>
Los foros antiguos se ocultarán tan pronto como comience el proceso
de conversión para ese foro. Esto asegura que no se crearán nuevas entradas, las
cuales \'perderían\' la conversión.</li>
<li>
Los foros nuevos se crean inicialmente ocultos y sólo se muestran una vez
se ha completado el proceso de conversión para ese foro. </li>
</ul>';
$string['convert_hide'] = 'Dejar ocultos los foros creados';
$string['convert_nodata'] = 'No incluir datos del usuario (entradas, suscripciones, etc.)';
$string['convert_process_init'] = 'Creando estructura del foro...';
$string['convert_process_state_done'] = 'hecho.';
$string['convert_process_show'] = 'Haciendo el foro visible...';
$string['convert_process_subscriptions_normal'] = 'Convirtiendo suscripciones normales...';
$string['convert_process_subscriptions_initial'] = 'Convirtiendo suscripciones iniciales...';
$string['convert_process_discussions'] = 'Convirtiendo temas...';
$string['convert_process_dashboard'] = 'Convirtiendo favoritos del tablón...';
$string['convert_process_dashboard_done'] = 'hecho (OK {$a->yay}, error {$a->nay}).';
$string['convert_process_assignments'] = 'Actualizando asignaciones de rol...';
$string['convert_process_overrides'] = 'Actualizando role overrides...';
$string['convert_process_search'] = 'Regenerando datos para búsquedas...';
$string['convert_process_update_subscriptions'] = 'Convirtiendo a suscripciones de grupo...';
$string['convert_process_complete'] = 'Conversión completada en {$a->seconds}s (ver {$a->link}).';
$string['convert_newforum'] = 'nuevo foro';
$string['convert_noneselected'] = '¡No se han seleccionado foros para convertir! Por favor, seleccione
uno o más foros.';
$string['convert_noforums'] = 'No hay foros antiguos en este curso para convertir.';
$string['pastediscussion']='Pegar tema';
$string['switchto_simple_text']= 'La vista estándar de este foro no siempre funciona bien con tecnología de asistencia. Se provee también
una vista simple que contiene todas las características del foro.';
$string['switchto_standard_text']= 'Estás utilizando la vista simple de este foro, que debería funcionar correctamente con tecnología de asistencia.';
$string['switchto_simple_link']='Cambiar a vista sencilla.';
$string['switchto_standard_link']='Cambiar a vista estándard.';
$string['displayversion'] = 'Versión de ForumNG: <strong>{$a}</strong>';

// OU only.
$string['externaldashboardadd'] = 'Añadir foro al panel';
$string['externaldashboardremove'] = 'Eliminar foro del panel';

// New error strings.
$string['error_fileexception'] = 'Ha ocurrido un error de proceso de ficheros. Seguramente estará causado por problemas en el sistema.
Prueba a intentarlo más tarde.';
$string['error_subscribeparams'] = 'Parámetros incorrectos: se requiere bien id o curso o d.';
$string['error_nopermission'] = 'No estás autorizado a llevar a cabo esta petición.';
$string['error_exception'] = 'Ocurrió un error en el foro. Por favor, inténtalo más tarde o prueba otra
cosa.<div class=\'forumng-errormessage\'>Mensaje de error: {$a}</div>';
$string['error_cannotchangesubscription'] = 'No estás autorizado a suscribirte, o cancelar tu suscripción, a este foro.';
$string['error_cannotchangediscussionsubscription'] = 'No estás autorizado a suscribirte, o cancelar tu suscripción, a este tema.';
$string['error_cannotchangegroupsubscription'] = 'No estás autorizado a suscribirte, o cancelar tu suscripción, a este grupo.';
$string['error_cannotsubscribetogroup'] = 'No estás autorizado a suscribirte al grupo seleccionado.';
$string['error_cannotunsubscribefromgroup'] = 'No estás autorizado a cancelar la suscripción al grupo seleccionado.';
$string['error_invalidsubscriptionrequest'] = 'Tu petición de suscripción es inválida.';
$string['error_unknownsort'] = 'Opción de ordenación desconocida.';
$string['error_ratingthreshold'] = 'El umbral de valoraciones debe ser un número positivo.';
$string['error_duplicate'] = 'Ya has creado una entrada utilizando el formulario anterior. (Este error
ocurre en ocasiones al pulsar dos veces el botón. En ese caso seguramente tu entrada
ha sido guardada.)';
$string['edit_notcurrentpost'] = 'No puedes editar entradas eliminadas o versiones anteriores de una entrada.';
$string['edit_timeout'] = 'Ya no puedes editar esta entrada; ha pasado el límite de tiempo en el que se permitía la edición.';
$string['edit_notyours'] = 'No puedes editar las entradas de otro usuario.';
$string['edit_nopermission'] = 'No tienes permisos para editar este tipo de entradas.';
$string['edit_readonly'] = 'Este foro está en modo de sólo lectura, no se permite la edición de entradas.';
$string['edit_notdeleted'] = 'No puedes restaurar una entrada que no haya sido borrada.';
$string['edit_rootpost'] = 'Esta acción no es aplicable a la entrada inicial de un tema.';
$string['edit_locked'] = 'El tema está cerrado.';
$string['edit_notlocked'] = 'El tema no está cerrado actualmente.';
$string['edit_wronggroup'] = 'No puedes realizar cambios en entradas que no sean de tu grupo.';
$string['reply_notcurrentpost'] = 'No puedes responder a entradas eliminadas o versiones anteriores de las entradas.';
$string['reply_nopermission'] = 'No tienes permisos para responder aquí.';
$string['reply_readonly'] = 'Este foro está en modo de sólo lectura, no pueden añadirse nuevas respuestas.';
$string['reply_typelimit'] = 'Debido al tipo de este foro, no tienes permitido en este momento responder a esta entrada.';
$string['reply_wronggroup'] = 'No puedes responder a entradas en este tema porque no perteneces al grupo adecuado.';
$string['reply_postquota'] = 'No puedes responder a entradas en este momento porque hay llegado a tu límite diario de redacción de entradas.';
$string['reply_missing'] = 'No puedes responder porque el sistema no puede encontrar la entrada.';
$string['startdiscussion_nopermission'] = 'No tienes permisos para crear un nuevo tema aquí.';
$string['startdiscussion_groupaccess'] = 'No tienes permisos para crear un nuevo tema en este grupo.';
$string['startdiscussion_postquota'] = 'No puedes crear un nuevo tema en este momento porque has llegado a tu límite de creación de entradas.';
$string['error_markreadparams'] = 'Parámetros incorrectos: requiere bien id o d.';
$string['error_cannotmarkread'] = 'No estás autorizado a marcar temas como leídos en este foro.';
$string['error_cannotviewdiscussion'] = 'No tienes permisos para ver este tema.';
$string['error_cannotmanagediscussion'] = 'No tienes permisos para gestionar este tema.';
$string['error_draftnotfound'] = 'Imposible encontrar el borrador. Puede que el borrador haya sido publicado o eliminado.';
$string['jserr_load'] = 'Ocurrió un error recuperando la entrada.

Recarga la página y prueba de nuevo.';
$string['jserr_save'] = 'Ocurrio un error al guardar la entrada.

Copia el texto en otro programa para no perderlo, recarga la página y prueba de nuevo.';
$string['jserr_alter'] = 'Ocurrió un error al modificar la entrada.

Recarga la página y prueba de nuevo.';
$string['jserr_attachments'] = 'Ocurrió un error al cargar el editor de adjuntos.

Recarga la página y prueba de nuevo.';
$string['rate_nopermission'] = 'No tienes permisos para valorar esta entrada ({$a}).';
$string['subscribers_nopermission'] = 'No tienes permisos para ver la lista de suscriptores.';
$string['feed_nopermission'] = 'No tienes permisos para acceder a este feed.';
$string['feed_notavailable'] = 'Este feed no está disponible.';
$string['crondebugdesc'] = 'SOLO PARA DEPURACIÓN -- Marca para incluir información de depuración en los logs del cron';
$string['crondebug'] = 'Mensajes de depuración del programa cron';
$string['unsubscribeselected'] = 'Cancelar la suscripción de los usuarios seleccionados';
$string['unsubscribe_nopermission'] = 'No tienes permisos para cancelar la suscripción de otros usuarios.';
$string['draft_noedit'] = 'La característica de borradores no puede utilizarse cuando se están editando entradas.';
$string['draft_mismatch'] = 'Error al acceder al borrador (o bien no pertenece a tu usuario o no forma parte del tema solicitado).';
$string['draft_cannotreply'] = '<p>No es posible en este momento añadir una respuesta al post al que se refiere tu borrador.
{$a}</p><p>Puedes usar el botón de la X debajo de este borrador en la página principal del foro
para ver el texto completo del borrador (así podrás copiarlo y pegarlo en otro lugar) y para
eliminarlo definitivamente.';
$string['invalidemail'] = 'La dirección de correo no es correcta. Por favor, introduce una única dirección de correo.';
$string['invalidemails'] = 'La dirección de correo no es correcta. Por favor, introduce una o más direcciones
separadas por espacios o punto y coma.';
$string['error_forwardemail'] = 'Ocurrió un error enviando el correo a <strong>{$a}</strong>. El correo
no se ha enviado.';
$string['alert_link'] = 'Alerta';
$string['alert_linktitle'] = 'Notificar entrada como inaceptable';
$string['reportunacceptable'] = 'Correo para notificar entradas ofensivas';
$string['reportingemail'] = 'Correo para notificar entradas ofensivas';
$string['reportingemail_help'] = 'Si se proporciona esta dirección de correo, aparecerá un enlace de notificación
junto a cada entrada. Los usuario pueden pulsar el enlace para notificar entradas ofensivas.
La información se enviará a esta dirección de correo.

Si se deja en blanco esta dirección de correo la opción de Notificación no se mostrará (a no ser
que se haya proporcionado una dirección a nivel del sitio).';
$string['configreportunacceptable'] = 'Esta dirección de correo se utiliza para notificar entradas ofensivas de ForumNG a nivel del sitio.
Si este correo se deja en blanco, la función de notificación se desactivará a no ser que se active
a nivel de cada foro individual.';
$string['alert_info'] = 'La característica de \'Notificación\' puede enviar esta entrada a un administrador
para que lo revise. <strong>Por favor, utiliza esta opción sólo si piensas que la entrada incumple las reglas del foro</strong>.';
$string['alert_reasons'] = 'Motivos del aviso';
$string['alert_condition1'] = 'Es abusivo';
$string['alert_condition2'] = 'Es acoso';
$string['alert_condition3'] = 'Tiene contenido obsceno o pornográfico';
$string['alert_condition4'] = 'Es calumnioso o difamatorio';
$string['alert_condition5'] = 'Infringe derechos de autor';
$string['alert_condition6'] = 'Está en contra de las reglas de uso por cualquier otra razón';
$string['alert_conditionmore'] = 'Otra información (opcional)';
$string['alert_reporterinfo'] = '<strong>Detalles del notificador</strong>:';
$string['alert_reporterdetail'] = '{$a->fullname} ({$a->username}; {$a->email}; {$a->ip})';
$string['invalidalert'] = 'Es necesario que especifiques la razón por la que notificas esta entrada.';
$string['invalidalertcheckbox'] = 'Debes marcar al menos una de las opciones.';
$string['alert_submit'] = 'Enviar alerta';
$string['error_sendalert'] = 'Ha ocurrido un error enviando el informe a {$a}.
El informe no ha podido ser enviado.';
$string['error_portfoliosave'] = 'Ocurrió un error al guardar los datos en tu portafolio.';
$string['alert_pagename'] = 'Notificar una entrada como inaceptable';
$string['alert_emailsubject'] = 'Notificación de foro{$a->postid}: {$a->coursename} {$a->forumname}';
$string['alert_emailpreface'] = 'Una entrada del foro ha sido notificada por {$a->fullname} ({$a->username},
{$a->email}) {$a->url}';
$string['alert_feedback'] = 'Tu notificación se ha enviado correctamente. Un administrador revisará el caso.';
$string['alert_emailappendix'] = 'Has recibido este correo porque tu dirección se ha utilizado en ForumNG para notificar
contenido inaceptable.';
$string['alert_note'] = 'Ten en cuenta que: Este correo ha sido enviado también a {$a}';
$string['alert_notcurrentpost'] = 'Esta entrada ya ha sido eliminada.';
$string['alert_turnedoff'] = 'La función de notificación no está disponible.';
$string['move_notselected'] =
'Debes seleccionar un foro destino de la lista desplegable antes de pulsar el botón "Mover".';
$string['partialsubscribed'] = 'Parcial';
$string['move_nogroups'] = 'No tienes acceso a ningún grupo en el foro seleccionado.';
$string['beforestartdate'] = 'Puedes leer cualquier entrada en este foro, pero no crear nuevas entradas.
La creación de nuevas entradas en este foro se habilitará el {$a}.';
$string['beforestartdatecapable'] = 'Los estudiantes pueden leer cualquier entrada en este foro, pero no crear
entradas nuevas hasta {$a}. Tú tienes acceso a crear entradas nuevas antes de esa fecha.';
$string['beforeenddate'] = 'La creación de nuevas entradas en este foro se cerrará el {$a}.';
$string['beforeenddatecapable'] = 'La creación de nuevas entradas de los estudiantes en este foro se cerrará el {$a}.';
$string['afterenddate'] = 'Puedes leer cualquier entrada en este foro, pero no crear nuevas entradas.
La creación de nuevas entradas en este foro se cerró el {$a}.';
$string['afterenddatecapable'] = 'Los estudiantes pueden leer cualquier entrada en este foro, pero no han podido enviar
entradas nuevas desde que se cerró el foro el {$a}. Tú todavía tienes acceso a crear nuevas entradas.';
$string['removeolddiscussions'] = 'Eliminar temas antiguos';
$string['removeolddiscussions_help'] = 'El sistema puede eliminar temas automáticamente si no han tenido respuestas
durante un periodo determinado de tiempo.';
$string['removeolddiscussionsafter'] = 'Eliminar temas antiguos después de';
$string['removeolddiscussionsdefault'] = 'No eliminar nunca';
$string['withremoveddiscussions'] = 'Mover tema a';
$string['onemonth'] = '1 mes';
$string['withremoveddiscussions_help'] = 'Existen dos opciones para gestionar los temas eliminados:
<ul><li>Eliminarlos de forma permanente; al contrario que la utilidad estándar de borrado, los temas no podrán recuperarse.
Esta opción puede utilizarse para ahorrar espacio en la base de datos.</li>
<li>Moverlos a otro foro; por ejemplo, puedes tener un &lsquo;archivo del foro&rsquo;.
Puedes seleccionar cualquier otro foro del mismo curso.</li></ul>';
$string['deletepermanently'] = 'eliminar permanentemente';
$string['housekeepingstarthour']='Hora de comienzo del archivado';
$string['housekeepingstophour']='Hora de finalización del archivado';
$string['confighousekeepingstarthour']= 'Las tareas de archivado, como por ejemplo el borrado de temas antiguos, comenzará
cada día a esta hora.';
$string['confighousekeepingstophour']='El archivado de tareas terminará a esta hora.';
$string['invalidforum']='Este foro ya no existe';
$string['errorinvalidforum'] = 'El foro donde se archivaban los temas antiguos ya no existe. Por favor, elige un foro diferente.';
$string['archive_errorgrouping']= 'El foro que recibe los temas antiguos tiene una configuración de grupos diferente. Por favor,
actualiza el foro y cambia las opciones de <strong>Eliminar temas antiguos</strong>.';
$string['archive_errortargetforum']='El foro que se utilizaba para recibir los temas antiguos ya no existe. Por favor, actualiza
el foro y cambia las opciones de <strong>Eliminar entradas antiguas</strong>.';
$string['error_notwhensharing'] = 'Esta opción no está disponible en foros compartidos.';
$string['error_sharingrequiresidnumber'] = 'Al compartir el foro debes introducir un número ID que sea único en todo el sistema';
$string['error_sharingidnumbernotfound'] = 'Al utilizar un foro compartido, debes introducir un número ID exactamente igual al introducido
previamente en el foro que está compartido';
$string['error_sharinginuse'] = 'No puedes deshabilitar la compartición de este foro porque todavía existen foros compartidos con él.
Si es necesario debes eliminar primero esos otros foros.';
$string['error_nosharedforum'] = 'Foro <strong>{$a->name}</strong>: No se ha podido restaurar como foro
compartido; No se ha encontrado el número ID {$a->idnumber}. El foro restaurado es un foro independiente.';
$string['error_ratingrequired'] = 'Has elegido calificar por valoraciones, pero las valoraciones no están habilitadas';
$string['advancedsearch'] = 'Búsqueda avanzada';
$string['words'] = 'Buscar';
$string['words_help'] =
'Introduce aquí tu búsqueda.

Para buscar frases exactas utiliza las comillas.

Para excluir una palabra añade un guión precediendo a la palabra a excluir.

Ejemplo: la búsqueda <tt>picasso -escultura &quot;primeros trabajos</tt> devolverá resultados para
&lsquo;picasso&rsquo; o la frase &lsquo;primeros trabajos&rsquo; pero excluyendo ítems que contengan &lsquo;escultura&rsquo;.

Si dejas esto en blanco, entonces se mostrarán todas las entradas que cumplan los criterios de
autor y/o fecha, independientemente de su contenido.';
$string['authorname'] = 'Nombre del autor';
$string['authorname_help'] = 'Puedes introducir el nombre (Íñigo), un apellido (Montoya), el nombre completo (Íñigo Montoya),
o el principio de cualquiera de estos (Íñ, Mon, Íñi M). Las búsquedas no distinguen mayúsculas y minúsculas.

También puedes introducir el nombre de usuario (imon001).

Si dejas este campo en blanco se incluirán entradas de cualquier autor.';
$string['daterangefrom'] = 'Rango de fechas desde';
$string['daterangefrom_help'] = 'Utiliza estas fechas para restringir la búsqueda e incluir sólo las entradas
entre ese rango de fechas.

Si no indicas ninguna fecha se incluirán entradas de cualquier fecha en los resultados.';
$string['daterangeto'] = 'Rango de fechas hasta';
$string['searchresults'] = 'Resultados de la búsqueda: <strong>{$a}</strong>';
$string['searchtime'] = 'Hora de la búsqueda: {$a} s';
$string['nothingfound'] = ' No se han encontrado resultados. Prueba utilizando otra consulta.';
$string['previousresults'] = 'Volver a los resultados {$a}';
$string['nextresults'] = 'Ver más resultados';
$string['author'] = ' autor: \"{$a}\"';
$string['from'] = ' de: {$a}';
$string['to'] = ' a: {$a}';
$string['inappropriatedateortime'] = 'La fecha "desde" no puede ser posterior a hoy.';
$string['daterangemismatch'] = 'La fecha "hasta" es anterior a la fecha "desde".';
$string['nosearchcriteria'] = 'No hay criterios de búsqueda. Por favor, utilice uno o más de los criterios de búsqueda
que se muestran debajo.';
$string['searchallforums'] = 'Buscar en todos los foros';

$string['replies'] = 'Respuestas';
$string['newdiscussion'] = 'Tema nuevo';
$string['nothingtodisplay'] = '<h3>No hay nada que mostrar</h3>';
$string['re'] = 'Re: {$a}';

$string['error_feedlogin'] = 'Error al completar la identificación del usuario';

$string['error_makebig'] = 'El curso sólo tiene {$a->users} usuarios, pero has solicitado que
{$a->readusers} usuarios lean cada tema. Crea más usuarios.';
$string['error_system'] = 'Ocurrió un error del sistema: {$a}';


$string['modulename_help'] = 'ForumNG es un sustituto de los foros estándar de Moodle con prácticamente la misma funcionalidad
más características adicionales y un interfaz más dinámico.

NG viene de \'Next Generation\'.';
$string['mailnow_help'] = 'Enviar inmediatamente las entradas a los suscriptores.

A menos que selecciones esta opción, el sistema esperará un tiempo antes de enviar la entrada
para que las ediciones que se hubieran realizado se incluyan también en el correo.';
$string['displayperiod_help'] = 'Puedes ocultar este tema a los estudiantes desde, o hasta, una fecha determinada.

Mientras está oculto los estudiantes no verán el tema. Para los moderadores se mostrará
en la lista en color gris y con el icono de un reloj.';

$string['sticky_help'] = 'Esta opción puede hacer que los temas aparezcan en la parte superior de la lista,
incluso cuando se añadan posteriormente otros temas.

Los temas de tipo post-it se muestran con una flecha hacia arriba en la lista de temas. Puedes
tener más de un tema de tipo post-it';

$string['errorfindinglastpost'] = 'Error al recalcular la última entrada (¿base de datos inconsistente?)';

$string['drafts_help'] = 'Cuando guardas un borrador, aparece en esta lista. Pulsa sobre el
borrador para continuar trabajando con él.

Si quieres eliminar el borrador, pulsa el icono de eliminación que hay junto a él. Se mostrará una pantalla de confirmiacion.

En algunos casos no será posible continuar trabajando con el borrador (por ejemplo, si era una
respuesta a un tema que ha sido borrado). En esos casos puedes recuperar el contenido del borrador
pulsando en el icono de eliminación.';

$string['flaggedposts_help'] = 'Las entradas marcadas aparecen en esta lista. Para ver una entrada marcada,
pulsa sobre ella.

Para eliminar la marca de una entrada, pulsa en el icono de la bandera (aquí o en la entrada).';
$string['searchthisforum_help'] = 'Introduce tu búsqueda y pulsa Enter o el botón asociado.

Para buscar frases exactas utiliza las comillas.

Para excluir una palabra añade un guión precediendo a la palabra a excluir.

Ejemplo: la búsqueda <tt>picasso -escultura &quot;primeros trabajos</tt> devolverá resultados para
&lsquo;picasso&rsquo; o la frase &lsquo;primeros trabajos&rsquo; pero excluyendo ítems que contengan &lsquo;escultura&rsquo;.

Para buscar por autor o fecha, pulsa en &lsquo;Más opciones&rsquo;.';
$string['searchthisforumlink_help'] = 'Introduce tu búsqueda y pulsa Enter o el botón asociado.

Para buscar frases exactas utiliza las comillas.

Para excluir una palabra añade un guión precediendo a la palabra a excluir.

Ejemplo: la búsqueda <tt>picasso -escultura &quot;primeros trabajos</tt> devolverá resultados para
&lsquo;picasso&rsquo; o la frase &lsquo;primeros trabajos&rsquo; pero excluyendo ítems que contengan &lsquo;escultura&rsquo;.

Para buscar por autor o fecha, pulsa en &lsquo;Más opciones&rsquo;.';

$string['notext'] = '(sin texto)';

$string['grade'] = 'Calificación';
$string['gradingscale'] = 'Escala de calificación';
