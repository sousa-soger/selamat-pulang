import { createIcons, icons } from 'lucide';
import '../css/adapt360.css';
import '../css/auralis.css';
createIcons({icons});
const menu = document.querySelector('[data-menu]');
const closeMenu = () => { document.body.classList.remove('sidebar-open'); menu?.setAttribute('aria-expanded','false'); };
menu?.addEventListener('click',()=>{const open=document.body.classList.toggle('sidebar-open');menu.setAttribute('aria-expanded',String(open));});
document.querySelector('[data-menu-close]')?.addEventListener('click',closeMenu);
document.addEventListener('keydown',event=>{if(event.key==='Escape')closeMenu();});
for(const root of document.querySelectorAll('[data-member-search]')) {
 const input=root.querySelector('[role=combobox]'),email=root.querySelector('[data-member-email]'),list=root.querySelector('[role=listbox]'),status=root.querySelector('[role=status]'),selected=root.querySelector('[data-member-selected]'),submit=root.closest('form').querySelector('[data-add-member]');
 let timer,controller,results=[],active=-1,sequence=0;
 function close(){list.hidden=true;input.setAttribute('aria-expanded','false');input.removeAttribute('aria-activedescendant');active=-1;}
 function choose(user){email.value=user.email;input.value=user.name;selected.replaceChildren();const name=document.createElement('strong');name.textContent=user.name;const detail=document.createElement('span');detail.textContent=user.email+(user.mykkp_id?' · MyKKP '+user.mykkp_id:'');selected.append(name,detail);selected.hidden=false;submit.disabled=false;status.textContent='Profile selected. Ready to add to this organization.';close();}
 function highlight(index){active=index;[...list.children].forEach((item,i)=>item.setAttribute('aria-selected',String(i===active)));if(active>=0){input.setAttribute('aria-activedescendant',list.children[active].id);list.children[active].scrollIntoView({block:'nearest'});}}
 input.addEventListener('input',()=>{
 clearTimeout(timer);controller?.abort();const requestSequence=++sequence;email.value='';selected.hidden=true;submit.disabled=true;close();const query=input.value.trim();
 if(query.length<2){status.textContent='Enter at least 2 characters to find a profile.';return;}
 status.textContent='Searching profiles…';
 timer=setTimeout(async()=>{controller=new AbortController();try{const response=await fetch(root.dataset.url+'?q='+encodeURIComponent(query),{signal:controller.signal,headers:{Accept:'application/json'}});if(!response.ok)throw new Error();const users=await response.json();if(requestSequence!==sequence)return;results=users;list.replaceChildren();for(const [index,user] of users.entries()){const option=document.createElement('div');option.className='member-result';option.id='member-option-'+index;option.setAttribute('role','option');option.setAttribute('aria-selected','false');const avatar=document.createElement('span');avatar.className='member-result-avatar';avatar.textContent=user.name.trim().slice(0,1).toUpperCase();const copy=document.createElement('span');copy.className='member-result-copy';const name=document.createElement('strong');name.textContent=user.name;const detail=document.createElement('small');detail.textContent=user.email;copy.append(name,detail);if(user.mykkp_id){const id=document.createElement('small');id.className='member-result-id';id.textContent='MyKKP '+user.mykkp_id;copy.append(id);}option.append(avatar,copy);option.addEventListener('mousedown',event=>event.preventDefault());option.addEventListener('click',()=>choose(user));list.append(option);}list.hidden=!users.length;input.setAttribute('aria-expanded',String(!!users.length));status.textContent=users.length?users.length+' matching profiles. Select a person below.':'No matching profiles. Ask them to register a free profile first.';}catch(error){if(error.name!=='AbortError'&&requestSequence===sequence){close();status.textContent='Search is unavailable. Please try again.';}}},250);
 });
 input.addEventListener('keydown',event=>{if(event.key==='Escape'){close();return;}if(list.hidden)return;if(event.key==='ArrowDown'||event.key==='ArrowUp'){event.preventDefault();highlight((active+(event.key==='ArrowDown'?1:-1)+results.length)%results.length);}if(event.key==='Enter'&&active>=0){event.preventDefault();choose(results[active]);}});
 input.addEventListener('blur',close);
 root.closest('form').addEventListener('submit',event=>{if(!email.value){event.preventDefault();input.focus();status.textContent='Select a matching profile before adding a member.';}});
}
