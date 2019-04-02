'use strict';

import $ from 'jquery';
import Translator from 'bazinga-translator';

// load comment forms via ajax

$(function () {
    // hide open forms (they're initially visible for non-js users)
    $('.comment .comment-form').hide();

    $('.comment__reply-link').click(function (event) {
        event.preventDefault();

        const $parent = $(this).closest('.comment__main');
        const $existingForm = $parent.find('> .comment-form');

        // remove existing error messages
        $parent.find('> .comment-error').remove();

        if ($existingForm.length > 0) {
            // the form already exists, so just hide/unhide it as necessary
            $existingForm.toggle();
        } else {
            const url = $(this).data('form-url');

            // opacity indicates loading
            $(this).css('opacity', '0.5');

            $.ajax({url: url, dataType: 'html'}).done(data => {
                $parent.append(data);
            }).fail(() => {
                const error = Translator.trans('comments.form_load_error');
                $parent.append(`<p class="comment-error">${error}</p>`);
            }).always(() => {
                $(this).css('opacity', 'unset');
            });
        }
    });
});
