import { call as fetchMany } from 'core/ajax';
import WordCloud from 'mootimetertool_wordcloud/wordcloud2';

export const init = (id) => {

    if (!document.getElementById(id)) {
        return;
    }

    // Initially getAnswers.
    getAnswers(id);
    setTimeout(() => {
        const intervalms = document.getElementById('mootimeterstate').dataset.refreshinterval;
        const interval = setInterval(() => {
            if (!document.getElementById(id)) {
                clearInterval(interval);
                return;
            }
            getAnswers(id);
        }, intervalms);
    }, 5000);

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
 *
 * @param {string} id
 * @returns {mixed}
 */
const getAnswers = async (id) => {

    if (!document.getElementById(id)) {
        return;
    }

    var pageid = document.getElementById(id).dataset.pageid;

    const mtmstate = document.getElementById('mootimeterstate');

    // Early exit if there are no changes.
    if (mtmstate.dataset.lastupdated == mtmstate.dataset.contentchangedat) {
        return;
    }

    var lastposttimestamp = 0;
    if (document.getElementById('mootimeterstate').dataset.lastupdated) {
        lastposttimestamp = document.getElementById('mootimeterstate').dataset.lastupdated;
    }
    // Get the answer list.
    const response = await execGetAnswers(pageid, lastposttimestamp);

    // Set lastupdated.
    let nodelastupdated = document.getElementById('mootimeterstate');
    nodelastupdated.setAttribute('data-lastupdated', response.lastupdated);

    // Redraw wordcloud.
    document.getElementById(id).setAttribute('data-answers', JSON.stringify(response.answerlist));
    redrawwordcloud(id);

    return;
};

/**
 * Redraw the wordcloud.
 * @param {string} id
 */
function redrawwordcloud(id) {
    let mtmtcanvas = document.getElementById(id);
    let answers = JSON.parse(mtmtcanvas.dataset.answers);

    WordCloud(mtmtcanvas, { list: answers, weightFactor: 24, color: '#f98012', fontFamily: 'OpenSans' });
}
