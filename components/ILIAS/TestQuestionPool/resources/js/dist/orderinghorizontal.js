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
!function(e){"use strict";const t="{::}",n="answers",o="c-test__dropzone";let s;function r(){const e=[],r=s.querySelector(`.${o}`);s.querySelectorAll(`.${n}`).forEach((e=>{e.previousElementSibling?.classList.contains(o)||e.parentNode.insertBefore(r.cloneNode(),e),e.nextElementSibling?.classList.contains(o)||e.parentNode.insertBefore(r.cloneNode(),e.nextElementSibling)})),s.querySelectorAll(`.${o} + .${o}`).forEach((e=>{e.remove()})),s.querySelectorAll(`.${n} > div > span`).forEach((t=>{e.push(t.textContent)})),s.nextElementSibling.value=e.join(t)}function i(e){e.previousElementSibling?.classList.contains(o)&&e.previousElementSibling.remove(),e.nextElementSibling?.classList.contains(o)&&e.nextElementSibling.remove()}function l(e,t){s=e,function(){const e=s.querySelectorAll(`.${n}`);let t=0;e.forEach((e=>{t+=e.offsetWidth})),s.querySelectorAll(`.${o}`).forEach((n=>{n.style.width=t/e.length+"px",n.style.height=`${e.item(0).offsetHeight}px`}))}(),t("move",s,n,o,r,i)}const c="c-test__dropzone--active",a="c-test__dropzone--hover";let d,f,u,h,v,g,m,p,E;function L(e){setTimeout((()=>{S(e.target),e.dataTransfer.dropEffect=d,e.dataTransfer.effectAllowed=d,e.dataTransfer.setDragImage(m,0,0)}),0)}function y(e){e.preventDefault(),e.stopPropagation(),S(e.target.closest(`.${u}`));const t=m.offsetWidth,n=m.offsetHeight;p=m.cloneNode(!0),m.parentNode.insertBefore(p,m),m.style.position="fixed",m.style.left=e.touches[0].clientX-t/2+"px",m.style.top=e.touches[0].clientY-n/2+"px",m.style.width=`${t}px`,m.style.height=`${n}px`,m.addEventListener("touchmove",$),m.addEventListener("touchend",N)}function S(e){m=e,m.style.opacity=.5,g(m),f.querySelectorAll(`.${h}`).forEach((e=>{w(e),e.classList.add(c)})),m.querySelectorAll(`.${h}`).forEach((e=>{e.classList.remove(c)}))}function $(e){e.preventDefault(),m.style.left=e.touches[0].clientX-m.offsetWidth/2+"px",m.style.top=e.touches[0].clientY-m.offsetHeight/2+"px";const{documentElement:t}=f.ownerDocument;e.touches[0].clientY>.8*t.clientHeight&&t.scroll({left:0,top:.8*e.touches[0].pageY,behavior:"smooth"}),e.touches[0].clientY<.2*t.clientHeight&&t.scroll({left:0,top:.8*e.touches[0].pageY,behavior:"smooth"});const n=f.ownerDocument.elementsFromPoint(e.changedTouches[0].clientX,e.changedTouches[0].clientY).filter((e=>e.classList.contains(h)));0===n.length&&void 0!==E&&(E.classList.remove(a),E=void 0),1===n.length&&E!==n[0]&&(void 0!==E&&E.classList.remove(a),[E]=n,E.classList.add(a))}function x(e){e.preventDefault()}function A(e){e.target.classList.add(a)}function b(e){e.target.classList.remove(a)}function q(){m.removeAttribute("style"),f.querySelectorAll(`.${h}`).forEach((e=>{e.classList.remove(c),e.classList.remove(a)}))}function D(e){e.preventDefault(),T(e.target)}function N(e){e.preventDefault();const t=f.ownerDocument.elementsFromPoint(e.changedTouches[0].clientX,e.changedTouches[0].clientY).filter((e=>e.classList.contains(h)));q(),p.remove(),1===t.length&&T(t[0])}function T(e){let t=m;"move"!==d&&(t=m.cloneNode(!0),t.style.opacity=null,Y(t)),e.parentNode.insertBefore(t,e),v(t,e,m)}function Y(e){e.addEventListener("dragstart",L),e.addEventListener("dragend",q),e.addEventListener("touchstart",y)}function w(e){e.removeEventListener("dragover",x),e.removeEventListener("dragenter",A),e.removeEventListener("dragleave",b),e.removeEventListener("drop",D),e.addEventListener("dragover",x),e.addEventListener("dragenter",A),e.addEventListener("dragleave",b),e.addEventListener("drop",D)}function z(e,t,n,o,s,r){d=e,f=t,u=n,h=o,v=s,g=r,f.querySelectorAll(`.${u}`).forEach(Y),f.querySelectorAll(`.${h}`).forEach(w)}e.test=e.test||{},e.test.orderinghorizontal=e.test.orderinghorizontal||{},e.test.orderinghorizontal.init=e=>l(e,z)}(il);
