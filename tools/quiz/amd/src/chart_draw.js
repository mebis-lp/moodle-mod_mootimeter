import ChartJS from 'mootimetertool_quiz/chart.umd';
import {call as fetchMany} from 'core/ajax';

export const init = (id) => {

    if (!document.getElementById(id)) {
        return;
    }

    const pageid = document.getElementById(id).dataset.pageid;
    getAnswers(pageid, id);

    setTimeout(() => {
        const intervalms = document.getElementById('mootimeterstate').dataset.refreshinterval;
        const interval = setInterval(() => {
            if (!document.getElementById(id)) {
                clearInterval(interval);
                return;
            }
            getAnswers(pageid, id);
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
 * @param {string} id
 * @returns {mixed}
 */
const getAnswers = async (pageid, id) => {

    const mtmstate = document.getElementById('mootimeterstate');

    // Early exit if there are no changes.
    if (mtmstate.dataset.lastupdated == mtmstate.dataset.contentchangedat) {
        return;
    }

    const response = await execGetAnswers(pageid);

    if (!document.getElementById(id)) {
        return;
    }

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
