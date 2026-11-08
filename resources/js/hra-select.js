const colors={Unassigned:'neutral',Low:'low',Medium:'medium',High:'high',Urgent:'urgent',Draft:'neutral','In progress':'progress',Reviewed:'done',Open:'neutral',Done:'done'};
function enhance(){
 for(const select of document.querySelectorAll('#hraEditor select[data-styled]'))select.refreshStyled?.();
 for(const select of document.querySelectorAll('#hraEditor select:not([data-styled])')){
  select.dataset.styled='true';select.hidden=true;
  const menu=document.createElement('details');menu.className='hra-select-menu';
  const trigger=document.createElement('summary');trigger.setAttribute('aria-label',select.getAttribute('aria-label')||select.previousElementSibling?.textContent||'Choose option');
  const text=document.createElement('span'),arrow=document.createElement('span');arrow.textContent='⌄';arrow.className='hra-select-chevron';trigger.append(text,arrow);menu.append(trigger);
  const options=document.createElement('div');options.className='hra-select-options';menu.append(options);
  function sync(){const value=select.selectedOptions[0]?.textContent||'';if(text.textContent!==value)text.textContent=value;const isStatus=select.id==='hraStatus'||select.dataset.field==='status'||select.getAttribute('data-item-field')==='status'||select.previousElementSibling?.textContent.trim()==='Status';const cls='hra-value hra-tone-'+(colors[select.value]||'neutral');if(text.className!==cls)text.className=cls;const disabled=String(select.disabled);if(trigger.getAttribute('aria-disabled')!==disabled)trigger.setAttribute('aria-disabled',disabled);}
  for(const option of select.options){const button=document.createElement('button');button.type='button';button.textContent=option.textContent;button.className='hra-value hra-tone-'+(colors[option.value]||'neutral');button.addEventListener('click',()=>{if(select.disabled)return;select.value=option.value;select.dispatchEvent(new Event('change',{bubbles:true}));sync();menu.open=false;trigger.focus();});options.append(button);}
  trigger.addEventListener('click',event=>{sync();if(select.disabled)event.preventDefault();});menu.addEventListener('keydown',event=>{if(event.key==='Escape'){menu.open=false;trigger.focus();}if(['ArrowDown','ArrowUp'].includes(event.key)){event.preventDefault();const buttons=[...options.children];let index=buttons.indexOf(document.activeElement);buttons[(index+(event.key==='ArrowDown'?1:-1)+buttons.length)%buttons.length].focus();}});
  select.refreshStyled=sync;select.addEventListener('change',sync);select.after(menu);sync();
 }
}
enhance();new MutationObserver(enhance).observe(document.getElementById('hraEditor'),{childList:true,subtree:true});
document.addEventListener('click',event=>{for(const menu of document.querySelectorAll('.hra-select-menu[open]'))if(!menu.contains(event.target))menu.open=false;});
