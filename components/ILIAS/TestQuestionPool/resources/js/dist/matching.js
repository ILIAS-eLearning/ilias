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
!function(e){"use strict";const t="1:1",n="n:n",o="sourceArea",s="ilMatchingQuestionTerm",l="c-test__definition",i="c-test__term",r="c-test__dropzone";let c,a,d;function u(e,t,l){c===n&&function(e,t,n){n.parentNode.classList.contains(s)&&n.remove(),t.parentNode.id===o&&e.remove()}(e,t,l)}function f(){a.querySelectorAll(`.${s}`).forEach((e=>{var n;c!==t?null!==(n=e).firstElementChild&&n.lastElementChild.classList.contains(r)||n.append(d.cloneNode()):function(e){const t=e.firstElementChild;null!==t?t.classList.contains(i)&&null!==t.nextElementSibling&&t.nextElementSibling.remove():e.prepend(d.cloneNode())}(e)}))}function h(e,t,n){!function(e,t,n){const s=t.dataset;if(n.parentNode.id===o){t.id=`${s.type}_${s.id}`;const n=e.closest(`.${l}`).querySelector("input"),o=JSON.parse(n.value),i=o.indexOf(s.id);return i>-1&&o.splice(i,1),void(n.value=JSON.stringify(o))}const i=n.closest(`.${l}`);t.id=`${i.id}_${s.type}_${s.id}`;const r=JSON.parse(i.querySelector("input").value);r.push(s.id),i.querySelector("input").value=JSON.stringify(r)}(n,e,t),u(e,t,n)}function v(e){f();const t=a.querySelector(`#${o}`);null!==t.firstElementChild&&t.firstElementChild.classList.contains(r)||t.prepend(d.cloneNode()),e.parentNode.querySelectorAll(`.${r}`).forEach((e=>{e.remove()})),c===n&&a.querySelectorAll(`.${s}`).forEach((t=>{null!==t.lastElementChild&&t.lastElementChild.classList.contains(r)||t.append(d.cloneNode()),null!==t.querySelector(`[data-id='${e.dataset.id}']`)&&t.querySelector(`.${r}`)?.remove()}))}function p(e,n,o){a=e,c=o,function(){const e=a.querySelectorAll(`.${i}`);let t=0;e.forEach((e=>{e.offsetHeight<t&&(t=e.offsetHeight)})),a.querySelectorAll(`.${r}`).forEach((t=>{t.style.height=`${e.item(0).offsetHeight}px`})),d=a.querySelector(`.${r}`)}(),n(c===t?"move":"copy",a,i,r,h,v)}const m="c-test__dropzone--active",g="c-test__dropzone--hover";let y,E,L,$,S,q,N,A,_;function x(e){setTimeout((()=>{T(e.target),e.dataTransfer.dropEffect=y,e.dataTransfer.effectAllowed=y,e.dataTransfer.setDragImage(N,0,0)}),0)}function D(e){e.preventDefault(),e.stopPropagation(),T(e.target.closest(`.${L}`));const t=N.offsetWidth,n=N.offsetHeight;A=N.cloneNode(!0),N.parentNode.insertBefore(A,N),N.style.position="fixed",N.style.left=e.touches[0].clientX-t/2+"px",N.style.top=e.touches[0].clientY-n/2+"px",N.style.width=`${t}px`,N.style.height=`${n}px`,N.addEventListener("touchmove",Y),N.addEventListener("touchend",J)}function T(e){N=e,N.style.opacity=.5,q(N),E.querySelectorAll(`.${$}`).forEach((e=>{P(e),e.classList.add(m)})),N.querySelectorAll(`.${$}`).forEach((e=>{e.classList.remove(m)}))}function Y(e){e.preventDefault(),N.style.left=e.touches[0].clientX-N.offsetWidth/2+"px",N.style.top=e.touches[0].clientY-N.offsetHeight/2+"px";const{documentElement:t}=E.ownerDocument;e.touches[0].clientY>.8*t.clientHeight&&t.scroll({left:0,top:.8*e.touches[0].pageY,behavior:"smooth"}),e.touches[0].clientY<.2*t.clientHeight&&t.scroll({left:0,top:.8*e.touches[0].pageY,behavior:"smooth"});const n=E.ownerDocument.elementsFromPoint(e.changedTouches[0].clientX,e.changedTouches[0].clientY).filter((e=>e.classList.contains($)));0===n.length&&void 0!==_&&(_.classList.remove(g),_=void 0),1===n.length&&_!==n[0]&&(void 0!==_&&_.classList.remove(g),[_]=n,_.classList.add(g))}function C(e){e.preventDefault()}function H(e){e.target.classList.add(g)}function b(e){e.target.classList.remove(g)}function w(){N.removeAttribute("style"),E.querySelectorAll(`.${$}`).forEach((e=>{e.classList.remove(m),e.classList.remove(g)}))}function O(e){e.preventDefault(),X(e.target)}function J(e){e.preventDefault();const t=E.ownerDocument.elementsFromPoint(e.changedTouches[0].clientX,e.changedTouches[0].clientY).filter((e=>e.classList.contains($)));w(),A.remove(),1===t.length&&X(t[0])}function X(e){let t=N;"move"!==y&&(t=N.cloneNode(!0),t.style.opacity=null,z(t)),e.parentNode.insertBefore(t,e),S(t,e,N)}function z(e){e.addEventListener("dragstart",x),e.addEventListener("dragend",w),e.addEventListener("touchstart",D)}function P(e){e.removeEventListener("dragover",C),e.removeEventListener("dragenter",H),e.removeEventListener("dragleave",b),e.removeEventListener("drop",O),e.addEventListener("dragover",C),e.addEventListener("dragenter",H),e.addEventListener("dragleave",b),e.addEventListener("drop",O)}function B(e,t,n,o,s,l){y=e,E=t,L=n,$=o,S=s,q=l,E.querySelectorAll(`.${L}`).forEach(z),E.querySelectorAll(`.${$}`).forEach(P)}e.test=e.test||{},e.test.matching=e.test.matching||{},e.test.matching.init=(e,t)=>p(e,B,t)}(il);
