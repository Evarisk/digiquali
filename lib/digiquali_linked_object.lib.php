<?php

/* Copyright (C) 2022-2026 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiquali_linked_object.lib.php
 * \ingroup digiquali
 * \brief   Library files with functions for DigiQuali linked objects
 */

require_once __DIR__ . '/../../saturne/lib/object.lib.php';
require_once __DIR__ . '/../../saturne/lib/linked_object.lib.php';

// Configuration constant prefix driving every link of the module.
define('DIGIQUALI_LINKED_OBJECT_CONST_PREFIX', 'DIGIQUALI_SHEET_LINK_');

// DigiQuali objects are never offered as a link target.
define('DIGIQUALI_LINKED_OBJECT_EXCLUDED_PREFIX', 'digiquali_');

// Element types carrying the links on the DigiQuali side.
define('DIGIQUALI_LINKED_OBJECT_ELEMENT_TYPES', 'digiquali_control,digiquali_survey');

/**
 * Get the extrafield definitions carried by DigiQuali linked objects
 *
 * @return array Definitions accepted by saturne_sync_linked_object_extrafields()
 */
function digiquali_get_linked_object_extrafield_definitions(): array
{
    return [
        [
            'name'           => 'qc_frequency',
            'label'          => 'QcFrequency',
            'type'           => 'int',
            'pos'            => 100,
            'size'           => 10,
            'default_value'  => '',
            'param'          => 'a:1:{s:7:"options";a:1:{s:0:"";N;}}',
            'alwayseditable' => 1,
            'list'           => 1,
            'langfile'       => 'digiquali@digiquali',
            'enabled'        => '$conf->digiquali->enabled',
            'object_types'   => []
        ],
        [
            'name'           => 'control_history_link',
            'label'          => 'ControlHistoryLink',
            'type'           => 'varchar',
            'pos'            => 110,
            'size'           => 255,
            'default_value'  => '',
            'param'          => '',
            'alwayseditable' => 0,
            'list'           => 5,
            'langfile'       => 'digiquali@digiquali',
            'enabled'        => '$conf->digiquali->enabled',
            'object_types'   => ['productlot']
        ]
    ];
}

/**
 * Get every object whose extrafields the module manages
 *
 * Wider than the linkable set on purpose : DigiQuali objects are never linkable, but the extrafields
 * an older version left on their own tables must still be cleaned up.
 *
 * @return array Deduplicated subset of saturne_get_objects_metadata()
 */
function digiquali_get_managed_objects(): array
{
    global $conf;

    if (empty($conf->cache['digiqualiObjectsMetadata'])) {
        $conf->cache['digiqualiObjectsMetadata'] = saturne_get_objects_metadata();
    }

    return saturne_filter_linkable_objects($conf->cache['digiqualiObjectsMetadata']);
}

/**
 * Get the objects a sheet may be linked to
 *
 * @return array Subset of digiquali_get_managed_objects()
 */
function digiquali_get_linkable_objects(): array
{
    global $conf;

    if (empty($conf->cache['digiqualiObjectsMetadata'])) {
        $conf->cache['digiqualiObjectsMetadata'] = saturne_get_objects_metadata();
    }

    $excludedPrefixes = [DIGIQUALI_LINKED_OBJECT_EXCLUDED_PREFIX];

    return saturne_filter_linkable_objects($conf->cache['digiqualiObjectsMetadata'], $excludedPrefixes);
}

/**
 * Get the object types whose link is enabled
 *
 * @return array List of enabled object types
 */
function digiquali_get_enabled_linked_object_types(): array
{
    $linkableObjects = digiquali_get_linkable_objects();

    return saturne_get_enabled_linked_object_types($linkableObjects, DIGIQUALI_LINKED_OBJECT_CONST_PREFIX);
}

/**
 * Measure how much each linkable object is used
 *
 * @return array objectType => ['links' => int, 'extrafields' => [name => int]]
 */
function digiquali_get_linked_object_usage(): array
{
    return saturne_get_linked_object_usage(
        digiquali_get_linkable_objects(),
        ['qc_frequency'],
        explode(',', DIGIQUALI_LINKED_OBJECT_ELEMENT_TYPES)
    );
}

/**
 * Align extrafields, tabs and hooks on the enabled links
 *
 * Idempotent : replaying it converges to the same state whatever the starting point.
 * Must be called from a web request, see saturne_refresh_module_registrations().
 *
 * @return array ['tabs' => int, 'hooks' => int, 'added' => string[], 'deleted' => string[], 'errors' => int]
 */
function digiquali_sync_linked_objects(): array
{
    $enabledObjectTypes = digiquali_get_enabled_linked_object_types();

    $extraFieldReport = saturne_sync_linked_object_extrafields(
        digiquali_get_linked_object_extrafield_definitions(),
        digiquali_get_managed_objects(),
        $enabledObjectTypes
    );

    $registrationReport = saturne_refresh_module_registrations('digiquali', 'modDigiQuali');

    return [
        'tabs'    => $registrationReport['tabs'],
        'hooks'   => $registrationReport['hooks'],
        'added'   => $extraFieldReport['added'],
        'deleted' => $extraFieldReport['deleted'],
        'errors'  => $extraFieldReport['errors'] + $registrationReport['errors']
    ];
}

/**
 * Enable every link that is already in use, so that a cleanup can never hide existing data
 *
 * The target set is the union of the links already enabled, the objects carrying at least one
 * element_element row towards a control or a survey, and the objects carrying at least one
 * qc_frequency value. Only missing constants are written : a link explicitly disabled and unused
 * stays disabled.
 *
 * @return array List of object types enabled by this call
 */
function digiquali_run_linked_object_backward(): array
{
    global $conf, $db;

    $usage              = digiquali_get_linked_object_usage();
    $enabledObjectTypes = [];

    foreach (array_keys(digiquali_get_linkable_objects()) as $objectType) {
        $constName = DIGIQUALI_LINKED_OBJECT_CONST_PREFIX . strtoupper($objectType);

        if (getDolGlobalInt($constName) > 0) {
            continue;
        }

        $isUsed = $usage[$objectType]['links'] > 0 || $usage[$objectType]['extrafields']['qc_frequency'] > 0;
        if (!$isUsed) {
            continue;
        }

        dolibarr_set_const($db, $constName, 1, 'integer', 0, '', $conf->entity);
        $enabledObjectTypes[] = $objectType;
    }

    return $enabledObjectTypes;
}
