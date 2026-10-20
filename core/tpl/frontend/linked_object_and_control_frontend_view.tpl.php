<?php
/* Copyright (C) 2025-2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/frontend/linked_object_and_control_frontend_view.tpl.php
 * \ingroup digiquali
 * \brief   Status view of the public control page: the controlled object, its status and its next control.
 */

/**
 * The following vars must be defined:
 * Variable : $linkedObject, $linkableElements, $controlInfoArray
 */

$linkedObjectInfoArray = get_linked_object_infos($linkedObject, $linkableElements);
$controlStatus         = $controlInfoArray['status'];

$controlStatusDisplays = [
    'ok'      => ['icon' => 'fa-check', 'label' => 'ControlStatusOk'],
    'ko'      => ['icon' => 'fa-exclamation', 'label' => 'ControlStatusKo'],
    'overdue' => ['icon' => 'fa-hourglass-end', 'label' => 'ControlStatusOverdue'],
    'none'    => ['icon' => 'fa-minus', 'label' => 'NoControl']
];
$controlStatusDisplay = $controlStatusDisplays[$controlStatus['key']];

$lastControl     = $controlStatus['last_control'];
$nextControlDays = $controlStatus['next_control_days'];
if ($nextControlDays === null) {
    $nextControlDeadline = '';
} elseif ($nextControlDays < -1) {
    $nextControlDeadline = $langs->transnoentities('NextControlOverdueSince', abs($nextControlDays));
} elseif ($nextControlDays == -1) {
    $nextControlDeadline = $langs->transnoentities('NextControlOverdueOneDay');
} elseif ($nextControlDays == 0) {
    $nextControlDeadline = $langs->transnoentities('NextControlToday');
} elseif ($nextControlDays == 1) {
    $nextControlDeadline = $langs->transnoentities('NextControlTomorrow');
} else {
    $nextControlDeadline = $langs->transnoentities('NextControlInDays', $nextControlDays);
}

$hasObjectImage = !empty($linkedObjectInfoArray['images']) && strpos($linkedObjectInfoArray['images'], 'nophoto') === false; ?>

<div class="pwa-container public-control-screen">
    <div class="public-control-object">
        <div class="public-control-object__thumbnail<?php echo $hasObjectImage ? '' : ' public-control-object__thumbnail--placeholder'; ?>">
            <?php echo $hasObjectImage ? $linkedObjectInfoArray['images'] : img_picto('', $linkableElements[$linkedObject->element]['picto'] ?? 'generic'); ?>
        </div>
        <div class="public-control-object__info">
            <?php
            $parameters = ['linkedObjectInfoArray' => $linkedObjectInfoArray, 'linkableElements' => $linkableElements];
            $resHook    = $hookmanager->executeHooks('printPublicControlLinkedObjectIdentity', $parameters, $linkedObject);
            if ($resHook > 0) {
                print $hookmanager->resPrint;
            } else { ?>
                <div class="information-type"><?php echo $linkedObjectInfoArray['linkedObject']['title']; ?></div>
                <div class="information-label size-l"><?php echo $linkedObjectInfoArray['linkedObject']['name_field']; ?></div>
            <?php }
            if (!empty($linkedObjectInfoArray['parentLinkedObject']['title'])) : ?>
                <div class="information-type"><?php echo $linkedObjectInfoArray['parentLinkedObject']['title']; ?></div>
                <div class="information-label"><?php echo $linkedObjectInfoArray['parentLinkedObject']['name_field']; ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="public-control-status public-control-status--<?php echo $controlStatus['key']; ?>">
        <div class="public-control-status__icon"><i class="fas <?php echo $controlStatusDisplay['icon']; ?>"></i></div>
        <div class="public-control-status__body">
            <div class="public-control-status__label"><?php echo $langs->transnoentities($controlStatusDisplay['label']); ?></div>
            <div class="public-control-status__detail">
                <?php echo !empty($lastControl) ? $langs->transnoentities('LastControlOn', dol_print_date($lastControl['control_date'], 'day')) : $langs->transnoentities('NoControlYet'); ?>
            </div>
            <?php if ($controlStatus['key'] == 'overdue') : ?>
                <div class="public-control-status__detail"><?php echo $langs->transnoentities('ControlStatusOverdueDetail'); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($lastControl)) : ?>
        <div class="pwa-section">
            <h2 class="pwa-section-title"><i class="far fa-calendar-alt"></i> <?php echo $langs->transnoentities('NextControl'); ?></h2>
            <div class="public-control-panel">
                <?php if (!empty($controlStatus['next_control_date'])) : ?>
                    <div class="pwa-card-field">
                        <span class="pwa-card-field-label"><?php echo $langs->transnoentities('Date'); ?></span>
                        <span class="pwa-card-field-value public-control-panel__strong"><?php echo dol_print_date($controlStatus['next_control_date'], 'day'); ?></span>
                    </div>
                    <div class="pwa-card-field">
                        <span class="pwa-card-field-label"><?php echo $langs->transnoentities('NextControlDeadline'); ?></span>
                        <?php // The color comes from the module setup (DIGIQUALI_NEXT_CONTROL_DATE_COLOR_*): only a custom property can carry it ?>
                        <span class="pwa-card-field-value public-control-panel__deadline" style="--deadline-color: <?php echo dol_escape_htmltag($controlStatus['next_control_color']); ?>"><?php echo $nextControlDeadline; ?></span>
                    </div>
                <?php else : ?>
                    <div class="pwa-card-field">
                        <span class="pwa-card-field-value public-control-panel__muted"><?php echo $langs->transnoentities('NoPeriodicityControl'); ?></span>
                    </div>
                <?php endif;
                if (!empty($linkedObjectInfoArray['linkedObject']['qc_frequency'])) : ?>
                    <div class="pwa-card-field">
                        <span class="pwa-card-field-label"><?php echo $langs->transnoentities('QcFrequency'); ?></span>
                        <span class="pwa-card-field-value"><?php echo $linkedObjectInfoArray['linkedObject']['qc_frequency']; ?></span>
                    </div>
                <?php endif; ?>
                <div class="pwa-card-field">
                    <span class="pwa-card-field-label"><?php echo $langs->transnoentities('LastControl'); ?></span>
                    <span class="pwa-card-field-value">
                        <?php if (!empty($lastControl['url'])) : ?>
                            <a href="<?php echo $lastControl['url']; ?>" target="_blank"><?php echo dol_escape_htmltag($lastControl['ref']); ?></a>
                        <?php else :
                            echo dol_escape_htmltag($lastControl['ref']);
                        endif; ?>
                        <span class="public-control-panel__muted"><?php echo dol_escape_htmltag($lastControl['sheet']); ?></span>
                    </span>
                </div>
            </div>
        </div>
    <?php endif;

    if (!empty($controlStatus['create_url'])) :
        // Once answered, the control brings back to this page ?>
        <a class="wpeo-button button-primary public-control-create" href="<?php echo dol_escape_htmltag($controlStatus['create_url'] . '&backtopage=' . urlencode($_SERVER['REQUEST_URI'])); ?>">
            <i class="fas fa-plus"></i> <?php echo $langs->transnoentities('NewControl'); ?>
        </a>
    <?php endif; ?>
</div>
