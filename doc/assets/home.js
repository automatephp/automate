'use strict';

function toggleScrollClass(elem) {
  if (window.scrollY === 0) {
    elem.classList.remove('is-scrolled');
  } else {
    elem.classList.add('is-scrolled');
  }
}

async function loadYAML(path) {
  const response = await fetch(path);
  if (!response.ok) {
    throw new Error("Response in not OK");
  }
  const code = await response.text();
  let lineNb = 1;
  document.querySelector('.c-flip-card__code').innerHTML = code.split('\n').map((line) => {
    return '<span class="c-flip-card__position">&#x200b;<span class="c-flip-card__line">' + lineNb++ + '</span></span>' + line;
  }).join('\n');
}

document.addEventListener('DOMContentLoaded', function () {
  const baselineBtn = document.querySelector('.c-baseline__button');
  const header = document.querySelector('.c-header');

  // Add scroll class on click event for header
  baselineBtn.addEventListener('click', function () {
    header.classList.add('is-scrolled');
  });

  // Toggle scroll class on scroll event for header
  toggleScrollClass(header);
  document.addEventListener('scroll', function () {
    toggleScrollClass(header);
  });

  // Load Yaml content in flip card back
  loadYAML('./config-example.yaml');

  // Load asccinema in flip card front
  AsciinemaPlayer.create('./demo.cast', document.getElementById('demo'), {
    'autoPlay': true,
    'loop': true,
    'speed': 1,
    'cols': 120,
    'rows': 47,
    'terminalFontSize': '12px',
    'theme': "tango",
    'controls': false,
    'fit': false,
  });

  // Load Typewriter on baseline titles
  new Typewriter('#c-baseline-title-1', {
    strings: 'One command line !',
    autoStart: true,
    delay: 80,
  });

  new Typewriter('#c-baseline-title-2', {
    strings: 'One YAML file to organize everything',
    autoStart: true,
    delay: 80,
  });
});