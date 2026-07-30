<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       view/digiqualitools.php
 *	\ingroup    digiquali
 *	\brief      Tools page of digiquali left menu
 */

// Load DigiQuali environment
if (file_exists('../digiquali.main.inc.php')) {
	require_once __DIR__ . '/../digiquali.main.inc.php';
} elseif (file_exists('../../digiquali.main.inc.php')) {
	require_once __DIR__ . '/../../digiquali.main.inc.php';
} else {
	die('Include of digiquali main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';

require_once __DIR__ . '/../class/answer.class.php';
require_once __DIR__ . '/../class/question.class.php';
require_once __DIR__ . '/../class/questiongroup.class.php';
require_once __DIR__ . '/../class/sheet.class.php';
require_once __DIR__ . '/../lib/digiquali.lib.php';

// Global variables definitions
global $conf, $db, $entity, $langs, $user;

saturne_load_langs(['exports']);

// Get parameters
$action = GETPOST('action', 'alpha');

// Initialize objects
// Technical objets
$answer        = new Answer($db);
$question      = new Question($db);
$questionGroup = new QuestionGroup($db);
$sheet         = new Sheet($db);

$error      = 0;
$now        = dol_now();
$upload_dir = $conf->digiquali->multidir_output[isset($conf->entity) ? $conf->entity : 1];

// Diagnostic console log filled during import and rendered by core/tpl/digiquali_import_console.tpl.php
$importLog = array();
$dqLog = static function (string $msg, string $level = 'info') use (&$importLog) {
	$importLog[] = array('time' => date('H:i:s'), 'level' => $level, 'msg' => $msg);
};
// Best-effort error message from a Dolibarr object. createCommon() usually fills errors[] (not ->error),
// and the objects are reused across the loop so errors[] accumulates: keep the most recent entry.
$objErr = static function ($object): string {
	if (!empty($object->errors) && is_array($object->errors)) {
		return (string) end($object->errors);
	}
	return (string) $object->error;
};

// Security check
$permissionToReadQuestions   = $user->rights->digiquali->question->read;
$permissionToReadSheets      = $user->rights->digiquali->question->read;
$permissionToRead            = $permissionToReadQuestions && $permissionToReadSheets;
$permissionToImportQuestions = $user->rights->digiquali->question->write;
$permissionToImportSheets    = $user->rights->digiquali->sheet->write;
$permissionToWrite           = $permissionToImportQuestions && $permissionToImportSheets;

saturne_check_access($permissionToRead);

/*
 * Actions
 */

if ($action == 'data_migration_export_global' && $permissionToRead) {
	$digiqualiExportArray = [];
	if (GETPOST('data_migration_export_sqa', 'alpha')) {
		$allSheets  = $sheet->fetchAll();
		$exportName = 'all_models';
		if (is_array($allSheets) && !empty($allSheets)) {
			foreach ($allSheets as $sheetSingle) {
				$sheetExportArray['rowid']               = $sheetSingle->id;
				$sheetExportArray['ref']                 = $sheetSingle->ref;
				$sheetExportArray['status']              = $sheetSingle->status;
                $sheetExportArray['type']                = $sheetSingle->type;
				$sheetExportArray['label']               = $sheetSingle->label;
				$sheetExportArray['description']         = $sheetSingle->description;
				$sheetExportArray['element_linked']      = $sheetSingle->element_linked;
                $sheetExportArray['success_rate']        = $sheetSingle->success_rate;
				$sheetExportArray['mandatory_questions'] = $sheetSingle->mandatory_questions;

				$digiqualiExportArray['sheets'][$sheetSingle->id] = $sheetExportArray;

                $questionsAndGroupsLinked = $sheetSingle->fetchQuestionsAndGroups();
				if (is_array($questionsAndGroupsLinked) && !empty($questionsAndGroupsLinked)) {
					foreach ($questionsAndGroupsLinked as $questionOrGroup) {
                        if ($questionOrGroup->element == 'question') {
                            $digiqualiExportArray['element_element_questions'][$sheetSingle->id][] = $questionOrGroup->id;
                        } else if ($questionOrGroup->element == 'questiongroup') {
                            $digiqualiExportArray['element_element_questiongroups'][$sheetSingle->id][] = $questionOrGroup->id;
                        }
					}
				}
			}
		}
	} else {
		$exportName = 'all_questions';
	}

    $allQuestions = $question->fetchAll();
    if (is_array($allQuestions) && !empty($allQuestions)) {
        foreach ($allQuestions as $questionSingle) {
            $questionExportArray['rowid']                  = $questionSingle->id;
            $questionExportArray['ref']                    = $questionSingle->ref;
            $questionExportArray['status']                 = $questionSingle->status;
            $questionExportArray['type']                   = $questionSingle->type;
            $questionExportArray['label']                  = $questionSingle->label;
            $questionExportArray['description']            = $questionSingle->description;
            $questionExportArray['show_photo']             = $questionSingle->show_photo;
            $questionExportArray['authorize_answer_photo'] = $questionSingle->authorize_answer_photo;
            $questionExportArray['enter_comment']          = $questionSingle->enter_comment;

            $digiqualiExportArray['questions'][$questionSingle->id] = $questionExportArray;
		}
	}

	$allAnswers = $answer->fetchAll();
	if (is_array($allAnswers) && !empty($allAnswers)) {
		foreach ($allAnswers as $answerSingle) {
			$answerExportArray['rowid']       = $answerSingle->id;
			$answerExportArray['ref']         = $answerSingle->ref;
			$answerExportArray['status']      = $answerSingle->status;
			$answerExportArray['value']       = $answerSingle->value;
			$answerExportArray['position']    = $answerSingle->position;
			$answerExportArray['pictogram']   = $answerSingle->pictogram;
			$answerExportArray['color']       = $answerSingle->color;
			$answerExportArray['fk_question'] = $answerSingle->fk_question;

			$digiqualiExportArray['questions'][$answerSingle->fk_question]['answers'][$answerSingle->id] = $answerExportArray;
		}
    }

    $digiqualiExportJSON = json_encode($digiqualiExportArray, JSON_PRETTY_PRINT);

    $fileDir    = $upload_dir . '/temp/';
    $exportBase = $fileDir . dol_print_date(dol_now(), 'dayhourlog', 'tzuser') . '_dolibarr_' . $exportName . '_export';
    $fileName   = $exportBase . '.json';

    file_put_contents($fileName, $digiqualiExportJSON);

    $zip = new ZipArchive();
    if ($zip->open($exportBase . '.zip', ZipArchive::CREATE ) === TRUE) {
		setEventMessage($langs->transnoentities("ExportWellDone"));
		$zip->addFile($fileName, basename($fileName));
        $zip->close();
        $fileNameZip = dol_print_date(dol_now(), 'dayhourlog', 'tzuser') . '_dolibarr_' . $exportName . '_export.zip';
        $filepath = DOL_URL_ROOT . '/document.php?modulepart=digiquali&file=' . urlencode('temp/'.$fileNameZip);

        ?>
        <script>
            var alink = document.createElement( 'a' );
            alink.setAttribute('href', <?php echo json_encode($filepath); ?>);
            alink.setAttribute('download', <?php echo json_encode($fileNameZip); ?>);
            alink.click();
        </script>
        <?php
        $fileExportGlobals = dol_dir_list($fileDir, "files", 0, '', '', '', '', 1);
    }
}

// Import ZIP file
if (GETPOST('dataMigrationImportZip', 'alpha') && $permissionToWrite) {
    if (!empty($_FILES)) {
        if ($_FILES['dataMigrationImportZipFile']['size'][0] < 1) {
            setEventMessages($langs->trans('ErrorArchiveNotWellFormattedZIP'), [], 'errors');
        } else {
            if (is_array($_FILES['dataMigrationImportZipFile']['tmp_name'])) {
                $userFiles = $_FILES['dataMigrationImportZipFile']['tmp_name'];
            } else {
                $userFiles = array($_FILES['dataMigrationImportZipFile']['tmp_name']);
            }

            foreach ($userFiles as $key => $userFile) {
                if (empty($_FILES['dataMigrationImportZipFile']['tmp_name'][$key])) {
                    $error++;
                    if ($_FILES['dataMigrationImportZipFile']['error'][$key] == 1 || $_FILES['dataMigrationImportZipFile']['error'][$key] == 2) {
                        setEventMessages($langs->trans('ErrorFileSizeTooLarge'), [], 'errors');
                    } else {
                        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), [], 'errors');
                    }
                }
            }

            $result       = 0;
            $safeZipName  = '';
            $originalName = (string) ($_FILES['dataMigrationImportZipFile']['name'][0] ?? '');
            if (!$error) {
                $fileDir = $upload_dir . '/temp/';
                if (!empty($fileDir)) {
                    // Force an ASCII-safe filename. Under Windows the filesystem encoding is ISO-8859-1
                    // (see dol_osencode()) which can't represent Unicode punctuation like the en-dash "–",
                    // so move_uploaded_file() would receive a "?" and fail. We also reuse this exact name
                    // below to reopen the archive, since dol_add_file_process() saves the sanitized name.
                    $info        = pathinfo(trim($originalName));
                    $safeZipName = dol_sanitizeFileName($info['filename'] . (!empty($info['extension']) ? '.' . strtolower($info['extension']) : ''));
                    $safeZipName = dol_string_nohtmltag(preg_replace('/[^\x20-\x7E]/u', '_', $safeZipName));
                    $_FILES['dataMigrationImportZipFile']['name'][0] = $safeZipName;

                    $dqLog('Fichier reçu : "' . $originalName . '" → enregistré sous "' . $safeZipName . '"');

                    $result = dol_add_file_process($fileDir, 1, 1, 'dataMigrationImportZipFile', '', null, '', 0);
                    if ($result > 0) {
                        $dqLog('Upload OK dans ' . $fileDir, 'success');
                    } else {
                        $dqLog('Échec de l\'upload du fichier (dol_add_file_process = ' . $result . ')', 'error');
                    }
                }
            }

            $digiqualiExportArray = null;
            $jsonString = '';
            if ($result > 0) {
                $zip    = new ZipArchive;
                $zipRes = $zip->open($fileDir . $safeZipName);
                if ($zipRes === true) {
                    $zip->extractTo($fileDir);
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $stat = $zip->statIndex($i);
                        if (preg_match('/\.json$/i', $stat['name'])) {
                            $jsonString = $zip->getFromIndex($i);
                            break;
                        }
                    }
                    $zip->close();
                    $dqLog('Archive ZIP extraite', 'success');
                } else {
                    $dqLog('Impossible d\'ouvrir l\'archive ZIP "' . $safeZipName . '" (code ' . $zipRes . ')', 'error');
                }

                if (!empty($jsonString)) {
                    $digiqualiExportArray = json_decode($jsonString, true);
                    if (!is_array($digiqualiExportArray)) {
                        $digiqualiExportArray = null;
                        $dqLog('Fichier JSON illisible ou invalide (' . json_last_error_msg() . ')', 'error');
                    } else {
                        $dqLog(sprintf(
                            'JSON chargé : %d question(s), %d groupe(s), %d trame(s)',
                            is_array($digiqualiExportArray['questions'] ?? null) ? count($digiqualiExportArray['questions']) : 0,
                            is_array($digiqualiExportArray['questiongroups'] ?? null) ? count($digiqualiExportArray['questiongroups']) : 0,
                            is_array($digiqualiExportArray['sheets'] ?? null) ? count($digiqualiExportArray['sheets']) : 0
                        ), 'success');
                    }
                } else {
                    $dqLog('Fichier JSON introuvable dans l\'archive : ' . $fileName, 'error');
                }
            }

            $importKey             = dol_print_date($now, 'dayhourlog');
            $idCorrespondanceArray = [];
            $error                 = 0;

			if (is_array($digiqualiExportArray['questions'] ?? null) && !empty($digiqualiExportArray['questions'])) {
				foreach ($digiqualiExportArray['questions'] as $questionSingle) {
					$question->ref_ext                = $questionSingle['ref'];
					$question->type                   = $questionSingle['type'];
					$question->label                  = $questionSingle['label'];
					$question->description            = $questionSingle['description'];
					$question->show_photo             = $questionSingle['show_photo'];
					$question->authorize_answer_photo = $questionSingle['authorize_answer_photo'];
					$question->enter_comment          = $questionSingle['enter_comment'];
					$question->status                 = Question::STATUS_VALIDATED;
					$question->import_key             = $importKey;

					$questionId = $question->create($user);

					if ($questionId > 0) {
						$dqLog('✓ Question #' . $questionId . ' — ' . dol_trunc($question->label, 60), 'success');
						$idCorrespondanceArray['question'][$questionSingle['rowid']] = $questionId;
						if (array_key_exists('answers', $questionSingle) && !empty($questionSingle['answers'])) {
							foreach ($questionSingle['answers'] as $answerSingle) {
								$answer->ref_ext     = $answerSingle['ref'];
								$answer->status      = $answerSingle['status'];
								$answer->value       = $answerSingle['value'];
								$answer->position    = $answerSingle['position'];
								$answer->pictogram   = $answerSingle['pictogram'];
								$answer->color       = $answerSingle['color'];
								$answer->fk_question = $questionId;
								$answer->import_key  = $importKey;

								$answerId = $answer->create($user);

								if ($answerId <= 0) {
									$error++;
									$dqLog('✗ Réponse « ' . dol_trunc($answer->value, 40) . ' » : ' . $objErr($answer), 'error');
								}
							}
						}
					} else {
						$error++;
						$dqLog('✗ Question « ' . dol_trunc($questionSingle['label'] ?? '', 60) . ' » : ' . $objErr($question), 'error');
					}
				}
			}

            if (is_array($digiqualiExportArray['questiongroups'] ?? null) && !empty($digiqualiExportArray['questiongroups'])) {
                foreach($digiqualiExportArray['questiongroups'] as $questionGroupSingle) {
                    $previousQuestionGroup = new QuestionGroup($db);

                    $questionGroup->status       = $questionGroupSingle['status'];
                    $questionGroup->label        = $questionGroupSingle['label'];
                    $questionGroup->description  = $questionGroupSingle['description'];
                    $questionGroup->success_rate = $questionGroupSingle['success_rate'] ?? 0;

                    $questionGroupId = $questionGroup->create($user);

                    if ($questionGroupId > 0) {
                        $dqLog('✓ Groupe #' . $questionGroupId . ' — ' . dol_trunc($questionGroup->label, 60), 'success');
                        $idCorrespondanceArray['questiongroup'][$questionGroupSingle['rowid']] = $questionGroupId;

                        // Create questions for this question group
                        if (is_array($questionGroupSingle['questions']) && !empty($questionGroupSingle['questions'])) {
                            foreach ($questionGroupSingle['questions'] as $questionSingle) {
                                $question->ref_ext                = $questionSingle['ref'];
                                $question->type                   = $questionSingle['type'];
                                $question->label                  = $questionSingle['label'];
                                $question->description            = $questionSingle['description'];
                                $question->show_photo             = $questionSingle['show_photo'];
                                $question->authorize_answer_photo = $questionSingle['authorize_answer_photo'];
                                $question->enter_comment          = $questionSingle['enter_comment'];
                                $question->status                 = Question::STATUS_VALIDATED;
                                $question->import_key             = $importKey;

                                $newQuestionId = $question->create($user);
                                if ($newQuestionId > 0) {
                                    $dqLog('    ✓ Question de groupe #' . $newQuestionId . ' — ' . dol_trunc($question->label, 50), 'success');
                                    $idCorrespondanceArray['question'][$questionSingle['rowid']] = $newQuestionId;
                                    $questionGroup->addQuestion($newQuestionId);

                                    if (array_key_exists('answers', $questionSingle) && !empty($questionSingle['answers'])) {
                                        foreach ($questionSingle['answers'] as $answerSingle) {
                                            $answer->ref_ext     = $answerSingle['ref'];
                                            $answer->status      = $answerSingle['status'];
                                            $answer->value       = $answerSingle['value'];
                                            $answer->position    = $answerSingle['position'];
                                            $answer->pictogram   = $answerSingle['pictogram'];
                                            $answer->color       = $answerSingle['color'];
                                            $answer->fk_question = $newQuestionId;
                                            $answer->import_key  = $importKey;

                                            $newAnswerId = $answer->create($user);

                                            if ($newAnswerId <= 0) {
                                                $error++;
                                                $dqLog('    ✗ Réponse « ' . dol_trunc($answer->value, 40) . ' » : ' . $objErr($answer), 'error');
                                            }
                                        }
                                    }
                                } else {
                                    $error++;
                                    $dqLog('    ✗ Question de groupe « ' . dol_trunc($questionSingle['label'] ?? '', 50) . ' » : ' . $objErr($question), 'error');
                                }
                            }
                        }

                    } else {
                        $error++;
                        $dqLog('✗ Groupe « ' . dol_trunc($questionGroupSingle['label'] ?? '', 60) . ' » : ' . $objErr($questionGroup), 'error');
                    }
                }
            }

            // Re-link sub-groups to their parent group (nested question groups).
            if (!empty($digiqualiExportArray['questiongroups']) && is_array($digiqualiExportArray['questiongroups'])) {
                foreach ($digiqualiExportArray['questiongroups'] as $questionGroupSingle) {
                    if (empty($questionGroupSingle['parent_group_id'])) {
                        continue;
                    }
                    $oldParentId = $questionGroupSingle['parent_group_id'];
                    $oldChildId  = $questionGroupSingle['rowid'];
                    if (isset($idCorrespondanceArray['questiongroup'][$oldParentId], $idCorrespondanceArray['questiongroup'][$oldChildId])) {
                        $childGroup = new QuestionGroup($db);
                        $childGroup->fetch($idCorrespondanceArray['questiongroup'][$oldChildId]);
                        $childGroup->add_object_linked('digiquali_questiongroup', $idCorrespondanceArray['questiongroup'][$oldParentId]);
                    }
                }
            }

            if (is_array($digiqualiExportArray['sheets'] ?? null) && !empty($digiqualiExportArray['sheets'])) {
                foreach ($digiqualiExportArray['sheets'] as $sheetSingle) {
                    // A sheet must define at least one "objet à contrôler" (element_linked); without it,
                    // no control can ever be created from the imported model. The creation form already
                    // blocks this (NoLinkedObjectSelected), so the import must reject it too instead of
                    // silently letting an unusable model enter the system.
                    $linkedObjects         = json_decode($sheetSingle['element_linked'] ?? '', true);
                    $hasControllableObject = false;
                    if (is_array($linkedObjects)) {
                        foreach ($linkedObjects as $isObjectLinked) {
                            if (!empty($isObjectLinked)) {
                                $hasControllableObject = true;
                                break;
                            }
                        }
                    }
                    if (!$hasControllableObject) {
                        $error++;
                        $dqLog('✗ Trame « ' . dol_trunc($sheetSingle['label'] ?? '', 60) . ' » ignorée : aucun objet à contrôler défini', 'error');
                        continue;
                    }

					$sheet->ref_ext             = $sheetSingle['ref'];
                    $sheet->type                = $sheetSingle['type'];
                    $sheet->label               = $sheetSingle['label'];
                    $sheet->description         = $sheetSingle['description'];
					$sheet->element_linked      = $sheetSingle['element_linked'];
                    $sheet->success_rate        = $sheetSingle['success_rate'];
					$sheet->mandatory_questions = $sheetSingle['mandatory_questions'];
					$sheet->status              = Sheet::STATUS_VALIDATED;
					$sheet->import_key          = $importKey;

					$sheetMandatoryQuestions = json_decode($sheetSingle['mandatory_questions'] ?? '');
					$questionsToLink         = array();

					if (is_array($sheetMandatoryQuestions) && !empty($sheetMandatoryQuestions)) {
						foreach($sheetMandatoryQuestions as $sheetMandatoryQuestionId) {
							$newQuestionIdToLink = $idCorrespondanceArray['question'][$sheetMandatoryQuestionId] ?? 0;
							if ($newQuestionIdToLink > 0) {
								$questionsToLink[] = $newQuestionIdToLink;
							}
						}
						$sheet->mandatory_questions = !empty($questionsToLink) ? json_encode($questionsToLink) : '{}';
					} else {
						$sheet->mandatory_questions = '{}';
					}

                    $sheetId = $sheet->create($user);

                    if ($sheetId > 0) {
                        $dqLog('✓ Trame #' . $sheetId . ' — ' . dol_trunc($sheet->label, 60), 'success');
                        $idCorrespondanceArray['sheet'][$sheetSingle['rowid']] = $sheetId;
                        if (is_array($digiqualiExportArray['element_element_questions'] ?? null)) {
                            foreach ($digiqualiExportArray['element_element_questions'] as $previousSheetId => $previousQuestionIdArray) {
                                if (is_array($previousQuestionIdArray) && !empty($previousQuestionIdArray)) {
                                    foreach($previousQuestionIdArray as $previousQuestionId) {
                                        $newSheetId    = $idCorrespondanceArray['sheet'][$previousSheetId] ?? 0;
                                        $newQuestionId = $idCorrespondanceArray['question'][$previousQuestionId] ?? 0;
                                        if ($newSheetId > 0 && $newQuestionId > 0) {
                                            $question->fetch($newQuestionId);
                                            $question->add_object_linked('digiquali_sheet', $newSheetId);
                                        }
                                    }
                                }
                            }
                        }
                        if (is_array($digiqualiExportArray['element_element_questiongroups'] ?? null)) {
                            foreach ($digiqualiExportArray['element_element_questiongroups'] as $previousSheetId => $previousQuestionGroupIdArray) {
                                if (is_array($previousQuestionGroupIdArray) && !empty($previousQuestionGroupIdArray)) {
                                    foreach($previousQuestionGroupIdArray as $previousQuestionGroupId) {
                                        $newSheetId         = $idCorrespondanceArray['sheet'][$previousSheetId] ?? 0;
                                        $newQuestionGroupId = $idCorrespondanceArray['questiongroup'][$previousQuestionGroupId] ?? 0;
                                        if ($newSheetId > 0 && $newQuestionGroupId > 0) {
                                            $questionGroup->fetch($newQuestionGroupId);
                                            $questionGroup->add_object_linked('digiquali_sheet', $newSheetId);
                                        }
                                    }
                                }
                            }
                        }
                        $sheet->fetch($sheetId);
                        $sheet->fetchObjectLinked($sheetId, 'digiquali_' . $sheet->element, null, '', 'OR', 1, 'position', 0);
                        $questionGroupIds   = $sheet->linkedObjectsIds['digiquali_questiongroup'] ?? [];
                        $questionIds        = $sheet->linkedObjectsIds['digiquali_question'] ?? [];
                        $sheet->updateQuestionsAndGroupsPosition($questionIds, $questionGroupIds);
                    } else {
                        $error++;
                        $dqLog('✗ Trame « ' . dol_trunc($sheetSingle['label'] ?? '', 60) . ' » : ' . $objErr($sheet), 'error');
                    }
                }
            }

            // Import summary — the banner colour now reflects the real outcome (details in the console below)
            $importOk  = count(array_filter($importLog, static fn($l) => $l['level'] === 'success'));
            $importErr = count(array_filter($importLog, static fn($l) => $l['level'] === 'error'));
            if (!is_array($digiqualiExportArray)) {
                setEventMessages('Échec de l\'import : fichier illisible ou vide — voir la console ci-dessous.', null, 'errors');
            } elseif ($importErr > 0) {
                setEventMessages($importOk . ' élément(s) importé(s), ' . $importErr . ' en échec — voir la console ci-dessous.', null, 'warnings');
            } else {
                setEventMessage($langs->transnoentities('FileWasImported', $importKey));
            }
        }
	}
}

