(() => {
 const menus=document.querySelectorAll('.demo-navigation,.pack-menu');
 const compact=window.matchMedia('(max-width:800px)');
 const packCompact=window.matchMedia('(max-width:760px)');
 function layout(){menus.forEach(menu=>menu.open=!(menu.matches('.pack-menu')?packCompact.matches:compact.matches));}
 layout();compact.addEventListener('change',layout);packCompact.addEventListener('change',layout);
 let opener=null;
 document.addEventListener('click',event=>{
  const trigger=event.target.closest('[data-announcement-open]');
  if(trigger){const dialog=document.getElementById(trigger.dataset.announcementOpen);if(dialog instanceof HTMLDialogElement){opener=trigger;dialog.showModal();}}
  const close=event.target.closest('[data-announcement-close]');if(close)close.closest('dialog')?.close();
 });
 document.querySelectorAll('dialog').forEach(dialog=>dialog.addEventListener('close',()=>opener?.focus()));
})();
