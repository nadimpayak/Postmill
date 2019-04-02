'use strict';

import $ from 'jquery';
import Translator from 'bazinga-translator';

$(function () {
    $('.confirm-comment-delete').click(function () {
        return confirm(Translator.trans('prompt.confirm_comment_delete'));
    });

    $('.js-confirm-message-delete').click(() => {
        return confirm(Translator.trans('prompt.confirm_message_delete'));
    });

    $('.confirm-submission-delete').click(function () {
        return confirm(Translator.trans('prompt.confirm_submission_delete'));
    });

    $('.confirm-wiki-delete').click(function () {
        return confirm(Translator.trans('prompt.confirm_wiki_delete'));
    });
});
