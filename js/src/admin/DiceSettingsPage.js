import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Form from 'flarum/common/components/Form';
import Button from 'flarum/common/components/Button';
import Switch from 'flarum/common/components/Switch';
import defaults from '../../../resources/defaults.json';
import normalize from '../common/config';
import DiceIcon from '../common/DiceIcon';
const t = key => app.translator.trans('edgeinfinity-dice.admin.'+key);
const key='edgeinfinity-dice.config';
export default class DiceSettingsPage extends ExtensionPage {
  oninit(vnode) {
    super.oninit(vnode);
    this.config=normalize(this.setting(key)());
    this.specialText=this.config.specialNumbers.join(', ');
    this.priorityText=this.config.priority.join(', ');
  }
  change() {this.setting(key)(JSON.stringify(this.config));}
  field(name,input,help) {return m('label.EdgeDiceAdmin-field',[m('span',t(name)),input,help&&m('span.helpText',t(help))]);}
  number(name,min,max,step=1) {return this.field(name,m('input.FormControl',{name,type:'number',required:true,min,max,step,value:this.config[name],oninput:e=>{this.config[name]=Number(e.target.value);this.change();}}));}
  toggle(name) {return m('.Form-group',m(Switch,{state:this.config[name],onchange:value=>{this.config[name]=value;this.change();}},t(name)));}
  iconText(name) {return this.field(name,m('input.FormControl',{value:this.config[name],maxlength:100,oninput:e=>{this.config[name]=e.target.value;this.change();}}));}
  iconSettings() {
    return [m('h3',t('icons')),m('p.helpText',t('icons_help')),this.iconText('genericIcon'),
      m('.EdgeDiceAdmin-iconPreview',m(DiceIcon,{icon:{type:'font',value:this.config.genericIcon},generic:defaults.genericIcon})),
      this.toggle('markEdited'),this.iconText('editedIcon'),
      m('.EdgeDiceAdmin-iconPreview',m(DiceIcon,{icon:{type:'font',value:this.config.editedIcon},generic:defaults.genericIcon})),
      m('.EdgeDiceAdmin-icons',Object.entries(this.config.specialIcons).map(([faces,icon])=>m('fieldset',[
        m('legend','[1, '+faces+'] · d'+faces),
        m(Switch,{state:icon.enabled,onchange:value=>{icon.enabled=value;this.change();}},t('icon_enabled')),
        m('label.EdgeDiceAdmin-field',[m('span',t('icon_type')),m('select.FormControl',{value:icon.type,onchange:e=>{icon.type=e.target.value;icon.value=icon.type==='font'?this.config.genericIcon:'';this.change();}},['builtin','font','image'].map(type=>m('option',{value:type},t('icon_'+type))))]),
        icon.type!=='builtin'&&m('label.EdgeDiceAdmin-field',[m('span',t(icon.type==='font'?'icon_font_value':'icon_image_value')),m('input.FormControl',{type:'text',maxlength:icon.type==='font'?100:2048,value:icon.value,oninput:e=>{icon.value=e.target.value;this.change();}})]),
        m('.EdgeDiceAdmin-iconPreview',m(DiceIcon,{key:JSON.stringify(icon),icon:icon.enabled?(icon.type==='builtin'?{type:'builtin',value:'d'+faces}:icon):{type:'font',value:this.config.genericIcon},generic:this.config.genericIcon})),
      ]))),
    ];
  }
  content() {
    return m('.ExtensionPage-settings.EdgeDiceAdmin',m('.container',[
      m('p.helpText',t('intro')),m('p.Alert',t('lifecycle')),
      m(Form,{onsubmit:this.saveSettings.bind(this)},[
        m('h3',t('rules')),
        this.number('maxDice',1,100),this.toggle('allowEdit'),m('p.helpText',t('editing_help')),
        this.toggle('criticalEnabled'),this.number('criticalMinFaces',2,2000000001),
        this.field('criticalMode',m('select.FormControl',{name:'criticalMode',value:this.config.criticalMode,onchange:e=>{this.config.criticalMode=e.target.value;this.change();}},['count','percent'].map(mode=>m('option',{value:mode},t('criticalMode_'+mode))))),
        this.config.criticalMode==='percent'?this.number('criticalPercent',0.01,50,'any'):this.number('criticalCount',1,1000000000),this.toggle('successHigh'),
        m('p.helpText',t('critical_help')),
        this.toggle('signEnabled'),this.toggle('repeatEnabled'),this.number('repeatDigitsMin',2,10),this.toggle('sequenceEnabled'),this.number('sequenceDigitsMin',2,10),this.toggle('specialEnabled'),
        this.field('specialNumbers',m('input.FormControl',{value:this.specialText,oninput:e=>{this.specialText=e.target.value;this.config.specialNumbers=this.specialText.trim()?this.specialText.split(/[,，\s]+/).filter(Boolean).map(Number):[];this.change();}}),'numbers_help'),
        this.field('priority',m('input.FormControl',{value:this.priorityText,oninput:e=>{this.priorityText=e.target.value;this.config.priority=this.priorityText.split(/[,，\s]+/).filter(Boolean);this.change();}}),'priority_help'),
        m('h3',t('labels')),m('p.helpText',t('labels_help')),
        m('.EdgeDiceAdmin-labels',Object.keys(defaults.effectLabels).map(effect=>this.field('effect_'+effect,m('input.FormControl',{maxlength:40,value:this.config.effectLabels[effect],placeholder:t('effect_'+effect),oninput:e=>{this.config.effectLabels[effect]=e.target.value;this.change();}})))),
        ...this.iconSettings(),
        m('h3',t('colors')),m('p.helpText',t('colors_help')),
        m('.EdgeDiceAdmin-colors',Object.keys(defaults.colors).map(effect=>m('fieldset',[
          m('legend',t('effect_'+effect)),
          ...['light','dark'].map(theme=>m('.EdgeDiceAdmin-theme',[
            m('h4',t(theme)),...['text','background','icon'].map(property=>{
              const value=this.config.colors[effect][theme][property];
              return m('label',[m('span',t(property)),m('input.FormControl',{type:'text',maxlength:9,placeholder:defaults.colors[effect][theme][property]||t('inherit'),value,pattern:'(#([a-fA-F0-9]{3}|[a-fA-F0-9]{4}|[a-fA-F0-9]{6}|[a-fA-F0-9]{8}))?',oninput:e=>{this.config.colors[effect][theme][property]=e.target.value;this.change();}})]);
            }),
            m('span.EdgeDiceAdmin-swatch',{style:{color:this.config.colors[effect][theme].text||undefined,background:this.config.colors[effect][theme].background||undefined}},'20 [1, 20] ⚄'),
          ])),
        ]))),
        m('.Form-group.Form-controls',[this.submitButton(),m(Button,{className:'Button',onclick:()=>{this.config=JSON.parse(JSON.stringify(defaults));this.specialText=this.config.specialNumbers.join(', ');this.priorityText=this.config.priority.join(', ');this.change();}},t('reset'))]),
      ]),
    ]));
  }
}
