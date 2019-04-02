'use strict';

// set up some global variables
const $ = window.$ = require('jquery');
window.Translator = require('bazinga-translator');

// toggle JS mode
$('html').removeClass('no-js').addClass('js');

// actually initialise stuff
import './alerts';
import './comment-count';
import './commenting';
import './delete';
import './dropdowns';
import './fetch_titles';
import './forms';
import './markdown';
import './relative-time';
import './select2';
import './subscribe';
import './syntax';
import './vote';
