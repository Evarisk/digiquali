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

'use strict';

/**
 * \file    js/modules/survey_session.js
 * \ingroup digiquali
 * \brief   Collect sessions of surveys: printing of their summary (view/survey/survey_session_summary.php).
 */

window.digiquali.surveySession = {};

/**
 * Survey session init
 *
 * @return {void}
 */
window.digiquali.surveySession.init = function init() {
  window.digiquali.surveySession.event();
};

/**
 * Survey session events
 *
 * @return {void}
 */
window.digiquali.surveySession.event = function event() {
  $(document).on('click', '.survey-session-summary__print', window.digiquali.surveySession.print);
};

/**
 * Print the summary: the print stylesheet keeps the summary only, without the menus of the back office
 *
 * @return {void}
 */
window.digiquali.surveySession.print = function print() {
  window.print();
};
