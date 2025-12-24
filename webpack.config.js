/** frontend assets build configuration */
const Encore = require('@symfony/webpack-encore')

Encore
    // set build path
    .setOutputPath('public/build/')
    .setPublicPath('/build')

    // register css assets
    .addEntry('admin-css', './assets/css/admin.scss')
    .addEntry('public-css', './assets/css/public.scss')
    .addEntry('scrollbar-css', './assets/css/scrollbar.scss')
    .addEntry('error-page-css', './assets/css/error-page.scss')
    .addEntry('page-loading-css', './assets/css/page-loading.scss')
    .addEntry('bootstrap-css', './node_modules/bootstrap/dist/css/bootstrap.css')
    .addEntry('fontawesome-css', './node_modules/@fortawesome/fontawesome-free/css/all.css')
    .addEntry('bootstrap-icons-css', './node_modules/bootstrap-icons/font/bootstrap-icons.css')
    
    // register js assets
    .addEntry('dashboard-js', './assets/js/dashboard.js')
    .addEntry('page-loading-js', './assets/js/page-loading.js')
    .addEntry('admin-sidebar-js', './assets/js/admin-sidebar.js')
    .addEntry('visitors-manager-js', './assets/js/visitors-manager.js')
    .addEntry('account-settings-js', './assets/js/account-settings.js')
    .addEntry('database-browser-js', './assets/js/database-browser.js')
    .addEntry('visitors-metrics-js', './assets/js/visitors-metrics.js')
    .addEntry('boxicons-css', './node_modules/boxicons/css/boxicons.css')
    .addEntry('update-visitor-status-js', './assets/js/update-visitor-status.js')
    .addEntry('bootstrap-js', './node_modules/bootstrap/dist/js/bootstrap.bundle.js')

    // copy static assets
    .copyFiles(
        {
            from: './assets/img', 
            to: 'images/[path][name].[ext]' 
        }
    )

    // other webpack configs
    .splitEntryChunks()
    .enableSassLoader()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage'
        config.corejs = '3.23'
    })

module.exports = Encore.getWebpackConfig()
