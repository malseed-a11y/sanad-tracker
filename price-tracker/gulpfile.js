import gulp from 'gulp';
import * as dartSass from 'sass';
import gulpSass from 'gulp-sass';
import concat from 'gulp-concat';
import uglify_es from 'gulp-uglify-es';
import rename from 'gulp-rename';
import autoprefixer from 'gulp-autoprefixer';
import sourcemaps from 'gulp-sourcemaps';
import JavaScriptObfuscator from 'gulp-javascript-obfuscator';

const { src, dest } = gulp;
const sass = gulpSass(dartSass);
const uglify = uglify_es.default;

// JavaScript minify task
gulp.task('minify-front-js', () => {
  return gulp.src('assets/js/frontend/*.js')
    .pipe(sourcemaps.init())
    .pipe(uglify())
    // .pipe(JavaScriptObfuscator())
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./map'))
    .pipe(gulp.dest('dist/js/frontend'));
});

gulp.task('minify-admin-js', () => {
  return gulp.src('assets/js/admin/*.js')
    .pipe(sourcemaps.init())
    .pipe(uglify())
    // .pipe(JavaScriptObfuscator())
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./map'))
    .pipe(gulp.dest('dist/js/admin'));
});

// CSS minify task
gulp.task('minify-front-css', () => {
  return gulp.src('assets/scss/frontend/*.scss')
    .pipe(sourcemaps.init())
    .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
    .pipe(autoprefixer())
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./map'))
    .pipe(gulp.dest('dist/css/frontend'));
});

gulp.task('minify-admin-css', () => {
  return gulp.src('assets/scss/admin/*.scss')
    .pipe(sourcemaps.init())
    .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
    .pipe(autoprefixer())
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./map'))
    .pipe(gulp.dest('dist/css/admin'));
});


// 🕵️‍♀️ Watcher task
gulp.task('watch-all', () => {
  gulp.watch('assets/js/*/*.js', gulp.series(['minify-admin-js', 'minify-front-js']));
  gulp.watch('assets/scss/*/*.scss', gulp.series(['minify-admin-css', 'minify-front-css']));
});
