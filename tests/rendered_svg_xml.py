#!/usr/bin/env python3
"""Validate every reference-rendered SVG with the Python standard XML parser."""
from pathlib import Path
import json
import subprocess
import xml.etree.ElementTree as ET
ROOT = Path(__file__).resolve().parent.parent
CODE = '''require "src/UPIC.php";
foreach(range(0,17) as $p){
  foreach(range(1,10) as $cut){
    $code=UPIC::encode(["style"=>$cut,"pattern"=>$p]);
    foreach(["front","back"] as $side){
      echo json_encode(["p"=>$p,"cut"=>$cut,"side"=>$side,"svg"=>UPIC::render($code,$side)],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES)."\\n";
    }
  }
}'''
run = subprocess.run(['php', '-r', CODE], cwd=ROOT, text=True, capture_output=True, check=True)
count = 0
for line in run.stdout.splitlines():
    data=json.loads(line)
    try: svg=ET.fromstring(data['svg'])
    except ET.ParseError as exc: raise AssertionError(f"Pattern {data['p']:02X}, cut {data['cut']}, {data['side']}: {exc}") from exc
    assert svg.tag=='{http://www.w3.org/2000/svg}svg'
    if data['p'] > 0:
        defs=svg.findall('.//{http://www.w3.org/2000/svg}pattern')
        assert any(p.get('id') == 'ptrn' for p in defs), data
    count+=1
assert count == 360, count
print(f'PASS {count} XML-validated SVGs: 18 prints × 10 cuts × 2 sides')
