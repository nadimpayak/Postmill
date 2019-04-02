'use strict';

import $ from 'jquery';
import translator from 'bazinga-translator';

$(function () {
    $('.js-display-new-comments[data-submission-id][data-comment-count]')
        .each(function (i, el) {
            const $el = $(el);
            const submissionId = $el.data('submission-id');
            const lastCount = localStorage.getItem(`comments-${submissionId}`);

            if (lastCount === null) {
                return;
            }

            const currentCount = $el.data('comment-count');
            const newComments = Math.max(currentCount - lastCount, 0);
            const lang = $('html').attr('lang');

            if (newComments === 0) {
                return;
            }

            const number = newComments.toLocaleString(lang);

            $el.before(' ');
            $el.text(translator.trans('submissions.new_comments', {
                count: number
            }));
        });

    $('.js-update-comment-count[data-submission-id][data-comment-count]')
        .each(function (i, el) {
            const $el = $(el);
            const submissionId = $el.data('submission-id');
            const commentCount = $el.data('comment-count');

            localStorage.setItem(`comments-${submissionId}`, commentCount);
        });
});
