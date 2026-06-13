const Encore = require("@symfony/webpack-encore");

if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || "dev");
}

Encore
  // dossier de sortie
  .setOutputPath("public/build/")
  .setPublicPath("/build")

  // point d'entrée principal
  .addEntry("app", "./assets/app.js")

  // Stimulus
  .enableStimulusBridge("./assets/controllers.json")

  // nettoyage du dossier build avant compilation
  .cleanupOutputBeforeBuild()

  // découpe JS en chunks
  .splitEntryChunks()
  .enableSingleRuntimeChunk()

  // Sass/SCSS
  .enableSassLoader()

  // source maps pour dev, versioning pour prod
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())

  // petites images en inline (<8kb)
  .configureImageRule((options) => {
    options.type = "asset";
    options.maxSize = 8 * 1024;
  });

module.exports = Encore.getWebpackConfig();
