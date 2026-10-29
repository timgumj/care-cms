// Change this public address to your own CMS. Never put server secrets in frontend code.
const CMS_BASE='http://127.0.0.1:8020';
async function load(locale){
 const status=document.getElementById('status');status.textContent='Loading…';
 try{
  const response=await fetch(`${CMS_BASE}/api/v1/pages?locale=${encodeURIComponent(locale)}`,{credentials:'omit'});
  if(!response.ok)throw new Error('Content unavailable');
  const data=await response.json();const container=document.getElementById('pages');container.replaceChildren();
  for(const page of data.items){
   const article=document.createElement('article');const title=document.createElement('h2');title.textContent=page.title;
   const description=document.createElement('p');description.textContent=page.description;
   const link=document.createElement('a');link.textContent=locale==='de-AT'?'Seite ansehen →':'View page →';link.href=new URL(page.path,CMS_BASE).href;
   article.append(title,description,link);container.append(article);
  }
  status.textContent=`${data.items.length} published items loaded.`;
 }catch(error){status.textContent='Start the CMS on port 8020 and allow this frontend origin in API_ALLOWED_ORIGIN.';}
}
document.getElementById('de').addEventListener('click',()=>load('de-AT'));
document.getElementById('en').addEventListener('click',()=>load('en-AT'));
load('en-AT');
