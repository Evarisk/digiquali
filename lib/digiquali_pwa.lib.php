<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiquali_pwa.lib.php
 * \ingroup digiquali
 * \brief   Library functions for the DigiQuali PWA (frontend): navigation items + dashboard counters.
 */

/**
 * Return the canonical list of PWA bottom nav items.
 *
 * Keys are stable slugs, array order is the display order in the bottom bar.
 *
 * @return array<string,array{url:string,page:string,icon:string,label:string}>
 */
function digiquali_pwa_nav_get_items(): array
{
    global $langs;

    $urlBase = dol_buildpath('/custom/digiquali/view/frontend/', 1);

    return [
        'home'     => ['url' => $urlBase . 'pwa_home.php?source=pwa',     'page' => 'pwa_home.php',     'icon' => 'fa-home',            'label' => ucfirst($langs->trans('Home'))],
        'sheets'   => ['url' => $urlBase . 'pwa_sheets.php?source=pwa',   'page' => 'pwa_sheets.php',   'icon' => 'fa-clipboard-list',  'label' => ucfirst($langs->trans('Sheets'))],
        'controls' => ['url' => $urlBase . 'pwa_controls.php?source=pwa', 'page' => 'pwa_controls.php', 'icon' => 'fa-clipboard-check', 'label' => ucfirst($langs->trans('Controls'))],
        'surveys'  => ['url' => $urlBase . 'pwa_surveys.php?source=pwa',  'page' => 'pwa_surveys.php',  'icon' => 'fa-list-ol',         'label' => ucfirst($langs->trans('Surveys'))],
    ];
}

/**
 * Return the PWA object types definition (class, card page, permission key, icon).
 *
 * Keys are the stable slugs used by the bottom nav and the AJAX search endpoint.
 *
 * @return array<string,array{class:string,card:string,right:string,icon:string}>
 */
function digiquali_pwa_get_object_types(): array
{
    return [
        'sheets'   => ['class' => 'Sheet',   'card' => '/custom/digiquali/view/sheet/sheet_card.php',     'right' => 'sheet',   'icon' => 'fa-clipboard-list'],
        'controls' => ['class' => 'Control', 'card' => '/custom/digiquali/view/control/control_card.php', 'right' => 'control', 'icon' => 'fa-clipboard-check'],
        'surveys'  => ['class' => 'Survey',  'card' => '/custom/digiquali/view/survey/survey_card.php',   'right' => 'survey',  'icon' => 'fa-list-ol'],
    ];
}

/**
 * Count records of a DigiQuali object type for the PWA dashboard.
 *
 * Thin wrapper around saturne_fetch_all_object_type() in count mode. The object
 * class must already be loaded by the caller.
 *
 * @param  string $className Object class name (Sheet, Control, Survey).
 * @param  array  $filter    Optional extra filters (e.g. ['customsql' => "t.status = 1"]).
 * @return int               Number of records (0 on error or empty).
 */
function digiquali_pwa_count(string $className, array $filter = []): int
{
    if (!function_exists('saturne_fetch_all_object_type')) {
        require_once __DIR__ . '/../../saturne/lib/object.lib.php';
    }

    $nb = saturne_fetch_all_object_type($className, '', '', 0, 0, $filter, 'AND', false, true, false, '', ['count' => 1]);

    return (is_numeric($nb) && $nb > 0) ? (int) $nb : 0;
}

/**
 * Return the list-visible fields of an object, formatted like a normal Dolibarr list.
 *
 * Mirrors the column selection of the standard list pages: keeps fields that are
 * enabled and visible on list (visible in {1, 2, 5}), ordered by 'position', and
 * formats each value with CommonObject::showOutputField() (dates, select labels,
 * foreign-key links, prices, ...). Fields handled elsewhere on the card (ref, label,
 * status) and empty values are skipped.
 *
 * @param  object $object Loaded DigiQuali object (Sheet, Control, Survey).
 * @return array<int,array{label:string,html:string}> Ordered field rows.
 */
