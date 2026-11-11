#!/usr/bin/env python3
"""Generate cut-specific edge profiles from the *original SVG geometry*.
Build-only script; renderer uses assets/edge_profiles.json without Python.
Requires fontTools and shapely; no external APIs. Does not change original SVGs.
"""
import json,re,math,pathlib
from fontTools.svgLib.path import parse_path
from shapely.geometry import LineString
ROOT=pathlib.Path(__file__).resolve().parent.parent
art=json.loads((ROOT/'assets/artwork.json').read_text())

class SamplePen:
    def __init__(self):self.pt=None;self.start=None;self.points=[]
    def moveTo(self,pt):self.pt=complex(*pt);self.start=self.pt;self.points.append((self.pt.real,self.pt.imag))
    def lineTo(self,pt):self.pt=complex(*pt);self.points.append((self.pt.real,self.pt.imag))
    def curveTo(self,*pts):
        p0=self.pt;p1,p2,p3=(complex(*xy) for xy in pts)
        N=max(12,min(60,int((abs(p1-p0)+abs(p2-p1)+abs(p3-p2))*1.1)))
        for i in range(1,N+1):
            t=i/N;u=1-t;p=p0*u**3+3*p1*u*u*t+3*p2*u*t*t+p3*t**3
            self.points.append((p.real,p.imag))
        self.pt=p3
    def qCurveTo(self,*pts):
        for q in pts[:-1]:
            end=complex(*pts[-1]);control=complex(*q);start=self.pt
            for i in range(1,21):
                t=i/20;u=1-t;p=start*u*u+2*control*u*t+end*t*t;self.points.append((p.real,p.imag))
            self.pt=end
    def closePath(self):
        if abs(self.pt-self.start)>0.001:self.points.append((self.start.real,self.start.imag))
    def endPath(self):pass

def arc(points,i,j,inc=1):
    res=[];n=len(points);p=i
    while True:
        res.append(points[p]);
        if p==j:break
        p=(p+inc)%n
        if len(res)>n+1:raise ValueError('Cannot split')
    return res

def contains_index(i,j,needle,n,inc=1):
    k=i
    while k!=j:
        if k==needle:return True
        k=(k+inc)%n
    return j==needle

def shrink(p):
    simp=LineString(p).simplify(.06,preserve_topology=False)
    return [[round(x,3),round(y,3)] for x,y in simp.coords]

out={}
for key,svg in art['templates'].items():
    if key=='0':continue
    out[key]={}
    for side in ['front','back']:
        path=re.search(r"<path class='"+side.capitalize()+"'[^>]+d='([^']+)'",svg).group(1)
        pen=SamplePen();parse_path(path,pen)
        p=pen.points[:-1] if pen.points[0]==pen.points[-1] else pen.points
        n=len(p)
        i_left=min(range(n),key=lambda i:p[i][0]);i_right=max(range(n),key=lambda i:p[i][0]);i_bottom=max(range(n),key=lambda i:p[i][1]);
        if len({i_left,i_right,i_bottom})<3:raise Exception((key,side,'vertices collide'))
        wa=arc(p,i_left,i_right,1 if not contains_index(i_left,i_right,i_bottom,n,1) else -1)
        le=arc(p,i_left,i_bottom,1 if not contains_index(i_left,i_bottom,i_right,n,1) else -1)
        ri=arc(p,i_right,i_bottom,1 if not contains_index(i_right,i_bottom,i_left,n,1) else -1)
        # Normal directions are computed against the original polygon winding.
        area=sum((p[i][0]*p[(i+1)%n][1]-p[(i+1)%n][0]*p[i][1]) for i in range(n))/2
        # Edge traversal orientation relative to polygon needed to place outward scallops.
        def outward_sign(segment):
            # determine original winding direction for the initial segment
            xy=segment[0];a=next(i for i,v in enumerate(p) if v==xy)
            forward=segment[1]==p[(a+1)%n]
            # positive for left-normal in segment direction (dx,dy)->(dy,-dx)
            return (1 if area>0 else -1)*(1 if forward else -1)
        out[key][side]={
          'waist':{'points':shrink(wa),'outward':outward_sign(wa)},
          'left_leg':{'points':shrink(le),'outward':outward_sign(le)},
          'right_leg':{'points':shrink(ri),'outward':outward_sign(ri)}
        }
    print(key,art['names'].get(key),'points',*[len(out[key][side][z]['points']) for side in ('front','back') for z in ('waist','left_leg','right_leg')])
(ROOT/'assets/edge_profiles.json').write_text(json.dumps(out,separators=(',',':'))+'\n')
