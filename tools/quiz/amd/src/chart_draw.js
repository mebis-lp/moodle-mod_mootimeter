import ChartJS from 'mootimetertool_quiz/chart.umd';
import {call as fetchMany} from 'core/ajax';

export const init = (id) => {

    if (!document.getElementById(id)) {
        return;
    }

    const pageid = document.getElementById(id).dataset.pageid;
    let lastposttimestamp = parseInt(document.getElementById('mootimeterstate').dataset.lastupdated);
    getAnswers(pageid, lastposttimestamp, id);

    setTimeout(() => {
        const intervalms = document.getElementById('mootimeterstate').dataset.refreshinterval;
        const interval = setInterval(() => {
            lastposttimestamp = parseInt(document.getElementById('mootimeterstate').dataset.lastupdated);
            getAnswers(pageid, lastposttimestamp, id);
            if (!document.getElementById(id)) {
                clearInterval(interval);
            }
        }, intervalms);
    }, 5000);
};

/**
 * Execute the ajax call to get the aswers and more important data.
 * @param {int} pageid
 * @returns {mixed}
 */
const execGetAnswers = (
    pageid,
) => fetchMany([{
    methodname: 'mootimetertool_quiz_get_answers',
    args: {
        pageid,
    },
}])[0];

/**
 * Get the answers and other important data, as well as processing them.
 * @param {int} pageid
 * @param {int} lastposttimestamp
 * @param {string} id
 * @returns {mixed}
 */
const getAnswers = async (pageid, lastposttimestamp, id) => {

    const mtmstate = document.getElementById('mootimeterstate');

    // Early exit if there are no changes.
    if (mtmstate.dataset.lastupdated == mtmstate.dataset.lastnewanswer) {
        return;
    }

    const response = await execGetAnswers(pageid);

    if (!document.getElementById(id)) {
        return;
    }

    // We do not want to do anything if nothing has changed.
    // if (
    //     lastposttimestamp == response.lastupdated
    //     &&
    //     response.chartsettings == document.getElementById(id).dataset.chartsettings
    //     &&
    //     response.values == document.getElementById(id).dataset.values
    //     &&
    //     response.labels == document.getElementById(id).dataset.labels
    // ) {
    //     return;
    // }

    // Write the new data to the canvas data attributes.
    let nodelastupdated = document.getElementById('mootimeterstate');
    nodelastupdated.setAttribute('data-lastupdated', response.lastupdated);

    let nodecanvas = document.getElementById(id);
    nodecanvas.setAttribute('data-labels', response.labels);
    nodecanvas.setAttribute('data-values', response.values);
    nodecanvas.setAttribute('data-chartsettings', response.chartsettings);

    // (Re-)Draw the chart.
    var config = {
        type: JSON.parse(response.chartsettings).charttype,
        data: {
            labels: JSON.parse(response.labels),
            datasets: [{
                label: response.question,
                data: JSON.parse(response.values),
                backgroundColor: JSON.parse(response.chartsettings).backgroundColor,
                borderRadius: JSON.parse(response.chartsettings).borderRadius,
                pointStyle: JSON.parse(response.chartsettings).pointStyle,
                pointRadius: JSON.parse(response.chartsettings).pointRadius,
                pointHoverRadius: JSON.parse(response.chartsettings).pointHoverRadius,
            }]
        },
        options: JSON.parse(response.chartsettings).options
    };

    let chartStatus = ChartJS.getChart(id); // <canvas> id
    if (chartStatus != undefined) {
        chartStatus.destroy();
    }

    new ChartJS(document.getElementById(id), config);
    ChartJS.defaults.font.size = 25;
    ChartJS.defaults.stepSize = 1;
};
