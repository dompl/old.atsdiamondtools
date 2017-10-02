var gulp 						= require('gulp'),
    del 						= require('del'),
    googleWebFonts 	= require('gulp-google-webfonts');
    config 					= require('../../gulpconfig').gfonts;

gulp.task('google-fonts', ['clean-fonts'], function() {
    return gulp.src(config.build.src)
        .pipe(googleWebFonts({
            fontsDir: config.font.dir,
            cssDir: config.font.cssDir,
            cssFilename: config.font.cssFile,
        }))
        .pipe(gulp.dest(config.build.dest));
});

gulp.task('clean-fonts', function() {
    return del([config.build.clean], { force: true });
});

gulp.task('gfonts', ['google-fonts']);
