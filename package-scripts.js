const npsUtils = require("nps-utils");

module.exports = {
  scripts: {
    clean: 'rimraf build/**',
    plugin: 'node -r esm ./scripts/build-plugin.js',
    archive: 'node -r esm ./scripts/archive-plugin.js',
    build: npsUtils.series.nps('clean', 'plugin', 'archive'),
    default: 'nps build'
  }
};
