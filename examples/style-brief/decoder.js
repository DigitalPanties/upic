/* UPIC Commerce Bridge: independent read-only parser for the 2026-11-10 positional UPIC protocol.
 * No transfer, authenticity, or ownership claims. Supports legacy core and CRC-checked TLV extensions.
 * License: MIT. Standalone browser consumer; the root UPIC registry remains authoritative. */
(function (root, factory) {
  const api = factory();
  if (typeof module === 'object' && module.exports) module.exports = api;
  root.UPICBridge = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
  'use strict';
  const cuts = ['','Boyshort','Control high-waist','Hipster','Cheeky Brazilian','Bikini','Slip','Tanga','String bikini','Thong','G-string'];
  const patterns = ['Solid','Small dots','Large dots','Small check','Large check','Narrow horizontal stripes','Narrow vertical stripes','Wide horizontal stripes','Wide vertical stripes','Small hearts','Large hearts','Small floral','Large floral','Leopard','Lace look','Stars','Camouflage','Plaid'];
  const fabrics = ['Unspecified','Cotton','Satin','Silk','Nylon','Mesh','Lace fabric','Microfiber','Stretch lace','Chiffon','Jersey','Velvet'];
  const zones = {1:'Waist',2:'Left leg',3:'Right leg',4:'All edges'};
  const materials = ['Plain','Elastic','Lace','Ribbon','Piping','Stitch','Crochet','Mesh','Satin binding','Beaded'];
  const edges = ['Smooth','Scalloped','Picot','Wavy','Ruffled','Zigzag','Eyelash fringe'];
  const details = {1:'Bow',2:'Rosette',3:'Heart',4:'Side tie',5:'Pearl',6:'Flower appliqué'};
  const placements = {1:'Front centre',2:'Left hip',3:'Right hip',4:'Back centre'};
  const partIds={1:'front',2:'back',3:'trim',4:'pattern'};
  const label=(table,n)=>Array.isArray(table)?(table[n] || 'Unregistered #'+n):(table[n] || 'Unregistered #'+n);
  const toHex=(n,len=2)=>n.toString(16).toUpperCase().padStart(len,'0');
  function crc32(bytes) { let c=0xFFFFFFFF; for (let b of bytes){c^=b;for(let i=0;i<8;i++)c=(c>>>1)^((c&1)?0xEDB88320:0);}return (c^0xFFFFFFFF)>>>0; }
  function toBytes(code) {
    if(typeof code!=='string') throw Error('A UPIC code is required.');
    code=code.trim();
    if(!code || !/^[A-Za-z0-9*|]+$/.test(code))throw Error('UPIC uses letters, digits, * and | only.');
    if(code.length>684)throw Error('Code exceeds the 512-byte protocol limit.');
    if(code.length%4===1)throw Error('Invalid Base64 code length.');
    const standard=code.replace(/\*/g,'+').replace(/\|/g,'/');
    let raw;
    try {if (typeof atob==='function')raw=atob(standard.padEnd(Math.ceil(standard.length/4)*4,'='));
      else raw=Buffer.from(standard,'base64').toString('binary');}
    catch {throw Error('UPIC is not valid modified Base64.');}
    const bytes=Uint8Array.from(raw, ch=>ch.charCodeAt(0));
    if(bytes.length<9 || bytes.length>512)throw Error('UPIC payload must be between 9 and 512 bytes.');
    // Reject permissive decoders' silent noncanonical extra chars.
    const canon = bytesToCode(bytes);
    if(canon!==code)throw Error('Non-canonical or invalid Base64 code.');
    return bytes;
  }
  function bytesToCode(bytes){
    let binary=''; for(const b of bytes)binary+=String.fromCharCode(b);
    return (typeof btoa==='function'?btoa(binary):Buffer.from(bytes).toString('base64'))
      .replace(/\+/g,'*').replace(/\//g,'|').replace(/=+$/,'');
  }
  function color3(h){return '#'+h.split('').map(c=>c+c).join('').toUpperCase();}
  function decode(code){
    const bytes=toBytes(code);
    if(bytes[0]>>>4!==0)throw Error('Original reserved nibble must be 0.');
    const h=Array.from(bytes.slice(0,9),x=>toHex(x)).join('');
    const base={
      patternColor:color3(h.slice(1,4)),patternRef:parseInt(h.slice(4,6),16),
      sheerness:parseInt(h.slice(6,7),16),trimColor:color3(h.slice(7,10)),
      backColor:color3(h.slice(10,13)),frontColor:color3(h.slice(13,16)),
      cutRef:parseInt(h.slice(16,18),16)};
    const trims=[],decorations=[],opaque=[],extraColors={},seen=new Set(),warnings=[];
    let fabric=0, cutHigh=null, patternHigh=null;
    if(bytes.length!==9){
      if(bytes.length<15)throw Error('Extended UPIC must contain TLV data and four CRC bytes.');
      const end=bytes.length-4;
      const expected=((bytes[end]*0x1000000)+(bytes[end+1]<<16)+(bytes[end+2]<<8)+bytes[end+3])>>>0;
      if(crc32(bytes.slice(0,end))!==expected)throw Error('CRC32 integrity check failed.');
      let i=9;
      while(i<end){
        if(i+2>end)throw Error('Incomplete UPIC attribute header.');
        const tag=bytes[i++], len=bytes[i++];
        if(i+len>end)throw Error('UPIC attribute value is truncated.');
        const v=bytes.slice(i,i+len);i+=len;
        if(tag===0 || tag>=0x80)throw Error('Reserved/critical UPIC tag '+toHex(tag)+' cannot be decoded.');
        const unique=(key)=>{if(seen.has(key))throw Error('Duplicate UPIC '+key+'.');seen.add(key);};
        if(tag===0x01){unique('fabric');if(len!==1)throw Error('Invalid fabric length.');fabric=v[0];}
        else if(tag===0x10 || tag===0x11){
          if(len!==(tag===0x10?7:8))throw Error('Invalid trim length.');
          const offset=tag===0x10?0:1, layer=tag===0x10?0:v[1], zone=v[0];
          if(zone<1||zone>4||layer>3||(tag===0x11 && layer===0)||v[3+offset]<1||v[3+offset]>5)throw Error('Invalid trim zone, layer or width.');
          unique('trim:'+zone+':'+layer);
          trims.push({zone,zoneName:label(zones,zone),layer,materialRef:v[1+offset],material:label(materials,v[1+offset]),edgeRef:v[2+offset],edge:label(edges,v[2+offset]),width:v[3+offset],color:'#'+Array.from(v.slice(4+offset,7+offset),x=>toHex(x)).join('')});
        }
        else if(tag===0x20){
          if(len!==6)throw Error('Invalid detail length.');
          decorations.push({ref:v[0],name:label(details,v[0]),placement:label(placements,v[1]),size:v[2],color:'#'+Array.from(v.slice(3,6),x=>toHex(x)).join('')});
        }
        else if(tag===0x30){
          if(len!==4 || !(v[0] in partIds))throw Error('Invalid exact-colour override.');
          unique('colour:'+v[0]);extraColors[partIds[v[0]]]='#'+Array.from(v.slice(1,4),x=>toHex(x)).join('');
        }
        else if(tag===0x41||tag===0x42){
          if(len<1||len>3)throw Error('Invalid high-order reference.');
          unique(tag===0x41?'cutHigh':'patternHigh');
          let value=0;for(const b of v)value=value*256+b;
          if(tag===0x41)cutHigh=value;else patternHigh=value;
        }
        else if(tag===0x40){
          opaque.push({tag:toHex(tag),bytes:Array.from(v,x=>toHex(x)).join('')});
          warnings.push('Legacy F1 compatibility tag 40 was preserved but not interpreted.');
        }
        else {opaque.push({tag:toHex(tag),bytes:Array.from(v,x=>toHex(x)).join('')});}
      }
    }
    const cutRef=base.cutRef+(cutHigh===null?0:cutHigh*256);
    const patternRef=base.patternRef+(patternHigh===null?0:patternHigh*256);
    if(cutRef===0)warnings.push('Cut reference 00 is not registered.');
    if(cutRef>=cuts.length || patternRef>=patterns.length || fabric>=fabrics.length)warnings.push('One or more references are unregistered; do not infer artwork.');
    return {
      code:code.trim(),legacy:bytes.length===9,coreCode:bytesToCode(bytes.slice(0,9)),bytes:bytes.length,
      cutRef,cut:label(cuts,cutRef),patternRef,pattern:label(patterns,patternRef),fabricRef:fabric,fabric:label(fabrics,fabric),
      sheerness:base.sheerness,colors:{front:extraColors.front||base.frontColor,back:extraColors.back||base.backColor,trim:extraColors.trim||base.trimColor,pattern:extraColors.pattern||base.patternColor},
      trims,details:decorations,unknownAttributes:opaque,warnings
    };
  }
  function makeBrief(d, opts={}) {
    const quantity=String(opts.quantity||'').trim();
    const size=String(opts.size||'').trim();
    const budget=String(opts.budget||'').trim();
    const note=String(opts.note||'').trim();
    const line=(a,b)=>a+': '+b;
    const lines=['PANTY STYLE REQUEST','',line('Style',d.cut),line('Fabric',d.fabric),line('Pattern',d.pattern),
      line('Front colour',d.colors.front),line('Back colour',d.colors.back),line('Base trim colour',d.colors.trim),line('Pattern colour',d.colors.pattern),line('Sheerness (0–15)',d.sheerness)];
    for(const t of d.trims) lines.push('Trim: '+t.zoneName+', layer '+t.layer+' — '+t.material+', '+t.edge+', width '+t.width+'/5, '+t.color);
    for(const t of d.details) lines.push('Detail: '+t.name+', '+t.placement+', size '+t.size+', '+t.color);
    if(quantity)lines.push(line('Quantity',quantity));
    if(size)lines.push(line('Requested garment size',size));
    if(budget)lines.push(line('Budget',budget));
    if(note)lines.push('Notes: '+note);
    lines.push('',line('UPIC design code',d.code),line('Preview', 'https://pantylane.com/maker/show.php?id='+encodeURIComponent(d.code)));
    lines.push('','This is a visual style reference, not a manufacturing specification, promise of supply, or proof of ownership.');
    return lines.join('\n');
  }
  return {decode,makeBrief,crc32,bytesToCode,toBytes};
});
