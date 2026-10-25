// FA Free omits these regular glyphs. Use the loaded family's solid face in
// that case; keep actual Pro regular faces untouched. Canvas glyph probing is
// unsuitable here because browser/system font substitution can hide a miss.
const regularDefaults=new Set(['far fa-dice','far fa-question']);
function fallback(element, value) {
  element.classList.toggle('EdgeDice-solidFallback',regularDefaults.has(value)
    && /Font Awesome (?:\d+ )?Free/.test(getComputedStyle(element).fontFamily));
}
export default {view({attrs:{icon,generic}}) {
  if(icon.type==='image')return m('span.EdgeDice-icon.EdgeDice-imageBox',[m('img.EdgeDice-image',{
    src:icon.value,alt:'','aria-hidden':'true',referrerpolicy:'no-referrer',
    onerror:e=>e.currentTarget.parentElement.classList.add('EdgeDice-imageFailed'),
    onload:e=>e.currentTarget.parentElement.classList.remove('EdgeDice-imageFailed'),
  }),m('i.EdgeDice-imageFallback',{className:'icon '+generic,'aria-hidden':'true',
    oncreate:vnode=>fallback(vnode.dom,generic),onupdate:vnode=>fallback(vnode.dom,generic),
  })]);
  return m('i.EdgeDice-icon',{'aria-hidden':'true',className:icon.type==='builtin'?'EdgeDice-icon--'+icon.value:'icon '+icon.value,
    oncreate:vnode=>fallback(vnode.dom,icon.type==='font'?icon.value:''),
    onupdate:vnode=>fallback(vnode.dom,icon.type==='font'?icon.value:''),
  });
}};
