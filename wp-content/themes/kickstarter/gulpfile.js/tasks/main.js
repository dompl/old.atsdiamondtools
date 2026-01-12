// ==== MAIN ==== //

var gulp = require('gulp');

// Default task chain: build -> (livereload or browsersync) -> watch
gulp.task('default', ['watch']);

// Build a working copy of the theme
// Note: 'gfonts' removed - using Google Fonts CDN in _google-fonts.scss instead
gulp.task('build', ['images', 'scripts', 'icons', 'styles', 'fonts', 'theme']);

// Dist task chain: wipe -> build -> clean -> copy -> compress images
// NOTE: this is a resource-intensive task!
gulp.task('dist', ['images-optimize']);
