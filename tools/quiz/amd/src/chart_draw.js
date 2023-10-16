import ChartJS from 'mootimetertool_quiz/chart.umd';
import { call as fetchMany } from 'core/ajax';

export const init = () => {

    // var labels = document.getElementById('mtmt_quiz_canvas').dataset.labels;
    // var values = document.getElementById('mtmt_quiz_canvas').dataset.values;
    // var charttype = document.getElementById('mtmt_quiz_canvas').dataset.charttype;

    var pageid = document.getElementById('mtmt_quiz_canvas').dataset.pageid;



    setInterval(function () {
        var lastposttimestamp = parseInt(document.getElementById('mootimeterstate').dataset.lastupdated);
        getAnswers(pageid, lastposttimestamp);
    }, 1000);
};


/**
 * Execute the ajax call to get the aswers and more important data.
 * @param {int} pageid
 * @returns
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
 * @returns
 */
const getAnswers = async (pageid, lastposttimestamp) => {
    const response = await execGetAnswers(pageid);

    // We do not want to do anything if nothing has changed.
    if (
        lastposttimestamp == response.lastupdated
        &&
        response.chartsettings == document.getElementById('mtmt_quiz_canvas').dataset.chartsettings
        &&
        response.values == document.getElementById('mtmt_quiz_canvas').dataset.values
        &&
        response.labels == document.getElementById('mtmt_quiz_canvas').dataset.labels
    ) {
        return;
    }

    window.console.log(document.getElementById('mtmt_quiz_canvas').dataset.values);
    window.console.log(response.values);

    // Write the new data to the canvas data attributes.
    let nodelastupdated = document.getElementById('mootimeterstate');
    nodelastupdated.setAttribute('data-lastupdated', response.lastupdated);

    let nodecanvas = document.getElementById('mtmt_quiz_canvas');
    nodecanvas.setAttribute('data-labels', response.labels);
    nodecanvas.setAttribute('data-values', response.values);
    nodecanvas.setAttribute('data-chartsettings', response.chartsettings);

    window.console.log(response);

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
            }]
        },
        options: JSON.parse(response.chartsettings).options
    };

    let chartStatus = ChartJS.getChart("mtmt_quiz_canvas"); // <canvas> id
    if (chartStatus != undefined) {
        chartStatus.destroy();
    }

    new ChartJS(document.getElementById('mtmt_quiz_canvas'), config);
    ChartJS.defaults.font.size = 25;
    ChartJS.defaults.stepSize = 1;
};