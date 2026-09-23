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
 * \file    js/modules/control_tag_stats.js
 * \ingroup digiquali
 * \brief   Dynamic tag statistics charts on control card
 */

'use strict';

window.digiquali.controlTagStats = {};

/**
 * Private chart instances (bar + donuts), keyed by tag id
 *
 * @type {{bar: Chart|null, donuts: Object}}
 */
window.digiquali.controlTagStats._charts = { bar: null, donuts: {} };

/**
 * Whether charts have been rendered at least once
 *
 * @type {boolean}
 */
window.digiquali.controlTagStats._initialized = false;

/**
 * Init
 *
 * @return {void}
 */
window.digiquali.controlTagStats.init = function () {
    window.digiquali.controlTagStats.event();

    if (!$('#controlTagStatsContainer').length) {
        return;
    }

    // Charts are lazy-rendered on first tab switch, not on page load
};

/**
 * Events
 *
 * @return {void}
 */
window.digiquali.controlTagStats.event = function () {
    $(document).on('click', '.control-view-tab', window.digiquali.controlTagStats.switchTab);
    $(document).on('change', '#controlTagStatsFilter', window.digiquali.controlTagStats.onFilterChange);
};

/**
 * Switch between Questions and Statistics sections
 *
 * @return {void}
 */
window.digiquali.controlTagStats.switchTab = function () {
    var $tab    = $(this);
    var section = $tab.data('section');

    $('.control-view-tab').removeClass('active');
    $tab.addClass('active');

    if (section === 'controlStatsSection') {
        $('#controlQuestionsSection').hide();
        $('#controlStatsSection').show();

        if (!window.digiquali.controlTagStats._initialized) {
            window.digiquali.controlTagStats._initialized = true;
            window.digiquali.controlTagStats.render(0);
        }
    } else {
        $('#controlStatsSection').hide();
        $('#controlQuestionsSection').show();
    }
};

/**
 * On filter select change — re-render without page reload
 *
 * @return {void}
 */
window.digiquali.controlTagStats.onFilterChange = function () {
    window.digiquali.controlTagStats.render(parseInt($(this).val(), 10) || 0);
};

/**
 * Read stats data embedded in the page
 *
 * @return {Object|null}
 */
window.digiquali.controlTagStats.getData = function () {
    var el = document.getElementById('controlTagStatsData');
    if (!el) {
        return null;
    }

    try {
        return JSON.parse(el.textContent);
    } catch (e) {
        return null;
    }
};

/**
 * Render (or re-render) all charts and table for a given tag filter
 *
 * @param  {number} filterTagId  0 = all tags
 * @return {void}
 */
window.digiquali.controlTagStats.render = function (filterTagId) {
    var data = window.digiquali.controlTagStats.getData();
    if (!data) {
        return;
    }

    var allTags        = data.tags;
    var allValues      = data.answerValues;
    var answerColors   = data.answerColors;
    var i18n           = data.i18n;

    // Filter which tags to display
    var tagsToShow = {};
    if (filterTagId > 0 && allTags[filterTagId]) {
        tagsToShow[filterTagId] = allTags[filterTagId];
    } else {
        tagsToShow = allTags;
    }

    var isSingleTag = Object.keys(tagsToShow).length === 1;

    // Side-by-side layout when a single tag is selected
    $('#controlTagChartsRow').toggleClass('control-charts-side-by-side', isSingleTag);

    window.digiquali.controlTagStats.renderBarChart(tagsToShow, allValues, answerColors);
    window.digiquali.controlTagStats.renderTable(tagsToShow, allValues, answerColors, i18n);
    window.digiquali.controlTagStats.renderDonuts(tagsToShow, i18n);
};

/**
 * Render grouped bar chart
 *
 * @param  {Object} tagsToShow
 * @param  {Array}  allValues
 * @param  {Object} answerColors
 * @return {void}
 */
