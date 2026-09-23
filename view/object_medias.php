<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    view/object_medias.php
 * \ingroup digiquali
 * \brief   Tab for medias on object
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
    require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
    die('Include of digiquali main fails');
}

// Get module parameters
$objectType = GETPOST('object_type', 'alpha');

// Load DigiQuali libraries
require_once __DIR__ . '/../class/' . $objectType . '.class.php';
require_once __DIR__ . '/../lib/digiquali_' . $objectType . '.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id         = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action     = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$className = ucfirst($objectType);
$object    = new $className($db);

$hookmanager->initHooks([$object->element . 'media', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once

// Security check - Protection if external user
$permissionToRead = $user->rights->digiquali->$objectType->read;
saturne_check_access($permissionToRead);

/*
 * Action
 */

$parameters = ['id' => $id];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Actions set_thirdparty, set_project
    require_once __DIR__ . '/../../saturne/core/tpl/actions/banner_actions.tpl.php';
}

/*
 * View
 */

$title   = $langs->trans('Medias');
$helpUrl = 'FR:Module_DigiQuali';

saturne_header(0,'', $title, $helpUrl);

if ($id > 0 || !empty($ref)) {
    saturne_get_fiche_head($object, 'medias', $title);
    saturne_banner_tab($object, 'ref', '', 1, 'ref', 'ref', '', !empty($object->photo));

    print '<div class="fichecenter element-list-medias">';
    print '<div class="underbanner clearboth"></div>';

    print load_fiche_titre($langs->trans('MediaGalleryQuestionAnswers'), '', '');

    $object->fetchObjectLinked($object->fk_sheet, 'digiquali_sheet');
    $questionIds     = $object->linkedObjectsIds;
    $questionsLinked = $object->linkedObjects;
    $linkedMedias    = 0;
    $confName        = 'DIGIQUALI_' . dol_strtoupper($object->element) . '_USE_LARGE_MEDIA_IN_GALLERY';

    if (is_array($questionsLinked['digiquali_question']) && !empty($questionsLinked['digiquali_question'])) {
        foreach ($questionsLinked['digiquali_question'] as $questionLinked) {
            if ($questionLinked->authorize_answer_photo > 0) {
                $photo = saturne_show_medias_linked('digiquali', $conf->digiquali->multidir_output[$conf->entity] . '/' . $object->element . '/' . $object->ref . '/answer_photo/' . $questionLinked->ref, (getDolGlobalInt($confName) ? 'large' : 'medium'), '', 0, 0, 0, 200, 200, 0, 0, 0, $object->element . '/' . $object->ref . '/answer_photo/' . $questionLinked->ref, $object, '', 0, 0);
                print '<div class="question-section">';
                print '<span class="question-ref">' . $questionLinked->getNomUrl(0, '', 1, '', -1, 1) . (empty($object->nbphoto) ? ' - ' . $langs->transnoentities('NoPhotoYet') : '') . '</span>';
                print '<div class="table-cell table-full linked-medias answer_photo">';
                if (!empty($object->nbphoto)) {
                    print $photo;
                }
                print '</div>';
                print '</div>';
                $linkedMedias++;
            }
        }
    }

    if ($linkedMedias == 0) {
        print $langs->trans('NoObjectLineAnswersPhoto', dol_strtolower($langs->transnoentities(ucfirst($object->element))));
    }

    print '</div>';
    print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
