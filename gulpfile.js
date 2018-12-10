'use strict';
 
var gulp  = require('gulp');
var sass  = require('gulp-sass');
var watch = require('gulp-watch');
 
gulp.task('watch', function () {
    gulp.watch(['./assets/sass/**/*.scss', './assets/sass/*.scss'], ['sass']);
    gulp.watch('./style.css');
});

gulp.task('sass', function () {
  return gulp.src('./assets/sass/**/*.scss')
    .pipe(sass().on('error', sass.logError))
    .pipe(gulp.dest('./'));
});
 
gulp.task('sass:watch', function () {
  gulp.watch('./assets/sass/**/*.scss', ['sass']);
});