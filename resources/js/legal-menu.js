// Put legal status menus in the browser top layer, outside scrolling table ancestors.
let activeLegalMenu = null;
function closeLegalMenu() {
 if(!activeLegalMenu)return;
 const {menu,details}=activeLegalMenu;
 if(menu.matches(':popover-open'))menu.hidePopover();
 details.append(menu);details.open=false;activeLegalMenu=null;
}
document.addEventListener('click',event=>{
 const summary=event.target.closest('.legal-status-menu>summary');
 if(summary){
  event.preventDefault();
  const details=summary.parentElement;
  if(activeLegalMenu?.details===details){closeLegalMenu();return;}
  closeLegalMenu();details.open=true;
  const menu=details.querySelector('.legal-status-options');
  menu.setAttribute('popover','manual');document.body.append(menu);menu.showPopover();
  const rect=summary.getBoundingClientRect(),width=Math.max(190,rect.width);
  menu.style.width=width+'px';menu.style.left=Math.max(8,Math.min(rect.right-width,innerWidth-width-8))+'px';
  menu.style.top=(rect.bottom+8+menu.offsetHeight>innerHeight?Math.max(8,rect.top-menu.offsetHeight-8):rect.bottom+8)+'px';
  activeLegalMenu={menu,details,summary};
  menu.querySelector('[aria-pressed=true]')?.focus();return;
 }
 if(activeLegalMenu && (!activeLegalMenu.menu.contains(event.target)||event.target.closest('button')))closeLegalMenu();
});
document.addEventListener('keydown',event=>{if(event.key==='Escape'&&activeLegalMenu){const trigger=activeLegalMenu.summary;closeLegalMenu();trigger.focus();}});
window.addEventListener('resize',closeLegalMenu);
window.addEventListener('scroll',event=>{if(activeLegalMenu&&!activeLegalMenu.menu.contains(event.target))closeLegalMenu();},true);
