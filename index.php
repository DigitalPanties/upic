<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>UPIC — Panty Identification Code</title><meta name="description" content="Build, decode and render portable panty design codes; legacy Panty Maker IDs supported.">
<style>
:root{color-scheme:light;--ink:#271c2a;--muted:#625663;--accent:#9e3f74;--faint:#fbf4f9;--edge:#e8d8e2;--surface:#fff}*{box-sizing:border-box}
body{margin:0;background:#f8f5f8;color:var(--ink);font:15px/1.55 system-ui,-apple-system,sans-serif}main{max-width:1250px;margin:auto;padding:25px 18px 60px}
header{display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--edge);padding-bottom:15px;margin-bottom:23px;gap:15px;flex-wrap:wrap}.brand{font-weight:850;letter-spacing:.08em;color:var(--accent);font-size:22px}.sub{color:var(--muted);font-size:13px}
h1{font-size:clamp(24px,4vw,36px);line-height:1.15;letter-spacing:-.035em;margin:2px 0 9px}p{color:var(--muted);margin:0 0 18px}.layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(290px,.78fr);gap:18px}.panel{background:var(--surface);border:1px solid var(--edge);border-radius:16px;padding:19px;box-shadow:0 4px 25px #3e163a08}
fieldset{border:0;padding:0;margin:0 0 19px}legend{font-weight:740;margin-bottom:12px;font-size:16px;width:100%;padding-bottom:7px;border-bottom:1px solid var(--edge)}.fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.field{min-width:0}.field label{display:block;color:var(--muted);font-size:12px;font-weight:650;margin-bottom:5px}select,input{width:100%;min-width:0;background:#fff;color:#382535;border:1px solid #dbccd7;border-radius:8px;padding:10px 9px;font:inherit}input[type=color]{height:42px;padding:4px;cursor:pointer}input[type=range]{accent-color:var(--accent);padding:7px 0}input[type=text]{font-family:ui-monospace,monospace}.span2{grid-column:1/-1}.row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
button{border:0;border-radius:9px;padding:11px 14px;background:var(--accent);color:#fff;font-weight:700;cursor:pointer;font:inherit}button.secondary{background:#f1e5ed;color:#6e3155}button:hover{filter:brightness(.94)}.stage{background:linear-gradient(140deg,#fff5fa,#f1eef6);border:1px solid #e7dae7;border-radius:13px;padding:12px;min-height:310px;display:grid;grid-template-columns:1fr 1fr;gap:5px;align-items:center}.visual{text-align:center}.visual img{width:100%;aspect-ratio:1;object-fit:contain;display:block}.visual small{color:#62485a;font-weight:650}.code{padding:12px;background:#f9f4f9;border:1px solid var(--edge);border-radius:9px;overflow-wrap:anywhere;font:13px/1.6 ui-monospace,monospace;min-height:62px}.status{min-height:22px;font-size:13px;color:var(--accent);margin-top:11px}.note{color:var(--muted);font-size:12px;margin-top:13px}.preview{position:sticky;top:14px;align-self:start}
@media(max-width:770px){main{padding:15px 12px 35px}.layout{grid-template-columns:1fr}.preview{position:static}.panel{padding:14px}.stage{min-height:220px}.fields{gap:10px}}
</style></head><body><main>
<header><div class="brand">UPIC <span class="sub">/ Panty Lane</span></div><span class="sub">Universal Panty Identification Code · positional hex + edge expansions</span></header>
<h1>Design code laboratory</h1><p>One portable ID for a garment's design, colours, fabric, edge treatments and decorative details. Existing Panty Maker IDs load directly.</p>
<div class="layout"><section class="panel">
<fieldset><legend>Original design</legend><div class="fields">
<div class="field"><label for="style">Cut</label><select id="style"><option value="1">Boyshort</option><option value="2">Control high-waist</option><option value="3">Hipster</option><option value="4">Cheeky / Brazilian</option><option value="5" selected>Bikini</option><option value="6">Slip</option><option value="7">Tanga</option><option value="8">String bikini</option><option value="9">Thong</option><option value="10">G-string</option></select></div>
<div class="field"><label for="pattern">Print</label><select id="pattern"><option value="0">Solid</option><option value="1">Small dots</option><option value="2">Large dots</option><option value="3">Small check</option><option value="4">Large check</option><option value="5">Horizontal fine stripes</option><option value="6">Vertical fine stripes</option><option value="7">Horizontal broad stripes</option><option value="8">Vertical broad stripes</option><option value="9">Small hearts</option><option value="10">Big hearts</option><option value="11">Small floral</option><option value="12">Large floral</option><option value="13">Leopard</option><option value="14">Lace look</option><option value="15">Stars</option><option value="16">Camouflage</option><option value="17">Plaid</option></select></div>
<div class="field"><label for="fabric">Fabric</label><select id="fabric"><option value="unspecified">Unspecified / legacy</option><option value="cotton">Cotton</option><option value="satin" selected>Satin</option><option value="silk">Silk</option><option value="nylon">Nylon</option><option value="mesh">Mesh</option><option value="lace">Lace fabric</option><option value="microfiber">Microfiber</option><option value="stretch_lace">Stretch lace</option><option value="chiffon">Chiffon</option><option value="jersey">Jersey</option><option value="velvet">Velvet</option></select></div>
<div class="field"><label for="sheer">Sheerness (0–14)</label><input id="sheer" type="range" min="0" max="14" value="1"></div>
<div class="field"><label for="front">Front colour</label><input id="front" type="color" value="#e83b6a"></div><div class="field"><label for="back">Back colour</label><input id="back" type="color" value="#6cbbf0"></div>
<div class="field"><label for="trim">Base trim colour</label><input id="trim" type="color" value="#ffeedd"></div><div class="field"><label for="ink">Print colour</label><input id="ink" type="color" value="#80173e"></div>
</div></fieldset>
<fieldset><legend>Edges and trims</legend><div class="fields">
<div class="field"><label for="waistMat">Waist trim material</label><select id="waistMat"><option value="none">No special trim</option><option value="plain">Plain</option><option value="elastic">Elastic</option><option value="lace" selected>Lace</option><option value="ribbon">Ribbon</option><option value="piping">Piping</option><option value="stitch">Decorative stitch</option><option value="crochet">Crochet</option><option value="mesh">Mesh</option><option value="satin_binding">Satin binding</option><option value="beaded">Beaded</option></select></div>
<div class="field"><label for="waistEdge">Waist edge</label><select id="waistEdge"><option value="smooth">Smooth</option><option value="scalloped" selected>Scalloped</option><option value="picot">Picot</option><option value="wavy">Wavy</option><option value="ruffled">Ruffled</option><option value="zigzag">Zigzag</option><option value="eyelash">Eyelash fringe</option></select></div>
<div class="field"><label for="waistColor">Waist trim colour</label><input id="waistColor" type="color" value="#ffffff"></div>
<div class="field"><label for="waistWidth">Trim thickness (1–5)</label><input id="waistWidth" type="range" min="1" max="5" value="3"></div>
<div class="field"><label for="legsMat">Both leg openings</label><select id="legsMat"><option value="none">No special trim</option><option value="plain">Plain</option><option value="elastic" selected>Elastic</option><option value="lace">Lace</option><option value="ribbon">Ribbon</option><option value="piping">Piping</option><option value="stitch">Decorative stitch</option><option value="crochet">Crochet</option><option value="mesh">Mesh</option><option value="satin_binding">Satin binding</option><option value="beaded">Beaded</option></select></div>
    <div class="field"><label for="legsEdge">Leg edge</label><select id="legsEdge"><option value="smooth">Smooth</option><option value="scalloped">Scalloped</option><option value="picot" selected>Picot</option><option value="wavy">Wavy</option><option value="ruffled">Ruffled</option><option value="zigzag">Zigzag</option><option value="eyelash">Eyelash fringe</option></select></div>
    <div class="field"><label for="legsColor">Leg trim colour</label><input id="legsColor" type="color" value="#ffccdd"></div>
    <div class="field"><label for="rightOverride">Right leg independent</label><select id="rightOverride"><option value="same">Same as left</option><option value="none">No right-leg trim</option><option value="lace">Lace</option><option value="elastic">Elastic</option><option value="crochet">Crochet</option><option value="beaded">Beaded</option><option value="ribbon">Ribbon</option><option value="stitch">Decorative stitch</option><option value="mesh">Mesh</option><option value="piping">Piping</option></select></div>
    <div class="field"><label for="rightEdge">Right leg edge</label><select id="rightEdge"><option value="smooth">Smooth</option><option value="scalloped">Scalloped</option><option value="picot">Picot</option><option value="wavy">Wavy</option><option value="ruffled">Ruffled</option><option value="zigzag">Zigzag</option><option value="eyelash">Eyelash fringe</option></select></div>
    <div class="field"><label for="rightColor">Right leg colour</label><input id="rightColor" type="color" value="#ffccdd"></div>
    <div class="field"><label for="extraWaist">Additional waist trim layer</label><select id="extraWaist"><option value="none">None</option><option value="ribbon">Ribbon</option><option value="lace">Lace</option><option value="piping">Piping</option><option value="beaded">Beaded</option><option value="crochet">Crochet</option></select></div>
    <div class="field"><label for="extraColor">Additional layer colour</label><input id="extraColor" type="color" value="#ffffff"></div>
</div></fieldset>
<fieldset><legend>Decorative detail</legend><div class="fields"><div class="field"><label for="detail">Detail</label><select id="detail"><option value="none">None</option><option value="bow" selected>Bow</option><option value="rosette">Rosette</option><option value="heart">Heart charm</option><option value="side_tie">Side tie</option><option value="pearl">Pearl</option><option value="flower">Flower appliqué</option></select></div><div class="field"><label for="placement">Placement</label><select id="placement"><option value="front_center" selected>Front centre</option><option value="back_center">Back centre</option><option value="left_hip">Left hip</option><option value="right_hip">Right hip</option></select></div><div class="field"><label for="detailColor">Detail colour</label><input type="color" id="detailColor" value="#ffe6f0"></div><div class="field"><label for="detailSize">Detail size (1–5)</label><input type="range" id="detailSize" min="1" max="5" value="3"></div></div></fieldset>
</section><aside class="panel preview"><div class="stage"><div class="visual"><img id="frontImage" alt="Front design rendering"><small>Front</small></div><div class="visual"><img id="backImage" alt="Back design rendering"><small>Back</small></div></div>
<h3>Design code</h3><div id="code" class="code" aria-live="polite"></div><div class="row" style="margin-top:11px"><button id="copy">Copy code</button><button id="copyUrl" class="secondary">Copy preview link</button><button id="legacy" class="secondary">Show legacy base</button></div>
<div class="field" style="margin-top:20px"><label for="import">Import a legacy or UPIC code</label><input id="import" type="text" spellcheck="false" placeholder="Paste a design code here"></div><button id="load" style="margin-top:8px" class="secondary">Load code</button><div id="status" class="status"></div>
<div class="field" style="margin-top:14px"><label>Try an expanded design</label><div class="row"><button class="secondary" data-preset="lace-scallop">Lace &amp; scallops</button><button class="secondary" data-preset="picot-contrast">Picot &amp; ribbon</button><button class="secondary" data-preset="crochet-wave">Crochet &amp; beads</button><button class="secondary" data-preset="eyelash-lace">Eyelash lace</button></div></div>
    <div class="note">Codes identify the design, not its creator or owner. Legacy IDs remain 12 characters. Extended IDs keep those same 12 characters at the start, then append encoded feature bytes. This is a standalone demo, not a replacement for the current Maker.</div>
</aside></div></main>
<script>
'use strict';
const $=id=>document.getElementById(id);let currentCode='', currentDesign=null,unknown=[],importedDesign=null;
const n=id=>Number($(id).value),v=id=>$(id).value;
function getDesign(){
  const trims=[];
  if(v('waistMat')!=='none') trims.push({zone:'waist',material:v('waistMat'),edge:v('waistEdge'),width:n('waistWidth'),color:v('waistColor')});
  if(v('legsMat')!=='none')trims.push({zone:'left_leg',material:v('legsMat'),edge:v('legsEdge'),width:2,color:v('legsColor')});
  if(v('rightOverride')==='same'&&v('legsMat')!=='none')trims.push({zone:'right_leg',material:v('legsMat'),edge:v('legsEdge'),width:2,color:v('legsColor')});
  else if(!['same','none'].includes(v('rightOverride')))trims.push({zone:'right_leg',material:v('rightOverride'),edge:v('rightEdge'),width:2,color:v('rightColor')});
  if(v('extraWaist')!=='none')trims.push({zone:'waist',layer:1,material:v('extraWaist'),edge:'smooth',width:2,color:v('extraColor')});
  const details=v('detail')==='none'?[]:[{type:v('detail'),at:v('placement'),size:n('detailSize'),color:v('detailColor')}];
  return {style:n('style'),pattern:n('pattern'),fabric:v('fabric'),sheerness:n('sheer'),colors:{front:v('front'),back:v('back'),trim:v('trim'),pattern:v('ink')},trims,details,unknown};
}
let seq=0;
function showCode(code,design){
 currentCode=code;currentDesign=design;$('code').textContent=code;
 $('frontImage').src='render.php?code='+encodeURIComponent(code)+'&side=front';
 $('backImage').src='render.php?code='+encodeURIComponent(code)+'&side=back';
}
async function update(changed){
 const my=++seq;try{
 let design=getDesign();
 if(importedDesign){
  const from=JSON.parse(JSON.stringify(importedDesign));
  const origin=(changed?.target?.id)||'';
  // Patch only the field the visitor changed. Preserve attributes not shown in this compact demo.
  if(origin==='style')from.style=design.style;
  else if(origin==='pattern')from.pattern=design.pattern;
  else if(origin==='fabric')from.fabric=design.fabric;
  else if(origin==='sheer')from.sheerness=design.sheerness;
  else if(['front','back','trim','ink'].includes(origin))from.colors[origin==='ink'?'pattern':origin]=design.colors[origin==='ink'?'pattern':origin];
  else if(['waistMat','waistEdge','waistColor','waistWidth'].includes(origin)){
   from.trims=from.trims.filter(t=>!(t.zone==='waist'&&(t.layer??0)===0));
   from.trims.push(...design.trims.filter(t=>t.zone==='waist'&&(t.layer??0)===0));
  }else if(['legsMat','legsEdge','legsColor','rightOverride','rightEdge','rightColor'].includes(origin)){
   from.trims=from.trims.filter(t=>!['left_leg','right_leg'].includes(t.zone)||(t.layer??0)!==0);
   from.trims.push(...design.trims.filter(t=>['left_leg','right_leg'].includes(t.zone)&&(t.layer??0)===0));
  }else if(['extraWaist','extraColor'].includes(origin)){
   from.trims=from.trims.filter(t=>!(t.zone==='waist'&&(t.layer??0)===1));
   from.trims.push(...design.trims.filter(t=>t.zone==='waist'&&(t.layer??0)===1));
  }else if(['detail','placement','detailColor','detailSize'].includes(origin)){
   const old=from.details.slice(1);from.details=[...design.details,...old];
  }
  design=from;
 }
 const response=await fetch('api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(design)});const result=await response.json();
 if(!response.ok||!result.ok)throw Error(result.error||'Encoding failed');if(my!==seq)return;
 showCode(result.code,result.design);
 importedDesign=result.design;
 $('status').textContent=result.design.format==='legacy'?'Original 12-character Panty Maker code':'Extended positional UPIC code';
 }catch(e){if(my===seq)$('status').textContent=e.message;}
}
function populate(d){
 $('style').value=d.style;$('pattern').value=d.pattern;$('fabric').value=d.fabric;$('sheer').value=d.sheerness;
 for(const [key,id] of Object.entries({front:'front',back:'back',trim:'trim',pattern:'ink'}))$(id).value=d.colors[key];
 const waist=d.trims.find(t=>(t.zone==='waist'||t.zone==='all_edges')&&(t.layer??0)===0);
 const leg=d.trims.find(t=>(t.zone==='left_leg'||t.zone==='all_edges')&&(t.layer??0)===0);
 const right=d.trims.find(t=>t.zone==='right_leg'&&(t.layer??0)===0);
 const extra=d.trims.find(t=>t.zone==='waist'&&(t.layer??0)===1);
 $('waistMat').value=waist?.material||'none';$('waistEdge').value=waist?.edge||'smooth';$('waistColor').value=waist?.color||d.colors.trim;$('waistWidth').value=waist?.width||2;
 $('legsMat').value=leg?.material||'none';$('legsEdge').value=leg?.edge||'smooth';$('legsColor').value=leg?.color||d.colors.trim;
 const same=leg&&right&&leg.material===right.material&&leg.edge===right.edge&&leg.color===right.color&&leg.width===right.width;
 $('rightOverride').value=same?'same':(right?.material||'none');$('rightEdge').value=right?.edge||'smooth';$('rightColor').value=right?.color||d.colors.trim;
 $('extraWaist').value=extra?.material||'none';$('extraColor').value=extra?.color||d.colors.trim;
 const detail=d.details[0];$('detail').value=detail?.type||'none';$('placement').value=detail?.at||'front_center';$('detailColor').value=detail?.color||d.colors.trim;$('detailSize').value=detail?.size||2;
 unknown=d.unknown||[];
 if(d.trims.some(t=>t.zone==='all_edges'||(t.layer??0)>1)||d.details.length>1){
  $('status').textContent='This design includes arrangements beyond the demo form; use the API for lossless editing.';
 }
}
const watch=document.querySelectorAll('select,input[type="color"],input[type="range"]');watch.forEach(input=>input.addEventListener('input',event=>update(event)));
$('load').addEventListener('click',async()=>{try{const response=await fetch('api.php?code='+encodeURIComponent(v('import').trim()));const result=await response.json();if(!response.ok||!result.ok)throw Error(result.error);populate(result.design);importedDesign=result.design;showCode(result.design.code,result.design);$('status').textContent='Imported '+result.design.format+' without changing the code';}catch(e){$('status').textContent=e.message;}});
document.querySelectorAll('[data-preset]').forEach(b=>b.addEventListener('click',async()=>{try{const r=await fetch('examples.json');const presets=await r.json();const entry=presets[b.dataset.preset];if(!entry)throw Error('Preset not found');populate(entry.design);importedDesign=entry.design;showCode(entry.code,entry.design);$('status').textContent='Loaded '+b.textContent+' — all attributes retained';}catch(e){$('status').textContent=e.message;}}));
$('copy').addEventListener('click',async()=>{try{await navigator.clipboard.writeText(currentCode);$('status').textContent='Code copied';}catch(e){$('status').textContent='Copy unavailable; select the code manually.';}});
$('copyUrl').addEventListener('click',async()=>{try{await navigator.clipboard.writeText(new URL('?code='+encodeURIComponent(currentCode),location.href).href);$('status').textContent='Preview link copied';}catch(e){$('status').textContent='Copy unavailable';}});
$('legacy').addEventListener('click',()=>{if(currentDesign){$('status').textContent='Base code: '+currentDesign.legacyCode+' (extended features not included)';}});
const initial=new URL(location.href).searchParams.get('code');if(initial){$('import').value=initial;$('load').click();}else update();
</script></body></html>
