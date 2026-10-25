export function effects(min, max, result, config) {
  if (!Number.isInteger(result) || result < min || result > max) return [];
  const matches = [];
  const faces = max - min + 1;
  if (config.criticalEnabled && faces >= config.criticalMinFaces) {
    const count = config.criticalMode === 'percent' ? Math.max(1, Math.floor(faces * config.criticalPercent / 100)) : config.criticalCount;
    const band = Math.min(Math.floor(faces / 2), count);
    if (result >= max - band + 1) matches.push(config.successHigh ? 'success' : 'failure');
    if (result <= min + band - 1) matches.push(config.successHigh ? 'failure' : 'success');
  }
  if (config.signEnabled && min < 0 && max > 0) matches.push(result > 0 ? 'positive' : result < 0 ? 'negative' : 'zero');
  const digits = String(Math.abs(result));
  if (config.repeatEnabled && digits.length >= config.repeatDigitsMin && /^(\d)\1+$/.test(digits)) matches.push('repeat');
  if (config.sequenceEnabled && digits.length >= config.sequenceDigitsMin) {
    const differences = [...digits].slice(1).map((d, i) => Number(d) - Number(digits[i]));
    if (differences.every(d => d === 1) || differences.every(d => d === -1)) matches.push('sequence');
  }
  if (config.specialEnabled && config.specialNumbers.includes(result)) matches.push('special');
  return config.priority.filter(effect => matches.includes(effect));
}
export function iconType(min, max) {
  return min === 1 && [4,6,8,10,12,20,100].includes(max) ? 'd' + max : 'generic';
}
export function cssVariables(config, effect) {
  const vars = {};
  for (const theme of ['light','dark']) {
    for (const property of ['text','background','icon']) {
      const value = config.colors[effect]?.[theme]?.[property]
        || (property === 'icon' ? '' : config.colors.normal[theme][property]);
      if (value && /^#(?:[a-f\d]{3}|[a-f\d]{4}|[a-f\d]{6}|[a-f\d]{8})$/i.test(value)) vars['--edge-dice-' + property + '-' + theme] = value;
    }
  }
  return vars;
}

export function resolveIcon(min, max, edited, config) {
  if (edited && config.markEdited) return {type:'font', value:config.editedIcon};
  const special = min === 1 ? config.specialIcons[String(max)] : null;
  if (special?.enabled) return special.type === 'builtin' ? {type:'builtin',value:'d'+max} : special;
  return {type:'font',value:config.genericIcon};
}

// Identical ranges are reused FIFO, matching the server policy. Previews only
// display known results; new/changed ranges stay pending until the save succeeds.
export function previewRolls(saved, ranges, config) {
  const matches=saved.length===ranges.length && saved.every((s,i)=>s.min===ranges[i].min && s.max===ranges[i].max);
  if (!config.allowEdit && saved.length && !matches) return ranges.map(r=>({...r,result:null,state:'invalid-edit',edited:false}));
  const available=new Map();
  for(const die of saved) {const key=die.min+':'+die.max;if(!available.has(key))available.set(key,[]);available.get(key).push(die);}
  return ranges.map(r=>{
    const previous=available.get(r.min+':'+r.max)?.shift();
    return {...r,result:previous?.result??null,state:previous?'rolled':config.allowEdit?'pending':'unrolled',edited:!!previous?.edited || (config.allowEdit&&!matches)};
  });
}
