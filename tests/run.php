<?php
require __DIR__.'/bootstrap.php';
use EdgeInfinity\Dice\{DiceSyntax,DiceConfig,ConfigureFormatter,RenderDice,RollPolicy};
use Flarum\Post\CommentPost;
use Flarum\Settings\SettingsRepositoryInterface;
$count=0;
function check(bool $condition,string $label):void {global $count;$count++;if(!$condition)throw new RuntimeException($label);}
function rejects(callable $f,string $label):void {try{$f();}catch(InvalidArgumentException|Flarum\Foundation\ValidationException $e){check(true,$label);return;}check(false,$label);}
$c=new s9e\TextFormatter\Configurator;
$c->rendering->setEngine('PHP');$c->Litedown;$c->Escaper;$c->BBCodes->addFromRepository('QUOTE');
(new ConfigureFormatter)($c);$f=$c->finalize();
$parse=fn(string $text):string=>$f['parser']->parse($text);
$cases=['[1, 6]'=>[[1,6]],'[ -5 , +10 ]'=>[[-5,10]],'[0,0]'=>[[0,0]],'[10,5]'=>[], '[1.2,6]'=>[],'[1e2,200]'=>[], '[1,1000000001]'=>[], '[1, 6, 8]'=>[], '[1,6](https://example.com)'=>[], '\\[1,6]'=>[], '> [1,6]'=>[], '[quote][1,6][/quote]'=>[], '中文 [1,6] + [-10,-1]'=>[[1,6],[-10,-1]]];
$cases[chr(96).'[1,6]'.chr(96)]=[];$cases[str_repeat(chr(96),3)."\n[1,6]\n".str_repeat(chr(96),3)]=[];
foreach($cases as $s=>$ranges){$xml=$parse($s);check(DiceSyntax::ranges($xml)===$ranges,'parse '.$s);check(s9e\TextFormatter\Unparser::unparse($xml)===$s,'unparse '.$s);}
$settings=new class implements SettingsRepositoryInterface {
 public array $values=[];public function all():array{return $this->values;}public function get(string $key,mixed $default=null):mixed{return $this->values[$key]??$default;}public function set(string $key,mixed $value):void{$this->values[$key]=$value;}public function delete(string $keyLike):void{unset($this->values[$keyLike]);}
};
$translator=new Flarum\Locale\Translator('en');
$policy=new RollPolicy($settings,$translator);
$post=new CommentPost;$post->parsed_content=$parse('[1,20] [-5,5] [0,0]');
$before=$post->getAttribute('edge_dice');$render=new RenderDice;
$pending=$render($f['renderer'],$post,$post->parsed_content);
check(!str_contains($pending,'result='),'preview does not roll');check($post->getAttribute('edge_dice')===$before,'render has no writes');
$policy->creating($post);$ledger=$post->getAttribute('edge_dice');$data=DiceSyntax::ledger($ledger);
check(count($data['rolls'])===3,'three rolls created');check($data['rolls'][2][2]===0,'zero result preserved');
foreach($data['rolls'] as [$a,$b,$r])check($r>=$a&&$r<=$b,'result in range');
$post->exists=true;$post->syncOriginal();
$rolled=$render($f['renderer'],$post,$post->parsed_content);check(substr_count($rolled,'state="rolled"')===3,'render saved results');
check(str_contains($f['renderer']->render($rolled),'data-result="0"'),'zero HTML');
$html=$f['renderer']->render($rolled);
check(strip_tags($html)==='[1,20] [-5,5] [0,0]','rolled excerpt text contains only original placeholders');
check(!str_contains($html,'EdgeDice-result')&&!str_contains($html,'EdgeDice-icon')&&!str_contains($html,'tabindex='),'server HTML has no partial component or focus target');
check(strip_tags($f['renderer']->render($pending))==='[1,20] [-5,5] [0,0]','pending excerpt has no question marks');
$spaced=$parse('原文 [ -5 , +10 ] 与 [1, 20]');
check(strip_tags($f['renderer']->render($render($f['renderer'],null,$spaced)))==='原文 [ -5 , +10 ] 与 [1, 20]','excerpt preserves placeholder whitespace and explicit signs');

