(() => {
  'use strict';
  const prefix='care-cms-editor-recovery:v1:',ttl=7*24*60*60*1000;
  const allowed=/\[(title|description|content|canonical|translationKey|published|path|locale)\]$/;
  function clearSaved(){try{const saved=document.querySelector('meta[name="practice-editor-saved"]');if(saved){const m=JSON.parse(saved.content);localStorage.removeItem(prefix+m.user+':'+m.kind+':'+m.id);if(m.created)localStorage.removeItem(prefix+m.user+':'+m.kind+':new');saved.remove();}}catch(error){}}
  clearSaved();document.addEventListener('turbo:load',clearSaved);
  function fingerprint(value){let h=2166136261;for(let i=0;i<value.length;i++)h=Math.imul(h^value.charCodeAt(i),16777619);return (h>>>0).toString(16);}
  window.PracticeDraftRecovery={mount(form,field,options){
    clearSaved();const user=field.dataset.recoveryUser;if(!user||user==='0')return null;
    const kind=field.dataset.recoveryKind==='post'?'post':'page',id=field.dataset.pageId&&field.dataset.pageId!=='0'?field.dataset.pageId:'new',key=prefix+user+':'+kind+':'+id+(field.dataset.recoveryField==='footerHtml'?':footer':'');
    const english=(field.dataset.uiLocale||'de').startsWith('en'),text=(en,de)=>english?en:de;
    function controls(){return [...form.elements].filter(el=>el.name&&allowed.test(el.name)&&!el.disabled&&!['hidden','file','submit','button'].includes(el.type));}
    function capture(){const data={};controls().forEach(el=>data[el.name]=el.type==='checkbox'?el.checked:el.value);return data;}
    const baseline=fingerprint(JSON.stringify(capture()));let timer=null,changed=false,saving=false,submitting=false;
    const box=document.createElement('section');box.className='pm-recovery';box.setAttribute('role','status');box.hidden=true;
    const message=document.createElement('p'),actions=document.createElement('div'),restore=document.createElement('button'),discard=document.createElement('button');
    actions.className='pm-recovery-actions';restore.type=discard.type='button';restore.disabled=true;restore.textContent=text('Restore recovery copy','Wiederherstellungskopie laden');discard.textContent=text('Discard copy','Kopie verwerfen');actions.append(restore,discard);box.append(message,actions);form.prepend(box);
    const status=document.createElement('p');status.className='pm-recovery-status';status.setAttribute('role','status');field.append(status);
    let pending=null;
    try{pending=JSON.parse(localStorage.getItem(key)||'null');if(pending&&(!pending.at||Date.now()-pending.at>ttl||pending.key!==key||!pending.data)){localStorage.removeItem(key);pending=null;}}catch(error){pending=null;}
    if(pending){box.hidden=false;message.textContent=pending.baseline!==baseline?text('A recovery copy is available. The saved page may have changed since this copy was made. Review restored changes before saving.','Eine Wiederherstellungskopie ist verfügbar. Die gespeicherte Seite wurde möglicherweise inzwischen geändert. Prüfen Sie wiederhergestellte Änderungen vor dem Speichern.'):text('Unsaved changes from this browser are available. Restore them or discard the recovery copy.','Nicht gespeicherte Änderungen aus diesem Browser sind verfügbar. Laden Sie die Kopie oder verwerfen Sie sie.');}
    function flush(){
      clearTimeout(timer);if(!changed||saving)return;saving=true;
      try{
        options.serialize();const encoded=JSON.stringify({key,at:Date.now(),baseline,data:capture(),live:options.live?.()||null});if(encoded.length>1000000)throw new Error('Recovery size limit');
        const keys=[];for(let i=0;i<localStorage.length;i++){const candidate=localStorage.key(i);if(candidate?.startsWith(prefix+user+':')&&candidate!==key){try{const other=JSON.parse(localStorage.getItem(candidate));if(Date.now()-other.at>ttl)localStorage.removeItem(candidate);else keys.push({key:candidate,at:other.at||0});}catch(error){localStorage.removeItem(candidate);}}}keys.sort((a,b)=>a.at-b.at);while(keys.length>=20)localStorage.removeItem(keys.shift().key);
        localStorage.setItem(key,encoded);status.textContent=text('Recovery copy saved in this browser. Use Save to update the website.','Wiederherstellungskopie in diesem Browser gespeichert. Mit Speichern aktualisieren Sie die Website.');
      }catch(error){status.textContent=text('Browser recovery is unavailable or full. Save your changes before closing this page.','Die Browser-Wiederherstellung ist nicht verfügbar oder voll. Speichern Sie Ihre Änderungen vor dem Schließen.');}finally{saving=false;}
    }
    function change(){if(saving)return;changed=true;clearTimeout(timer);timer=setTimeout(flush,800);}
    restore.addEventListener('click',()=>{if(!pending||!options.canRestore())return;saving=true;try{controls().forEach(el=>{if(!Object.hasOwn(pending.data,el.name))return;const value=pending.data[el.name];if(el.type==='checkbox')el.checked=value===true;else if(typeof value==='string')el.value=value;el.dispatchEvent(new Event('change',{bubbles:true}));});options.apply(pending.live);}finally{saving=false;}box.hidden=true;pending=null;change();status.textContent=text('Recovery copy restored. Review it, then Save to apply your changes.','Wiederherstellungskopie geladen. Prüfen Sie sie und speichern Sie anschließend Ihre Änderungen.');});
    discard.addEventListener('click',()=>{try{localStorage.removeItem(key);}catch(error){}pending=null;box.hidden=true;status.textContent=text('Recovery copy discarded.','Wiederherstellungskopie verworfen.');});
    form.addEventListener('input',change);form.addEventListener('change',change);form.addEventListener('submit',event=>{if(event.defaultPrevented)return;flush();submitting=true;});
    window.addEventListener('pagehide',flush);document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='hidden')flush();});window.addEventListener('beforeunload',event=>{flush();if(changed&&!submitting){event.preventDefault();event.returnValue='';}});
    return {changed:change,flush,ready(){restore.disabled=!options.canRestore();}};
  }};
})();
