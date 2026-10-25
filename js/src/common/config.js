import defaults from '../../../resources/defaults.json';
export default function normalize(value) {
  const result=JSON.parse(JSON.stringify(defaults));
  let source=value;
  try {if(typeof source==='string')source=JSON.parse(source);}catch{return result;}
  if(!source||typeof source!=='object'||Array.isArray(source))return result;
  source={...source};
  if(!Object.prototype.hasOwnProperty.call(source,'criticalMode'))source.criticalMode=Object.prototype.hasOwnProperty.call(source,'criticalPercent')&&!Object.prototype.hasOwnProperty.call(source,'criticalCount')?'percent':'count';
  if(Number.isInteger(source.digitsMin)&&source.digitsMin>=2&&source.digitsMin<=10) {
    if(!Object.prototype.hasOwnProperty.call(source,'repeatDigitsMin'))source.repeatDigitsMin=source.digitsMin;
    if(!Object.prototype.hasOwnProperty.call(source,'sequenceDigitsMin'))source.sequenceDigitsMin=source.digitsMin;
  }
  for(const key of Object.keys(result)) {
    if(['colors','effectLabels','specialIcons'].includes(key))continue;
    if(typeof source[key]===typeof result[key] && Array.isArray(source[key])===Array.isArray(result[key]))result[key]=source[key];
  }
  for(const effect of Object.keys(result.colors))for(const theme of ['light','dark'])for(const property of ['text','background','icon']) {
    const value=source.colors?.[effect]?.[theme]?.[property];
    if(typeof value==='string')result.colors[effect][theme][property]=value;
  }
  for(const effect of Object.keys(result.effectLabels))if(typeof source.effectLabels?.[effect]==='string')result.effectLabels[effect]=source.effectLabels[effect];
  for(const faces of Object.keys(result.specialIcons))for(const property of ['enabled','type','value']) {
    const value=source.specialIcons?.[faces]?.[property];
    if(typeof value===typeof result.specialIcons[faces][property])result.specialIcons[faces][property]=value;
  }
  return result;
}
