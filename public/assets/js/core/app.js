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

  // fill the sign-in form from a demo account:  <button data-demo-fill
  // data-demo-email="…" data-demo-password="…">.  The button lives in the brand
  // aside, outside the form, so reach the fields by id rather than by closest().
  const demo = e.target.closest('[data-demo-fill]');
  if (demo) {
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    if (email && password) {
      email.value = demo.dataset.demoEmail;
      password.value = demo.dataset.demoPassword;
      // the browser autofill highlight sticks unless the field reports a change
      [email, password].forEach((f) => f.dispatchEvent(new Event('input', { bubbles: true })));
      email.form?.querySelector('button[type="submit"]')?.focus();
    }
  }
});

// Acknowledge every chosen file, on every form.
//
// This used to key off [data-file-input] inside a .dropzone, which only the
// registration document partial has - so picking a photo on the reuse form, a
// PDF on a request, or a screenshot on Report a Problem looked like nothing had
// happened. It now runs for any <input type="file">, names every file picked
// (not just the first) and prints its size, so a PDF is acknowledged the same
// way a picture is.
//
// The receipt is inserted after the input's own label wrapper where there is
// one, so it lands under the dropzone rather than inside it.
const fileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

document.addEventListener('change', (e) => {
  const input = e.target;
  if (!input.matches || !input.matches('input[type=file]')) return;

  const files = Array.from(input.files || []);

  // The old markup names its own target; keep honouring it.
  const named = input.closest('.dropzone')?.querySelector('[data-file-name]');
  if (named) {
    named.textContent = files.map((f) => f.name).join(', ');
    named.hidden = files.length === 0;
  }

  // A file input is usually wrapped in the label that draws the dropzone. Put
  // the receipt after that wrapper, not inside it, or clicking it reopens the
  // picker.
  const anchor = input.closest('label') || input;
  let receipt = anchor.nextElementSibling;
  if (!receipt || !receipt.classList.contains('file-chosen')) {
    receipt = document.createElement('ul');
    receipt.className = 'file-chosen';
    anchor.after(receipt);
  }

  receipt.innerHTML = '';
  receipt.hidden = files.length === 0;
  files.forEach((f) => {
    const li = document.createElement('li');
    li.innerHTML = '<span class="file-chosen__tick">&#10003;</span>';
    li.append(f.name + ' (' + fileSize(f.size) + ')');
    receipt.append(li);
  });
});

// switch a form between two mutually exclusive halves without reloading:
//   <input type="radio" name="request_type" value="items" data-type-switch>
//   <div data-type-panel="items"> … </div>
//
// The donation create form uses it for Relief Items and Monetary Funds. Those
// were two links carrying ?type=, so changing your mind reloaded the page and
// threw away everything already typed above the switch. Both halves are in the
// page now and only the chosen one is shown; the server still reads the radio,
// so a browser with no JavaScript submits exactly what it displays.
document.addEventListener('change', (e) => {
  const chosen = e.target.closest('[data-type-switch]');
  if (!chosen) return;

  document.querySelectorAll('[data-type-panel]').forEach((panel) => {
    panel.hidden = panel.dataset.typePanel !== chosen.value;
  });

  document.querySelectorAll('[data-type-switch]').forEach((radio) => {
    radio.closest('label')?.classList.toggle('is-active', radio === chosen);
  });
});

// live character count for textareas:  <textarea data-count-for="descCount">
document.addEventListener('input', (e) => {
  const area = e.target.closest('[data-count-for]');
  if (!area) return;

  const out = document.getElementById(area.dataset.countFor);
  if (out) out.textContent = `${area.value.length}/${area.maxLength}`;
});

// admin sidebar collapse
document.addEventListener('click', (e) => {
  if (!e.target.closest('[data-sidebar-toggle]')) return;
  document.querySelector('.app-shell')?.classList.toggle('is-collapsed');
});

// auto-hide flash messages
setTimeout(() => document.querySelectorAll('.alert--success').forEach(a => a.remove()), 6000);
