(() => {
 'use strict';
 const toggle=document.querySelector('[data-cms-sidebar-toggle]');
 toggle?.addEventListener('click',()=>{
  const open=document.body.classList.toggle('cms-sidebar-open');
  toggle.setAttribute('aria-expanded',String(open));
 });
 document.addEventListener('click',event=>{
  document.querySelectorAll('.cms-toolbar-dropdown[open]').forEach(menu=>{if(!menu.contains(event.target))menu.open=false;});
  if(document.body.classList.contains('cms-sidebar-open')&&!event.target.closest('.cms-custom-sidebar,[data-cms-sidebar-toggle]')){document.body.classList.remove('cms-sidebar-open');toggle?.setAttribute('aria-expanded','false');}
 });
 document.addEventListener('keydown',event=>{
  if(event.key!=='Escape')return;
  document.querySelectorAll('.cms-toolbar-dropdown[open]').forEach(menu=>{menu.open=false;menu.querySelector('summary').focus();});
  if(document.body.classList.contains('cms-sidebar-open')){document.body.classList.remove('cms-sidebar-open');toggle?.setAttribute('aria-expanded','false');toggle?.focus();}
 });
})();
