const dashboardSidebar=document.querySelector('.app-shell aside');
const closeDashboardSidebar=()=>dashboardSidebar?.classList.remove('open');
document.querySelectorAll('[data-menu]').forEach((button)=>button.addEventListener('click',(event)=>{event.stopPropagation();dashboardSidebar?.classList.toggle('open');}));
document.querySelectorAll('[data-menu-close]').forEach((button)=>button.addEventListener('click',closeDashboardSidebar));
document.addEventListener('keydown',(event)=>{if(event.key==='Escape')closeDashboardSidebar();});
document.addEventListener('click',(event)=>{if(!dashboardSidebar?.classList.contains('open'))return;if(dashboardSidebar.contains(event.target)||event.target.closest('[data-menu]'))return;closeDashboardSidebar();});
document.querySelectorAll('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => { if (!window.confirm(form.dataset.confirm || 'Continue?')) event.preventDefault(); }));
document.querySelectorAll('form[data-auto-submit] select').forEach((select) => select.addEventListener('change', () => select.form?.submit()));
document.addEventListener('click', async (event) => { const button = event.target.closest('[data-copy-value]'); if (!button) return; try { await navigator.clipboard.writeText(button.dataset.copyValue || ''); const label = button.textContent; button.textContent = 'Copied'; setTimeout(() => { button.textContent = label; }, 1800); } catch (_) { button.textContent = 'Copy unavailable'; } });

document.querySelectorAll('[data-popup-close]').forEach((button)=>button.addEventListener('click',()=>button.closest('[data-popup]')?.remove()));
document.querySelectorAll('[data-popup]').forEach((popup,index)=>{ setTimeout(()=>{ popup.classList.add('popup-visible'); },120*index); setTimeout(()=>{ popup.classList.add('popup-leaving'); setTimeout(()=>popup.remove(),320); },9000+(index*800)); });

const modalLayer=document.querySelector('[data-popup-modal-layer]');
if(modalLayer){
  document.body.classList.add('popup-modal-open');
  const syncModalState=()=>{
    if(!modalLayer.querySelector('[data-popup-modal]')){
      modalLayer.remove();
      document.body.classList.remove('popup-modal-open');
    }
  };
  modalLayer.querySelectorAll('[data-popup-modal-close]').forEach((button)=>{
    button.addEventListener('click',()=>{
      button.closest('[data-popup-modal]')?.remove();
      syncModalState();
    });
  });
}

document.querySelectorAll('[data-deposit-method-type]').forEach((select)=>{
  const syncDepositMethodSections=()=>{
    const form=select.closest('form');
    if(!form)return;
    form.querySelectorAll('[data-method-section]').forEach((section)=>{
      section.hidden=section.dataset.methodSection!==select.value;
    });
  };
  select.addEventListener('change',syncDepositMethodSections);
  syncDepositMethodSections();
});
