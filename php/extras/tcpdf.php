<?php
/*
	References:
	http://www.tcpdf.org/
*/
$progpath=dirname(__FILE__);
if(!is_dir("{$progpath}/tcpdf")){
	$msg= "<b>NOTE:</b> WaSQL supports the tcpdf library but since it is gnu licensed you need to download it yourself and add the tcpdf folder to extras.";
	$msg.= '<br /><br />To download tcpdf go to <a target="new" href="http://www.tcpdf.org">http://www.tcpdf.org</a> and click on the Download menu at the very top.'."\n";
	abort($msg,'Missing Module','tcpdf module');
	exit;
}
if(is_file("{$progpath}/tcpdf/config/lang/eng.php")){
	require_once("{$progpath}/tcpdf/config/lang/eng.php");
}
require_once("{$progpath}/tcpdf/tcpdf.php");
require_once("{$progpath}/../XML2Array.php");
//echo K_PATH_CACHE;exit;
//---------- begin function tcpdfHTML--------------------
/**
* @describe converts into a PDF file
* @note 
*	WaSQL supports the tcpdf library but since it is gnu licensed you need to download it yourself and add the tcpdf folder to extras.
*	To download tcpdf go to http://www.tcpdf.org and click on the Download menu at the very top.
* @param html string - html to convert
* @param params array
*	[title] string - title
*	[name] string - name
*	[page_orientation] char - (P = Portrait, L = Landscape)
*	[unit] string - Default unit of measure for document. Default is 'mm'
*	[page_format] string
* @usage
*	loadExtras(array('tcpdf'));
*	$html='
*		<h1>This is an h1 tag</h1>
*		<div align="right"><img src="http://localhost/wfiles/iconsets/64/wasql.png" border="0" /></div>
*		<table cellspacing="0" cellpadding="2" border="1" style="border-collapse:collapse;">
*			<tr><th>Name</th><th>Age</th></tr>
*			<tr>
*				<td>Billy Bob</td>
*				<td>33</td>
*			</tr>
*		</table>
*		<img src="http://localhost/php/barcode.php?BBE1233533&height=50" border="0" />
*	';
*	tcpdfHTML($html,array('title'=>"HTML PDF Test",'name'=>"htmltest.pdf"));
*	exit;
*/
function tcpdfHTML($html,$params=array()){
	$page_orientation=PDF_PAGE_ORIENTATION;
	if(isset($params['page_orientation'])){$page_orientation=$params['page_orientation'];}
	$unit=PDF_UNIT;
	if(isset($params['unit'])){$unit=$params['unit'];}
	$page_format=PDF_PAGE_FORMAT;
	if(isset($params['page_format'])){$page_format=$params['page_format'];}
	$pdf=new TCPDF($page_orientation,$unit,$page_format, true, 'UTF-8', false);

	//set default margins
	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
	//set auto page breaks
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
	//check for main attributes
	$filename=isset($params['name'])?$params['name']:'current.pdf';
	if(isset($xml['@attributes'])){
		$pdf=tcpdfApplyAttributes($pdf,$xml['@attributes']);
	}
	// remove default header/footer
	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);
	//set display mode to 100% zoom
	$pdf->setDisplayMode(100,'SinglePage','UseNone');
	//add a page
	$pdf->AddPage();
	//write the HTML
	$pdf->writeHTML($html, true, false, true, false, '');
	//output to screen
	$pdf->Output($filename,'I');
}
//---------- begin function tcpdfXML--------------------
/**
* @describe converts xml into a PDF file
* @param xml string - xml to convert.  Beginning xml tag needs to be named 'pdf' and can have the following attributes
* @param params array
*	[title] string - title
*	[name] string - name
*	[orientation] char - (P = Portrait, L = Landscape)
*	[unit] string - Default unit of measure for document. Default is 'mm'
*	[format] string - page format
*	[unicode] true|false - defaults to true and sets
*	[encoding] string - defaults to UTF-8
* @note
*	WaSQL supports the tcpdf library but since it is gnu licensed you need to download it yourself and add the tcpdf folder to extras.
*	To download tcpdf go to http://www.tcpdf.org and click on the Download menu at the very top.
* @usage
*	loadExtras(array('tcpdf'));
*	$xml='
*	<pdf name="yodog.pdf" margin="2,1,2" title="Monkey Business" subject="All about Monkeys" author="Steve Lloyd" autoprint="true">
*			<box position="10,10" ishtml="true" width="300" height="50" border="1" align="center" color="#584b38" background="#efefef"><![CDATA[
*				<style>
*					table th{
*						color:#FFFFFF;
*						background-color:#584b38;
*					}
*					h1 {color:#990000}
*				</style>
*				<h1 class="title">Sample HTML + CSS</h1>
*				<div><b>Bold some stuff</b> <i>italic some stuff</i></div>
*				<div align="right">HTML Image Tag: <img src="http://localhost/wfiles/iconsets/16/woman.png" width="16" height="16" /></div>
*				<table border="1">
*					<tr><th style="background:#c5b3a7;">Name</th><th>Age</th></tr>
*					<tr><td>Bob</td><td>12</td></tr>
*					<tr><td>John</td><td>42</td></tr>
*					<tr><td>Sue</td><td>23</td></tr>
*				</table>
*			]]></box>
*			<tip position="400,200" title="Sample Tip Title" width="200" height="50">And this is a tip annotation</tip>
*			<box position="400,10" width="100" height="50" border="1" font="arial,bold,8" align="center">Another text box</box>
*			<image src="http://localhost/wfiles/iconsets/64/user.png" position="400,100" width="64" height="64" />
*			<barcode type="code 39" font="times,bold,20" fitwidth="false" border="1" position="100,300" width="150" height="75">XY23454</barcode>
*			<barcode type="QRCODE" font="times,bold,20" fitwidth="false" bgcolor="#efefef" position="400,300" width="100" height="100">www.wasql.com</barcode>
*		</pdf>
*	';
*	tcpdfXML($xml);
*	exit;
*/
function tcpdfXML($xml){
	/*
		margin is set using SetMargins
			SetMargins(float left, float top [, float right])
		box is renderd using MultiCell
			MultiCell(float w, float h, string txt [, mixed border [, string align [, boolean fill]]])
		color is rendered using SetTextColor
			SetTextColor(int r [, int g, int b]) expressed in RGB components or gray scale
		title is set using SetTitle
			SetTitle(string title [, boolean isUTF8])
		position is set using SetXY
			SetXY(float x, float y)
		subject is set using SetSubject
			SetSubject(string subject [, boolean isUTF8])
		author is set using SetAuthor
			SetAuthor(string author [, boolean isUTF8])
		font is set using SetFont
			SetFont(name [, string style [, float size]])
				name: Courier, Arial, Helvitica, Times, Symbol,ZapfDingbats
				style: B=bold,I=italic,U=underline
				size : font size in points
	*/
	$xml_array = XML2Array::createArray($xml);
	if(!isset($xml_array['pdf'])){
    	echo 'Invalid XML format: missing base pdf tag';
    	return;
	}
	$xml=$xml_array['pdf'];
	//Page orientation (P=portrait, L=landscape).
	$orientation=isset($xml['@attributes']['orientation'])?$xml['@attributes']['orientation']:'P';
	//Document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch].
	$unit=isset($xml['@attributes']['unit'])?$xml['@attributes']['unit']:'px';
	//Paper size/ format [Letter, Legal, A4, etc]
	$format=isset($xml['@attributes']['format'])?$xml['@attributes']['format']:'Letter';
	//Paper size/ format [Letter, Legal, A4, etc]
	$unicode=isset($xml['@attributes']['unicode']) && strtolower($xml['@attributes']['unicode'])=='false'?false:true;
	//Paper size/ format [Letter, Legal, A4, etc]
	$encoding=isset($xml['@attributes']['encoding'])?$xml['@attributes']['encoding']:'UTF-8';

	$pdf=new TCPDF($orientation,$unit,$format,$unicode,$encoding);
	// remove default header/footer
	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);
	//set display mode to 100% zoom
	$pdf->setDisplayMode(100,'SinglePage','UseNone');
	//add a page
	$pdf->AddPage();
	$pdf->SetAutoPageBreak(true);
	$pdf->setJPEGQuality(100);
	//check for main attributes
	$filename=isset($xml['@attributes']['name'])?$xml['@attributes']['name']:'current.pdf';
	if(isset($xml['@attributes'])){
		$pdf=tcpdfApplyAttributes($pdf,$xml['@attributes']);
	}
	foreach($xml as $key=>$tags){
		if($key=='@attributes'){continue;}
		if(isset($tags['@value']) || isset($tags['@attributes'])){
        	$tags=array($tags);
		}
		$pdf->SetXY(0,0);
		switch(strtolower($key)){
			case 'box':
			case 'html':
				//MultiCell(float w, float h, string txt [, mixed border [, string align [, boolean fill]]])
				//$w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, 
				//$stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false
				foreach($tags as $tag){
					$string=isset($tag['@cdata'])?$tag['@cdata']:$tag['@value'];
					//set font if specified
					if(isset($tag['@attributes']['font'])){
						//$pdf->SetFont($family,$style,$size)
						$parts=preg_split('/\,/',$tag['@attributes']['font']);
						$parts[0]=tcpdfTranslate('font',$parts[0]);
						$parts[1]=tcpdfTranslate('fontstyle',$parts[1]);
						//echo "SetFont",printValue($parts);
						$pdf->SetFont(
							$parts[0],
							$parts[1],
							$parts[2]
						);
					}
					//set position if specified: SetXY(float x, float y)
					$ln=isset($tag['@attributes']['ln'])?$tag['@attributes']['ln']:1;
					$w=isset($tag['@attributes']['width'])?$tag['@attributes']['width']:0;
					$h=isset($tag['@attributes']['height'])?$tag['@attributes']['height']:0;
					$align=isset($tag['@attributes']['align'])?tcpdfTranslate('align',$tag['@attributes']['align']):'J';
					$x='';
					$y='';
					$reseth=isset($tag['@attributes']['reseth'])?tcpdfTranslate('boolean',$tag['@attributes']['reseth']):true;
					$stretch=isset($tag['@attributes']['stretch'])?$tag['@attributes']['stretch']:false;
					if(strtolower($key)=='html'){$ishtml=true;}
					else{
						$ishtml=isset($tag['@attributes']['ishtml'])?tcpdfTranslate('boolean',$tag['@attributes']['ishtml']):false;
					}
					$autopadding=isset($tag['@attributes']['autopadding'])?tcpdfTranslate('boolean',$tag['@attributes']['autopadding']):true;
					$maxh=isset($tag['@attributes']['maxh'])?$tag['@attributes']['maxh']:0;
					$valign=isset($tag['@attributes']['valign'])?tcpdfTranslate('valign',$tag['@attributes']['valign']):'T';
					$fitcell=isset($tag['@attributes']['fitcell'])?tcpdfTranslate('boolean',$tag['@attributes']['fitcell']):false;
					if(isset($tag['@attributes']['position'])){
						list($x,$y)=preg_split('/\,+/',$tag['@attributes']['position']);
					}
					//background?
					$fill=0;
					if(isset($tag['@attributes']['background'])){
						//convert hex to rgb
						list($r,$g,$b)=hex2RGB($tag['@attributes']['background']);
                    	$pdf->SetFillColor($r,$g,$b);
                    	$fill=1;
					}
					if(isset($tag['@attributes']['color'])){
						//convert hex to rgb
						list($r,$g,$b)=hex2RGB($tag['@attributes']['color']);
                    	$pdf->SetTextColor($r,$g,$b);
                    	$fill=1;
					}
					//border? 0,1,dashed, dotted
					$resetlinestyle=0;
					switch(strtolower($tag['@attributes']['border'])){
                    	case 0:
                    	case 'none':
							$border=0;
						break;
						case 'dashed':
							$border=1;
							$resetlinestyle=1;
							//$pdf->SetLineStyle(array('width' => 0.3, 'dash' => 3));
							$pdf->SetLineStyle(array('dash' => 3));
						break;
						case 'dotted':
							$border=1;
							$resetlinestyle=1;
							$pdf->SetLineStyle(array('dash' => 1));
						break;
						default:
							$border=1;
						break;
					}
					$pdf->MultiCell(
						$w,$h,$string,$border,$align,
						$fill,$ln,$x,$y,$reseth,$stretch,$ishtml,$autopadding,
						$maxh,$valign,$fitcell
					);
					if($resetlinestyle==1){
						$pdf->SetLineStyle(array('width' => 0.2, 'dash' => 0));
					}
					if($fill==1){
                    	$pdf->SetFillColor(255,255,255);
                    	$pdf->SetTextColor(0,0,0);
					}
				}
				break;
			case 'pagebreak':
				$pdf->AddPage();
			break;
			case 'image':
			case 'img':
				foreach($tags as $tag){
					//<image src="/wfiles/iconsets/64/user.png" position="100,100" width="100" height="30" />
					//$pdf->Image(
					//	$file,$x,$y,$w,$h,$type,$link,$align,$resize=true|false,$dpi=300,
					//	$palign,$ismask=false,$border=0,$fitbox=false,$hidden=false,$fitonpage=false,
					//	$alt=false,$altimgs=array()
					$x=0;$y=0;$w=100;$h=50;
					if(isset($tag['@attributes']['position'])){
	                	list($x,$y)=preg_split('/\,+/',$tag['@attributes']['position'],2);
					}
					if(isset($tag['@attributes']['width'])){
	                	$w=$tag['@attributes']['width'];
					}
					if(isset($tag['@attributes']['height'])){
	                	$h=$tag['@attributes']['height'];
					}
					$file=$tag['@attributes']['src'];
					//type
					if(isset($tag['@attributes']['type'])){
						$type=$tag['@attributes']['type'];
					}
					elseif(isset($tag['@attributes']['ext'])){
						$type=$tag['@attributes']['ext'];
					}
					else{
                    	$type=strtoupper(getFileExtension($file));
					}
					//tcpdfTranslate('align',$tag['@attributes']['align']),
					$link=isset($tag['@attributes']['link'])?$tag['@attributes']['link']:'';
					$align=isset($tag['@attributes']['align'])?tcpdfTranslate('align',$tag['@attributes']['align']):'';
					$resize=isset($tag['@attributes']['resize'])?$tag['@attributes']['resize']:true;
					$dpi=isset($tag['@attributes']['dpi'])?$tag['@attributes']['dpi']:300;
					$palign=isset($tag['@attributes']['palign'])?$tag['@attributes']['palign']:'';
					$ismask=isset($tag['@attributes']['ismask'])?$tag['@attributes']['ismask']:false;
					$border=isset($tag['@attributes']['border'])?$tag['@attributes']['border']:0;
					$fitbox=isset($tag['@attributes']['fitbox'])?$tag['@attributes']['fitbox']:false;

					$hidden=isset($tag['@attributes']['hidden'])?$tag['@attributes']['hidden']:false;
					$fitonpage=isset($tag['@attributes']['fitonpage'])?$tag['@attributes']['fitonpage']:false;
					$alt=isset($tag['@attributes']['alt'])?$tag['@attributes']['alt']:'';
					if(isset($tag['@attributes']['altimgs'])){
	                	$altimgs=preg_split('/[\,\;]+/',$tag['@attributes']['altimgs']);
					}
					$pdf->Image($file,$x,$y,$w,$h,$type,$link,$align,$resize,$dpi,$palign,$ismask,$border,$fitbox,$hidden,$fitonpage,$alt,$altimgs);
				}
				break;
			case 'annotate':
			case 'tip':
				foreach($tags as $tag){
					//<image src="/wfiles/iconsets/64/user.png" position="100,100" width="100" height="30" />
					//$pdf->Annotation(
					//	$x,$y,$w,$h,$type,$text,$opts,$spaces
					$x=0;$y=0;$w=100;$h=50;
					if(isset($tag['@attributes']['position'])){
	                	list($x,$y)=preg_split('/\,+/',$tag['@attributes']['position'],2);
					}
					if(isset($tag['@attributes']['width'])){
	                	$w=$tag['@attributes']['width'];
					}
					if(isset($tag['@attributes']['height'])){
	                	$h=$tag['@attributes']['height'];
					}
     				$text=isset($tag['@cdata'])?$tag['@cdata']:$tag['@value'];
     				//opts: Subtype,Name,T,Subj,C
     				$opts=array(
						'Subtype'=>'Text',
						'Name' => 'Comment',
						'T' => '',
						'Subj' => '',
						'C' => array(255, 255, 0)
					);
					if(isset($tag['@attributes']['type'])){$opts['Subtype']=$tag['@attributes']['type'];}
					if(isset($tag['@attributes']['name'])){$opts['Name']=$tag['@attributes']['name'];}
					if(isset($tag['@attributes']['title'])){$opts['T']=$tag['@attributes']['title'];}
					if(isset($tag['@attributes']['subject'])){$opts['Subj']=$tag['@attributes']['subject'];}
					if(isset($tag['@attributes']['color'])){
                    	list($r,$g,$b)=hex2RGB($tag['@attributes']['color']);
                    	$opts['C']=array($r,$g,$b);
					}
					$spaces='';
					$pdf->Annotation($x,$y,$w,$h,$text,$opts,$spaces);
				}
				break;
			case 'barcode':
				foreach($tags as $tag){
					//<barcode position="100,200" width="100" height="30">ABCTST11923</barcode>
					//$pdf->write1DBarcode($code,$type,$x,$y,$w,$h,$xres,$style,$align)
					//$pdf->write2DBarcode ($code,$type,$x,$y,$w,$h,$style='',$align='', $distort=false)
					// define barcode style
					$style = array(
					    'position' => '',
					    'align' => 'C',
					    'stretch' => true,		//stretch the barcode to best fit the available width, otherwise uses $xres resolution for a single bar
					    'fitwidth' => false, 	//if true reduce the width to fit the barcode width + padding
					    'cellfitalign' => '',
					    'border' => false,
					    'hpadding' => 'auto',
					    'vpadding' => 'auto',
					    'fgcolor' => array(0,0,0),
					    'bgcolor' => false, 	//color array for background (set to false for transparent)
					    'text' => true,			//prints text below the barcode
					    'font' => 'helvetica',
					    'fontsize' => 9,
					    'stretchtext' => 4 //0=disabled;1=horizontal scaling only if necessary;2=forced horizontal scaling; 3=character spacing only if necessary; 4=forced character spacing
					);
					$type=isset($tag['@attributes']['type'])?tcpdfTranslate('barcode',$tag['@attributes']['type']):'C39+';
					if(stringContains($type,'QRCODE') || stringContains($type,'RAW')){
						$style = array(
						    'border' => 2,
						    'vpadding' => 'auto',
						    'hpadding' => 'auto',
						    'fgcolor' => array(0,0,0),
						    'bgcolor' => false, //array(255,255,255)
						    'module_width' => 1, // width of a single module in points
						    'module_height' => 1 // height of a single module in points
						);
					}
					foreach($style as $key=>$val){
                    	if(isset($tag['@attributes'][$key])){
							if(preg_match('/^true$/i',$tag['@attributes'][$key])){$style[$key]=true;}
							elseif(preg_match('/^false$/i',$tag['@attributes'][$key])){$style[$key]=false;}
							else{
								if($key=='font'){
									$parts=preg_split('/\,/',$tag['@attributes']['font']);
									$style['font']=tcpdfTranslate('font',"{$parts[0]} {$parts[1]}");
									$style['fontsize']=isset($parts[2])?$parts[2]:9;
								}
								elseif(stringContains($key,'color')){
									//convert hex to rgb
									list($r,$g,$b)=hex2RGB($tag['@attributes'][$key]);
                    				$style[$key]=array($r,$g,$b);
								}
								else{$style[$key]=$tag['@attributes'][$key];}
							}
						}
					}
					$x=0;$y=0;$w=100;$h=50;
					if(isset($tag['@attributes']['position'])){
	                	list($x,$y)=preg_split('/\,+/',$tag['@attributes']['position'],2);
					}
					if(isset($tag['@attributes']['width'])){
	                	$w=$tag['@attributes']['width'];
					}
					if(isset($tag['@attributes']['height'])){
	                	$h=$tag['@attributes']['height'];
					}
					$code=isset($tag['@cdata'])?$tag['@cdata']:$tag['@value'];

					//tcpdfTranslate('align',$tag['@attributes']['align']),
					
					$align=isset($tag['@attributes']['align'])?tcpdfTranslate('align',$tag['@attributes']['align']):'';
					$xres=isset($tag['@attributes']['xres'])?$tag['@attributes']['xres']:0.4;
					//echo "($code,$type,$x,$y,$w,$h,$xres,$style,$align)";exit;
					if(stringContains($type,'QRCODE') || stringContains($type,'RAW')){
						$distort=isset($tag['@attributes']['distort'])?tcpdfTranslate('align',$tag['@attributes']['distort']):'N';
						//echo "pdf->write2DBarcode($code,$type,$x,$y,$w,$h,$style,$align,$distort)";exit;
						//$pdf->write2DBarcode('www.tcpdf.org', 'QRCODE,H', 20, 210, 50, 50, $style, 'N');
						//pdf->write2DBarcode(www.wasql.com,QRCODE,H,400,300,100,50,Array,,)
						//echo printValue($style)."pdf->write2DBarcode($code,$type,$x,$y,$w,$h,$style,$align,$distort)";exit;
						$pdf->write2DBarcode($code,$type,$x,$y,$w,$h,$style,$align,$distort);
					}
					else{
						if(isset($tag['@attributes']['rotate'])){
							$parts=preg_split('/\,+/',$tag['@attributes']['rotate']);
                        	if(count($parts)==1){
								$pdf->StartTransform();
								$pdf->setXY($x,$y);
                        		$pdf->Rotate($parts[0]);
                        		$pdf->write1DBarcode($code,$type,$x,$y,$w,$h,$xres,$style,$align);
                        		$pdf->StopTransform();
							}
							elseif(count($parts)==3){
								$pdf->StartTransform();
								$pdf->setXY($x,$y);
                        		$pdf->Rotate($parts[0],$parts[1],$parts[2]);
                        		$pdf->write1DBarcode($code,$type,$x,$y,$w,$h,$xres,$style,$align);
                        		$pdf->StopTransform();
							}
							else{
                            	$pdf->write1DBarcode($code,$type,$x,$y,$w,$h,$xres,$style,$align);
							}

						}
						else{
                    		$pdf->write1DBarcode($code,$type,$x,$y,$w,$h,$xres,$style,$align);
						}
					}


				}
				break;
		}
	}
	$pdf->Output($filename,'I');
}
//---------- begin function loadPage
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function tcpdfApplyAttributes($pdf,$atts=array()){
	foreach($atts as $key=>$val){
		if(!strlen($val)){continue;}
        	switch(strtolower($key)){
				case 'autoprint':
					if(strtolower($val)=='true'){
						// Automatic printing
						$pdf->IncludeJS("autoprint=app.setTimeOut(\&quot;this.print()\&quot;,1);");
					}
					break;
				case 'creator':
            		//SetTitle(string title [, boolean isUTF8])
            		$pdf->SetCreator($val,true);
            		break;
            	case 'keywords':
            		//SetTitle(string title [, boolean isUTF8])
            		$pdf->SetKeywords($val);
            		break;
            	case 'margin':
            		//SetMargins(float left, float top [, float right])
            		$vals=preg_split('/\,+/',$val);
            		$pdf->SetMargins($vals[0],$vals[1],$vals[2]);
            		break;
            	case 'title':
            		//SetTitle(string title [, boolean isUTF8])
            		$pdf->SetTitle($val,true);
            		break;

            	case 'subject':
            		//SetSubject(string title [, boolean isUTF8])
            		$pdf->SetSubject($val,true);
            		break;
            	case 'author':
            		//SetAuthor(string title [, boolean isUTF8])
            		$pdf->SetAuthor($val,true);
            		break;
		}
	}
	return $pdf;
}
//---------- begin function loadPage
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function tcpdfTranslate($key,$val){
	switch(strtolower($key)){
		case 'boolean':
			if(stringContains($val,'true')){return true;}
			return false;
			break;
    	case 'align':
    		switch(strtolower($val)){
            	case 'l':
            	case 'left':
					return 'L';
					break;
				case 'c':
            	case 'center':
					return 'C';
					break;
				case 'r':
            	case 'right':
					return 'R';
					break;
				default:return 'J';break;
			}
    	break;
    	case 'valign':
    		switch(strtolower($val)){
            	case 't':
            	case 'top':
					return 'T';
					break;
				case 'm':
            	case 'middle':
					return 'M';
					break;
				case 'b':
            	case 'bottom':
					return 'B';
					break;
				default:return 'T';break;
			}
    	break;
    	case 'fontstyle':
    		switch(strtolower($val)){
            	case 'b':
            	case 'bold':
					return 'B';
					break;
				case 'i':
            	case 'italic':
					return 'I';
					break;
				case 'u':
            	case 'underline':
					return 'U';
					break;
				default:return '';break;
			}
    	break;
    	case 'barcode':
    		switch(strtoupper($val)){
            	case 'CODE 39':
            	case 'ANSI MH10.8M-1983':
            	case 'USD-3':
            	case '3 OF 9':
					return 'C39';
					break;
            	case 'CODE 39 +':
            	case 'CODE 39 + CHECKSUM':
            		return 'C39+';
            		break;
            	case 'CODE 39 EXTENDED':
            	case 'CODE 39 E':
            		return 'C39E';
            		break;
            	case 'CODE 39 EXTENDED + CHECKSUM':
            	case 'CODE 39 E+':
            		return 'C39E+';
            		break;
            	case 'CODE 93':return 'C93';break;
            	case 'STANDARD 2 OF 5':return 'S25';break;
            	case 'STANDARD 2 OF 5':return 'S25+';break;
            	case 'INTERLEAVED 2 OF 5 + CHECKSUM':return 'I25';break;
            	case 'INTERLEAVED 2 OF 5 + CHECKSUM':return 'I25+';break;
            	case 'CODE 128 AUTO':
            	case 'CODE 128':
					return 'C128';
					break;
            	case 'CODE 128 A':return 'C128A';break;
            	case 'CODE 128 B':return 'C128B';break;
            	case 'CODE 128 C':return 'C128C';break;
            	case 'EAN 8':return 'EAN8';break;
            	case 'EAN 13':return 'EAN13';break;
            	case 'UPC-A':return 'UPCA';break;
            	case 'UPC-E':return 'UPCE';break;
            	case '5-DIGITS UPC-BASED EXTENTION':return 'EAN5';break;
            	case '2-DIGITS UPC-BASED EXTENTION':return 'EAN2';break;
            	case 'MSI':return 'MSI';break;
            	case 'MSI + CHECKSUM':return 'MSI+';break;
            	case 'CODABAR':return 'CODABAR';break;
            	case 'CODE 11':return 'CODE11';break;
            	case 'PHARMACODE':return 'PHARMA';break;
            	case 'PHARMACODE TWO-TRACKS':return 'PHARMA2T';break;
            	case 'IMB':
				case 'INTELLIGENT MAIL BARCODE':
				case 'ONECODE':
				case 'USPS-B-3200':
					return 'IMB';
					break;
            	case 'POSTNET':return 'POSTNET';break;
            	case 'PLANET':return 'PLANET';break;
            	case 'RMS4CC':
				case 'Royal Mail 4-state Customer Code':
				case 'CBC':
				case 'CUSTOMER BAR CODE':
					return 'RMS4CC';
					break;
            	case 'KIX':
				case 'KLANT INDEX':
				case 'CUSTOMER INDEX':
					return 'KIX';
					break;
				//2DBarcodes
				case 'QRCODE':return 'QRCODE,H';return;
				case 'RAW':return 'RAW';return;
				default:return 'C39';break;
			}
    	break;
    	case 'font':
    		//valid fonts
    		switch(strtolower($val)){
			    case 'courier':return 'courier';break;
			    case 'courier bold':return 'courierB';break;
			    case 'courier italic bold':
			    case 'courier bold italic':return 'courierBI';break;
			    case 'courier italic':return 'courierI';break;
			    case 'helvetica':return 'helvetica';break;
			    case 'helvetica bold':return 'helveticaB';break;
				case 'helvetica italic bold':
			    case 'helvetica bold italic':return 'helveticaBI';break;
			    case 'helvetica italic':return 'helveticaI';break;
			    case 'symbol':return 'symbol';break;
			    case 'times':
			    case 'times new roman':return 'times';	break;
				case 'times bold':
			    case 'times new roman bold':return 'timesB';break;
			    case 'times bold italic':
			    case 'times italic bold':
			    case 'times new roman italic bold':
			    case 'times new roman bold italic':return 'timesBI';break;
			    case 'times italic':
			    case 'times new roman italic':return 'timesI';break;
			    case 'zapf dingbats':return 'zapfdingbats';break;
				default:return 'helvetica';break;
			}
    	break;
	}
	return '';
}
?>