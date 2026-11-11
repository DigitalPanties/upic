<?php
declare(strict_types=1);
/** UPIC positional hexadecimal references with append-only attributes. */
final class UPIC {
    public const FABRICS = ['unspecified'=>0,'cotton'=>1,'satin'=>2,'silk'=>3,'nylon'=>4,'mesh'=>5,'lace'=>6,'microfiber'=>7,'stretch_lace'=>8,'chiffon'=>9,'jersey'=>10,'velvet'=>11];
    public const ZONES = ['waist'=>1,'left_leg'=>2,'right_leg'=>3,'all_edges'=>4];
    public const MATERIALS = ['plain'=>0,'elastic'=>1,'lace'=>2,'ribbon'=>3,'piping'=>4,'stitch'=>5,'crochet'=>6,'mesh'=>7,'satin_binding'=>8,'beaded'=>9];
    public const EDGES = ['smooth'=>0,'scalloped'=>1,'picot'=>2,'wavy'=>3,'ruffled'=>4,'zigzag'=>5,'eyelash'=>6];
    public const DETAILS = ['bow'=>1,'rosette'=>2,'heart'=>3,'side_tie'=>4,'pearl'=>5,'flower'=>6];
    public const PLACEMENTS = ['front_center'=>1,'left_hip'=>2,'right_hip'=>3,'back_center'=>4];
    private const PARTS = ['front'=>1,'back'=>2,'trim'=>3,'pattern'=>4];
    private static ?array $artwork = null;
    private static ?array $anchors = null;
    private static ?array $edgeProfiles = null;

