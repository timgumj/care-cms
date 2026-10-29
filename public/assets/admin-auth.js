"use strict";
const english=document.documentElement.lang.startsWith('en');
document.querySelectorAll('[data-toggle-password]').forEach(button=>{
 const input=document.getElementById(button.getAttribute('aria-controls'));if(!input)return;
 const render=show=>{button.setAttribute('aria-pressed',String(show));button.setAttribute('aria-label',english?(show?'Hide password':'Show password'):(show?'Passwort verbergen':'Passwort anzeigen'));const svg=document.createElementNS('http://www.w3.org/2000/svg','svg');svg.classList.add('studio-icon');svg.setAttribute('aria-hidden','true');svg.setAttribute('width','16');svg.setAttribute('height','16');const use=document.createElementNS('http://www.w3.org/2000/svg','use');use.setAttribute('href','/assets/admin-icons.svg#'+(show?'eye-off':'eye'));svg.append(use);button.replaceChildren(svg,document.createTextNode(english?(show?'Hide':'Show'):(show?'Verbergen':'Anzeigen')));};
 render(false);button.addEventListener('click',()=>{const show=input.type==='password';input.type=show?'text':'password';render(show);});
});