function digiquali_pwa_get_card_fields(object $object): array
{
    global $db, $langs;

    $skip = ['rowid', 'ref', 'label', 'status'];
    $rows = [];

    // 1) Native object fields shown on a normal list.
    if (!empty($object->fields) && is_array($object->fields)) {
        foreach ($object->fields as $key => $val) {
            if (in_array($key, $skip, true)) {
                continue;
            }

            // Respect the field 'enabled' condition (numeric flag or PHP condition string).
            $enabled = $val['enabled'] ?? 1;
            if (!is_numeric($enabled)) {
                $enabled = verifCond($enabled) ? 1 : 0;
            }
            if (empty($enabled)) {
                continue;
            }

            // Keep meaningful fields: visible on list/view (1, 2, 5) or on form (4, e.g. Controller, Project).
            if (!in_array((int) ($val['visible'] ?? 0), [1, 2, 4, 5], true)) {
                continue;
            }

            $html = $object->showOutputField($val, $key, $object->$key ?? '');
            if (trim((string) $html) === '') {
                continue;
            }

            $rows[(int) ($val['position'] ?? 0)] = [
                'label' => $langs->trans($val['label']),
                'html'  => $html,
            ];
        }
    }

    // 2) Extrafields (custom attributes) shown on list, like the standard list pages.
    if (!empty($object->table_element)) {
        static $efCache = [];
        $table = $object->table_element;
        if (!isset($efCache[$table])) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            $extraFields = new ExtraFields($db);
            $extraFields->fetch_name_optionals_label($table);
            $efCache[$table] = $extraFields;
        }
        $attributes = $efCache[$table]->attributes[$table] ?? [];

        if (!empty($attributes['label']) && is_array($attributes['label'])) {
            foreach ($attributes['label'] as $key => $label) {
                if (($attributes['type'][$key] ?? '') === 'separate') {
                    continue;
                }
                // Respect the extrafield "show on list" visibility.
                if (!in_array((int) ($attributes['list'][$key] ?? 0), [1, 2, 4, 5], true)) {
                    continue;
                }
                $value = $object->array_options['options_' . $key] ?? '';
                if ($value === '' || $value === null) {
                    continue;
                }
                $html = $efCache[$table]->showOutputField($key, $value, '', $table);
                if (trim((string) $html) === '') {
                    continue;
                }
                // Offset extrafield positions so they sort after native fields.
                $rows[10000 + (int) ($attributes['pos'][$key] ?? 0)] = [
                    'label' => $langs->trans($label),
                    'html'  => $html,
                ];
            }
        }
    }

    ksort($rows);

    return array_values($rows);
}

/**
 * Build the HTML of a single mobile list card for a DigiQuali object.
 *
 * @param  object $object     Loaded object to render.
 * @param  string $cardPage   Relative path to the object card page (for the link).
 * @param  bool   $filterable When true, adds data attributes used by the client-side search filter.
 * @return string             Card HTML (an <a> block).
 */
function digiquali_pwa_render_card(object $object, string $cardPage, bool $filterable = false): string
{
    global $langs;

    $cardUrl    = dol_buildpath($cardPage, 1) . '?id=' . $object->id;
    $title      = !empty($object->label) ? $object->label : $object->ref;
    $hasRef     = !empty($object->ref) && $object->ref !== $title;
    $statusHtml = $object->getLibStatut(5);
    $fields     = digiquali_pwa_get_card_fields($object);

    // Build the client-side search haystack from every displayed value (ref, title, status,
    // and all rendered fields incl. foreign keys & extrafields) so the search bar matches them all.
    $filterAttr = '';
    if ($filterable) {
        $searchParts = [$object->ref, $title, strip_tags($statusHtml)];
        if ($hasRef) {
            $searchParts[] = $object->ref;
        }
        foreach ($fields as $field) {
            $searchParts[] = $field['label'];
            $searchParts[] = strip_tags($field['html']);
        }
        $searchText = dol_strtolower(trim(preg_replace('/\s+/', ' ', implode(' ', $searchParts))));
        $filterAttr = ' data-pwa-item data-pwa-text="' . dol_escape_htmltag($searchText) . '"';
    }

    // The card is a <div> (not an <a>): foreign-key values rendered by showOutputField()
    // contain their own <a> links, and nesting anchors is invalid HTML. The whole card is
    // made clickable through JS (data-pwa-href) while inner links keep working.
    $out  = '<div class="pwa-card" data-pwa-href="' . dol_escape_htmltag($cardUrl) . '"' . $filterAttr . '>';
    $out .= '<div class="pwa-card-body">';
    $out .= '<div class="pwa-card-head">';
    $out .= '<span class="pwa-card-title">' . dol_escape_htmltag($title) . '</span>';
    $out .= '<span class="pwa-card-status">' . $statusHtml . '</span>';
    $out .= '</div>';

    $out .= '<div class="pwa-card-fields">';
    if ($hasRef) {
        $out .= '<div class="pwa-card-field"><span class="pwa-card-field-label">' . $langs->trans('Ref') . '</span><span class="pwa-card-field-value">' . dol_escape_htmltag($object->ref) . '</span></div>';
    }
    foreach ($fields as $field) {
        $out .= '<div class="pwa-card-field"><span class="pwa-card-field-label">' . dol_escape_htmltag($field['label']) . '</span><span class="pwa-card-field-value">' . $field['html'] . '</span></div>';
    }
    $out .= '</div>';

    $out .= '</div>';
    $out .= '<i class="fas fa-chevron-right pwa-card-arrow"></i>';
    $out .= '</div>';

    return $out;
}

/**
 * Fetch and render the inner HTML of a PWA object list (cards or empty state).
 *
 * Used for both the initial page render and the AJAX live search. The search is
 * server-side so it covers the whole dataset (not only loaded rows): it matches
 * the object's ref/label/description AND the linked model (Sheet) ref/label for
 * controls and surveys, via a JOIN.
 *
 * @param  string $className Object class name (must already be loaded).
 * @param  string $cardPage  Relative path to the object card page.
 * @param  string $search    Raw search string.
 * @param  string $emptyIcon FontAwesome icon for the empty state.
 * @return string            Cards HTML, or the empty-state HTML when nothing matches.
 */
