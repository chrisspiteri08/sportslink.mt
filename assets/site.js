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
  const status = new URLSearchParams(window.location.search).get('contact');
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
  }
}