/*
 * View
 */

$title    = $langs->trans('Tools', 'DigiQuali');
$help_url = 'FR:Module_DigiQuali';

saturne_header(0,'', $title);

print load_fiche_titre($langs->trans('Tools'), '', 'wrench');

print load_fiche_titre($langs->trans("DataMigrationDigiQualiToFile"), '', '');

print '<form class="data-migration-export-global-from" name="data_migration_export_global" id="data_migration_export_global" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="data_migration_export_global">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Action") . '</td>';
print '</tr>';

// Export sheets, questions and answers data from DigiQuali
print '<tr class="oddeven"><td>';
print $langs->trans('DataMigrationExportSQA');
print "</td><td>";
print $langs->trans('DataMigrationExportSQADescription');
print '</td>';

print '<td class="center data-migration-export-global">';
print '<input type="submit" class="button reposition data-migration-submit" name="data_migration_export_sqa" value="' . $langs->trans("ExportData") . '">';
print '</td>';
print '</tr>';

// Export questions and answers data from DigiQuali
print '<tr class="oddeven"><td>';
print $langs->trans('DataMigrationExportQA');
print "</td><td>";
print $langs->trans('DataMigrationExportQADescription');
print '</td>';

print '<td class="center data-migration-export-global">';
print '<input type="submit" class="button reposition data-migration-submit" name="dataMigrationExportQA" value="' . $langs->trans("ExportData") . '">';
print '</td>';
print '</tr>';
print '</form>';

print load_fiche_titre($langs->trans("DataMigrationFileToDolibarr"), '', '');

print '<form class="data-migration" name="DataMigration" id="DataMigration" action="' . $_SERVER["PHP_SELF"] . '" enctype="multipart/form-data" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Action") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('DataMigrationImportZIP');
print "</td><td>";
print $langs->trans('DataMigrationImportZIPDescription');
print '</td>';

print '<td class="center">';
print '<input class="flat" type="file" name="dataMigrationImportZipFile[]"/>';
print '<input type="submit" class="wpeo-button button reposition data-migration-submit" name="dataMigrationImportZip" value="' . $langs->trans("Upload") . '">';
print '</td>';
print '</tr>';

print '</form>';

// Diagnostic console — always rendered; stays empty until an import fills $importLog
include __DIR__ . '/../core/tpl/digiquali_import_console.tpl.php';

// Page end
llxFooter();
$db->close();
