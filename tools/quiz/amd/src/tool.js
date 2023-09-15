// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

import Ajax from 'core/ajax';
import SuperTool from "mod_mootimeter/tool";
import notification from "core/notification";
import Templates from 'core/templates';
import {ToolManager} from "mod_mootimeter/toolmanager";
import ChartJS from 'core/chartjs';

/**
 * Super class for tools.
 *
 * @module     mootimetertool_quiz/tool
 * @copyright  2023 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default class Tool extends SuperTool {

    /**
     * Renders a page.
     *
     * @return {Promise<HTMLElement>}
     */
    async render() {
        Templates.prefetchTemplates(['mootimetertool_quiz/view_content']);
        const page = this.page;

        const answeroptions = await Ajax.call([{
            methodname: 'mootimetertool_quiz_get_answeroptions',
            args: {
                pageid: page.id,
            },
            fail: notification.exception,
        }])[0];

        const context = {
            'question_text': page.question,
            'pageid': page.id,
            'ispoll': page.config.ispoll === '1',
            'isquiz': page.config.ispoll !== '1',
            'answer_options': answeroptions.map((option) => {
                return {
                    'aoid': option.id,
                    'ao_text': option.optiontext,
                    'ao_iscorrect': option.optioniscorrect,
                };
            }),
            'isediting': this.isEditing
        };

        const element = await this.renderTemplate('mootimetertool_quiz/view_content', context);

        if (!this.isEditing) {
            // Store data on click thing.
            element.querySelectorAll(".mtmt_answeroption").forEach(answerOptionEl => {
                answerOptionEl.onclick = async() => {
                    await Ajax.call([{
                        methodname: 'mootimetertool_quiz_store_answer',
                        args: {
                            pageid: page.id,
                            aoid: answerOptionEl.dataset.aoid,
                        },
                        fail: notification.exception,
                    }])[0];

                    ToolManager.route(page.id, true);
                };
            });
        }
        if (this.isEditing) {
            this.saveAnswerOptionTextsWhenChanged(element.firstElementChild);
            this.addNewAnswerOptionOnButtonClick(element.firstElementChild);
        }

        return element;
    }

    saveAnswerOptionTextsWhenChanged(element) {
        element.addEventListener('blur', (e) => {
            if (e.target.matches('textarea.mtmt-delayed-store')) {
                const aoid = e.target.closest('[data-aoid]').dataset.aoid;
                Ajax.call([{
                    methodname: 'mootimetertool_quiz_store_answeroption',
                    args: {
                        aoid: aoid,
                        value: e.target.value,
                    },
                    fail: notification.exception,
                }]);
            }
        }, true);
    }

    addNewAnswerOptionOnButtonClick(element) {
        Templates.prefetchTemplates(['mootimetertool_quiz/answer_option']);
        const newAOButton = element.querySelector('#new_ao_label');
        const questionElements = element.querySelector('#mtmt_questions');

        newAOButton.onclick = async() => {
            const result = await Ajax.call([{
                methodname: 'mootimetertool_quiz_new_answeroption',
                args: {
                    pageid: this.page.id,
                },
                fail: notification.exception,
            }])[0];

            const element = await this.renderTemplate('mootimetertool_quiz/answer_option', {
                aoid: result.aoid,
                isediting: true,
                ispoll: this.isPoll(),
                isquiz: !this.isPoll()
            });

            questionElements.append(...element.children);
        };
    }

    isPoll() {
        return this.page.config.ispoll === '1';
    }

    async renderResult() {
        Templates.prefetchTemplates(['mootimetertool_quiz/view_results']);
        const page = this.page;

        const ajax = Ajax.call([{
            methodname: 'mootimetertool_quiz_get_answeroptions',
            args: {
                pageid: page.id,
            },
            fail: notification.exception,
        }, {
            methodname: 'mootimetertool_quiz_get_answers',
            args: {
                pageid: page.id,
            },
            fail: notification.exception,
        }]);

        const answeroptions = await ajax[0];
        const answers = await ajax[1];

        const data = {
            labels: [],
            datasets: [{
                label: page.question,
                data: []
            }]
        };

        const answermapping = {};

        for (let i = 0; i < answeroptions.length; i++) {
            data.labels.push(answeroptions[i].optiontext);
            answermapping[answeroptions[i].id] = i;
        }

        data.datasets[0].data = new Array(data.labels.length);

        for (const answer of answers.answerlist) {
            data.datasets[0].data[answermapping[answer.optionid]] = answer.count;
        }

        const element = await this.renderTemplate('mootimetertool_quiz/view_results', {});

        const canvas = element.querySelector('#quizcanvas');

        const chart = new ChartJS(canvas, {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
            }
        });

        chart.update();

        return element;
    }
}
