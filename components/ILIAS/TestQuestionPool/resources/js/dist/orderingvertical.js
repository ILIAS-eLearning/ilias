/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 */
!function(e){"use strict";const t='[name*="[position]"',n='[name*="[indentation]"',o="dd-item",r="c-test__dropzone";let l;function s(e,r){!function(e,t){let r=0,s=t.parentElement.parentElement;for(;s!==l;)s=s.parentElement.parentElement,r+=1;e.querySelector(n).value=r,e.querySelectorAll(`.${o}`).forEach((e=>{r+=1,e.querySelector(n).value=r}))}(e,r),function(){let e=0;l.querySelectorAll(`.${o}`).forEach((n=>{n.querySelector(t).value=e,e+=1}))}()}function i(e){!function(){const e=l.querySelector(`.${r}`);l.querySelectorAll(`.${o}`).forEach((t=>{t.previousElementSibling?.classList.contains(r)||t.parentNode.insertBefore(e.cloneNode(),t),t.nextElementSibling?.classList.contains(r)||t.parentNode.insertBefore(e.cloneNode(),t.nextElementSibling)})),l.querySelectorAll(`.${r} + .${r}`).forEach((e=>{e.remove()}))}(),e.previousElementSibling?.classList.contains(r)&&e.previousElementSibling.remove(),e.nextElementSibling?.classList.contains(r)&&e.nextElementSibling.remove()}function c(e,t){l=e,function(){const e=l.querySelectorAll(`.${o}`);let t=0;e.forEach((e=>{e.offsetHeight<t&&(t=e.offsetHeight)})),l.querySelectorAll(`.${r}`).forEach((t=>{t.style.height=`${e.item(0).offsetHeight}px`}))}(),t("move",l,o,r,s,i)}const a="c-test__dropzone--active",d="c-test__dropzone--hover";let f,u,v,h,m,g,p,E,y;function L(e){setTimeout((()=>{$(e.target),e.dataTransfer.dropEffect=f,e.dataTransfer.effectAllowed=f,e.dataTransfer.setDragImage(p,0,0)}),0)}function S(e){e.preventDefault(),e.stopPropagation(),$(e.target.closest(`.${v}`));const t=p.offsetWidth,n=p.offsetHeight;E=p.cloneNode(!0),p.parentNode.insertBefore(E,p),p.style.position="fixed",p.style.left=e.touches[0].clientX-t/2+"px",p.style.top=e.touches[0].clientY-n/2+"px",p.style.width=`${t}px`,p.style.height=`${n}px`,p.addEventListener("touchmove",q),p.addEventListener("touchend",T)}function $(e){p=e,p.style.opacity=.5,g(p),u.querySelectorAll(`.${h}`).forEach((e=>{_(e),e.classList.add(a)})),p.querySelectorAll(`.${h}`).forEach((e=>{e.classList.remove(a)}))}function q(e){e.preventDefault(),p.style.left=e.touches[0].clientX-p.offsetWidth/2+"px",p.style.top=e.touches[0].clientY-p.offsetHeight/2+"px";const{documentElement:t}=u.ownerDocument;e.touches[0].clientY>.8*t.clientHeight&&t.scroll({left:0,top:.8*e.touches[0].pageY,behavior:"smooth"}),e.touches[0].clientY<.2*t.clientHeight&&t.scroll({left:0,top:.8*e.touches[0].pageY,behavior:"smooth"});const n=u.ownerDocument.elementsFromPoint(e.changedTouches[0].clientX,e.changedTouches[0].clientY).filter((e=>e.classList.contains(h)));0===n.length&&void 0!==y&&(y.classList.remove(d),y=void 0),1===n.length&&y!==n[0]&&(void 0!==y&&y.classList.remove(d),[y]=n,y.classList.add(d))}function A(e){e.preventDefault()}function x(e){e.target.classList.add(d)}function b(e){e.target.classList.remove(d)}function D(){p.removeAttribute("style"),u.querySelectorAll(`.${h}`).forEach((e=>{e.classList.remove(a),e.classList.remove(d)}))}function N(e){e.preventDefault(),Y(e.target)}function T(e){e.preventDefault();const t=u.ownerDocument.elementsFromPoint(e.changedTouches[0].clientX,e.changedTouches[0].clientY).filter((e=>e.classList.contains(h)));D(),E.remove(),1===t.length&&Y(t[0])}function Y(e){const t=p.parentNode;let n=p;"move"!==f&&(n=p.cloneNode(!0),n.style.opacity=null,H(n)),e.parentNode.insertBefore(n,e),m(n,e,p,t)}function H(e){e.addEventListener("dragstart",L),e.addEventListener("dragend",D),e.addEventListener("touchstart",S)}function _(e){e.removeEventListener("dragover",A),e.removeEventListener("dragenter",x),e.removeEventListener("dragleave",b),e.removeEventListener("drop",N),e.addEventListener("dragover",A),e.addEventListener("dragenter",x),e.addEventListener("dragleave",b),e.addEventListener("drop",N)}function w(e,t,n,o,r,l){f=e,u=t,v=n,h=o,m=r,g=l,u.querySelectorAll(`.${v}`).forEach(H),u.querySelectorAll(`.${h}`).forEach(_)}e.test=e.test||{},e.test.orderingvertical=e.test.orderingvertical||{},e.test.orderingvertical.init=(e,t)=>c(e,w)}(il);
