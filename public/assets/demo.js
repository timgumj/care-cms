(() => {
 const menus=document.querySelectorAll('.demo-navigation,.pack-menu');
 const compact=window.matchMedia('(max-width:800px)');
 const packCompact=window.matchMedia('(max-width:760px)');
 function layout(){menus.forEach(menu=>menu.open=!(menu.matches('.pack-menu')?(packCompact.matches||menu.dataset.desktopMenu==='hamburger'):compact.matches));}
 let lockedMenu=null,scrollPosition=0,bodyStyles=null;const inertStates=new Map();
 function syncOverlay(){
  const open=packCompact.matches?[...menus].find(menu=>menu.matches('.mint-header .pack-menu')&&menu.open):null;
  if(open===lockedMenu)return;
  if(lockedMenu){
   document.documentElement.classList.remove('care-menu-open');document.body.classList.remove('care-menu-open');
   for(const [property,value] of Object.entries(bodyStyles))document.body.style[property]=value;
   for(const [element,value] of inertStates)element.inert=value;inertStates.clear();
   window.scrollTo(0,scrollPosition);lockedMenu.querySelector('summary span').textContent='MENU';lockedMenu=null;
  }
  if(open){
   scrollPosition=window.scrollY;bodyStyles={position:document.body.style.position,top:document.body.style.top,width:document.body.style.width};
   document.body.style.position='fixed';document.body.style.top=`-${scrollPosition}px`;document.body.style.width='100%';
   document.documentElement.classList.add('care-menu-open');document.body.classList.add('care-menu-open');
   for(const element of document.body.children)if(!element.contains(open)){inertStates.set(element,element.inert);element.inert=true;}
   lockedMenu=open;open.querySelector('summary span').textContent=document.documentElement.lang.startsWith('de')?'SCHLIESSEN':'CLOSE';
  }
 }
 menus.forEach(menu=>{menu.addEventListener('toggle',syncOverlay);menu.addEventListener('click',event=>{if(packCompact.matches&&event.target.closest('nav a')){menu.open=false;syncOverlay();}});});
 document.addEventListener('keydown',event=>{
  if(!lockedMenu)return;
  if(event.key==='Escape'){const menu=lockedMenu;menu.open=false;syncOverlay();menu.querySelector('summary').focus();event.preventDefault();}
  if(event.key==='Tab'){const focusable=[...lockedMenu.querySelectorAll('summary,a[href],button')].filter(e=>e.getClientRects().length);const first=focusable[0],last=focusable[focusable.length-1];if(event.shiftKey&&(document.activeElement===first||!lockedMenu.contains(document.activeElement))){last.focus();event.preventDefault();}else if(!event.shiftKey&&(document.activeElement===last||!lockedMenu.contains(document.activeElement))){first.focus();event.preventDefault();}}
 });
 packCompact.addEventListener('change',syncOverlay);
 layout();compact.addEventListener('change',layout);packCompact.addEventListener('change',layout);
 let opener=null;
 document.addEventListener('click',event=>{
  const trigger=event.target.closest('[data-announcement-open]');
  if(trigger){const dialog=document.getElementById(trigger.dataset.announcementOpen);if(dialog instanceof HTMLDialogElement){opener=trigger;dialog.showModal();}}
  const close=event.target.closest('[data-announcement-close]');if(close)close.closest('dialog')?.close();
 });
 document.querySelectorAll('dialog').forEach(dialog=>dialog.addEventListener('close',()=>opener?.focus()));
})();
