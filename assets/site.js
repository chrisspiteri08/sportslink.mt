const toggle = document.querySelector('.menu-toggle');
const nav = document.querySelector('#site-nav');

if (toggle && nav) {
  toggle.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', String(!open));
    nav.classList.toggle('open', !open);
  });
  nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
    toggle.setAttribute('aria-expanded', 'false');
    nav.classList.remove('open');
  }));
}

document.querySelectorAll('#year').forEach((year) => { year.textContent = new Date().getFullYear(); });

const formStatus = document.querySelector('#form-status');
if (formStatus) {
  const params = new URLSearchParams(window.location.search);
  const status = params.get('contact') || params.get('network');
  if (status === 'sent') {
    formStatus.textContent = 'Thanks—your enquiry has been sent. We’ll be in touch.';
    formStatus.classList.add('success');
  } else if (status === 'invalid') {
    formStatus.textContent = 'Please check the form fields and try again.';
    formStatus.classList.add('error');
  } else if (status === 'failed') {
    formStatus.textContent = 'The message could not be sent. Please use email or WhatsApp instead.';
    formStatus.classList.add('error');
  } else if (status === 'slow') {
    formStatus.textContent = 'Please wait a moment before sending another enquiry.';
    formStatus.classList.add('error');
  } else if (status === 'captcha') {
    formStatus.textContent = 'The security answer was incorrect or expired. Please try the new question.';
    formStatus.classList.add('error');
  }
}

const captchaQuestion = document.querySelector('#captcha-question');
const contactForm = document.querySelector('.contact-form');
if (captchaQuestion && contactForm) {
  fetch('/captcha.php', { credentials: 'same-origin', cache: 'no-store' })
    .then((response) => {
      if (!response.ok) throw new Error('Captcha unavailable');
      return response.json();
    })
    .then((data) => { captchaQuestion.textContent = data.question; })
    .catch(() => {
      captchaQuestion.textContent = 'Security question unavailable. Please use email or WhatsApp.';
      contactForm.querySelector('button[type="submit"]').disabled = true;
    });
}

const routeInputs = document.querySelectorAll('input[name="route"]');
const routeGroups = document.querySelectorAll('[data-fields-for]');

function setNetworkRoute(route) {
  routeInputs.forEach((input) => { input.checked = input.value === route; });
  routeGroups.forEach((group) => {
    const active = group.dataset.fieldsFor === route;
    group.hidden = !active;
    group.querySelectorAll('input, select, textarea').forEach((field) => { field.disabled = !active; });
  });
}

if (routeInputs.length && routeGroups.length) {
  routeInputs.forEach((input) => input.addEventListener('change', () => setNetworkRoute(input.value)));
  document.querySelectorAll('[data-route-link]').forEach((link) => link.addEventListener('click', () => setNetworkRoute(link.dataset.routeLink)));
  const requestedRoute = new URLSearchParams(window.location.search).get('route');
  const initialRoute = requestedRoute === 'business' || requestedRoute === 'athlete'
    ? requestedRoute
    : document.querySelector('input[name="route"]:checked')?.value || 'athlete';
  setNetworkRoute(initialRoute);
}

const interestBoxes = [...document.querySelectorAll('input[name="interests[]"]')];
if (interestBoxes.length) {
  const syncInterestRequirement = () => {
    interestBoxes[0].required = !interestBoxes.some((box) => box.checked);
  };
  interestBoxes.forEach((box) => box.addEventListener('change', syncInterestRequirement));
  routeInputs.forEach((input) => input.addEventListener('change', syncInterestRequirement));
  syncInterestRequirement();
}
