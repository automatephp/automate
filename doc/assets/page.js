'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const burger = document.querySelector('.c-menu__header');
  const nav = document.querySelector('.c-menu__list');
  burger.addEventListener('click', function () {
    nav.classList.toggle('is-active');
  });
});