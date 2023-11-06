import { call as fetchMany } from 'core/ajax';
import WordCloud from 'mootimetertool_wordcloud/wordcloud2';

export const init = () => {

    setInterval(function () {
        getAnswers();
    }, 1000);
};

/**
 * Call to get all answers
 * @param {int} pageid
 * @param {int} lastupdated
 * @returns {array}
 */
const execGetAnswers = (
    pageid,
    lastupdated
) => fetchMany([{
    methodname: 'mootimetertool_wordcloud_get_answers',
    args: {
        pageid,
        lastupdated
    },
}])[0];

/**
 * Executes the call to get all answers.
 */
const getAnswers = async () => {

    var pageid = document.getElementById('wordcloudcanvas').dataset.pageid;
    var lastposttimestamp = document.getElementById('mootimeterstate').dataset.lastupdated;

    const response = await execGetAnswers(pageid, lastposttimestamp);

    if (response.lastupdated == lastposttimestamp) {
        return;
    }

    // Set lastupdated.
    let nodelastupdated = document.getElementById('mootimeterstate');
    nodelastupdated.setAttribute('data-lastupdated', response.lastupdated);

    // Redraw wordcloud.
    document.getElementById('wordcloudcanvas').setAttribute('data-answers', JSON.stringify(response.answerlist));
    redrawwordcloud();

    return;
};

/**
 * Redraw the wordcloud.
 */
function redrawwordcloud() {
    let mtmtcanvas = document.getElementById('wordcloudcanvas');
    let answers = JSON.parse(mtmtcanvas.dataset.answers);

    WordCloud(mtmtcanvas, { list: answers, weightFactor: 24, color: '#f98012', fontFamily: 'OpenSans' });
}