function digiquali_pwa_render_list_items(string $className, string $cardPage, string $search, string $emptyIcon = 'fa-clipboard-list'): string
{
    global $db, $langs;

    if (!function_exists('saturne_fetch_all_object_type')) {
        require_once __DIR__ . '/../../saturne/lib/object.lib.php';
    }

    $filter = [];
    $join   = '';
    $search = trim($search);
    if ($search !== '') {
        $probe = new $className($db);
        $like  = "'%" . $db->escape($search) . "%'";
        $conds = [];

        // Linked tables → display columns used when searching a foreign-key value.
        $fkSearchColumns = [
            'projet'             => ['ref', 'title'],
            'projet_task'        => ['ref', 'label'],
            'societe'            => ['nom'],
            'socpeople'          => ['lastname', 'firstname'],
            'user'               => ['lastname', 'firstname', 'login'],
            'product'            => ['ref', 'label'],
            'digiquali_sheet'    => ['ref', 'label'],
            'digiquali_question' => ['ref', 'label'],
            'digiquali_survey'   => ['ref'],
            'digiquali_control'  => ['ref', 'label'],
        ];

        // Technical / non-searchable columns.
        $skip        = ['rowid', 'entity', 'import_key', 'tms', 'ref_ext', 'note_public', 'note_private', 'photo', 'answer_photo', 'track_id', 'element_linked', 'mandatory_questions', 'json', 'fk_user_creat', 'fk_user_modif', 'fk_master_task'];
        $stringTypes = ['varchar', 'text', 'html', 'mail', 'email', 'phone', 'tel', 'url', 'ip'];

        if (!empty($probe->fields) && is_array($probe->fields)) {
            foreach ($probe->fields as $key => $val) {
                if (in_array($key, $skip, true)) {
                    continue;
                }
                $type     = (string) ($val['type'] ?? '');
                $baseType = preg_replace('/\(.*$/', '', $type);

                if (strpos($type, 'integer:') === 0 || !empty($val['foreignkey'])) {
                    // Foreign key: join the linked table and search its display columns.
                    $fkTable = !empty($val['foreignkey']) ? preg_replace('/\..*$/', '', $val['foreignkey']) : '';
                    if (isset($fkSearchColumns[$fkTable])) {
                        $alias = 'dqj_' . $key;
                        $join .= ' LEFT JOIN ' . MAIN_DB_PREFIX . $fkTable . ' AS ' . $alias . ' ON ' . $alias . '.rowid = t.' . $key;
                        foreach ($fkSearchColumns[$fkTable] as $col) {
                            $conds[] = $alias . '.' . $col . ' LIKE ' . $like;
                        }
                    }
                } elseif (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
                    // Selector (status, verdict, type): match the search against the option labels.
                    $codes = [];
                    foreach ($val['arrayofkeyval'] as $code => $label) {
                        if (stripos($langs->trans($label), $search) !== false || stripos((string) $label, $search) !== false) {
                            $codes[] = is_numeric($code) ? $code : "'" . $db->escape($code) . "'";
                        }
                    }
                    if (!empty($codes)) {
                        $conds[] = 't.' . $key . ' IN (' . implode(',', $codes) . ')';
                    }
                } elseif (in_array($baseType, $stringTypes, true)) {
                    $conds[] = 't.' . $key . ' LIKE ' . $like;
                }
            }
        }

        // Extrafields (text types): the "eft" join is added by saturne_fetch_all_object_type (extraFieldManagement).
        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        $efSearch = new ExtraFields($db);
        $efSearch->fetch_name_optionals_label($probe->table_element);
        $efAttributes = $efSearch->attributes[$probe->table_element] ?? [];
        if (!empty($efAttributes['label']) && is_array($efAttributes['label'])) {
            foreach ($efAttributes['label'] as $key => $label) {
                if (in_array($efAttributes['type'][$key] ?? '', ['varchar', 'text', 'html', 'mail', 'phone', 'url'], true)) {
                    $conds[] = 'eft.' . $key . ' LIKE ' . $like;
                }
            }
        }

        if (!empty($conds)) {
            $filter['customsql'] = '(' . implode(' OR ', $conds) . ')';
        }
    }

    $objects = saturne_fetch_all_object_type($className, 'DESC', 't.date_creation', 50, 0, $filter, 'AND', true, true, false, $join);

    if (empty($objects) || !is_array($objects)) {
        return '<div class="pwa-empty"><i class="fas ' . dol_escape_htmltag($emptyIcon) . '"></i><p>' . $langs->trans('NoRecordFound') . '</p></div>';
    }

    $out = '';
    foreach ($objects as $object) {
        $out .= digiquali_pwa_render_card($object, $cardPage, false);
    }

    return $out;
}