    private static function art(): array {
        if (self::$artwork === null) self::$artwork = json_decode(file_get_contents(__DIR__.'/../assets/artwork.json'),true,512,JSON_THROW_ON_ERROR);
        return self::$artwork;
    }
    private static function anchor(int $cut, string $field): float {
        if (self::$anchors===null) self::$anchors=json_decode(
            file_get_contents(__DIR__.'/../assets/cut_anchors.json'),true,512,JSON_THROW_ON_ERROR
        );
        if (!isset(self::$anchors[(string)$cut][$field]))
            throw new InvalidArgumentException('Cut reference 0x'.strtoupper(dechex($cut)).' needs registered '.$field.' anchors');
        return (float)self::$anchors[(string)$cut][$field];
    }
    private static function item(array $map, $value, string $what): int {
        if (is_string($value) && isset($map[$value])) return $map[$value];
        if (is_int($value) && $value>=0 && $value<=255) return $value;
        throw new InvalidArgumentException('Invalid '.$what);
    }
    private static function name(array $map,int $v): string|int { $name=array_search($v,$map,true); return $name===false?$v:$name; }
    private static function integer($v,int $min,int $max,string $name): int {
        if (!is_int($v) || $v<$min || $v>$max) throw new InvalidArgumentException('Invalid '.$name.' ('.$min.'..'.$max.')');
        return $v;
    }
    private static function rgb($color): string {
        if (!is_string($color) || !preg_match('/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/D',$color)) throw new InvalidArgumentException('Invalid colour');
        $c=strtolower(ltrim($color,'#'));
        if(strlen($c)===3) $c=$c[0].$c[0].$c[1].$c[1].$c[2].$c[2];
        return '#'.$c;
    }
    private static function shrink(string $c): string {
        $out='';for($i=1;$i<7;$i+=2) $out.=dechex((int)round(hexdec(substr($c,$i,2))/17));
        return $out;
    }
    private static function expand(string $c): string {return '#'.$c[0].$c[0].$c[1].$c[1].$c[2].$c[2];}
    // Use the same text alphabet as original Panty Maker codes at every length.
    // The old 9-byte core is a multiple of 3, so its original 12-character
    // code is an EXACT prefix of any appended byte stream.
    private static function textEncode(string $bytes): string {
        return rtrim(strtr(base64_encode($bytes), '+/', '*|'), '=');
    }
    private static function textDecode(string $s): string {
        if (!preg_match('/^[A-Za-z0-9*|]+$/D', $s) || strlen($s) % 4 === 1)
            throw new InvalidArgumentException('Invalid design-code alphabet or length');
        $raw = base64_decode(strtr($s, '*|', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4), true);
        if ($raw === false || self::textEncode($raw) !== $s)
            throw new InvalidArgumentException('Noncanonical encoded design');
        return $raw;
    }
    private static function core(string $raw,bool $extended=false): array {
        if(strlen($raw)!==9) throw new InvalidArgumentException('Legacy core must be 9 bytes');
        $h=bin2hex($raw);
        // Original hexadecimal layout: 0 + PPP + NN + S + TTT + BBB + FFF + CC.
        $style=hexdec(substr($h,16,2)); $pattern=hexdec(substr($h,4,2));$sheer=hexdec(substr($h,6,1));
        if ($h[0]!=='0'|| (!$extended && $style<1)) throw new InvalidArgumentException('Invalid positional core');
        $colors=['front'=>self::expand(substr($h,13,3)),'back'=>self::expand(substr($h,10,3)),
                 'trim'=>self::expand(substr($h,7,3)),'pattern'=>self::expand(substr($h,1,3))];
        // Keep the original 12-bit colour slots separate from 24-bit overrides.
        // Decoding and re-encoding an extended code must not silently rewrite its 9-byte core.
        return ['style'=>$style,'styleName'=>self::art()['names'][(string)$style]??('Unregistered cut 0x'.strtoupper(str_pad(dechex($style),2,'0',STR_PAD_LEFT))),'pattern'=>$pattern,'sheerness'=>$sheer,
                'colors'=>$colors,'coreColors'=>$colors,'fabric'=>'unspecified','trims'=>[],'details'=>[],'unknown'=>[]];
    }
    private static function oldEncode(string $core): string {return strtr(base64_encode($core),'+/','*|');}
    private static function oldDecode(string $code): string {
        if (!preg_match('/^[A-Za-z0-9*|+\/]{12}$/D',$code)) throw new InvalidArgumentException('Legacy code must be 12 characters');
        $canonical=strtr($code,'*|','+/');$raw=base64_decode($canonical,true);
        if ($raw===false||strlen($raw)!==9||base64_encode($raw)!==$canonical) throw new InvalidArgumentException('Malformed legacy code');
        return $raw;
    }
    public static function decode(string $code): array {
        if(strlen($code)>1024) throw new InvalidArgumentException('Code too long');
        if(strlen($code)===12) {
            $raw=self::oldDecode($code); $d=self::core($raw);
            return ['format'=>'legacy','version'=>0,'code'=>self::oldEncode($raw),'legacyCode'=>self::oldEncode($raw)]+$d;
        }
        // No textual header. Bytes 0..8 are always the ORIGINAL Maker code.
        // Byte 9 introduces the extension format and version, not a new ID.
        $bytes=self::textDecode($code);
        if(strlen($bytes)<16 || strlen($bytes)>512)
            throw new InvalidArgumentException('Extended design length invalid');
        $body=substr($bytes,0,-4); $check=substr($bytes,-4);
        if(!hash_equals(hex2bin(hash('crc32b',$body)),$check))
            throw new InvalidArgumentException('Checksum mismatch');
        $raw=substr($body,0,9);
        // 0xF1 is accepted only for previously issued extended codes.
        // Newly generated codes begin extension TLVs directly at byte 9.
        $priorFormat=ord($body[9])===0xf1;
        $d=self::core($raw,true);
        $legacy=self::oldEncode($raw);
        if(substr($code,0,12)!==$legacy) throw new InvalidArgumentException('Legacy prefix mismatch');
        $d['format']='upic-extended'; $d['version']=$priorFormat?1:2; $d['code']=$code; $d['legacyCode']=$legacy;
        $used=[]; $p=$priorFormat?10:9; $len=strlen($body);
        while($p<$len){
            if($p+2>$len)throw new InvalidArgumentException('Truncated extension header');
            $tag=ord($body[$p++]);$size=ord($body[$p++]);
            if($tag===0)throw new InvalidArgumentException('Reserved extension tag');
            if($p+$size>$len)throw new InvalidArgumentException('Truncated extension value');
            $v=substr($body,$p,$size);$p+=$size;
            if(in_array($tag,[1,0x30,0x40,0x41,0x42],true)){
                $key=$tag===0x30?($tag.':'.(strlen($v)?ord($v[0]):0)):(string)$tag;
                if(isset($used[$key]))throw new InvalidArgumentException('Duplicate singleton extension');$used[$key]=true;
            }
            if($tag===1){if($size!==1)throw new InvalidArgumentException('Invalid fabric TLV');$d['fabric']=self::name(self::FABRICS,ord($v[0]));if($d['fabric']===0)throw new InvalidArgumentException('Zero fabric record must be omitted');}
            elseif($tag===0x10 || $tag===0x11){
                if($size!==($tag===0x10?7:8))throw new InvalidArgumentException('Invalid trim TLV');
                $z=ord($v[0]);$layer=$tag===0x10?0:ord($v[1]);$off=$tag===0x10?1:2;
                $m=ord($v[$off]);$e=ord($v[$off+1]);$w=ord($v[$off+2]);
                if($z===0||$layer>3||($tag===0x11&&$layer===0)||$w<1||$w>5)throw new InvalidArgumentException('Invalid trim attribute');
                $zone=self::name(self::ZONES,$z);
                foreach($d['trims'] as $prev)if($prev['zone']===$zone&&($prev['layer']??0)===$layer)throw new InvalidArgumentException('Duplicate trim zone/layer');
                $entry=['zone'=>$zone,'material'=>self::name(self::MATERIALS,$m),'edge'=>self::name(self::EDGES,$e),'width'=>$w,'color'=>'#'.bin2hex(substr($v,$off+3,3))];
                if($layer)$entry['layer']=$layer;
                $d['trims'][]=$entry;
            }
            elseif($tag===0x20){
                if($size!==6)throw new InvalidArgumentException('Invalid detail TLV');$t=ord($v[0]);$loc=ord($v[1]);$s=ord($v[2]);
                if($t===0||$loc===0||$s<1||$s>5)throw new InvalidArgumentException('Invalid detail attribute');
                $d['details'][]=['type'=>self::name(self::DETAILS,$t),'at'=>self::name(self::PLACEMENTS,$loc),'size'=>$s,'color'=>'#'.bin2hex(substr($v,3,3))];
            }
            elseif($tag===0x30){
                if($size!==4)throw new InvalidArgumentException('Invalid colour TLV');$part=self::name(self::PARTS,ord($v[0]));
                if(!is_string($part))throw new InvalidArgumentException('Unknown colour part');$d['colors'][$part]='#'.bin2hex(substr($v,1,3));
            }
            elseif($tag===0x40){
                // Compatibility with the old F1 implementation: it represented
                // patterns 09 and 0A in an extra TLV instead of core byte 4.
                if(!$priorFormat||$size!==1||!in_array(ord($v[0]),[9,10],true)||$d['pattern']!==0)
                    throw new InvalidArgumentException('Obsolete pattern TLV');
                $d['pattern']=ord($v[0]);
            }
            elseif($tag===0x41 || $tag===0x42){
                // Hex reference grows by appending high-order bytes. The
                // original two-nibble low byte never moves or changes meaning.
                if($priorFormat||$size<1||$size>3||ord($v[0])===0)
                    throw new InvalidArgumentException('Invalid high-order reference');
                $upper=hexdec(bin2hex($v));
                $field=$tag===0x41?'style':'pattern';
                $d[$field]=$upper*256+$d[$field];
            }
            elseif($tag>=0x80)throw new InvalidArgumentException('Unknown critical TLV '.dechex($tag));
            else $d['unknown'][]=['tag'=>$tag,'hex'=>bin2hex($v)];
        }
        // Canonical representation: equivalent designs have one code under this protocol.
        if($d['style']<1) throw new InvalidArgumentException('Zero cut reference');
        $d['styleName']=self::art()['names'][(string)$d['style']]??('Unregistered cut 0x'.strtoupper(dechex($d['style'])));
        // Older F1 codes remain readable and can be upgraded when re-encoded.
        if(!$priorFormat&&self::encode($d)!==$code)throw new InvalidArgumentException('Noncanonical extended design code');
        return $d;
    }
    private static function tlv(int $tag,string $value): string {
        if(strlen($value)>255)throw new InvalidArgumentException('TLV too long');return chr($tag).chr(strlen($value)).$value;
    }
    public static function encode(array $data, bool $forceNew=false): string {
        $style=self::integer($data['style']??5,1,0xffffffff,'style');
        $pattern=self::integer($data['pattern']??0,0,0xffffffff,'pattern');
        $sheer=self::integer($data['sheerness']??0,0,15,'sheerness');
        $colors=[];$coarse=[];$overrides='';
        $default=['front'=>'#e888aa','back'=>'#e888aa','trim'=>'#ffffff','pattern'=>'#ffffff'];
        foreach(self::PARTS as $part=>$key){
            $colors[$part]=self::rgb($data['colors'][$part]??$default[$part]);
            $coarse[$part]=self::shrink($colors[$part]);
        }
        // Extended codes are allowed to specify an exact RGB override while
        // retaining the ORIGINAL coarse RGB nibble values. When re-encoding a
        // decoded code, preserve its 9-byte core instead of re-quantizing it.
        // Earlier F1 codes require migration of their obsolete pattern slot.
        if(isset($data['legacyCode']) && ($data['version']??null)!==1){
            $core=self::oldDecode((string)$data['legacyCode']);
            $original=self::core($core,true);
            if($original['style']!==($style & 255) || $original['pattern']!==($pattern & 255) || $original['sheerness']!==$sheer)
                throw new InvalidArgumentException('Legacy core conflicts with design references');
            $baseColors=$original['colors'];
        }else{
            $hex='0'.$coarse['pattern'].sprintf('%02x',$pattern&255).sprintf('%x',$sheer).
                 $coarse['trim'].$coarse['back'].$coarse['front'].sprintf('%02x',$style&255);
            $core=hex2bin($hex);if($core===false)throw new InvalidArgumentException('Invalid core');
            $baseColors=[];
            foreach(self::PARTS as $part=>$key)$baseColors[$part]=self::expand($coarse[$part]);
        }
        foreach(self::PARTS as $part=>$key){
            if($colors[$part]!==$baseColors[$part])
                $overrides.=self::tlv(0x30,chr($key).hex2bin(substr($colors[$part],1)));
        }
        $extensions='';
        // Only after the original one-byte field is exhausted do we add
        // high-order hexadecimal reference bytes; no new code family needed.
        foreach(['style'=>0x41,'pattern'=>0x42] as $field=>$tag){
            $ref=$field==='style'?$style:$pattern;
            if($ref>255){
                $high=$ref>>8;
                $highHex=dechex($high);
                if(strlen($highHex)%2)$highHex='0'.$highHex;
                $extensions.=self::tlv($tag,hex2bin($highHex));
            }
        }
        $fabric=self::item(self::FABRICS,$data['fabric']??'unspecified','fabric');
        if($fabric!==0)$extensions.=self::tlv(1,chr($fabric));
        $trims=$data['trims']??[];
        if(!is_array($trims)||count($trims)>16)throw new InvalidArgumentException('Too many trim zones/layers');
        $unique=[];$trimBytes=[];
        foreach($trims as $t){
            if(!is_array($t))throw new InvalidArgumentException('Invalid trim');
            $zone=self::item(self::ZONES,$t['zone']??null,'trim zone');
            if($zone<1)throw new InvalidArgumentException('Reserved trim placement 00');
            $layer=self::integer($t['layer']??0,0,3,'trim layer');
            $identity=$zone.':'.$layer;
            if(isset($unique[$identity]))throw new InvalidArgumentException('Duplicate trim zone/layer');$unique[$identity]=true;
            $material=self::item(self::MATERIALS,$t['material']??'plain','trim material');
            $edge=self::item(self::EDGES,$t['edge']??'smooth','trim edge');
            $width=self::integer($t['width']??2,1,5,'trim width');
            $color=self::rgb($t['color']??$colors['trim']);
            $value=chr($zone).($layer?chr($layer):'').chr($material).chr($edge).chr($width).hex2bin(substr($color,1));
            $trimBytes[sprintf('%02d-%02d',$zone,$layer)]=self::tlv($layer?0x11:0x10,$value);
        }
        ksort($trimBytes,SORT_NUMERIC);$extensions.=implode('',$trimBytes);
        $details=$data['details']??[];
        if(!is_array($details)||count($details)>12)throw new InvalidArgumentException('Too many details');
        $detailBytes=[];
        foreach($details as $d){
            if(!is_array($d))throw new InvalidArgumentException('Invalid detail');
            $type=self::item(self::DETAILS,$d['type']??null,'detail type');
            $at=self::item(self::PLACEMENTS,$d['at']??null,'detail placement');
            $size=self::integer($d['size']??2,1,5,'detail size');
            $c=self::rgb($d['color']??$colors['trim']);
            $detailBytes[]=self::tlv(0x20,chr($type).chr($at).chr($size).hex2bin(substr($c,1)));
        }
        sort($detailBytes,SORT_STRING);$extensions.=implode('',$detailBytes);
        $extensions.=$overrides;
                foreach($data['unknown']??[] as $u){
            if(!is_array($u)||!is_int($u['tag']??null)||$u['tag']<2||$u['tag']>=128||in_array($u['tag'],[0x10,0x11,0x20,0x30,0x40,0x41,0x42],true)|| !is_string($u['hex']??null)||strlen($u['hex'])%2||!ctype_xdigit($u['hex']))throw new InvalidArgumentException('Invalid opaque TLV');
            $extensions.=self::tlv($u['tag'],hex2bin($u['hex']));
        }
        // A design with no extensions MUST remain an original 12-character code.
        if($extensions==='')return self::oldEncode($core);
        // Append typed attribute bytes directly after the original 9 bytes.
        $body=$core.$extensions;
        $blob=$body.hex2bin(hash('crc32b',$body));
        if(strlen($blob)>512)throw new InvalidArgumentException('Design too large');
        return self::textEncode($blob);
    }
    private static function stylePath(array $art,int $style,string $side): string {
        if(!isset($art['templates'][(string)$style]))throw new InvalidArgumentException('Cut reference 0x'.strtoupper(dechex($style)).' has no registered SVG geometry');
        $template=$art['templates'][(string)$style];
        if(!preg_match("/<path class='".ucfirst($side)."'[^>]* d='([^']+)'/",$template,$m))throw new RuntimeException('Missing template path');
        return $m[1];
    }
    private static function attr(string $x): string {return htmlspecialchars($x,ENT_QUOTES|ENT_XML1,'UTF-8');}
    private static function defsExtras(array $d,string $side): string {
        $color=$d['colors']['pattern'];$base=$d['colors'][$side];
        if($d['pattern']===9||$d['pattern']===10){
            $unit=$d['pattern']===9?7:13;$size=$d['pattern']===9?1.7:3.3;
            // Mathematically symmetrical heart silhouette from two arcs and two quadratic curves.
            $heart="M 0 -0.5 C -0.8 -1.4 -2.2 -0.2 0 1.5 C 2.2 -0.2 0.8 -1.4 0 -0.5 Z";
            $pattern='<pattern id="ptrn" width="'.$unit.'" height="'.$unit.'" patternUnits="userSpaceOnUse"><rect width="100%" height="100%" fill="'.$base.'"/><path d="'.$heart.'" transform="translate('.($unit/2).' '.($unit/2).') scale('.($size/2).')" fill="'.$color.'"/></pattern>';
        }elseif($d['pattern']>0){
            $pattern=str_replace(['rgb(66,66,66)','rgb(255,255,255)'],[$color,$base],self::art()['patterns'][(string)$d['pattern']]);
        }else{$pattern='';}
        $fabric=$d['fabric'];$texture='';
        if($fabric==='cotton'||$fabric==='microfiber')$texture='<pattern id="upic-fabric" width="3" height="3" patternUnits="userSpaceOnUse"><circle cx="1" cy="1" r=".22" fill="#fff" opacity=".36"/></pattern>';
        elseif($fabric==='mesh')$texture='<pattern id="upic-fabric" width="3" height="3" patternUnits="userSpaceOnUse"><path d="M0 0L3 3M3 0L0 3" stroke="#fff" stroke-width=".28" opacity=".45"/></pattern>';
        elseif($fabric==='lace'||$fabric==='stretch_lace')$texture='<pattern id="upic-fabric" width="9" height="9" patternUnits="userSpaceOnUse"><path d="M4.5 1Q9 4.5 4.5 8Q0 4.5 4.5 1ZM0 4.5H9M4.5 0V9" stroke="#fff" stroke-width=".45" opacity=".6" fill="none"/><circle cx="4.5" cy="4.5" r="1.3" fill="#fff" opacity=".14"/></pattern>';
        elseif($fabric==='chiffon')$texture='<pattern id="upic-fabric" width="4" height="4" patternUnits="userSpaceOnUse"><path d="M0 0L4 4M2 0L4 2" stroke="#fff" opacity=".32" stroke-width=".2"/></pattern>';
        elseif($fabric==='velvet')$texture='<pattern id="upic-fabric" width="2" height="2" patternUnits="userSpaceOnUse"><circle cx=".5" cy=".8" r=".45" fill="#12091e" opacity=".20"/><circle cx="1.5" cy="1.5" r=".35" fill="#fff" opacity=".11"/></pattern>';
        elseif(in_array($fabric,['satin','silk','nylon'],true))$texture='<linearGradient id="upic-fabric" x1="0" y1="0" x2="1" y2=".3"><stop offset="0" stop-color="#fff" stop-opacity=".05"/><stop offset=".27" stop-color="#fff" stop-opacity=".40"/><stop offset=".43" stop-color="#fff" stop-opacity=".02"/><stop offset=".83" stop-color="#fff" stop-opacity=".25"/><stop offset="1" stop-color="#fff" stop-opacity=".04"/></linearGradient>';
        if($fabric==='jersey')$texture='<pattern id="upic-fabric" width="2.8" height="2.8" patternUnits="userSpaceOnUse"><path d="M0 0Q1.4 1 2.8 0M0 1.4Q1.4 2.4 2.8 1.4" fill="none" stroke="#fff" stroke-width=".16" opacity=".35"/></pattern>';
        return $pattern.$texture;
    }
    private static function edgeProfiles(): array {
        if(self::$edgeProfiles===null)self::$edgeProfiles=json_decode(
            file_get_contents(__DIR__.'/../assets/edge_profiles.json'),true,512,JSON_THROW_ON_ERROR
        );
        return self::$edgeProfiles;
    }
    private static function fmt(float $n): string {return rtrim(rtrim(sprintf('%.3f',$n),'0'),'.') ?: '0';}
    private static function point(array $samples,float $s): array {
        $s=max(0,min($s,$samples['total']));
        $lengths=$samples['lengths'];$pts=$samples['points'];$n=count($pts);
        for($i=0;$i<$n-1;$i++){
            if($s<=$lengths[$i+1] || $i===$n-2){
                $span=max(0.000001,$lengths[$i+1]-$lengths[$i]);
                $t=($s-$lengths[$i])/$span;$dx=($pts[$i+1][0]-$pts[$i][0])/$span;
                $dy=($pts[$i+1][1]-$pts[$i][1])/$span;
                $sense=$samples['outward'];
                return [$pts[$i][0]+$t*($pts[$i+1][0]-$pts[$i][0]),$pts[$i][1]+$t*($pts[$i+1][1]-$pts[$i][1]),$sense*$dy,-$sense*$dx];
            }
        }
        throw new RuntimeException('Invalid contour samples');
    }
    private static function samples(array $profile): array {
        $pts=$profile['points'];if(count($pts)<2)throw new RuntimeException('Invalid registered edge');
        $lens=[0.0];for($i=1;$i<count($pts);$i++){
            $len=hypot($pts[$i][0]-$pts[$i-1][0],$pts[$i][1]-$pts[$i-1][1]);
            $lens[]=$lens[$i-1]+$len;
        }
        return ['points'=>$pts,'outward'=>$profile['outward'],'lengths'=>$lens,'total'=>end($lens)];
    }
    private static function edgePath(array $points): string {
        $pieces=[];
        foreach($points as $p)$pieces[]=self::fmt((float)$p[0]).','.self::fmt((float)$p[1]);
        return 'M'.implode(' L',$pieces);
    }
    private static function trimShape(array $profile,array $t): string {
        $data=self::samples($profile);$pts=$data['points'];$total=$data['total'];
        $width=(float)$t['width'];$color=self::attr($t['color']);
        $stroke=self::fmt(0.35+$width*.29);$path=self::attr(self::edgePath($pts));
        $mat=$t['material'];$edge=$t['edge'];$layer=$t['layer']??0;
        $baseOpacity=$layer>0?'.81':'.94';
        $out='<path d="'.$path.'" fill="none" stroke="'.$color.'" stroke-width="'.$stroke.'" opacity="'.$baseOpacity.'" stroke-linecap="round" stroke-linejoin="round"/>';
        if(in_array($mat,['ribbon','satin_binding'],true)){
            $out.='<path d="'.$path.'" fill="none" stroke="#fff" stroke-width="'.self::fmt($width*.14).'" opacity=".52"/>';
        }elseif($mat==='piping'){
            $out.='<path d="'.$path.'" fill="none" stroke="'.$color.'" stroke-width=".32" opacity=".95"/>';
        }elseif($mat==='stitch'){
            $out.='<path d="'.$path.'" fill="none" stroke="#fff" stroke-width=".22" stroke-dasharray=".58 .5" opacity=".9"/>';
        }elseif($mat==='mesh'){
            $out.='<path d="'.$path.'" fill="none" stroke="#fff" stroke-width=".45" stroke-dasharray=".35 .55" opacity=".7"/>';
        }
        // Lace/crochet motifs are placed along the CUT-SPECIFIC contour, not
        // over the entire garment silhouette. No rectangle clipping is used.
        if(in_array($mat,['lace','crochet','beaded'],true)){
            $period=$mat==='crochet'?2.6:($mat==='beaded'?2.15:2.05);
            for($s=$period/2;$s<$total;$s+=$period){
                [$x,$y,$nx,$ny]=self::point($data,$s);
                $x+=$nx*($width*.16);$y+=$ny*($width*.16);
                $r=$mat==='beaded'?.34:($mat==='crochet'?.67:.54);
                if($mat==='beaded'){
                    $out.='<circle cx="'.self::fmt($x).'" cy="'.self::fmt($y).'" r="'.$r.'" fill="#fff" stroke="'.$color.'" stroke-width=".18"/>';
                }else{
                    $out.='<circle cx="'.self::fmt($x).'" cy="'.self::fmt($y).'" r="'.$r.'" fill="none" stroke="'.$color.'" stroke-width=".27"/>';
                    $out.='<circle cx="'.self::fmt($x).'" cy="'.self::fmt($y).'" r=".19" fill="#fff" opacity=".75"/>';
                }
            }
        }
        if($edge==='scalloped' || $edge==='wavy' || $edge==='ruffled' || $edge==='zigzag'){
            $period=$edge==='scalloped'?2.35:($edge==='ruffled'?1.65:($edge==='zigzag'?1.55:2.5));
            $n=max(1,(int)round($total/$period));$step=$total/$n;
            $amplitude=$edge==='ruffled'?(.55+$width*.18):(.45+$width*.13);
            $out.='<path fill="none" stroke="'.$color.'" stroke-width=".32" stroke-linecap="round" stroke-linejoin="round" d="';
            [$x,$y]=self::point($data,0);$out.='M'.self::fmt($x).','.self::fmt($y);
            for($i=0;$i<$n;$i++){
                $start=$i*$step;$end=($i+1)*$step;$mid=($start+$end)/2;
                [$mx,$my,$nx,$ny]=self::point($data,$mid);[$ex,$ey]=self::point($data,$end);
                if($edge==='scalloped'||$edge==='ruffled'){
                    $out.=' Q'.self::fmt($mx+2*$nx*$amplitude).','.self::fmt($my+2*$ny*$amplitude).' '.self::fmt($ex).','.self::fmt($ey);
                }elseif($edge==='zigzag'){
                    $out.=' L'.self::fmt($mx+$nx*$amplitude).','.self::fmt($my+$ny*$amplitude).' '.self::fmt($ex).','.self::fmt($ey);
                }else{
                    [$q1x,$q1y,$n1x,$n1y]=self::point($data,$start+$step*.25);
                    [$q3x,$q3y,$n3x,$n3y]=self::point($data,$start+$step*.75);
                    $out.=' Q'.self::fmt($q1x+$n1x*$amplitude).','.self::fmt($q1y+$n1y*$amplitude).' '.self::fmt($mx).','.self::fmt($my);
                    $out.=' Q'.self::fmt($q3x-$n3x*$amplitude).','.self::fmt($q3y-$n3y*$amplitude).' '.self::fmt($ex).','.self::fmt($ey);
                }
            }
            $out.='"/>';
        }elseif($edge==='picot'||$edge==='eyelash'){
            $step=$edge==='picot'?1.4:.82;
            for($s=$step/2;$s<$total;$s+=$step){
                [$x,$y,$nx,$ny]=self::point($data,$s);
                $length=$edge==='eyelash'?(.72+$width*.16):(.39+$width*.1);
                if($edge==='picot')$out.='<circle cx="'.self::fmt($x+$nx*$length).'" cy="'.self::fmt($y+$ny*$length).'" r=".18" fill="'.$color.'"/>';
                else $out.='<path d="M'.self::fmt($x).','.self::fmt($y).' L'.self::fmt($x+$nx*$length).','.self::fmt($y+$ny*$length).'" stroke="'.$color.'" stroke-width=".19"/>';
            }
        }
        return $out;
    }
    private static function trimOverlay(array $d,string $side): string {
        if(!$d['trims'])return '';
        $profiles=self::edgeProfiles()[(string)$d['style']][$side]??null;
        if(!$profiles)throw new InvalidArgumentException('Cut reference has no registered waist/leg contours');
        $trims=$d['trims'];$out='<g id="upic-contour-trims">';
        // An all-edges layer provides a default, which an explicit waist/leg
        // declaration at the SAME layer overrides. Independent layers stack.
        for($layer=0;$layer<=3;$layer++){
            $all=null;$overrides=[];
            foreach($trims as $t){
                if(($t['layer']??0)!==$layer)continue;
                if($t['zone']==='all_edges')$all=$t;
                else $overrides[$t['zone']]=$t;
            }
            foreach(['waist','left_leg','right_leg'] as $zone){
                $t=$overrides[$zone]??$all;if(!$t)continue;
                $out.='<g data-zone="'.$zone.'" data-layer="'.$layer.'" data-material="'.self::attr((string)$t['material']).'" data-edge="'.self::attr((string)$t['edge']).'">';
                $out.=self::trimShape($profiles[$zone],$t).'</g>';
            }
        }
        return $out.'</g>';
    }
    private static function detailOverlay(array $d,string $side): string {
        if(!$d['details'])return '';
        $out='';$y=self::anchor($d['style'],'detailY');
        foreach($d['details'] as $t){
            if($side==='front'&&$t['at']==='back_center')continue;
            if($side==='back'&&$t['at']!=='back_center')continue;
            [$x,$dy]=match($t['at']){'front_center'=>[34.6,$y],'back_center'=>[34.6,$y], 'left_hip'=>[13,$y+3],'right_hip'=>[56,$y+3]};
            $scale=.5+($t['size']*.18);$color=self::attr($t['color']);
            $out.='<g transform="translate('.$x.' '.$dy.') scale('.$scale.')" fill="'.$color.'" stroke="'.self::attr($d['colors']['trim']).'" stroke-width=".32">';
            if($t['type']==='bow')$out.='<path d="M0 0C-9 -7 -9 7 0 0C9 -7 9 7 0 0Z"/><circle r="1.3"/>';
            elseif($t['type']==='rosette')$out.='<path d="M0 -4Q5 -8 4 -2Q9 2 3 3Q0 9 -3 3Q-9 2 -4 -2Q-5 -8 0 -4"/><circle r="1.4" fill="#fff"/>';
            elseif($t['type']==='heart')$out.='<path d="M0 4C-10 -2 -5 -8 0 -3C5 -8 10 -2 0 4Z"/>';
            elseif($t['type']==='pearl')$out.='<circle r="2.4" fill="#fff8f0" stroke="'.$color.'" stroke-width=".8"/><circle cx="-.65" cy="-.65" r=".7" fill="white"/>';
            elseif($t['type']==='flower')$out.='<path d="M0 -5C2 -9 5 -6 3 -3C8 -3 8 1 3 2C4 7 0 8 -1 3C-6 6 -8 2 -4 -1C-8 -4 -4 -8 0 -5Z"/><circle r="1.3" fill="white"/>';
            elseif($t['type']==='side_tie')$out.='<path d="M0 -2Q-6 -5 -3 4L-1 8M0 -2Q6 -5 3 4L1 8" fill="none" stroke="'.$color.'" stroke-width="1.2"/><circle cy="-2" r="1.1"/>';
            $out.='</g>';
        }
        return $out;
    }
    public static function render($codeOrDesign,string $side='front'): string {
        if(!in_array($side,['front','back'],true))throw new InvalidArgumentException('Side must be front or back');
        $d=is_string($codeOrDesign)?self::decode($codeOrDesign):self::decode(self::encode($codeOrDesign));
        $art=self::art();
        if(!isset($art['templates'][(string)$d['style']]))
            throw new InvalidArgumentException('Cut reference 0x'.strtoupper(dechex($d['style'])).' is not registered for rendering');
        if($d['pattern']>10 && !isset($art['patterns'][(string)$d['pattern']]))
            throw new InvalidArgumentException('Pattern reference 0x'.strtoupper(dechex($d['pattern'])).' is not registered for rendering');
        if(!is_string($d['fabric']) || !array_key_exists($d['fabric'],self::FABRICS))
            throw new InvalidArgumentException('Fabric reference has no registered renderer');
        foreach($d['trims'] as $t){
            foreach(['zone'=>self::ZONES,'material'=>self::MATERIALS,'edge'=>self::EDGES] as $field=>$registry)
                if(!is_string($t[$field])||!isset($registry[$t[$field]]))throw new InvalidArgumentException('Unregistered trim '.$field.' reference');
        }
        foreach($d['details'] as $t){
            foreach(['type'=>self::DETAILS,'at'=>self::PLACEMENTS] as $field=>$registry)
                if(!is_string($t[$field])||!isset($registry[$t[$field]]))throw new InvalidArgumentException('Unregistered detail '.$field.' reference');
        }
        $svg=$art['templates'][(string)$d['style']];
        // Preserve original geometry, viewBox, class visibility and original 12-bit rendering.
        $front=$d['colors']['front'];$back=$d['colors']['back'];$trim=$d['colors']['trim'];
        $pdef=self::defsExtras($d,$side);
        if($d['pattern']>0){
            $replacement=$side==='front'?'rgba(255,122,122,255)':'rgba(255,122,255,255)';
            $svg=str_replace($replacement,'url(#ptrn)',$svg);
        }
        $svg=str_replace(['rgba(255,122,122,255)','rgba(255,122,255,255)','rgb(0,0,0)'],[$front,$back,$trim],$svg);
        $svg=str_replace('<style>','<style>path.'.($side==='front'?'Back':'Front').'{visibility:hidden}', $svg);
        $opacity=((16-$d['sheerness'])*16)/255;
        $svg=str_replace("class='Front'", "class='Front' opacity='".$opacity."'", $svg);
        $svg=str_replace("class='Back'", "class='Back' opacity='".$opacity."'", $svg);
        $svg=str_replace('</defs>',$pdef.'</defs>',$svg);
        $path=self::stylePath($art,$d['style'],$side);
        $fabric='';
        if($d['fabric']!=='unspecified')$fabric='<path d="'.self::attr($path).'" fill="url(#upic-fabric)" opacity="'.($d['fabric']==='lace'?'0.70':'0.50').'" stroke="none"/>';
        $decor=$fabric.self::trimOverlay($d,$side).self::detailOverlay($d,$side);
        return str_replace('</svg>',$decor.'</svg>',$svg);
    }
}
