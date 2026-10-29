(() => {
  'use strict';
  const english = document.documentElement.lang.toLowerCase().startsWith('en');
  const englishLabels = {"Editor-Werkzeuge": "Editor tools", "Bausteine": "Blocks", "Struktur": "Structure", "Design": "Style", "Eigenschaften": "Properties", "Ungespeicherte Änderungen – mit „Speichern“ übernehmen.": "Unsaved changes — use the page Save button to save.", "Text doppelt anklicken. Bausteine ziehen oder anklicken. Speichern über die Seiten-Schaltfläche.": "Double-click text to edit. Drag or click blocks to add them. Save using the page Save button.", "Wählen Sie zuerst eine Zeile für die neue Spalte aus.": "Select a row before adding a column.", "Diese Zeile hat bereits drei Spalten.": "This row already has three columns.", "Editor konnte nicht geladen werden. HTML bleibt verfügbar.": "The editor could not be loaded. HTML editing is still available.", "Editor wird geladen …": "Loading editor …", "Abstände & Größe": "Spacing & size", "Schrift & Farbe": "Typography & colour", "Hintergrund & Rahmen": "Background & border", "Mobil": "Mobile", "Bilder hierher ziehen oder auswählen (max. 5 MB)": "Drop images here or select files (up to 5 MB each)", "Bild-URL hinzufügen": "Add image URL", "Überschrift": "Heading", "Ebene": "Level", "Bild-URL": "Image URL", "Alternativtext": "Alternative text", "Titel": "Title", "Link-Ziel": "Link destination", "Öffnen": "Open in", "Gleiches Fenster": "Same window", "Neues Fenster": "New window", "1 Spalte": "1 column", "2 Spalten": "2 columns", "3 Spalten": "3 columns", "Spalte": "Column", "Inhalt": "Content", "Bild": "Image", "Liste": "List", "Trennlinie": "Divider", "Abstand": "Spacer", "Bild konnte nicht hochgeladen werden. Sitzung, Dateiformat und Größe prüfen.": "The image could not be uploaded. Check your session, file type and size.", "Bild-Upload abgeschlossen. Wählen Sie ein Bild aus.": "Image upload completed. Select an image.", "Ausgewählt: {name}. Verschieben, duplizieren oder entfernen über die Element-Werkzeuge. Design und Eigenschaften rechts.": "Selected: {name}. Use the element tools to move, duplicate or remove it. Style and properties are on the right.", "Bereit. Vorhandene Inhalte wurden noch nicht verändert.": "Ready. Existing content has not been changed.", "Editor bereit. Medienliste konnte nicht geladen werden.": "Editor ready. The media library could not be loaded.", "Editor konnte nicht gestartet werden. HTML bleibt verfügbar.": "The editor could not start. HTML editing is still available.", "Visuellen Editor öffnen": "Open visual editor", "HTML anzeigen / ausblenden": "Show / hide HTML", "Rückgängig": "Undo", "Wiederholen": "Redo", "Bilder": "Images", "Nach oben": "Move up", "Nach unten": "Move down", "Duplizieren": "Duplicate", "Entfernen": "Remove", "Vollbild": "Fullscreen", "Editor schließen": "Close editor", "Ungespeicherte HTML-Änderungen.": "Unsaved HTML changes.", "Bitte warten Sie, bis der Editor geladen ist.": "Please wait until the editor has loaded.", "Visuellen Editor öffnen, um mit der Bearbeitung zu beginnen.": "Open the visual editor to start editing.", "Text hier bearbeiten …": "Edit text here …", "Spalteninhalt": "Column content", "Neue Überschrift": "New heading", "Hier steht Ihr Text. Doppelt anklicken zum Bearbeiten.": "Your text goes here. Double-click to edit.", "Jetzt anfragen": "Contact us", "Erster Punkt": "First item", "Zweiter Punkt": "Second item", "Neue Seite": "New page", "Text hier bearbeiten.": "Edit text here."};
  const t = text => english ? (englishLabels[text] || text) : text;
  const mounted = new WeakSet();
  const builderCss = `.pm-builder-section{padding:clamp(28px,5vw,64px) var(--page-pad,24px)}.pm-builder-row{display:grid;gap:24px;align-items:start;width:100%}.pm-builder-cols-1{grid-template-columns:1fr}.pm-builder-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}.pm-builder-cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}.pm-builder-column{min-width:0;min-height:72px}.pm-builder-button{display:inline-block;background:#24333d;color:white;padding:14px 22px;text-decoration:none}.pm-builder-image{max-width:100%;height:auto}.pm-builder-spacer{height:40px}@media(max-width:700px){.pm-builder-cols-2,.pm-builder-cols-3{grid-template-columns:1fr}}`;
  const icon = name => {const svg=document.createElementNS('http://www.w3.org/2000/svg','svg');svg.classList.add('studio-icon');svg.setAttribute('aria-hidden','true');svg.setAttribute('width','16');svg.setAttribute('height','16');const use=document.createElementNS('http://www.w3.org/2000/svg','use');use.setAttribute('href','/assets/admin-icons.svg#'+name);svg.append(use);return svg;};
  const buttonIcons={'Visuellen Editor öffnen':'layers','HTML anzeigen / ausblenden':'code','Rückgängig':'undo','Wiederholen':'forward','Desktop':'desktop','Tablet':'tablet','Mobil':'mobile','Bilder':'image','Nach oben':'arrow-up','Nach unten':'arrow-down','Duplizieren':'copy','Entfernen':'delete','Vollbild':'expand','Editor schließen':'close','Bausteine':'workspace','Struktur':'layers','Design':'sliders','Eigenschaften':'sliders'};
  const button = (label, fn) => {const b=document.createElement('button');b.type='button';if(buttonIcons[label])b.append(icon(buttonIcons[label]));b.append(document.createTextNode(t(label)));b.addEventListener('click',fn);return b;};
  function mount(field) {
    if(mounted.has(field)) return; mounted.add(field);
    const source=field.querySelector('textarea'); if(!source) return;
    const form=source.closest('form'); let editor=null, ready=false, dirty=false, busy=false, original=source.value, full=false;
    let previousOverflow='', submitting=false, recovery=null, liveComponent=null;
    const controls=document.createElement('div'); controls.className='pm-editor-controls';
    const status=document.createElement('span'); status.className='pm-editor-status'; status.setAttribute('role','status');
    const frame=document.createElement('div'); frame.className='pm-builder';frame.hidden=true;
    const toolbar=document.createElement('div');toolbar.className='pm-builder-toolbar';
    const workspace=document.createElement('div');workspace.className='pm-builder-workspace';
    const canvas=document.createElement('div');canvas.className='pm-builder-canvas';
    const sidebar=document.createElement('aside');sidebar.className='pm-builder-sidebar';sidebar.setAttribute('aria-label',t('Editor-Werkzeuge'));
    const tabs=document.createElement('div');tabs.className='pm-builder-tabs'; tabs.setAttribute('role','tablist');
    const panels={};
    ['Bausteine','Struktur','Design','Eigenschaften'].forEach((label,index)=>{
      const panel=document.createElement('div');panel.className='pm-builder-panel';panel.hidden=index!==0;panels[label]=panel;
      const tab=button(label,()=>{Object.entries(panels).forEach(([key,value])=>value.hidden=key!==label);[...tabs.children].forEach(el=>el.setAttribute('aria-selected',String(el===tab)));});
      tab.setAttribute('role','tab');tab.setAttribute('aria-selected',String(index===0));tabs.append(tab);
    });
    sidebar.append(tabs,...Object.values(panels));workspace.append(canvas,sidebar);frame.append(toolbar,workspace);controls.append(status);field.insertBefore(controls,source);field.append(frame);
    source.hidden=true;
    const tell=(text,error=false)=>{status.textContent=t(text);status.classList.toggle('pm-error',error);};
    function serialize() {
      if(!editor || !ready || !dirty) return;
      const css=editor.getCss();
      source.value=`<style data-care-cms-builder="true">${builderCss}\n${css.replace(/<\/style/gi,'<\\/style')}</style>\n${editor.getHtml()}`;
      source.dispatchEvent(new Event('change',{bubbles:true}));
    }
    const markDirty=()=>{if(!ready)return;dirty=true;recovery?.changed();tell('Ungespeicherte Änderungen – mit „Speichern“ übernehmen.');};
    const notice=document.createElement('p');notice.className='pm-builder-selection';notice.textContent=t('Text doppelt anklicken. Bausteine ziehen oder anklicken. Speichern über die Seiten-Schaltfläche.');
    sidebar.prepend(notice);
    function addBlock(block) {
      const root=editor.getWrapper();
      const mains=root.find('main'); const host=mains[0] || root;
      const inner=host.find('.entry-content');
      let target=inner[0]||host;
      const selected=editor.getSelected();
      const column=selected && [selected,...selected.parents()].find(c=>c.getClasses().includes('pm-builder-column'));
      if(column && block.id!=='column' && !['row1','row2','row3'].includes(block.id)) target=column;
      if(block.id==='column'){
        const row=selected && [selected,...selected.parents()].find(c=>c.getClasses().includes('pm-builder-row'));
        if(!row){tell('Wählen Sie zuerst eine Zeile für die neue Spalte aus.',true);return;}
        if(row.components().length>=3){tell('Diese Zeile hat bereits drei Spalten.',true);return;}
        target=row;
      }
      const added=target.append(block.get('content'));
      if(block.id==='column')target.setClass(target.getClasses().filter(c=>!/^pm-builder-cols-/.test(c)).concat('pm-builder-cols-'+target.components().length));
      if(added[0]){editor.select(added[0]);added[0].getEl()?.scrollIntoView({block:'center'});}
    }
    const contentEnglish=(field.dataset.contentLocale||'de-AT').startsWith('en');
    const contentText = text => contentEnglish ? (englishLabels[text] || text) : text;
    const row = n => `<section class="pm-builder-section"><div class="pm-builder-row pm-builder-cols-${n}">${Array.from({length:n},()=>'<div class="pm-builder-column"><p>Text hier bearbeiten …</p></div>').join('')}</div></section>`;
    async function start() {
      if(busy)return;
      source.hidden=true;frame.hidden=false;
      if(editor){editor.refresh();return;}
      if(!window.grapesjs){frame.hidden=true;source.hidden=false;tell('Editor konnte nicht geladen werden. HTML bleibt verfügbar.',true);recovery?.ready();return;}
      busy=true;frame.hidden=false;tell('Editor wird geladen …');
      try {
        const canvasResponse=await fetch(field.dataset.canvasUrl+'?page='+encodeURIComponent(field.dataset.pageId||'0'),{credentials:'same-origin'});
        if(!canvasResponse.ok)throw new Error('Canvas stylesheet configuration unavailable');
        const canvasAssets=await canvasResponse.json();
        const germanMessages=english ? {} : await fetch('/assets/vendor/grapesjs/de.json').then(response=>response.json());
        editor=window.grapesjs.init({
          container:canvas,height:'100%',width:'auto',storageManager:false,noticeOnUnload:false,
          i18n:{locale:english?'en':'de',localeFallback:'en',detectLocale:false,messages:{de:germanMessages}},
          panels:{defaults:[]},selectorManager:{componentFirst:true},
          canvas:{styles:canvasAssets.styles,scripts:[]},
          parser:{optionsHtml:{allowScripts:false,allowUnsafeAttr:false,allowUnsafeAttrValue:false}},
          blockManager:{appendTo:panels.Bausteine},layerManager:{appendTo:panels.Struktur},traitManager:{appendTo:panels.Eigenschaften},
          styleManager:{appendTo:panels.Design,sectors:[
            {name:t('Abstände & Größe'),open:true,buildProps:['display','width','max-width','min-height','margin','padding']},
            {name:t('Schrift & Farbe'),open:true,buildProps:['font-family','font-size','font-weight','line-height','letter-spacing','color','text-align']},
            {name:t('Hintergrund & Rahmen'),open:false,buildProps:['background-color','border','border-radius','box-shadow']},
          ]},
          deviceManager:{devices:[{id:'desktop',name:t('Desktop'),width:''},{id:'tablet',name:t('Tablet'),width:'768px',widthMedia:'992px'},{id:'mobile',name:t('Mobil'),width:'375px',widthMedia:'700px'}]},
          assetManager:{upload:field.dataset.uploadUrl,uploadName:'files',autoAdd:true,headers:{'X-CSRF-Token':field.dataset.uploadToken},uploadText:t('Bilder hierher ziehen oder auswählen (max. 5 MB)'),addBtnText:t('Bild-URL hinzufügen'),assets:[]},
          plugins:[ed=>{
            ed.DomComponents.addType('pm-heading',{extend:'text',isComponent:el=>/^H[1-6]$/.test(el.tagName)?{type:'pm-heading'}:false,model:{defaults:{name:t('Überschrift'),traits:[{type:'select',name:'tagName',label:t('Ebene'),changeProp:true,options:['h1','h2','h3','h4','h5','h6'].map(id=>({id,label:id.toUpperCase()}))}]}}});
            ed.DomComponents.addType('image',{model:{defaults:{traits:[{type:'text',name:'src',label:t('Bild-URL')},{type:'text',name:'alt',label:t('Alternativtext')},{type:'text',name:'title',label:t('Titel')}]}}});
            ed.DomComponents.addType('link',{model:{defaults:{traits:[{type:'text',name:'href',label:t('Link-Ziel')},{type:'select',name:'target',label:t('Öffnen'),options:[{id:'',label:t('Gleiches Fenster')},{id:'_blank',label:t('Neues Fenster')}]},{type:'text',name:'title',label:t('Titel')}]}}});
          }],
        });
        const blocks=[['row1','1 Spalte',row(1),'Layout'],['row2','2 Spalten',row(2),'Layout'],['row3','3 Spalten',row(3),'Layout'],['column','Spalte','<div class="pm-builder-column"><p>Spalteninhalt</p></div>','Layout'],['heading','Überschrift','<h2>Neue Überschrift</h2>','Inhalt'],['text','Text','<p>Hier steht Ihr Text. Doppelt anklicken zum Bearbeiten.</p>','Inhalt'],['image','Bild',{type:'image',attributes:{class:'pm-builder-image',alt:''}},'Inhalt'],['button','Button','<a class="pm-builder-button" href="/contact/">Jetzt anfragen</a>','Inhalt'],['list','Liste','<ul><li>Erster Punkt</li><li>Zweiter Punkt</li></ul>','Inhalt'],['divider','Trennlinie','<hr>','Inhalt'],['spacer','Abstand','<div class="pm-builder-spacer"></div>','Layout']];
        blocks.forEach(([id,label,content,category])=>{
          if(typeof content==='string'){
            ['Text hier bearbeiten …','Spalteninhalt','Neue Überschrift','Hier steht Ihr Text. Doppelt anklicken zum Bearbeiten.','Jetzt anfragen','Erster Punkt','Zweiter Punkt'].forEach(text=>content=content.replaceAll(text,contentText(text)));
          }
          const mediaIcons={row1:'row',row2:'columns',row3:'columns',column:'columns',heading:'heading',text:'text',image:'image',button:'link',list:'list',divider:'minus',spacer:'space'};
          editor.Blocks.add(id,{label:t(label),content,category:t(category),media:'<svg aria-hidden="true" width="24" height="24"><use href="/assets/admin-icons.svg#'+mediaIcons[id]+'"/></svg>'});
        });
        editor.on('block:click',addBlock);
        editor.on('asset:upload:error',()=>tell('Bild konnte nicht hochgeladen werden. Sitzung, Dateiformat und Größe prüfen.',true));
        editor.on('asset:upload:end',()=>tell('Bild-Upload abgeschlossen. Wählen Sie ein Bild aus.'));
        editor.on('component:selected',component=>{notice.textContent=t('Ausgewählt: {name}. Verschieben, duplizieren oder entfernen über die Element-Werkzeuge. Design und Eigenschaften rechts.').replace('{name}',component.getName());});
        editor.on('load',()=>{
          editor.setComponents(source.value || '<main id="main"><section class="pm-builder-section"><h1>'+contentText('Neue Seite')+'</h1><p>'+contentText('Text hier bearbeiten.')+'</p></section></main>');
          editor.addStyle(builderCss);
          if(canvasAssets.css){const style=editor.Canvas.getDocument().createElement('style');style.textContent=canvasAssets.css;editor.Canvas.getDocument().head.appendChild(style);}
          const body=editor.Canvas.getBody();body.className=field.dataset.bodyClass || '';editor.Canvas.getDocument().documentElement.lang=field.dataset.contentLocale||'de-AT';body.addEventListener('submit',e=>e.preventDefault());
          const main=editor.getWrapper().find('main')[0];if(main)main.set({draggable:false,removable:false,copyable:false});
          ready=true;busy=false;dirty=false;editor.UndoManager.clear();editor.clearDirtyCount();tell('Bereit. Vorhandene Inhalte wurden noch nicht verändert.');
          ['component:add','component:remove','component:update','style:property:update','styleable:change','undo','redo'].forEach(event=>editor.on(event,markDirty));
          editor.on('component:input',component=>{liveComponent=component;markDirty();});
          editor.on('rte:disable',()=>{liveComponent=null;});
          recovery?.ready();
        });
        fetch(field.dataset.mediaUrl,{credentials:'same-origin'}).then(res=>res.ok?res.json():Promise.reject()).then(data=>editor.AssetManager.add(data.data)).catch(()=>tell('Editor bereit. Medienliste konnte nicht geladen werden.',true));
      } catch(error){busy=false;frame.hidden=true;source.hidden=false;recovery?.ready();tell('Editor konnte nicht gestartet werden. HTML bleibt verfügbar.',true);console.error('Page editor initialization failed',error);}
    }
    const modeButton=button('HTML anzeigen / ausblenden',()=>{
      if(busy)return;
      if(source.hidden){serialize();frame.hidden=true;source.hidden=false;full=false;frame.classList.remove('pm-builder-fullscreen');document.body.style.overflow=previousOverflow;source.focus();}
      else start();
      updateModeButton();
    });
    modeButton.classList.add('pm-editor-mode-toggle');
    function updateModeButton(){
      const htmlMode=!source.hidden;
      modeButton.replaceChildren(icon(htmlMode?'layers':'code'),document.createTextNode(htmlMode?(english?'Visual editor':'Visueller Editor'):(english?'Show HTML':'HTML anzeigen')));
      modeButton.setAttribute('aria-pressed',String(htmlMode));
    }
    controls.insertBefore(modeButton,status);updateModeButton();
    toolbar.append(button('Rückgängig',()=>editor?.UndoManager.undo()),button('Wiederholen',()=>editor?.UndoManager.redo()));
    ['desktop','tablet','mobile'].forEach(id=>toolbar.append(button({desktop:'Desktop',tablet:'Tablet',mobile:'Mobil'}[id],()=>editor?.Devices.select(id))));
    toolbar.append(button('Bilder',()=>editor?.runCommand('open-assets')));
    function moveSelected(direction){
      const selected=editor?.getSelected();if(!selected || selected.get('draggable')===false)return;
      const parent=selected.parent();if(!parent)return;
      const index=parent.components().indexOf(selected), next=index+direction;
      if(next>=0&&next<parent.components().length)selected.move(parent,{at:next});
    }
    toolbar.append(button('Nach oben',()=>moveSelected(-1)),button('Nach unten',()=>moveSelected(1)));
    toolbar.append(button('Duplizieren',()=>{const selected=editor?.getSelected();if(selected&&selected.get('copyable')!==false){const parent=selected.parent();if(parent){const clone=selected.clone();parent.append(clone,{at:parent.components().indexOf(selected)+1});editor.select(clone);}}}));
    toolbar.append(button('Entfernen',()=>{const selected=editor?.getSelected();if(selected&&selected.get('removable')!==false)selected.remove();}));
    toolbar.append(button('Vollbild',()=>{full=!full;frame.classList.toggle('pm-builder-fullscreen',full);if(full){previousOverflow=document.body.style.overflow;document.body.style.overflow='hidden';}else document.body.style.overflow=previousOverflow;editor?.refresh();}));
    // Reuse the real EasyAdmin actions, preserving validation, CSRF and redirect behaviour.
    const saveActions=document.createElement('div');saveActions.className='pm-builder-save-actions';
    ['saveAndContinue','saveAndReturn'].forEach(action=>{
      const originalButton=[...document.querySelectorAll(`button[data-action-name="${action}"]`)].find(candidate=>candidate.form===form);
      if(!originalButton)return;
      const save=button(originalButton.textContent.trim(),()=>{
        if(originalButton.disabled||busy)return;
        serialize();originalButton.click();
      });
      save.dataset.saveAction=action;
      saveActions.append(save);
    });
    toolbar.append(saveActions);
    // Required fields elsewhere on the page must remain reachable after failed validation.
    form?.addEventListener('invalid',()=>{
      if(!full)return;
      full=false;frame.classList.remove('pm-builder-fullscreen');document.body.style.overflow=previousOverflow;editor?.refresh();
    },true);
    
    source.addEventListener('input',()=>{if(editor&&ready){ready=false;editor.setComponents(source.value);editor.addStyle(builderCss);ready=true;}dirty=true;tell('Ungespeicherte HTML-Änderungen.');});
    form?.addEventListener('submit',event=>{if(busy){event.preventDefault();tell('Bitte warten Sie, bis der Editor geladen ist.',true);return;}serialize();submitting=true;});
    recovery=window.PracticeDraftRecovery?.mount(form,field,{
      serialize,canRestore:()=>!busy,
      live:()=>{if(!liveComponent||!editor||!liveComponent.getEl()?.isContentEditable)return null;const path=[];let component=liveComponent;while(component&&component!==editor.getWrapper()){const parent=component.parent();if(!parent)return null;path.unshift(parent.components().indexOf(component));component=parent;}return {path,html:liveComponent.getEl().innerHTML};},
      apply:live=>{source.dispatchEvent(new Event('input',{bubbles:true}));if(editor&&ready&&live&&Array.isArray(live.path)&&typeof live.html==='string'){let component=editor.getWrapper();for(const index of live.path){component=component?.components().at(index);if(!component)break;}component?.components(live.html);}liveComponent=null;dirty=true;}
    });
    if(!recovery)window.addEventListener('beforeunload',event=>{if(dirty&&!submitting){event.preventDefault();event.returnValue='';}});

    start().finally(updateModeButton);
  }
  function initialize(){document.querySelectorAll('[data-pm-editor]').forEach(mount);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initialize);else initialize();
  document.addEventListener('turbo:load',initialize);
})();
