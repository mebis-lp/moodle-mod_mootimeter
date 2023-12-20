import {call as fetchMany} from 'core/ajax';
import WordCloud from 'mootimetertool_wordcloud/wordcloud2';

export const init = (id) => {

    if (!document.getElementById(id)) {
        return;
    }

    var interval = setInterval(() => {
        getAnswers(id);
        if (!document.getElementById(id)) {
            clearInterval(interval);
        }
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
 *
 * @param {string} id
 * @returns {mixed}
 */
const getAnswers = async(id) => {

    if (!document.getElementById(id)) {
        return;
    }

    var pageid = document.getElementById(id).dataset.pageid;

    var lastposttimestamp = 0;
    if (document.getElementById('mootimeterstate').dataset.lastupdated) {
       lastposttimestamp = document.getElementById('mootimeterstate').dataset.lastupdated;
    }

    const response = await execGetAnswers(pageid, lastposttimestamp);

    if (
        response.lastupdated == lastposttimestamp
        &&
        JSON.stringify(response.answerlist) == document.getElementById(id).dataset.answers
    ) {
        return;
    }

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

    WordCloud(mtmtcanvas, {list: answers, weightFactor: 24, color: '#f98012', fontFamily: 'OpenSans'});
}
