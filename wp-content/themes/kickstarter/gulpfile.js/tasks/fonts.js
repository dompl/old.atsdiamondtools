// ==== THEME ==== //

var gulp        = require('gulp'),
    plugins     = require('gulp-load-plugins')({ camelize: true }),
    config      = require('../../gulpconfig').fonts;

// Copy PHP source files to the `build` folder
gulp.task('fonts', function() {
  return gulp.src(config.files.src)
  .pipe(plugins.changed(config.files.dest))
  .pipe(gulp.dest(config.files.dest));
});
