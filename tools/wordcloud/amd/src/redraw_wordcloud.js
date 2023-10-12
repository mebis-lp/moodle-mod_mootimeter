import Ajax from 'core/ajax';
import notification from 'core/notification';

export const init = () => {
    setInterval(function () {
        var pageid = document.getElementById('wordcloudcanvas').dataset.pageid;
        var lastposttimestamp = document.getElementById('mootimeterstate').dataset.lastupdated;
        const promise = Ajax.call([{
            methodname: 'mootimetertool_wordcloud_get_answers',
            args: {
                pageid: pageid,
                lastupdated: 0
            },
            fail: notification.exception,
        }]);

        promise[0].then(function (results) {
            if (results.lastupdated == lastposttimestamp) {
                return;
            }

            // Set lastupdated.
            let nodelastupdated = document.getElementById('mootimeterstate');
            nodelastupdated.setAttribute('data-lastupdated', results.lastupdated);

            // Redraw wordcloud.
            document.getElementById('wordcloudcanvas').setAttribute('data-answers', JSON.stringify(results.answerlist));
            document.getElementById('wordcloudcanvas').dispatchEvent(new Event("redrawwordcloud"));

            return;
        }).fail();

    }, 1000);

    redrawwordcloud()

    const event = new Event("redrawwordcloud");
    let mtmtcanvas = document.getElementById('wordcloudcanvas');
    mtmtcanvas.addEventListener(
        "redrawwordcloud",
        (e) => {
            redrawwordcloud();
        },
        false
    );

    /**
     *
     */
    function redrawwordcloud() {
        let mtmtcanvas = document.getElementById('wordcloudcanvas');
        let answers = JSON.parse(mtmtcanvas.dataset.answers);
        WordCloud(mtmtcanvas, { list: answers, weightFactor: 24, color: '#f98012', fontFamily: 'OpenSans' });
    }
};
