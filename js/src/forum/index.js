import app from 'flarum/forum/app';
import { override } from 'flarum/common/extend';
import Tooltip from 'flarum/common/components/Tooltip';
import ComposerPostPreview from 'flarum/forum/components/ComposerPostPreview';
import extractText from 'flarum/common/utils/extractText';
import normalize from '../common/config';
import DiceIcon from '../common/DiceIcon';
import { effects, resolveIcon, previewRolls, cssVariables } from '../common/effects';

const mounted = new Map();
const wrappedPreview = Symbol('edgeDicePreview');
const t = (key, values = {}) => extractText(app.translator.trans('edgeinfinity-dice.forum.' + key, values));
const config = () => normalize(app.forum.attribute('edgeDiceConfig'));
const editorSelector = 'textarea, input, [contenteditable="true"], .ProseMirror, .tiptap';
function eligible(element) {
  // Excerpts may retain rich HTML or flatten it to text. Only the actual post
  // body and composer preview opt into tooltip/icon enhancement.
  return !element.closest(editorSelector + ', .Synopsis-excerpt, .DiyHome-excerpt, .PostPreview, .DiscussionListItem')
    && !!element.closest('.Post-body, .Post-preview');
}
function model(element) {
  const min = Number(element.dataset.min), max = Number(element.dataset.max);
  if (!Number.isInteger(min) || !Number.isInteger(max) || min > max || Math.abs(min)>1e9 || Math.abs(max)>1e9) return null;
  const result = element.hasAttribute('data-result') ? Number(element.dataset.result) : null;
  const state = element.dataset.state || 'pending';
  const raw = element.querySelector('.EdgeDice-range')?.textContent ?? element.textContent ?? '[' + min + ', ' + max + ']';
  return {min, max, result: Number.isInteger(result) && result>=min && result<=max ? result : null, state, raw, edited:element.dataset.edited==='1'};
}
export function decorateDice(root) {
  const elements = [...(root.matches?.('.EdgeDice') ? [root] : []), ...root.querySelectorAll('.EdgeDice')];
  for (const el of elements) {
    // Never alter editor DOM. Restore moved/cached components in summaries.
    if (el.closest(editorSelector)) continue;
    const data = model(el);
    if (!data) continue;
    if (!eligible(el)) {
      if (mounted.has(el)) { m.render(el, []); mounted.delete(el); }
      if (el.textContent !== data.raw || el.children.length) el.textContent = data.raw;
      el.classList.remove('EdgeDice--enhanced');
      el.removeAttribute('tabindex');
      el.removeAttribute('data-effect');
      continue;
    }
    const fingerprint = JSON.stringify([data,config()]);
    if (mounted.get(el) === fingerprint && el.querySelector('.EdgeDice-inner')) continue;
    // s9e mutates preview DOM in place; cached Mithril vnode pointers must reset.
    m.render(el, []);
    el.replaceChildren();
    mounted.delete(el);
    const cfg = config();
    const selected = data.result === null ? [] : effects(data.min,data.max,data.result,cfg);
    const effect = selected[0] || 'normal';
    const tooltip = data.result === null
      ? t(data.state === 'invalid-edit' ? 'invalid_edit' : data.state === 'unrolled' ? 'unrolled' : 'pending')
      : t('rolled', {min:data.min,max:data.max,faces:data.max-data.min+1,result:data.result}) + (selected.length ? '—' + (cfg.effectLabels[selected[0]] || t('effect_' + selected[0])) : '') + (data.edited && cfg.markEdited ? ' · ' + t('edited') : '');
    el.removeAttribute('tabindex');
    el.dataset.effect = effect;
    el.classList.add('EdgeDice--enhanced');
    const die = resolveIcon(data.min,data.max,data.edited,cfg);
    m.render(el,m(Tooltip,{text:tooltip,container:'body'},m('span.EdgeDice-inner',{
      tabindex:0, role:'img', 'aria-label':tooltip, style:cssVariables(cfg,effect),
      // This is a static result, not a button. Pointer presses must not leave
      // focus behind and pin the standard tooltip; Tab focus stays available.
      onpointerdown:e=>{e.redraw=false;if(e.button===0){e.preventDefault();e.currentTarget.blur();}},
    },[
      m('span.EdgeDice-result', data.result === null ? '?' : String(data.result)),
      m('span.EdgeDice-range',data.raw),
      m(DiceIcon,{icon:die,generic:cfg.genericIcon}),
    ])));
    mounted.set(el,fingerprint);
  }
}
function preview(root, composer) {
  const nodes = [...root.querySelectorAll('.EdgeDice')];
  const post = composer?.body?.attrs?.post;
  if (post) {
    const original = document.createElement('div');
    original.innerHTML = post.contentHtml() || '';
    const saved = [...original.querySelectorAll('.EdgeDice[data-result]')];
    const predictions=previewRolls(saved.map(model),nodes.map(model),config());
    nodes.forEach((node,i)=>{
      const prediction=predictions[i];
      if(prediction.result===null)node.removeAttribute('data-result');else node.dataset.result=String(prediction.result);
      node.dataset.state=prediction.state;
      node.dataset.edited=prediction.edited?'1':'0';
    });
  }
  decorateDice(root);
}
function wrapPreview(component, root) {
  const surround = component.attrs.surround;
  if (surround[wrappedPreview]) return;
  const wrapped = (render) => surround(() => {
    for (const el of root.querySelectorAll('.EdgeDice')) {
      if (mounted.has(el)) { m.render(el, []); mounted.delete(el); }
    }
    render();
    preview(root, component.attrs.composer);
  });
  wrapped[wrappedPreview] = true;
  component.attrs.surround = wrapped;
}
app.initializers.add('edgeinfinity/flarum-dice', () => {
  // Wrap only the preview component. The input and rich-text editor remain plain placeholders.
  override(ComposerPostPreview.prototype,'oncreate',function(original,vnode) {
    wrapPreview(this, vnode.dom);
    original(vnode);
  });
  // Component.setAttrs replaces attrs on every Mithril redraw. Re-wrap the new
  // surround callback so edit previews remain bound to their original results.
  override(ComposerPostPreview.prototype,'onbeforeupdate',function(original,vnode) {
    const result = original(vnode);
    wrapPreview(this, this.element);
    return result;
  });
  let queued = false;
  const run = () => {
    queued=false;
    for (const el of mounted.keys()) if(!el.isConnected) {m.render(el,[]);mounted.delete(el);}
    decorateDice(document.body);
  };
  new MutationObserver((changes) => {
    // Ignore our own text/tooltip writes; inspect only added/removed dice containers.
    const relevant = changes.some(change => [...change.addedNodes,...change.removedNodes].some(node => node.nodeType===1 && (node.matches?.('.EdgeDice') || node.querySelector?.('.EdgeDice'))));
    if (relevant && !queued) {queued=true;queueMicrotask(run);}
  }).observe(document.body,{childList:true,subtree:true});
  run();
});
