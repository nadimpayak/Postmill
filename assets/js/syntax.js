'use strict';

import $ from 'jquery';

const languageAliases = {
    'html': 'xml',
    'c': 'cpp',
    'js': 'javascript'
};

$(function () {
    $('code[class^="language-"]').each(function () {
        const nightMode = $('body').hasClass('night-mode');

        let language = this.className.replace(/.*language-(\S+).*/, '$1');

        if (languageAliases.hasOwnProperty(language)) {
            language = languageAliases[language];
        }

        const theme = nightMode ? 'darkula' : 'tomorrow';

        Promise.all([
            import('highlight.js/lib/highlight'),
            import(`highlight.js/lib/languages/${language}.js`),
            import(`highlight.js/styles/${theme}.css`),
        ]).then(imports => {
            const [
                { default: hljs },
                { default: definition}
            ] = imports;

            console.log(imports, hljs, definition);

            hljs.registerLanguage(language, definition);
            hljs.highlightBlock(this);
        });
    });
});
