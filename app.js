document.querySelectorAll('.save-btn').forEach(button=>button.addEventListener('click',event=>{event.preventDefault();event.stopPropagation();button.classList.toggle('saved');button.textContent=button.classList.contains('saved')?'♥':'♡'}));
const menu=document.querySelector('.menu-toggle');
const nav=document.getElementById('primary-navigation');
if(menu&&nav){
  const setMenuOpen=open=>{
    nav.classList.toggle('mobile-open',open);
    menu.setAttribute('aria-expanded',String(open));
    menu.setAttribute('aria-label',open?'Close navigation':'Open navigation');
    const icon=menu.querySelector('span');
    if(icon)icon.textContent=open?'×':'☰';
  };
  menu.addEventListener('click',()=>setMenuOpen(menu.getAttribute('aria-expanded')!=='true'));
  nav.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>setMenuOpen(false)));
  document.addEventListener('click',event=>{if(menu.getAttribute('aria-expanded')==='true'&&!nav.contains(event.target)&&!menu.contains(event.target))setMenuOpen(false)});
  document.addEventListener('keydown',event=>{if(event.key==='Escape'&&menu.getAttribute('aria-expanded')==='true'){setMenuOpen(false);menu.focus()}});
  window.addEventListener('resize',()=>{if(window.innerWidth>640)setMenuOpen(false)});
}


// Room photo viewer: click a gallery image, then navigate by buttons, keys, or touch swipe.
const galleryButtons=[...document.querySelectorAll('.gallery-trigger')];
if(galleryButtons.length){
  let galleryIndex=0;
  let lastFocused=null;
  let touchStartX=0;
  const dialog=document.createElement('div');
  dialog.className='photo-viewer';
  dialog.setAttribute('role','dialog');
  dialog.setAttribute('aria-modal','true');
  dialog.setAttribute('aria-label','Room photo viewer');
  dialog.hidden=true;
  dialog.innerHTML='<button class="viewer-close" type="button" aria-label="Close photo viewer">×</button><button class="viewer-arrow viewer-prev" type="button" aria-label="Previous photo">‹</button><figure class="viewer-content"><img class="viewer-image" alt=""><figcaption class="viewer-count"></figcaption></figure><button class="viewer-arrow viewer-next" type="button" aria-label="Next photo">›</button>';
  document.body.append(dialog);
  const viewerImage=dialog.querySelector('.viewer-image');
  const viewerCount=dialog.querySelector('.viewer-count');
  const showPhoto=index=>{
    galleryIndex=(index+galleryButtons.length)%galleryButtons.length;
    const source=galleryButtons[galleryIndex].querySelector('img');
    viewerImage.src=source.currentSrc||source.src;
    viewerImage.alt=source.alt;
    viewerCount.textContent=`${galleryIndex+1} / ${galleryButtons.length}`;
  };
  const closeViewer=()=>{dialog.hidden=true;document.body.classList.remove('viewer-open');if(lastFocused)lastFocused.focus()};
  galleryButtons.forEach((button,index)=>button.addEventListener('click',event=>{event.preventDefault();lastFocused=button;showPhoto(index);dialog.hidden=false;document.body.classList.add('viewer-open');dialog.querySelector('.viewer-close').focus()}));
  dialog.querySelector('.viewer-close').addEventListener('click',closeViewer);
  dialog.querySelector('.viewer-prev').addEventListener('click',()=>showPhoto(galleryIndex-1));
  dialog.querySelector('.viewer-next').addEventListener('click',()=>showPhoto(galleryIndex+1));
  dialog.addEventListener('click',event=>{if(event.target===dialog)closeViewer()});
  dialog.addEventListener('touchstart',event=>{touchStartX=event.changedTouches[0].screenX},{passive:true});
  dialog.addEventListener('touchend',event=>{const delta=event.changedTouches[0].screenX-touchStartX;if(Math.abs(delta)>45)showPhoto(galleryIndex+(delta<0?1:-1))},{passive:true});
  document.addEventListener('keydown',event=>{if(dialog.hidden)return;if(event.key==='Escape')closeViewer();if(event.key==='ArrowRight')showPhoto(galleryIndex+1);if(event.key==='ArrowLeft')showPhoto(galleryIndex-1)});
}
