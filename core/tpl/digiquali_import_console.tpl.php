<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/digiquali_import_console.tpl.php
 * \ingroup digiquali
 * \brief   Diagnostic console popup listing every step of a data-migration import.
 *          Always rendered by view/digiqualitools.php: it stays empty (placeholder) until an
 *          import populates $importLog, then lists each step and auto-opens.
 *          Styles live in css/scss/page/_import-console.scss, behaviour in js/modules/tools.js.
 */

// Protection
if (!defined('DOL_DOCUMENT_ROOT')) {
	print 'Error, template page can not be called as URL';
	exit(1);
}

/** @var array $importLog Filled in view/digiqualitools.php: array of ['time' => , 'level' => , 'msg' => ] */
if (!isset($importLog) || !is_array($importLog)) {
	$importLog = array();
}

$hasEntries      = !empty($importLog);
$consoleOkCount  = count(array_filter($importLog, static function ($l) { return ($l['level'] ?? '') === 'success'; }));
$consoleErrCount = count(array_filter($importLog, static function ($l) { return ($l['level'] ?? '') === 'error'; }));
?>
<div class="digiquali-console" id="digiquali-console" data-auto-open="<?php print $hasEntries ? '1' : '0'; ?>">
	<div class="digiquali-console-header">
		<span class="digiquali-console-heading">
			<span class="digiquali-console-title">&gt;_ CONSOLE</span>
			<span class="digiquali-console-sub">
				<?php if ($hasEntries) : ?>
				<span class="digiquali-console-ok"><?php print $consoleOkCount; ?> OK</span>
					<?php if ($consoleErrCount > 0) : ?>
				<span class="digiquali-console-sep">|</span>
				<span class="digiquali-console-err"><?php print $consoleErrCount; ?> erreur(s)</span>
					<?php endif; ?>
				<?php else : ?>
				En attente d'un import…
				<?php endif; ?>
			</span>
		</span>
		<span class="digiquali-console-actions">
			<button type="button" class="digiquali-console-copy">Copier</button>
			<span class="digiquali-console-sep">|</span>
			<button type="button" class="digiquali-console-clear">Vider</button>
			<span class="digiquali-console-sep">|</span>
			<button type="button" class="digiquali-console-toggle" title="Ouvrir / Fermer">&#9650;</button>
		</span>
	</div>
	<div class="digiquali-console-body">
		<?php if ($hasEntries) : ?>
			<?php foreach ($importLog as $entry) :
				$lineClass = 'digiquali-log-' . substr($entry['level'] ?? 'info', 0, 1); ?>
		<div class="digiquali-console-line">
			<span class="digiquali-console-time"><?php print dol_escape_htmltag($entry['time'] ?? ''); ?></span>
			<span class="digiquali-console-pfx">&gt;_</span>
			<span class="<?php print $lineClass; ?>"><?php print dol_escape_htmltag($entry['msg'] ?? ''); ?></span>
		</div>
			<?php endforeach; ?>
		<?php else : ?>
		<div class="digiquali-console-line">
			<span class="digiquali-console-time"></span>
			<span class="digiquali-console-pfx">&gt;_</span>
			<span class="digiquali-log-i">En attente d'un import. Sélectionnez un fichier ZIP puis cliquez sur « Upload ».</span>
		</div>
		<?php endif; ?>
	</div>
</div>
