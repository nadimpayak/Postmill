'use strict';

import $ from 'jquery';

const KEY_ESC = 27;
const KEY_HOME = 36;
const KEY_END = 35;
const KEY_UP = 38;
const KEY_DOWN = 40;

const FOCUSABLE_ELEMENTS = `> .dropdown__toggle,
> .dropdown__menu a[href],
> .dropdown__menu button:not([disabled]),
> .dropdown__menu input:not([disabled])`;

const MENU_ACTIONS = `.dropdown__menu a[href],
.dropdown__menu input[type="button"],
.dropdown__menu button[type="submit"],
.dropdown__menu button:not([type])`;

function toggle($dropdown) {
    const $toClose = $dropdown.add($dropdown.parents('.dropdown--expanded'));

    // close all open dropdowns that are not self or a parent of self
    toggleAttributes($('.dropdown--expanded').not($toClose));

    // toggle the current dropdown
    toggleAttributes($dropdown);
}

function toggleAttributes($dropdowns) {
    $dropdowns.each(function (i, el) {
        const $dropdown = $(el);
        const expanded = !$dropdown.hasClass('dropdown--expanded');

        $dropdown
            .toggleClass('dropdown--expanded')
            .find('.dropdown__toggle')
            .attr('aria-expanded', expanded);
    });
}

function moveInList($dropdown, amount) {
    const $elements = $dropdown.find(FOCUSABLE_ELEMENTS);
    let i = $elements.index($(':focus')) + amount;

    if (i >= $elements.length) {
        i = 0;
    } else if (i < 0) {
        i = $elements.length - 1;
    }

    $elements.get(i).focus();
}

function globalKeyDownHandler(event) {
    if (event.metaKey || event.ctrlKey || event.altKey) {
        return;
    }

    const $dropdown = $('.dropdown--expanded');

    if ($dropdown.length == 0) {
        return;
    }

    switch (event.which) {
    case KEY_ESC:
        toggle($dropdown);

        // give focus back to toggle
        $dropdown.find('> .dropdown__toggle').first().focus();

        break;
    case KEY_DOWN:
        moveInList($dropdown, 1);

        break;
    case KEY_UP:
        moveInList($dropdown, -1);

        break;
    case KEY_HOME:
        moveInList($dropdown, Infinity);

        break;
    case KEY_END:
        moveInList($dropdown, -Infinity);

        break;
    default:
        return;
    }

    event.preventDefault();
}

// init

$('.dropdown__toggle').attr('aria-haspopup', true).attr('aria-expanded', false);

$(document).on('keydown', globalKeyDownHandler);

// close the menu upon clicking a link or button or similar inside it
$(document).on('click', MENU_ACTIONS, () => {
    event.stopPropagation();

    toggle($('.dropdown--expanded'));
});

// prevent closing the menu when clicking on things in it that aren't buttons or
// links or anything
$(document).on('click', '.dropdown__menu', event => event.stopPropagation());

// make the toggles work
$(document).on('click', '.dropdown__toggle', function (event) {
    event.stopPropagation();

    toggle($(this).parent('.dropdown'));
});

// close the menu when clicking elsewhere on a page
$(document).on('click', () => toggle($('.dropdown--expanded')));
