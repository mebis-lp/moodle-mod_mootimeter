import { call as fetchMany } from 'core/ajax';
import WordCloud from 'mootimetertool_wordcloud/wordcloud2';

export const init = (id) => {

    if (!document.getElementById(id)) {
        return;
    }

    // Initially getAnswers.
    getAnswersAsync(id);

    setTimeout(() => {
        const intervalms = document.getElementById('mootimeterstate').dataset.refreshinterval;
        const interval = setInterval(() => {
            if (!document.getElementById(id)) {
                clearInterval(interval);
                return;
            }
            getAnswers(id);
        }, intervalms);
    }, 2000);

    const mtmstate = document.getElementById('mootimeterstate');
    mtmstate.setAttribute('data-wclastupdated', 0);
};

/**
 * This is because the execution should be finished befor proceeding.
 * @param {string} id
 */
async function getAnswersAsync(id) {
    await getAnswers(id);
}

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
    if (mtmstate.dataset.wclastupdated && mtmstate.dataset.wclastupdated == mtmstate.dataset.contentchangedat) {
        return;
    }

    // Get the answer list.
    const response = await execGetAnswers(pageid);

    // Set wclastupdated.
    mtmstate.setAttribute('data-wclastupdated', mtmstate.dataset.contentchangedat);

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
