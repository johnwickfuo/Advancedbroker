document.querySelectorAll('[data-menu]').forEach((button) => button.addEventListener('click', () => document.querySelector('.app-shell aside')?.classList.toggle('open')));
document.querySelectorAll('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => { if (!window.confirm(form.dataset.confirm || 'Continue?')) event.preventDefault(); }));
document.querySelectorAll('form[data-auto-submit] select').forEach((select) => select.addEventListener('change', () => select.form?.submit()));
document.addEventListener('click', async (event) => { const button = event.target.closest('[data-copy-value]'); if (!button) return; try { await navigator.clipboard.writeText(button.dataset.copyValue || ''); const label = button.textContent; button.textContent = 'Copied'; setTimeout(() => { button.textContent = label; }, 1800); } catch (_) { button.textContent = 'Copy unavailable'; } });

document.querySelectorAll('[data-popup-close]').forEach((button)=>button.addEventListener('click',()=>button.closest('[data-popup]')?.remove()));
document.querySelectorAll('[data-popup]').forEach((popup,index)=>{ setTimeout(()=>{ popup.classList.add('popup-visible'); },120*index); setTimeout(()=>{ popup.classList.add('popup-leaving'); setTimeout(()=>popup.remove(),320); },9000+(index*800)); });
