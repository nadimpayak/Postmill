'use strict';

import $ from 'jquery';
import translator from 'bazinga-translator';

const LOADING_HTML = '<span class="icon icon--spin"><svg><use xlink:href="#spinner"/></svg></span>';

/**
 * Get the current vote selection (-1: downvoted, 0: not voted on, 1: upvoted).
 *
 * @param {jQuery} $form
 *
 * @return {number}
 */
function getCurrentChoice($form) {
    if ($form.hasClass('vote--user-upvoted')) {
        return 1;
    }

    if ($form.hasClass('vote--user-downvoted')) {
        return -1;
    }

    return 0;
}

/**
 * @param {jQuery}  $form
 * @param {boolean} isUp
 *
 * @return {number}
 */
function getNewChoice($form, isUp) {
    return getNewScore($form, isUp, getCurrentChoice($form));
}

/**
 * @param {jQuery} $form
 * @param {boolean} isUp
 * @param {number} score
 *
 * @return {number}
 */
function getNewScore($form, isUp, score) {
    const dir = getCurrentChoice($form);
    const weight = isUp ? 1 : -1;

    if (dir === weight) {
        return score - weight;
    }

    if (dir === -weight) {
        return score + 2 * (weight);
    }

    return score + weight;
}

function getUpButtonTitle(choice) {
    //noinspection JSUnresolvedFunction
    return translator.trans('action.' + (choice === 1 ? 'retract_upvote' : 'upvote'));
}

function getDownButtonTitle(choice) {
    //noinspection JSUnresolvedFunction
    return translator.trans('action.' + (choice === -1 ? 'retract_downvote' : 'downvote'));
}

/**
 * @param {jQuery} $form
 * @param {boolean} isUp
 */
function vote($form, isUp) {
    const url = $form.data('ajax-action');
    const choice = getNewChoice($form, isUp);

    const data = {
        choice: choice,
        token: $form.find('input[name=token]').val(),
    };

    $form.removeClass('vote--failed');
    $form.find('.vote__net-score').html(LOADING_HTML);

    $.post(url, data).done(() => {
        const newScore = getNewScore($form, isUp, $form.data('score'));

        $form
            .toggleClass(isUp ? 'vote--user-upvoted' : 'vote--user-downvoted')
            .removeClass(isUp ? 'vote--user-downvoted' : 'vote--user-upvoted')
            .data('score', newScore)
            .find('.vote__net-score').text(newScore);

        // update title attributes
        $form.find('.vote__up').attr('title', getUpButtonTitle(choice));
        $form.find('.vote__down').attr('title', getDownButtonTitle(choice));
    }).fail((xhr, textStatus, err) => {
        $form
            .addClass('vote--failed')
            .find('.vote__net-score')
            .text($form.data('score'));

        console && console.log('Failed to vote', textStatus, err);
    });
}

$(function () {
    $(document)
        .on('submit', '.user-logged-in .vote', event => event.preventDefault())
        .on('click', '.user-logged-in .vote__button', function () {
            const $form = $(this).parents('.vote');

            vote($form, $(this).hasClass('vote__up'));
        });
});
