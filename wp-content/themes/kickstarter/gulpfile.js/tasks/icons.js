// ==== Font import and generator ==== //

var gulp        = require('gulp'),
		gutil       = require('gulp-util'),
		del         = require('del'),
		iconfont    = require('gulp-iconfont'),
		iconfontCss = require('gulp-iconfont-css'),
		config      = require('../../gulpconfig').icons;

gulp.task('iconfont', ['delete-icons'], function() {
    return gulp.src(config.svg.src)
        .pipe(iconfontCss({
            fontName: config.icon.name,
            path: config.icon.template,
            targetPath: config.build.scssDest,
            fontPath: config.build.iconsCss,
        }))
        .pipe(iconfont({
            fontName: config.icon.name,
            prependUnicode: config.icon.unicode,
            formats: config.icon.formats,
            normalize: config.icon.normalize,
            fontHeight: config.icon.height,
        }))
        .pipe(gulp.dest(config.build.src));
});

gulp.task('delete-icons', function() {
    return del([config.build.src], { force: true });
});

gulp.task('icons', ['iconfont']);