window.digiquali.controlTagStats.renderBarChart = function (tagsToShow, allValues, answerColors) {
    var el = document.getElementById('controlTagBarChart');
    if (!el) {
        return;
    }

    var tagLabels = [];
    var datasets  = {};

    allValues.forEach(function (val) {
        datasets[val] = { label: val, data: [], backgroundColor: answerColors[val] || '#999', borderRadius: 4 };
    });

    $.each(tagsToShow, function (catId, tag) {
        tagLabels.push(tag.label);
        allValues.forEach(function (val) {
            datasets[val].data.push(tag.stats[val] || 0);
        });
    });

    if (window.digiquali.controlTagStats._charts.bar) {
        window.digiquali.controlTagStats._charts.bar.destroy();
    }

    window.digiquali.controlTagStats._charts.bar = new Chart(el, {
        type: 'bar',
        data: {
            labels: tagLabels,
            datasets: Object.values(datasets)
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
};

/**
 * Render stats table
 *
 * @param  {Object} tagsToShow
 * @param  {Array}  allValues
 * @param  {Object} answerColors
 * @param  {Object} i18n
 * @return {void}
 */
window.digiquali.controlTagStats.renderTable = function (tagsToShow, allValues, answerColors, i18n) {
    var $table = $('#controlTagStatsTable');
    if (!$table.length) {
        return;
    }

    // Header
    var $thead = $table.find('thead');
    $thead.empty();
    var $headerRow = $('<tr class="liste_titre">').append($('<th>').text(i18n.tag));
    allValues.forEach(function (val) {
        $headerRow.append($('<th>').text(val));
    });
    $headerRow.append($('<th>').text(i18n.total));
    $headerRow.append($('<th>').text(i18n.conformityRate));
    $thead.append($headerRow);

    // Body
    var $tbody    = $table.find('tbody');
    $tbody.empty();

    var totalPerAnswer = {};
    allValues.forEach(function (val) { totalPerAnswer[val] = 0; });
    var grandTotal   = 0;
    var grandCorrect = 0;

    $.each(tagsToShow, function (catId, tag) {
        var tagTotal   = tag.total || 0;
        var tagCorrect = 0;

        allValues.forEach(function (val) {
            var cnt = tag.stats[val] || 0;
            totalPerAnswer[val] += cnt;
            if (tag.answerMeta[val] && tag.answerMeta[val].correct) {
                tagCorrect += cnt;
            }
        });

        grandTotal   += tagTotal;
        grandCorrect += tagCorrect;

        var cr    = tagTotal > 0 ? Math.round((tagCorrect / tagTotal) * 100) : 0;
        var crc   = cr >= 70 ? '#47e58e' : (cr >= 50 ? '#e9ad4f' : '#e05353');
        var $row  = $('<tr>').append($('<td>').html('<b>' + $('<span>').text(tag.label).html() + '</b>'));

        allValues.forEach(function (val) {
            $row.append($('<td>').text(tag.stats[val] || 0));
        });

        $row.append($('<td>').text(tagTotal));
        $row.append($('<td>').html('<b style="color:' + crc + '">' + cr + '%</b>'));
        $tbody.append($row);
    });

    // Total row
    var gcr  = grandTotal > 0 ? Math.round((grandCorrect / grandTotal) * 100) : 0;
    var gcrc = gcr >= 70 ? '#47e58e' : (gcr >= 50 ? '#e9ad4f' : '#e05353');
    var $totalRow = $('<tr class="liste_titre">').append($('<td>').html('<b>' + i18n.total + '</b>'));

    allValues.forEach(function (val) {
        $totalRow.append($('<td>').html('<b>' + (totalPerAnswer[val] || 0) + '</b>'));
    });

    $totalRow.append($('<td>').html('<b>' + grandTotal + '</b>'));
    $totalRow.append($('<td>').html('<b style="color:' + gcrc + '">' + gcr + '%</b>'));
    $tbody.append($totalRow);
};

/**
 * Render donut charts (one per tag)
 *
 * @param  {Object} tagsToShow
 * @param  {Object} i18n
 * @return {void}
 */
window.digiquali.controlTagStats.renderDonuts = function (tagsToShow, i18n) {
    // Destroy all existing donut charts
    $.each(window.digiquali.controlTagStats._charts.donuts, function (id, chart) {
        chart.destroy();
    });
    window.digiquali.controlTagStats._charts.donuts = {};

    var $container = $('#controlTagDonutsContainer');
    $container.empty();

    $.each(tagsToShow, function (catId, tag) {
        var total   = tag.total || 0;
        var correct = 0;
        var labels  = [];
        var data    = [];
        var colors  = [];

        $.each(tag.stats, function (val, cnt) {
            labels.push(val);
            data.push(cnt);
            colors.push(tag.answerMeta[val] ? tag.answerMeta[val].color : '#999');
            if (tag.answerMeta[val] && tag.answerMeta[val].correct) {
                correct += cnt;
            }
        });

        var cr  = total > 0 ? Math.round((correct / total) * 100) : 0;
        var crc = cr >= 70 ? '#47e58e' : (cr >= 50 ? '#e9ad4f' : '#e05353');

        var canvasId = 'donutChart_' + catId;

        // Build legend HTML
        var legendHtml = '';
        $.each(tag.stats, function (val, cnt) {
            var color = tag.answerMeta[val] ? tag.answerMeta[val].color : '#999';
            legendHtml += '<div><span class="donut-legend-swatch" style="color:' + color + ';">&#9632;</span> ' + $('<span>').text(val).html() + ' <b>' + cnt + '</b></div>';
        });

        var $card = $(
            '<div class="donut-card">' +
            '<div class="donut-card-title">' + $('<span>').text(tag.label).html() + '</div>' +
            '<div class="donut-card-body">' +
            '<canvas id="' + canvasId + '" width="140" height="140"></canvas>' +
            '<div class="donut-card-legend">' +
            legendHtml +
            '<div class="donut-card-conformity" style="color:' + crc + ';"> ' + i18n.conformity + ' : ' + cr + '%</div>' +
            '</div>' +
            '</div>' +
            '</div>'
        );

        $container.append($card);

        // Must draw after element is in DOM
        var el = document.getElementById(canvasId);
        if (el) {
            window.digiquali.controlTagStats._charts.donuts[catId] = new Chart(el, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{ data: data, backgroundColor: colors, borderWidth: 2 }]
                },
                options: {
                    responsive: false,
                    plugins: { legend: { display: false } },
                    cutout: '65%'
                }
            });
        }
    });
};
