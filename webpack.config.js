'use strict';

const Encore = require('@symfony/webpack-encore');
const merge = require('webpack-merge');

Encore
    .addEntry('main', './assets/js/main.js')
    .addStyleEntry('core', './assets/css/core.less')
    .addStyleEntry('postmill', './assets/css/postmill.css')
    .addStyleEntry('postmill-night', './assets/css/postmill-night.css')
    .cleanupOutputBeforeBuild()
    .copyFiles({
        from: './assets/icons',
        to: 'images/[path][name].[hash:8].[ext]',
        pattern: /\.svg$/i,
    })
    .enableLessLoader()
    .enableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning()
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .createSharedEntry('vendor', './assets/js/vendor.js');

module.exports = merge(Encore.getWebpackConfig(), {
    externals: {
        "fosjsrouting": "Routing",
    },
});
