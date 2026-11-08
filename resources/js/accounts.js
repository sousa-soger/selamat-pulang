import { createIcons, icons } from 'lucide';
import './sidebar.js';
import '../css/adapt360.css';
import '../css/auralis.css';
createIcons({icons});
const menu = document.querySelector('[data-menu]');
const closeMenu = () => { document.body.classList.remove('sidebar-open'); menu?.setAttribute('aria-expanded','false'); };
menu?.addEventListener('click',()=>{const open=document.body.classList.toggle('sidebar-open');menu.setAttribute('aria-expanded',String(open));});
document.querySelector('[data-menu-close]')?.addEventListener('click',closeMenu);
document.addEventListener('keydown',event=>{if(event.key==='Escape')closeMenu();});
import './member-search.js';

window.dispatchEvent(new Event('adapt:ready'));