for($i=0;$i<20;$i++){check($render($f['renderer'],$post,$post->parsed_content)===$rolled,'repeat renders immutable');}
$post->parsed_content=$parse('Edit surrounding text [1,20] [-5,5] [0,0]');$policy->saving($post);check($post->getAttribute('edge_dice')===$ledger,'text edit preserves rolls');
$settings->values['edgeinfinity-dice.config']=json_encode(['allowEdit'=>false]);
foreach(['[1,20]','[1,21] [-5,5] [0,0]','[-5,5] [1,20] [0,0]','[1,20] [-5,5] [0,0] [1,6]','> [1,20] [-5,5] [0,0]'] as $bad){$post->parsed_content=$parse($bad);rejects(fn()=>$policy->saving($post),'reject changed sequence');}
$post->parsed_content=$parse('[1,20] [-5,5] [0,0]');$post->setAttribute('edge_dice','forged');$policy->saving($post);check($post->getAttribute('edge_dice')===$ledger,'ignore result overwrite');
$old=new CommentPost;$old->exists=true;$old->setRawAttributes(['content'=>$parse('old [1,6]'),'edge_dice'=>null],true);$old->parsed_content=$parse('edit old [1,20]');$policy->saving($old);check($old->getAttribute('edge_dice')===null,'legacy edit never rolls');
check(str_contains($render($f['renderer'],$old,$old->parsed_content),'state="unrolled"'),'legacy rendered unrolled');
$forged=str_replace('<EDGEDICE ','<EDGEDICE result="999" state="rolled" ',$old->parsed_content);
check(!str_contains($render($f['renderer'],$old,$forged),'result="999"'),'forged XML ignored');
$limit=new CommentPost;$limit->parsed_content=$parse(str_repeat('[1,6] ',51));rejects(fn()=>$policy->creating($limit),'limit rejects whole post');
$bare=new s9e\TextFormatter\Configurator;$bare->rendering->setEngine('PHP');$bare->Litedown;$g=$bare->finalize();
check(str_contains($g['renderer']->render($post->parsed_content),'[1,20]'),'removed plugin shows source');
check(!str_contains($g['renderer']->render($post->parsed_content),'EdgeDice'),'removed plugin no broken component');
$defaults=DiceConfig::defaults();check(DiceConfig::validate($defaults)===$defaults,'default settings valid');
foreach(['maxDice'=>101,'criticalCount'=>0,'specialNumbers'=>['1'],'priority'=>['special'],'colors'=>['normal'=>['light'=>['text'=>'url(javascript:bad)']]],'bogus'=>true] as $key=>$value){$cfg=$defaults;$cfg[$key]=$value;rejects(fn()=>DiceConfig::validate($cfg),'reject config '.$key);}
check(DiceConfig::decode('invalid')===$defaults,'corrupt settings safe fallback');
foreach([['normal'=>'bad'],['normal'=>['light'=>'bad']],['normal'=>['light'=>['text'=>42]]],['normal'=>['moon'=>[]]]] as $colors){$cfg=$defaults;$cfg['colors']=$colors;rejects(fn()=>DiceConfig::validate($cfg),'reject malformed palette');}
check(DiceConfig::validate(['colors'=>[]])['colors']===$defaults['colors'],'partial palette restores defaults');
$eventValues=['edgeinfinity-dice.config'=>json_encode(['maxDice'=>10])];
$event=new Flarum\Settings\Event\Saving($eventValues);
(new EdgeInfinity\Dice\ValidateSettings)->handle($event);
check(json_decode($event->settings['edgeinfinity-dice.config'],true)['maxDice']===10,'settings event normalizes valid configuration');
$badValues=['edgeinfinity-dice.config'=>'{bad'];
$badEvent=new Flarum\Settings\Event\Saving($badValues);
rejects(fn()=>(new EdgeInfinity\Dice\ValidateSettings)->handle($badEvent),'settings event rejects malformed JSON');
check(DiceSyntax::document('<!DOCTYPE r [<!ENTITY e SYSTEM "file:///etc/passwd">]><r><EDGEDICE>&e;</EDGEDICE></r>')===null,'reject XML external entities');
check(DiceSyntax::ledger('{"v":1,"rolls":[[1,6,9]]}')===null,'reject invalid ledger result');
check(DiceSyntax::ledger('{"v":1,"rolls":{"1":[1,6,3]}}')===null,'reject non-list ledger indices');
foreach(['en','zh-Hans'] as $locale){$messages=Symfony\Component\Yaml\Yaml::parseFile(__DIR__.'/../locale/'.$locale.'.yml');check(isset($messages['edgeinfinity-dice']['forum']['error_locked']),'locale '.$locale);}
// Default-enabled editing: deterministic fixtures prove exact reuse and cleanup.
$settings->values=[];
$edit=new CommentPost;$edit->exists=true;
$original=['v'=>1,'rolls'=>[[1,20,17],[1,6,2],[1,20,4]]];
$edit->setRawAttributes(['content'=>$parse('[1,20] [1,6] [1,20]'),'edge_dice'=>json_encode($original)],true);
$edit->parsed_content=$parse('[1,20] [0,0] [1,20]');$policy->saving($edit);
$revised=DiceSyntax::ledger($edit->edge_dice);
check($revised['rolls']===[[1,20,17],[0,0,0],[1,20,4]],'FIFO reuse preserves duplicate ranges and rolls only new range');
check($revised['v']===1&&$revised['edited']===true,'legacy tuple layout retained and editing flagged');
check(!in_array([1,6,2],$revised['rolls'],true),'unused result removed');
check(substr_count($render($f['renderer'],$edit,$edit->parsed_content),'edited="1"')===3,'selection-risk survivors all marked');
$edit->syncOriginal();$editedLedger=$edit->edge_dice;$edit->parsed_content=$parse('prose [1,20] [0,0] [1,20]');$policy->saving($edit);
check($edit->edge_dice===$editedLedger,'prose edit keeps flag and results');
$edit->syncOriginal();$edit->parsed_content=$parse('all removed');$policy->saving($edit);$empty=DiceSyntax::ledger($edit->edge_dice);
check($empty['rolls']===[]&&$empty['edited']===true,'all removed results discarded, only provenance retained');
$edit->syncOriginal();$edit->parsed_content=$parse('[0,0]');$policy->saving($edit);
check(DiceSyntax::ledger($edit->edge_dice)['rolls']===[[0,0,0]],'reinsert after deletion creates fresh result');
$settings->values['edgeinfinity-dice.config']=json_encode(['maxDice'=>1]);
$edit->syncOriginal();$edit->parsed_content=$parse('[0,0] [1,6]');rejects(fn()=>$policy->saving($edit),'edit limit enforced');
$settings->values=[];$legacy=new CommentPost;$legacy->exists=true;$legacy->setRawAttributes(['content'=>$parse('legacy'),'edge_dice'=>null],true);$legacy->parsed_content=$parse('[0,0]');$policy->saving($legacy);
check(DiceSyntax::ledger($legacy->edge_dice)['edited']===true,'manual legacy edit can roll but is flagged');
$config=DiceConfig::validate(['criticalPercent'=>5,'colors'=>['normal'=>['light'=>['text'=>'#123456']]]]);
check($config['criticalMode']==='percent'&&$config['criticalPercent']===5&&$config['criticalCount']===1&&$config['allowEdit']===true&&$config['colors']['normal']['light']['text']==='#123456','old configuration upgraded without losing colors');
foreach(['genericIcon'=>'<i>','editedIcon'=>'fas fa-dice onclick','effectLabels'=>['success'=>'<b>win</b>'],'specialIcons'=>['8'=>['type'=>'image','value'=>'javascript:alert(1)']]] as $key=>$value){$cfg=$defaults;$cfg[$key]=$value;rejects(fn()=>DiceConfig::validate($cfg),'reject unsafe '.$key);}
foreach(['//bad.test/a','data:image/svg+xml,x','https://user:pass@example.com/x',"/bad path",'/bad'.chr(92).'path'] as $url)rejects(fn()=>DiceConfig::validate(['specialIcons'=>['8'=>['type'=>'image','value'=>$url]]]),'reject unsafe image URL');
foreach(['/images/dice.png','https://example.com/dice.png'] as $url)check(DiceConfig::validate(['specialIcons'=>['8'=>['type'=>'image','value'=>$url]]])['specialIcons'][8]['value']===$url,'safe image allowed');
check(DiceConfig::validate(['specialIcons'=>['8'=>['enabled'=>false]]])['specialIcons'][8]['enabled']===false,'individual icon disabled');
check(DiceConfig::validate(['effectLabels'=>['success'=>'好耶']])['effectLabels']['success']==='好耶','custom effect wording');
check(DiceSyntax::ledger('{"v":1,"rolls":[],"edited":"yes"}')===null,'malformed provenance rejected');
check($defaults['criticalMode']==='count'&&$defaults['criticalCount']===1&&$defaults['criticalPercent']===5,'default count mode retains inactive percentage');
check($defaults['sequenceDigitsMin']===3&&$defaults['repeatDigitsMin']===2,'independent digit defaults');
$legacy=DiceConfig::validate(['digitsMin'=>4,'criticalCount'=>2,'effectLabels'=>['repeat'=>'custom']]);
check($legacy['repeatDigitsMin']===4&&$legacy['sequenceDigitsMin']===4&&!isset($legacy['digitsMin']),'legacy shared threshold preserved');
check($legacy['criticalMode']==='count'&&$legacy['criticalCount']===2&&$legacy['effectLabels']['repeat']==='custom','legacy count and custom label preserved');
$split=DiceConfig::validate(['digitsMin'=>4,'repeatDigitsMin'=>2,'sequenceDigitsMin'=>5]);
check($split['repeatDigitsMin']===2&&$split['sequenceDigitsMin']===5,'explicit split thresholds override legacy');
check(DiceConfig::validate(['criticalPercent'=>7.5])['criticalMode']==='percent','legacy fractional percentage chooses percentage');
$mode=DiceConfig::validate(['criticalMode'=>'count','criticalPercent'=>7.5,'criticalCount'=>3]);
check($mode['criticalMode']==='count'&&$mode['criticalPercent']===7.5&&$mode['criticalCount']===3,'explicit mode keeps both thresholds');
foreach (['criticalMode'=>'bogus','criticalPercent'=>0,'repeatDigitsMin'=>1,'sequenceDigitsMin'=>11,'digitsMin'=>'3'] as $key=>$value) rejects(fn()=>DiceConfig::validate([$key=>$value]),'reject invalid new rule '.$key);
foreach ([0.001,51,-1,'5',true,NAN,INF] as $value) rejects(fn()=>DiceConfig::validate(['criticalPercent'=>$value]),'reject invalid percentage type or bounds');
foreach ([0.01,5,50] as $value) check(DiceConfig::validate(['criticalMode'=>'percent','criticalPercent'=>$value])['criticalPercent']===$value,'percentage boundary accepted');
$values=['edgeinfinity-dice.config'=>json_encode(['criticalMode'=>'percent','criticalPercent'=>7.5,'repeatDigitsMin'=>2,'sequenceDigitsMin'=>4])];
$event=new Flarum\Settings\Event\Saving($values);(new EdgeInfinity\Dice\ValidateSettings)->handle($event);
check(json_decode($event->settings['edgeinfinity-dice.config'],true)['criticalPercent']===7.5,'settings save validates percentage and split thresholds');
$zh=Symfony\Component\Yaml\Yaml::parseFile(__DIR__.'/../locale/zh-Hans.yml')['edgeinfinity-dice'];
foreach (['forum','admin'] as $part) check($zh[$part]['effect_repeat']==='连号'&&$zh[$part]['effect_sequence']==='同花顺','Chinese effect names '.$part);
echo json_encode(['passed' =>$count,'php'=>PHP_VERSION],JSON_PRETTY_PRINT).PHP_EOL;
