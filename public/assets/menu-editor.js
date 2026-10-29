(() => {
  'use strict';
  const form=document.querySelector('[data-menu-form]');if(!form)return;
  const list=form.querySelector('[data-menu-items]');let dragging=null;
  function renumber(){[...list.children].forEach((row,index)=>{row.querySelectorAll('[name]').forEach(input=>input.name=input.name.replace(/items\[[^\]]+\]/,'items['+index+']'));row.querySelectorAll('[id]').forEach(input=>input.id=input.id.replace(/^menu_(?:__INDEX__|[0-9]+)_/,'menu_'+index+'_'));row.querySelectorAll('label[for]').forEach(label=>label.htmlFor=label.htmlFor.replace(/^menu_(?:__INDEX__|[0-9]+)_/,'menu_'+index+'_'));row.querySelector('[data-menu-up]').disabled=index===0;row.querySelector('[data-menu-down]').disabled=index===list.children.length-1;});form.querySelector('[data-menu-add]').disabled=list.children.length>=30;}
  function sync(row){const page=row.querySelector('[data-menu-type]').value==='page';row.querySelector('[data-menu-page]').hidden=!page;row.querySelector('[data-menu-custom]').hidden=page;row.querySelector('[data-menu-page-select]').required=page;row.querySelector('[data-menu-custom] input').required=!page;}
  list.querySelectorAll('[data-menu-item]').forEach(sync);renumber();
  form.addEventListener('change',event=>{const row=event.target.closest('[data-menu-item]');if(!row)return;sync(row);if(event.target.matches('[data-menu-page-select]')){const title=row.querySelector('[data-menu-title]');if(!title.value.trim())title.value=event.target.selectedOptions[0]?.dataset.pageTitle||'';}});
  form.addEventListener('click',event=>{
    if(event.target.closest('[data-menu-add]')){if(list.children.length>=30)return;const template=document.querySelector('[data-menu-template]');const fragment=template.content.cloneNode(true);const row=fragment.querySelector('[data-menu-item]');list.append(fragment);renumber();sync(row);row.querySelector('[data-menu-title]').focus();return;}
    const row=event.target.closest('[data-menu-item]');if(!row)return;
    if(event.target.closest('[data-menu-up]')&&row.previousElementSibling)list.insertBefore(row,row.previousElementSibling);
    if(event.target.closest('[data-menu-down]')&&row.nextElementSibling)list.insertBefore(row.nextElementSibling,row);
    if(event.target.closest('[data-menu-remove]')){row.remove();form.querySelector('[data-menu-add]').focus();}
    renumber();
  });
  list.addEventListener('dragstart',event=>{const handle=event.target.closest('[data-menu-drag]');if(!handle){event.preventDefault();return;}dragging=handle.closest('[data-menu-item]');dragging.classList.add('is-dragging');event.dataTransfer.effectAllowed='move';event.dataTransfer.setData('text/plain','menu-item');});
  list.addEventListener('dragover',event=>{if(!dragging)return;event.preventDefault();const target=event.target.closest('[data-menu-item]');if(!target||target===dragging)return;const bounds=target.getBoundingClientRect();list.insertBefore(dragging,event.clientY<bounds.top+bounds.height/2?target:target.nextSibling);});
  list.addEventListener('drop',event=>{if(dragging)event.preventDefault();});
  list.addEventListener('dragend',()=>{dragging?.classList.remove('is-dragging');dragging=null;renumber();});
  form.addEventListener('submit',renumber);
})();
