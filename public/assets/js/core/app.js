// Shared front-end helpers. Module JS goes in assets/js/modules/.
document.addEventListener('click', (e) => {
  // dismiss flash alerts
  if (e.target.matches('[data-dismiss]')) {
    e.target.closest('.alert')?.remove();
  }

  // confirm before destructive actions:  <button data-confirm="Delete this?">
  const trigger = e.target.closest('[data-confirm]');
  if (trigger && !confirm(trigger.dataset.confirm)) {
    e.preventDefault();
  }

  // show/hide a password:  <button data-toggle-password> next to the input
  const eye = e.target.closest('[data-toggle-password]');
  if (eye) {
    const input = eye.parentElement.querySelector('input');
    if (input) {
      const hidden = input.type === 'password';
      input.type = hidden ? 'text' : 'password';
      eye.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
    }
  }
});

// echo the chosen filename back inside the dropzone
document.addEventListener('change', (e) => {
  const input = e.target.closest('[data-file-input]');
  if (!input) return;

  const label = input.closest('.dropzone')?.querySelector('[data-file-name]');
  if (!label) return;

  const file = input.files[0];
  label.textContent = file ? file.name : '';
  label.hidden = !file;
});

// live character count for textareas:  <textarea data-count-for="descCount">
document.addEventListener('input', (e) => {
  const area = e.target.closest('[data-count-for]');
  if (!area) return;

  const out = document.getElementById(area.dataset.countFor);
  if (out) out.textContent = `${area.value.length}/${area.maxLength}`;
});

// auto-hide flash messages
setTimeout(() => document.querySelectorAll('.alert--success').forEach(a => a.remove()), 6000);
