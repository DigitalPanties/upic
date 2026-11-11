'use strict';
const assert=require('assert');
const d=require('../decoder.js');
function core(hex){assert.strictEqual(hex.length,18);return d.bytesToCode(Uint8Array.from(hex.match(/../g).map(x=>parseInt(x,16))))}
function extend(base,records){const prefix=d.toBytes(base),raw=Uint8Array.from([...prefix,...records]);const crc=d.crc32(raw);return d.bytesToCode(Uint8Array.from([...raw,(crc>>>24)&255,(crc>>>16)&255,(crc>>>8)&255,crc&255]))}
const raw='0F48090FCFFFFF9B04';
const legacy=core(raw);
assert.strictEqual(d.toBytes(legacy).length,9);
let a=d.decode(legacy);assert.strictEqual(a.legacy,true);assert.strictEqual(a.pattern,'Small hearts');assert.strictEqual(a.cut,'Cheeky Brazilian');assert.strictEqual(a.colors.front,'#FF99BB');assert.strictEqual(a.colors.trim,'#FFCCFF');
for(const [id,label] of [[11,'Small floral'],[12,'Large floral'],[13,'Leopard'],[14,'Lace look'],[15,'Stars'],[16,'Camouflage'],[17,'Plaid']]) {
 const hex=raw.slice(0,4)+id.toString(16).toUpperCase().padStart(2,'0')+raw.slice(6);
 const floral=d.decode(core(hex));
 assert.strictEqual(floral.pattern,label);
 assert.strictEqual(floral.patternRef,id);
}
const tlv=[0x01,0x01,0x02,0x10,0x07,0x01,0x02,0x01,0x03,0xff,0x7c,0xab,0x20,0x06,0x01,0x01,0x03,0xff,0xff,0xff,0x30,0x04,0x01,0xff,0x44,0x66];
const sample=extend(legacy,tlv);const x=d.decode(sample);
assert.strictEqual(x.legacy,false);assert.strictEqual(x.coreCode,legacy);assert.strictEqual(x.code.slice(0,12),legacy);assert.strictEqual(x.fabric,'Satin');assert.strictEqual(x.trims[0].edge,'Scalloped');assert.strictEqual(x.trims[0].material,'Lace');assert.strictEqual(x.details[0].name,'Bow');assert.strictEqual(x.colors.front,'#FF4466');
assert.match(d.makeBrief(x,{size:'M',quantity:'1 pair',note:'Please quote'}),/Satin/);
assert.match(d.makeBrief(x,{size:'M',quantity:'1 pair',note:'Please quote'}),/Please quote/);
const corrupted=sample.slice(0,-2)+(sample.slice(-2,-1)==='A'?'B':'A')+sample.slice(-1);assert.throws(()=>d.decode(corrupted),/CRC32|Base64/);
const unknown=extend(legacy,[0x32,0x02,0x6a,0x65]);assert.strictEqual(d.decode(unknown).unknownAttributes[0].tag,'32');
const high=extend(legacy,[0x41,0x01,0x01]);assert.strictEqual(d.decode(high).cutRef,260);
assert.throws(()=>d.decode(extend(legacy,[0x80,0x00])),/critical/);
assert.throws(()=>d.decode(extend(legacy,[0x10,0x07,0x05,0x02,0x01,0x03,0xff,0x7c,0xab])),/Invalid trim zone/);
assert.throws(()=>d.decode('nosuch?'),/UPIC uses/);
console.log('PASS original checks plus 14 registered-print assertions: legacy, extended prefix, fabric/trim/detail/color, buyer brief, checksum, unknown optional, high-order, critical rejection, invalid zone, bad characters');
console.log('sample='+sample);console.log('legacy='+legacy);
