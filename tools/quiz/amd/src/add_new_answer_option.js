import {call as fetchMany} from 'core/ajax';
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';

export const init = () => {

    // Get all up elements.
    var ao = document.getElementById('wrapper_add_answer_option');

    if (!ao) {
        return;
    }

    ao.addEventListener("click", store);

    /**
     * Create new page.
     */
    function store() {
        const pageid = document.getElementById('add_answer_option').dataset.pageid;
        storeNewAnswerOption(pageid);
    }
};

/**
 * Call to create a new instance
 * @param {int} pageid
 * @returns
 */
const execStoreNewAnswerOption = (
    pageid,
) => fetchMany([{
    methodname: 'mootimetertool_quiz_new_answeroption',
    args: {
        pageid,
    },
}])[0];

/**
 * Executes the call to create a new page.
 * @param {int} pageid
 */
const storeNewAnswerOption = async(pageid) => {

    // ===== JUST FOR TEMPORARILY USE: - START
    const response = await execStoreNewAnswerOption(pageid);
    document.location.reload(true);
    return;
    // ===== JUST FOR TEMPORARILY USE: - ENDE

    // TODO Implement this.
    // eslint-disable-next-line no-unreachable
    const context = {
        'mtm-input-id': 'ao_text_' + response.aoid,
        'mtm-input-name': 'ao_text',
        'ajaxmethode': "mootimetertool_quiz_store_answeroption_text",
        'additional_class': 'mootimeter-answer-options mootimeter_settings_selector',
        'dataset': 'data-pageid=' + pageid + ' data-aoid=' + response.aoid,

        'mtm-cb-without-label-id': 'ao_iscorrect_' + response.aoid,
        'mtm-cb-without-label-name': 'ao_iscorrect',
        'mtm-cb-without-label-ajaxmethode': "mootimetertool_quiz_store_answeroption_is_correct",

        'button_icon_only_transparent_additionalclass': 'mootimeter-answer-options',
        'button_icon_only_transparent_dataset': 'data-pageid="' + pageid + '" data-aoid="' + response.aoid + '"',
        'button_icon_only_transparent_icon': 'fa-close',
    };

    // Add the answer to the Badges list.
    Templates.renderForPromise('mod_mootimeter/elements/snippet_input_with_checkbox-icon', context)
        .then(({html, js}) => {
            Templates.appendNodeContents('#mtmt-quiz-ao-wrapper', html, js);
            Templates.runTemplateJS(js);
            return true;
        })
        .catch((error) => displayException(error));
};
