#! python
'''
    modules list
        https://docs.python.org/3/py-modindex.html
'''
import os
import sys
try:
    import pprint
    import re
    import io
    import string
    import random
    import csv
    from contextlib import redirect_stdout
    from math import sin, cos, sqrt, atan2, radians
    import subprocess
    from datetime import datetime, timedelta
    from dateutil.relativedelta import relativedelta
    import calendar
    from dateutil import parser
    import time as ttime
    import base64
    import urllib.parse
    import json
    import smtplib
    import db
    import config
    from importlib import import_module
    import inspect
except Exception as err:
    exc_type, exc_obj, exc_tb = sys.exc_info()
    fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
    print("Import Error: {}. ExeptionType: {}, Filename: {}, Linenumber: {}".format(err,exc_type,fname,exc_tb.tb_lineno))
    sys.exit(3)

#wasql module is dynamically created. Import if it exists
wasql={}
for module_name in sys.modules:
    if module_name.startswith('wasql_'):
        wasql=import_module(module_name)

VIEWS = {}
VIEW = {}
DEBUG = []
#import dateparser

#---------- begin function abort ----------
# @describe recursive folder creator
# @param sys.exc_info() -- exc_type, exc_obj, exc_tb
# @param err
# @usage common.abort(sys.exc_info(),err)
def abort(exc_tuple,err):
    fname = os.path.split(exc_tuple[2].tb_frame.f_code.co_filename)[1]
    abort_err = "Error: {}. ExeptionType: {}, Filename: {}, Linenumber: {}".format(err,exc_tuple[0],fname,exc_tuple[2].tb_lineno)
    print(abort_err)
    sys.exit(123)

#---------- begin function arrayAverage ----------
# @describe returns average of all elements in a list
# @param lst list
# @usage avg=common.arrayAverage([12,3,4,7])
def arrayAverage(lst):
    return sum(lst) / len(lst)

#---------- begin function debug ----------
# @describe recursive folder creator
# @param sys.exc_info() -- exc_type, exc_obj, exc_tb
# @param err
# @usage common.debug(sys.exc_info(),err)
def debug(exc_tuple,err):
    fname = os.path.split(exc_tuple[2].tb_frame.f_code.co_filename)[1]
    return "Error: {}. ExeptionType: {}, Filename: {}, Linenumber: {}".format(err,exc_tuple[0],fname,exc_tuple[2].tb_lineno)

#---------- begin function buildDir ----------
# @describe recursive folder creator
# @param path string - path to create
# @param [mode] num - create mode. defaults to 0o777
# @param [recurse] boolean - create recursively. defaults to TRUE
# @return boolean
# @usage if(common.buildDir('/var/www/mystuff/temp/test')):
def buildDir(path,mode=0o777,recurse=True):
    if recurse:
        return os.makedirs(path,mode)
    else:
        return os.mkdir(path,mode)
# ---------- begin function buildFormButtonSelectMultiple--------------------
# @describe creates an button selection field
# @param name string
# @param opts array - true value/display value pairs.
# @param params array
#   [value] - sets default selection
#   [name] - name override
#   [class] - string - w_green, w_red, etc..
# @return string
# @usage print(buildFormButtonSelectMultiple('color',{"red":"Red","blue":"Blue","green":"Green"},{"required":"1"}))
def buildFormButtonSelectMultiple(name,opts={},params={}):
    params['value']=buildFormValueParam(name,params,1)
    if '-button' not in params:
        params['-button']='btn-default'
    #override name
    if 'name' in params:
        name=params['name']
        del params['name']
    #requiredif
    if 'requiredif' in params:
        params['data-requiredif']=params['requiredif']
        del params['requiredif']
    #tag
    tag='<div class="w_flexgroup" data-display="inline-flex"'
    #displayif
    if 'displayif' in params:
        tag+=' data-displayif="{}"'.format(params['displayif'])
        del params['displayif']

    tag +='>'+os.linesep
    for tval in opts:
        dval=opts[tval]
        checked=''
        if tval in params['value'] or dval in params['value']:
            checked=' checked'

        id="{}_{}".format(name,tval)
        classname=''
        tvalclass=tval+'_class'
        dvalclass=dval+'_class'
        if tvalclass in params:
            classname=params[tvalclass]
        elif dvalclass in params:
            classname=params[dvalclass]
        elif 'class' in params:
            classname=params['class']

        tag += '    <input type="checkbox" data-type="checkbox" class="btn {}" style="display:none"'.format(classname)
        if 'onclick' in params:
            tag+=' onclick="{}"'.format(params['onclick'])
        
        tag += ' name="{}[]"  id="{}" value="{}"{}>'.format(name,id,tval,checked)+os.linesep
        tag += '    <label for="{}">{}</label>'.format(id,dval)+os.linesep
    
    tag += '</div>'+os.linesep
    return tag
# ---------- begin function buildFormCheckAll--------------------
# 
# @describe creates a checkbox that checks other checkboxes
# @param att string
# @param attval string
# @param params list
# @return string
# @usage print(buildFormCheckAll('id','users'))
def buildFormCheckAll(att,attval,params={}):
    if '-label' in params:
        name=params['-label']
        del params['-label']
    else:
        name='Checkall'

    id='checkall_{}'.format(getRandomString());
    tag='''<input id="{}" type="checkbox" onclick="wacss.checkAllElements('{}','{}',this.checked);" >'''.format(id,att,attval)
    if 'for' in params:
        del params['for']

    tag += '<label for="{}" '.format(id)
    tag += setTagAttributes(params)
    tag += '>{}</label>'.format(name)
    return tag

#---------- begin function buildFormColor-------------------
# @describe creates an HTML color control
# @param name string - field name
# @param params array
#   [-formname] string - specify the form name - defaults to addedit
#   [value] string - specify the current value
#   [required] boolean - make it a required field - defaults to addedit false
#   [id] string - specify the field id - defaults to formname_fieldname
# @return string - html color control
# @usage print(buildFormColor('color'))
def buildFormColor(name,params={}):
    if '-formname' not in params:
        params['-formname']='addedit'

    if 'name' in params:
        name=params['name']

    if 'id' not in params:
        id='{}_{}'.format(params['-formname'],name)

    if 'requiredif' in params:
        params['data-requiredif']=params['requiredif']
        del params['requiredif']

    if 'displayif' in params:
        params['data-displayif']=params['displayif']
        del params['displayif']

    params['value']=buildFormValueParam(name,params)
    tag=''
    tag+='<div class="w_colorfield"'
    if 'data-displayif' in params and len(params['data-displayif']):
        tag+=' data-displayif="{}"'.format(params['data-displayif'])
        del params['data-displayif']

    tag+='>'+os.linesep
    tag+=' <div>'+os.linesep
    tag+= '   <input type="text" name="{}" value=""'.format(name,params['value'])
    tag+= setTagAttributes(params);
    tag+= ' >'+os.linesep
    tag+='     <label for="{}_check"'.format(name)
    if 'value' in params and len(params['value']):
        tag+=' style="background-color:{}"'.format(params['value'])
    tag+='></label>'+os.linesep
    tag+=' </div>'+os.linesep
    tag+=' <input type="checkbox" id="{}_check">'.format(name)+os.linesep
    tag+= buildFormColorWheelMap('{}_map'.format(name))+os.linesep
    tag+='</div>'+os.linesep
    return tag

# ---------- begin function buildFormColorWheelMap-------------------
# @exclude  - this function in only used internally by buildFormColor
def buildFormColorWheelMap(name):
    wpath=getWasqlPath('wfiles')
    afile="{}/color_wheel_map.htm".format(wpath)
    body=getFileContents(afile)
    areas=re.findall(r'title\=\"(.+?)\".+?data\-color\=\"(.+?)\"',body,re.MULTILINE)
    opts={}
    sparams={
        "onchange":"wacss.colorboxSelect(this)",
        "class":"select",
        "message":"-- Color By Name --",
        "style":"border-top-right-radius:0px;border-top-left-radius:0px;"
    }
    for area in areas:
        opts[area[1]]=area[0]
        sparams['{}_style']='background-color:{};color:{}'.format(area[1],'#000')

    selectmap=buildFormSelect('{}_select'.format(name),opts,sparams)
    map='''
<nav class="colorboxmap">
    <img class="wheel" src="/wfiles/color_wheel.png" usemap="#{}_map" style="width:100%;height:auto;">
    {}
    <map name="{}_map" style="display:none;">
        {}
    </map>
</nav>
'''.format(name,selectmap,name,body)
    return map

#---------- begin function buildFormColorHexagon-------------------
# @describe creates an HTML color control using the color_hexagon.gif in wfiles
# @param name string - field name
# @param params array
#   [-formname] string - specify the form name - defaults to addedit
#   [value] string - specify the current value
#   [required] boolean - make it a required field - defaults to addedit false
#   [id] string - specify the field id - defaults to formname_fieldname
# @return string - html color control
# @usage print(buildFormColorHexagon('color'))

def buildFormColorHexagon(name,params={}):
    if '-formname' not in params:
        params['-formname']='addedit'

    if 'name' in params:
        name=params['name']

    if 'id' not in params:
        id='{}_{}'.format(params['-formname'],name)

    if 'requiredif' in params:
        params['data-requiredif']=params['requiredif']
        del params['requiredif']

    if 'displayif' in params:
        params['data-displayif']=params['displayif']
        del params['displayif']

    params['value']=buildFormValueParam(name,params)
    tag=''
    tag+='<div class="w_colorfield"'
    if 'data-displayif' in params and len(params['data-displayif']):
        tag+=' data-displayif="{}"'.format(params['data-displayif'])
        del params['data-displayif']

    tag+='>'+os.linesep
    tag+=' <div>'+os.linesep
    tag+='   <input type="text" name="{}" value="{}"'.format(name,params['value'])
    tag+=setTagAttributes(params);
    tag+=' />'+os.linesep
    tag+='     <label for="{}_check"'.format(name)
    if 'value' in params and len(params['value']):
        tag+=' style="background-color:{}"'.format(params['value'])

    tag+='></label>'+os.linesep
    tag+=' </div>'+os.linesep
    tag+=' <input type="checkbox" id="{}_check">'.format(name)+os.linesep
    tag+= buildFormColorHexagonMap(name)+os.linesep
    tag+='</div>'+os.linesep
    return tag

# ---------- begin function buildFormColorHexagonMap-------------------
# * @exclude  - this function in only used internally by buildFormColor
def  buildFormColorHexagonMap(name):
    wpath=getWasqlPath('wfiles')
    afile="{}/color_hexagon_map.htm".format(wpath)
    body=getFileContents(afile)
    areas=re.findall(r'data\-color\=\"(.+?)\".+?title\=\"(.+?)\"',body,re.MULTILINE)
    opts={}
    sparams={
        "onchange":"wacss.colorboxSelect(this)",
        "class":"select",
        "message":"-- Color By Name --",
        "style":"margin-top:3px;border-top-right-radius:0px;border-top-left-radius:0px;"
    }
    for area in areas:
        opts[area[0]]=area[1]
        sparams['{}_style']='background-color:{};color:{}'.format(area[0],'#000')

    selectmap=buildFormSelect('{}_select'.format(name),opts,sparams)
    map='''
<nav class="colorboxmap hexagon">
    <img class="hexagon" src="/wfiles/color_hexagon.gif" usemap="#{}_map" style="width:234px;height:199px;">
    {}
    <map name="{}_map" style="display:none;">
        {}
    </map>
</nav>
'''.format(name,selectmap,name,body)
    return map

    
# ---------- begin function buildFormColorBox-------------------
# @describe creates an HTML color control using the color box built from color_names.csv
# @param name string - field name
# @param params array
#   [-formname] string - specify the form name - defaults to addedit
#   [value] string - specify the current value
#   [required] boolean - make it a required field - defaults to addedit false
#   [id] string - specify the field id - defaults to formname_fieldname
# @return string - html color control
# @usage print(buildFormColorBox('color'))
def buildFormColorBox(name,params={}):
    if '-formname' not in params:
        params['-formname']='addedit'

    if 'name' in params:
        name=params['name']

    if 'id' not in params:
        id='{}_{}'.format(params['-formname'],name)

    if 'requiredif' in params:
        params['data-requiredif']=params['requiredif']
        del params['requiredif']

    if 'displayif' in params:
        params['data-displayif']=params['displayif']
        del params['displayif']

    params['value']=buildFormValueParam(name,params)
    tag=''
    tag+='<div class="w_colorfield"'
    if 'data-displayif' in params and len(params['data-displayif']):
        tag+=' data-displayif="{}"'.format(params['data-displayif'])
        del params['data-displayif']

    tag+='>'+os.linesep
    tag+=' <div>'+os.linesep
    tag+='   <input type="text" name="{}" value="{}"'.format(name,params['value'])
    tag+=setTagAttributes(params);
    tag+=' />'+os.linesep
    tag+='     <label for="{}_check"'.format(name)
    if 'value' in params and len(params['value']):
        tag+=' style="background-color:{}"'.format(params['value'])

    tag+='></label>'+os.linesep
    tag+=' </div>'+os.linesep
    tag+=' <input type="checkbox" id="{}_check">'.format(name)+os.linesep
    tag+= buildFormColorBoxMap(name+'_map')+os.linesep
    tag+='</div>'+os.linesep
    return tag;

# ---------- begin function buildFormColorBoxMap-------------------
# * @exclude  - this function in only used internally by buildFormColor
def buildFormColorBoxMap(name):
    wpath=getWasqlPath('wfiles')
    afile="{}/color_names.csv".format(wpath)
    recs=getCSVRecords(afile)
    map='<nav class="colorboxmap" name="{}">'.format(name)+os.linesep
    opts={}
    sparams={
        "onchange":"wacss.colorboxSelect(this)",
        "class":"select",
        "message":"-- Color By Name --",
        "style":"border-top-right-radius:0px;border-top-left-radius:0px;"
    }
    for rec in recs:
        map+='<img src="/wfiles/clear.gif" title="{}" style="background-color:{};" onclick="wacss.colorboxSet(this);" data-color="{}">'.format(rec['name'],rec['hex'],rec['hex'])
        opts[rec['hex']]=rec['name']
        sparams["{}_style".format(rec['hex'])]="background-color:{};color:{};".format(rec['hex'],rec['contrast_color'])
        
    map+=buildFormSelect(name+'colorbox_select',opts,sparams)
    map+='</nav>'+os.linesep
    return map



# ---------- begin function buildFormSelect-------------------
# @describe creates an HTML form selection tag
# @param name string - name of select tag
# @param pairs array - tval/dval pairs array to populate select tag with
# @param params array - attribute/value pairs to add to select tag
# @return string - HTML Form select tag
# @usage echo buildFormSelect('age',array(5=>"Below Five",10=>"5 to 10"));
def buildFormSelect(name,pairs={},params={}):
    if '-formname' not in params:
        params['-formname']='addedit'

    if 'name' in params:
        name=params['name']

    if 'id' not in params:
        id='{}_{}'.format(params['-formname'],name)

    if 'requiredif' in params:
        params['data-requiredif']=params['requiredif']
        del params['requiredif']

    if 'displayif' in params:
        params['data-displayif']=params['displayif']
        del params['displayif']

    if 'class' not in params:
        params['class']='w_form-control'

    params['value']=buildFormValueParam(name,params)

    if 'viewonly' in params:
        return '<div class="w_viewonly" id="{}">{}</div>'.format(params['id'],nl2br(params['value']))

    pcnt=len(pairs);
    if pcnt==0 or (pcnt==1 and pairs[0]==''):
        return buildFormText(name,params)

    params['name']=name;
    skip=[];
    if '-noname' in params:
        skip.append('name')
    
    #select does not honor readonly so lets fix that
    if 'readonly' in params:
        params['style']="pointer-events: none;cursor: not-allowed;color:#a8a8a8;"+params['style']

    rtn = '<select data-value="{}"'.format(params['value'])
    rtn += setTagAttributes(params,skip)
    rtn += '>'
    if 'message' in params:
        rtn += '   <option value="">{}</option>'.format(params['message'])+os.linesep

    if '-groups' in params:
        for group,opts in enumerate(pairs):
            rtn += '   <optgroup label="{}">'.format(group)+os.linesep
            for tval in opts:
                dval=opts[tval]
                rtn += '       <option value="{}"'.format(tval)
                tvalstyle='{}_style'.format(tval)
                if tvalstyle in params:
                    rtn += ' style="{}"'.format(tvalstyle)

                tvalclass='{}_class'.format(tval)
                if tvalclass in params:
                    rtn += ' class="{}"'.format(tvalclass)

                for k in params:
                    v=params[k]

                    if not stringBeginsWith(k,"{}_data-".format(tval)):
                        continue

                    k=str_replace("{}_data-".format(tval),'',k)
                    rtn += ' data-{}="{}"'.format(k,v)

                if len(params['value']):
                    if params['value']== tval:
                        rtn += ' selected'
                    elif(params['value']==dval):
                        rtn += ' selected'

                rtn += '>{}</option>'.format(dval)+os.linesep

            rtn += '   </optgroup>'+os.linesep

    else:
        for tval in pairs:
            dval=pairs[tval]
            rtn += '       <option value="{}"'.format(tval)
            tvalstyle='{}_style'.format(tval)
            if tvalstyle in params:
                rtn += ' style="{}"'.format(tvalstyle)

            tvalclass='{}_class'.format(tval)
            if tvalclass in params:
                rtn += ' class="{}"'.format(tvalclass)

            for k in params:
                v=params[k]

                if not stringBeginsWith(k,"{}_data-".format(tval)):
                    continue

                k=str_replace("{}_data-".format(tval),'',k)
                rtn += ' data-{}="{}"'.format(k,v)

            if len(params['value']):
                if params['value']== tval:
                    rtn += ' selected'
                elif(params['value']==dval):
                    rtn += ' selected'

            rtn += '>{}</option>'.format(dval)+os.linesep

    rtn += '</select>'+os.linesep
    if 'onchange' in params and '-trigger' in params and params['-trigger']==1:
        rtn += buildOnLoad("commonEmulateEvent('{}','change')".format(params['id']))

    return rtn

#---------- begin function common.buildFormText
# @describe returns csv file contents as recordsets
# @param name string
# @param params - dictionary
#   [start] - int - row number to start on with 1 as the first data row
#   [stop]  - int - row number to stop at
# @usage recs = common.buildFormText(name)
# @usage recs = common.buildFormText(name,**params)
# @author Justin Cline
def buildFormText(name,params={}):
    if '-formname' not in params:
            params['-formname']='addedit'
    if 'inputtype' in params:
            params['-type']=params['inputtype']
    if '-type' not in params:
            params['-type']='text'
    if 'name' in params :
            name=params['name']
    if 'id' not in params:
            params['id']='{}_{}'.format(params['-formname'],name)
    if 'class' not in params:
            params['class']='w_form-control'
    params['value']=buildFormValueParam(name,params)
    if 'requiredif' in params:
            params['data-requiredif']=params['requiredif']
    if 'displayif' in params:
            params['data-displayif']=params['displayif']
    params['name']=name
    if 'viewonly' in params:
            return '<div class="w_viewonly" id="{}">{}</div>'.format(params['id'],nl2br(params['value']));
    tag='   <input type="{}" value="{}"'.format(params['-type'],params['value'])
    tag += setTagAttributes(params)
    selections=db.getFieldSelections(params)
    if 'tvals' in selections and isinstance(selections['tvals'], list) and len(selections['tvals']):
        list_id=name+'_datalist'
        tag += ' list="{}"'.format(list_id)
        tag += ' />' 
        tag += '    <datalist id="{}">'.format(list_id)
        for i,tval in enumerate(selections['tvals']):
            if i in selections['dvals']:
                dval=selections['dvals'][i]
            else:
                dval=tval
            tag += '   <option value="{}">{}</option>'.format(tval,dval)
        tag += '   </datalist>'
    else:
        tag += ' />'
    return tag

def buildFormValueParam(name,params={},arr=0):
    #load the wasql module from the parent script if it exists
    if 'value' not in params:
        namebk="{}[]".format(name)
        if '-value' in params:
            params['value']=params['-value']
        elif name in params:
            params['value']=params[name]
        elif namebk in params:
            params['value']=params[namebk]
        elif inspect.ismodule(wasql):
            if wasql.request(name):
                params['value']=wasql.request(name)
            elif wasql.request(namebk):
                params['value']=wasql.request(namebk)
            else:
                params['value']=''
        else:
            params['value']=''
    if arr==1:
        if 'value' in params:
            if type(params['value']) == 'list':
                val=params['value']
            else:
                try:
                    val=json.loads(params['value'])
                except Exception as e:
                    val=''
                else:
                    val=''
                finally:
                    val=''
        else:
            val=''
    else:
        if 'value' in params:
            if(type(params['value']) is list):
                val = json.dumps(params['value'])
            else:
                val=params['value']
    return val


#---------- begin function buildOnLoad
# @describe executes javascript in an ajax call by builing an image and invoking onload
# @param str string - javascript to invoke on load
# @param [img] string - image to load. defaults to /wfiles/clear.gif
# @param [width] integer - width of img. defaults to 1
# @param [height] integer - height of img. defaults to 1
# @return string - image tag with the specified javascript string invoked onload
# @usage <?py common.buildOnLoad("document.myform.myfield.focus()")?>
def buildOnLoad(str='',img='/wfiles/clear.gif',width=1,height=1):
    return '<img class="w_buildonload" src="{}" alt="onload functions" width="{}" height="{}" style="border:0px;" onload="eventBuildOnLoad();" data-onload="{}">'.format(img,width,height,str)

#---------- begin function calculateDistance--------------------
# @describe distance between two longitude & latitude points
# @param lat1 float - First Latitude
# @param lon1 float - First Longitude
# @param lat2 float - Second Latitude
# @param lon2 float - Second Longitude
# @param unit char - unit of measure - K=kilometere, N=nautical miles, M=Miles
# @return distance float
# @usage dist = common.calculateDistance(lat1, lon1, lat2, lon2)
def calculateDistance(lat1, lon1, lat2, lon2, unit='M'):
    #Python, all the trig functions use radians, not degrees
    # approximate radius of earth in km
    R = 6373.0
    lat1 = radians(abs(lat1))
    lon1 = radians(abs(lon1))
    lat2 = radians(abs(lat2))
    lon2 = radians(abs(lon2))

    dlon = lon2 - lon1
    dlat = lat2 - lat1

    a = sin(dlat / 2)**2 + cos(lat1) * cos(lat2) * sin(dlon / 2)**2
    c = 2 * atan2(sqrt(a), sqrt(1 - a))

    distance = R * c
    #miles
    if unit == 'M':
        miles = distance * 0.621371
        return miles
    else:
        return distance

#---------- begin function cmdResults---------------
# @describe executes command and returns results
# @param cmd string - the command to execute
# @param [args] string - a string of arguments to pass to the command
# @param [dir] string - directory
# @param [timeout] integer - seconds to let process run for. Defaults to 0 - unlimited
# @return string - returns the results of executing the command
# @usage  out=common.cmdResults('ls -al')
def cmdResults(cmd,args='',dir='',timeout=0):
    result = subprocess.run([cmd, args], stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    return result.stdout.decode('utf-8')

#---------- begin function common.coalesce
# @describe returns the first non-null, non-blank value in a list
# @param args
# @return mixed
# @usage privateToken=common.coalesce(params['token'],vals['gitlab_token'],'')
def coalesce(*values):
    for v in values:
        if type(v)==str and len(v) > 0:
            return v
        elif type(v)==int:
            return v
        elif type(v)==list:
            return v
        elif type(v)==tuple:
            return v
        elif type(v)==dict:
            return v
    return ''

#---------- begin function cronLog---------------
# @describe adds a log to the _cron_log table
# @param str string - message to log
# @return boolean
# @usage  ok=common.cronLog('running loop now')
def cronLog(host,pid,str):
    host="{}".format(host)
    pid="{}".format(pid)
    str="{}".format(str)
    ppath=getParentPath(scriptPath())
    cronlog=os.path.abspath(ppath+'/php/cronlog.php')
    result = subprocess.run(['php',cronlog,host,pid,str], stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    return result.stdout.decode('utf-8')

#---------- begin function decodeBase64
# @describe decodes a base64 encodes string - same as base64_decode
# @param str string - base64 string to decode
# @return str string - decodes a base64 encodes string - same as base64_decode
# @usage dec=common.decodeBase64(encoded_string)
def decodeBase64(str):
    base64_bytes = str.encode('ascii')
    message_bytes = base64.b64decode(base64_bytes)
    message = message_bytes.decode('ascii')
    return message

#---------- begin function decrypt
# @describe decrypt a string that was encoded with the encrypt function
# @param enc string - string to decrypt
# @param salt string - salt used during encrypt
# @return str - decrypted string
# @usage str=common.decrypt(enc,salt)
def decrypt(string, salt):
    result = ''
    str = base64.b64decode(string)
    i=0
    while(i < len(str)):
        charord = str[i]
        ki=(i % len(salt))-1
        keychar = salt[ki]
        chrval=charord-ord(keychar)
        char = chr(chrval)
        result=result+char
        i=i+1

    return result

#---------- begin function encodeBase64
# @describe wrapper for base64_encode
# @param str string - string to encode
# @return str - Base64 encoded string
# @usage enc=common.encodeBase64(str)
def encodeBase64(str):
    message_bytes = str.encode('ascii')
    base64_bytes = base64.b64encode(message_bytes)
    base64_message = base64_bytes.decode('ascii')
    return base64_message


#---------- begin function echo
# @describe wrapper for print
# @param str string - string to encode
# @return null
# @usage common.echo('hello')
def echo(str):
    if isCLI():
        print(str,end="\n")
    else:
        print(str,end="<br />\n")

#---------- begin function decodeURL
# @describe wrapper for urllib.parse.unquote
# @param str string - string to decode
# @return str - decoded string
# @usage dec=common.decodeURL(str)
def decodeURL(str):
    return urllib.parse.unquote(urllib.parse.unquote(str))

#---------- begin function encodeURL
# @describe wrapper for urllib.parse.quote_plus
# @param str string - string to encode
# @return str - encoded string
# @usage enc=common.encodeURL(str)
def encodeURL(str):
    return urllib.parse.quote_plus(str)

#---------- begin function encodeJson
# @describe wrapper for json.dumps
# @param arr array or list - array to list to encode
# @return str - JSON encoded string
# @usage print common.encodeJson(arr)
def encodeJson(arr):
    return json.dumps(arr)

#---------- begin function decodeJson
# @describe wrapper for json.loads
# @param str string - string to encode
# @return list - JSON object
# @usage json=common.decodeJson(str)
def decodeJson(str):
    return json.loads(str)

#---------- begin function setFileContents--------------------
# @describe writes data to a file
# @param file string - absolute path of file to write to
# @param data string - data to write
# @param [append] - set to true to append defaults to false
# @return boolean
# @usage $ok=setFileContents($file,$data)
def setFileContents(filename,data,append=False):
    if(append==True):
        f = open(filename, 'a')
    else:
        f = open(filename, 'w')

    f.write(data)
    f.close()

#---------- begin function strtotime--------------------
# @describe mimicks PHP's strtotime function - converts a string to a unix timestamp
# @param string - string to parse
# @return unix_timestamp (seconds since January 1, 1970)
def strtotime(str):
    dt=parseCustomDate(str)
    if dt is None:
        return ''

    try:
        return int(ttime.mktime(dt.timetuple()))
    except (ValueError, TypeError):
        return ''
    

def customDate(date_format="%Y-%m-%d %H:%M:%S",t=''):
    unix_timestamp=strtotime(t)

    dt = datetime.fromtimestamp(unix_timestamp)
    return dt.strftime(date_format)

#---------- begin function parseCustomDate--------------------
# @describe Parses human-readable date strings into a datetime object.
# @param string - string to parse
# @return date_object
# @usage print(parseCustomDate("now"))
# @usage print(parseCustomDate("+1 week"))
# @usage print(parseCustomDate("-1 week"))
# @usage print(parseCustomDate("last Monday"))
# @usage print(parseCustomDate("4th Thursday of November"))
# @usage print(parseCustomDate("November 11th"))
# @usage print(parseCustomDate("+2 week sun jun"))
# @usage print(parseCustomDate("last Monday of May"))
def parseCustomDate(date_str):
    """
    Parses human-readable date strings into a datetime object.
    Handles cases like 'Next Thursday', '-30 Days', '+1 week', '4th Thursday of November', etc.
    """
    date_str = date_str.lower()  # Handle case insensitivity

    # Add handling for invalid or empty input
    if not date_str:
        return "Error: Empty date string provided."

    weekdays = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"]
    weekdays_short = ["mon", "tue", "wed", "thu", "fri", "sat", "sun"]
    # Convert abbreviated month names to full names
    month_abbrev = {
        "jan": "January", "feb": "February", "mar": "March", "apr": "April",
        "may": "May", "jun": "June", "jul": "July", "aug": "August",
        "sep": "September", "oct": "October", "nov": "November", "dec": "December"
    }

    # Handle "now" or blank
    if len(date_str)==0 or date_str == "now":
        return datetime.now()
     # Handle "yesterday"
    if date_str == "yesterday":
        return datetime.now() - timedelta(days=1)

    # Handle "tomorrow"
    if date_str == "tomorrow":
        return datetime.now() + timedelta(days=1)

    # Handle relative dates like '+1 week', '+2 days', etc.
    if re.match(r"[-+]\d+ (weeks?|days?|months?|years?)", date_str):
        match = re.match(r"([-+]\d+) (weeks?|days?|months?|years?)", date_str)
        amount = int(match.group(1))
        unit = match.group(2).lower()

        target_date = datetime.now()
        if "week" in unit:
            target_date += timedelta(weeks=amount)
        elif "day" in unit:
            target_date += timedelta(days=amount)
        elif "month" in unit:
            target_date += relativedelta(months=amount)
        elif "year" in unit:
            target_date += relativedelta(years=amount)

        return target_date

    # Handle last day of month (e.g., 'last day of May 2024')
    if re.match(r"last day of \w+ \d{4}", date_str):
        match = re.match(r"last day of (\w+) (\d{4})", date_str)
        month_str = match.group(1).capitalize()
        year = int(match.group(2))
        month = datetime.strptime(month_str, "%B").month

        # Get the last day of the month
        last_day = calendar.monthrange(year, month)[1]
        return datetime(year, month, last_day)

    elif re.match(r"last day of \w+", date_str):
        match = re.match(r"last day of (\w+)", date_str)
        month_str = match.group(1).capitalize()
        year = int(datetime.now().strftime('%Y'))
        month = datetime.strptime(month_str, "%B").month

        # Get the last day of the month
        last_day = calendar.monthrange(year, month)[1]
        return datetime(year, month, last_day)

    # Handle "Nth <weekday> in/of <month> <year>" like "4th Monday in January", "3rd Thu of November", etc.
    if re.match(r"(first|second|third|fourth|last|\d+(st|nd|rd|th)) \w+ (in|of) \w+( \d{4})?", date_str):
        match = re.match(r"(first|second|third|fourth|last|\d+(st|nd|rd|th)) (\w+) (in|of) (\w+)( \d{4})?", date_str)
        position = match.group(1).lower()
        weekday_str = match.group(3).lower()  # Change to lowercase for matching
        month_str = match.group(5).capitalize()
        # Replace abbreviated month with full name if applicable
        month_str = month_abbrev.get(month_str.lower(), month_str)

        # Use current year if year is not specified
        year = int(match.group(6).strip()) if match.group(6) else datetime.now().year

        if weekday_str in weekdays or weekday_str in weekdays_short:
            weekday = weekdays.index(weekday_str) if weekday_str in weekdays else weekdays_short.index(weekday_str)
            month = datetime.strptime(month_str, "%B").month

            # Map text positions to numbers or use directly from ordinal number
            if position in ["first", "1st"]:
                n = 1
            elif position in ["second", "2nd"]:
                n = 2
            elif position in ["third", "3rd"]:
                n = 3
            elif position in ["fourth", "4th"]:
                n = 4
            elif position == "last":
                return last_weekday_of_month(year, month, weekday)

            return nth_weekday_of_month(year, month, weekday, n)

    # Handle "+2 week(s) sun (jun|july|august|...|dec)"
    if re.match(r"[-+]\d+ (weeks?|days?|months?|years?) \w+ \w+", date_str):
        match = re.match(r"([-+]\d+) (weeks?|days?|months?|years?) (\w+) (\w+)", date_str)
        amount = int(match.group(1))
        unit = match.group(2).lower()
        weekday_str = match.group(3).capitalize()
        month_str = match.group(4).capitalize()
        # Replace abbreviated month with full name if applicable
        month_str = month_abbrev.get(month_str.lower(), month_str)
        
        # Get the target date based on the relative time
        target_date = datetime.now()
        if "week" in unit:
            target_date += timedelta(weeks=amount)
        elif "day" in unit:
            target_date += timedelta(days=amount)
        elif "month" in unit:
            target_date += relativedelta(months=amount)
        elif "year" in unit:
            target_date += relativedelta(years=amount)

        if weekday_str in weekdays:
            weekday = weekdays.index(weekday_str)
            month = datetime.strptime(month_str, "%B").month

            # Adjust the target date to the correct month if needed
            target_date = target_date.replace(month=month, day=1)

            days_ahead = (weekday - target_date.weekday() + 7) % 7
            return target_date + timedelta(days=days_ahead)

        elif weekday_str in weekdays_short:
            weekday = weekdays_short.index(weekday_str)
            month = datetime.strptime(month_str, "%B").month

            # Adjust the target date to the correct month if needed
            target_date = target_date.replace(month=month, day=1)

            days_ahead = (weekday - target_date.weekday() + 7) % 7
            return target_date + timedelta(days=days_ahead)

        else:
            return target_date

    # Handle "Nth <weekday> of <month> <year>" like "4th Thursday of November 2021"
    if re.match(r"\d+(st|nd|rd|th) \w+ of \w+ \d{4}", date_str):
        match = re.match(r"(\d+)(st|nd|rd|th) (\w+) of (\w+) (\d{4})", date_str)
        n = int(match.group(1))
        weekday_str = match.group(3).capitalize()
        month_str = match.group(4).capitalize()
        # Replace abbreviated month with full name if applicable
        month_str = month_abbrev.get(month_str.lower(), month_str)
        year = int(match.group(5))
        if weekday_str in weekdays:
            weekday = weekdays.index(weekday_str)
            month = datetime.strptime(month_str, "%B").month
            return nth_weekday_of_month(year, month, weekday, n)

    elif re.match(r"\d+(st|nd|rd|th) \w+ of \w+", date_str):
        match = re.match(r"(\d+)(st|nd|rd|th) (\w+) of (\w+)", date_str)
        n = int(match.group(1))
        weekday_str = match.group(3).capitalize()
        month_str = match.group(4).capitalize()
        year = int(datetime.now().strftime('%Y'))
        if weekday_str in weekdays:
            weekday = weekdays.index(weekday_str)
            month = datetime.strptime(month_str, "%B").month
            return nth_weekday_of_month(year, month, weekday, n)

    # Handle "last <weekday> of <month> <year>" like "last Monday of May 2023"
    if re.match(r"last \w+ of \w+ \d{4}", date_str):
        match = re.match(r"last (\w+) of (\w+) (\d{4})", date_str)
        weekday_str = match.group(1).capitalize()
        month_str = match.group(2).capitalize()
        # Replace abbreviated month with full name if applicable
        month_str = month_abbrev.get(month_str.lower(), month_str)
        year = int(match.group(3))
        if weekday_str in weekdays:
            weekday = weekdays.index(weekday_str)
            month = datetime.strptime(month_str, "%B").month
            return last_weekday_of_month(year, month, weekday)

    elif re.match(r"last \w+ of \w+", date_str):
        match = re.match(r"last (\w+) of (\w+)", date_str)
        weekday_str = match.group(1).capitalize()
        month_str = match.group(2).capitalize()
        year = int(datetime.now().strftime('%Y'))
        if weekday_str in weekdays:
            weekday = weekdays.index(weekday_str)
            month = datetime.strptime(month_str, "%B").month
            return last_weekday_of_month(year, month, weekday)

    # Handle "Last <weekday>" like "last Monday"
    if re.match(r"last \w+", date_str):
        weekday_str = date_str.split()[1].capitalize()
        if weekday_str in weekdays:
            weekday = weekdays.index(weekday_str)
            return last_weekday(weekday)
    
    # Handle specific date like "November 11th"
    if re.match(r"\w+ \d+(st|nd|rd|th)?", date_str):
        try:
            return parser.parse(date_str)
        except (ValueError, TypeError):
            pass

    # Handle "Easter <year>"
    if re.match(r"easter \d{4}", date_str):
        match = re.match(r"easter (\d{4})", date_str)
        year = int(match.group(1))
        return calculate_easter(year)

    # Add other holidays
    if re.match(r"thanksgiving \d{4}", date_str):
        match = re.match(r"thanksgiving (\d{4})", date_str)
        year = int(match.group(1))
        # 4th Thursday of November
        return nth_weekday_of_month(year, 11, 3, 4)  # Thursday is the 3rd weekday

    if re.match(r"christmas \d{4}", date_str):
        match = re.match(r"christmas (\d{4})", date_str)
        year = int(match.group(1))
        # Christmas is always December 25
        return datetime(year, 12, 25)

    # Handle standard holidays in the current year
    if date_str == "easter":
        return calculate_easter(datetime.now().year)

    if date_str == "thanksgiving":
        return nth_weekday_of_month(datetime.now().year, 11, 3, 4)

    if date_str == "christmas":
        return datetime(datetime.now().year, 12, 25)

    # Handle standard date strings
    try:
        return parser.parse(date_str)
    except (ValueError, TypeError):
        return None  # Return None if unable to parse

# parseCustomDate Helper functions
#Returns the next occurrence of the given weekday (0=Monday, 6=Sunday)
def next_weekday(weekday):
    today = datetime.now()
    days_ahead = weekday - today.weekday()
    if days_ahead <= 0:  # If the target day has passed this week
        days_ahead += 7
    return today + timedelta(days=days_ahead)

#Returns the previous occurrence of a specific weekday (0=Monday, 6=Sunday)
def last_weekday(weekday):
    today = datetime.now()
    days_behind = today.weekday() - weekday
    if days_behind < 0:  # If today is earlier in the week than the target day
        days_behind += 7
    return today - timedelta(days=days_behind)

#Returns the Nth occurrence of a specific weekday in a given month and year. 
def nth_weekday_of_month(year, month, weekday, n):
    first_day = datetime(year, month, 1)
    first_weekday = first_day + timedelta(days=(weekday - first_day.weekday() + 7) % 7)
    nth_weekday = first_weekday + timedelta(weeks=n-1)
    return nth_weekday

#Returns the last occurrence of a specific weekday in a given month and year.
def last_weekday_of_month(year, month, weekday):
    last_day = datetime(year, month + 1, 1) - timedelta(days=1) if month < 12 else datetime(year, 12, 31)
    days_behind = (last_day.weekday() - weekday) % 7
    return last_day - timedelta(days=days_behind)

#Computes the date of Easter for a given year.
def calculate_easter(year):
    # Using the Anonymous Gregorian algorithm to calculate Easter
    a = year % 19
    b = year // 100
    c = year % 100
    d = b // 4
    e = b % 4
    f = (b + 8) // 25
    g = (b - f + 1) // 3
    h = (19 * a + b - d - g + 15) % 30
    i = c // 4
    k = c % 4
    l = (32 + 2 * e + 2 * i - h - k) % 7
    m = (a + 11 * h + 22 * l) // 451
    month = (h + l - 7 * m + 114) // 31
    day = ((h + l - 7 * m + 114) % 31) + 1
    return datetime(year, month, day)

#---------- begin function encodeHtml
# @describe encodes html chars that would mess in  a browser
# @param str string - string to encode
# @return str - encodes html chars that would mess in  a browser
# @usage html=encodeHtml(str);
def encodeHtml(str=''):
    if(len(str)==0):
        return str
    str=str.replace('?','[[!Q!]]')
    str=str.replace('<','&lt;')
    str=str.replace('>','&gt;')
    str=str.replace('"','&quot;')
    str=str.replace('[[!Q!]]','?')
    return str;
#---------- begin function evalPython ----------
# @describe compiles string and returns result
# @param str
# @usage $ok=common.evalPython(str)
def evalPython(str):
    str = str.strip()
    #point stdout to a variable
    sys.stdout.flush()
    old_stdout = sys.stdout
    new_stdout = io.StringIO()
    sys.stdout = new_stdout
    #compile
    compiledCodeBlock = compile(str, '<string>', 'exec')
    eval(compiledCodeBlock)
    #point stdout back
    output = new_stdout.getvalue().strip()
    sys.stdout = old_stdout
    #remove and None lines
    lines = output.splitlines()
    output = ''
    for line in lines:
        line = line.strip()
        if line != 'None':
            output += line+os.linesep
    #return
    return output

#---------- begin function formatPhone
# @describe formats a phone number
# @param string phone number
# @return string - formatted phone number (xxx) xxx-xxxx
# @usage ph=common.FormatPhone('8014584741')
def formatPhone(phone_number):
    clean_phone_number = re.sub('[^0-9]+', '', phone_number)
    formatted_phone_number = re.sub("(\d)(?=(\d{3})+(?!\d))", r"\1-", "%d" % int(clean_phone_number[:-1])) + clean_phone_number[-1]
    return formatted_phone_number

#---------- begin function getFileContents
# @describe gets the contents of a file
# @param filename string - full path to file
# @return string - file contants
# @usage ph=common.getFileContents('/var/tmp/abc.txt')
def getFileContents(filename):
    with open(filename) as f:
        return f.read()

#---------- begin function getParentPath
# @describe gets the parent path
# @param string phone number
# @return string - formatted phone number (xxx) xxx-xxxx
# @usage ph=common.FormatPhone('8014584741')
def getParentPath(path):
    return os.path.abspath(os.path.join(path, os.pardir))

#---------- begin function getRandomString
# @describe returns a random string on size length
# @param size int
# @return string 
# @usage id=common.getRandomString(6)
def getRandomString(size=6, chars=string.ascii_uppercase + string.digits):
    return ''.join(random.choice(chars) for _ in range(size))

#---------- begin function getCSVRecords
# @describe returns csv file contents as recordsets
# @param afile string - full path to csv file
# @param params - dictionary
#   [start] - int - row number to start on with 1 as the first data row
#   [stop]  - int - row number to stop at
# @usage recs = common.getCSVRecords(afile)
# @usage recs = common.getCSVRecords(afile,**params)
def getCSVRecords(afile,params={}):
    #read a small portion to determine the dialect
    with open(afile, mode="r", encoding="utf-8-sig") as csvfile:
        sample = csvfile.read(1024)
        try:
            deduced_dialect = csv.Sniffer().sniff(sample)
        except Exception as err:
            deduced_dialect = None
    recs=[]
    rownum=0
    with open(afile, mode="r", encoding="utf-8-sig") as csvfile:
        if(type(deduced_dialect)==None):
            reader = csv.reader(csvfile, delimiter=',')
        else:
            reader = csv.reader(csvfile, deduced_dialect)
        # list to store the names of columns
        fields = next(reader)
        for row in reader:
            rownum+=1
            if ('start' in params and params['start'] > rownum):
                continue
            #convert row to dictionary
            rec=dict(zip(fields, row))
            #append row to recs list
            recs.append(rec)
            if ('stop' in params and params['stop'] <= rownum):
                break
    #return recs list
    return recs
def getWasqlPath(str=''):
    wpath=getParentPath(scriptPath())
    if len(str):
        wpath+='/{}'.format(str)

    return wpath
#---------- begin function hex2RGB
# @describe converts a hex string into a rgb tuple
# @param hexvalue string
# @return tuple
# @usage host=common.hex2RGB('#9495a3')
def hex2RGB(hexvalue):
    hexvalue = hexvalue.lstrip('#')
    lv = len(hexvalue)
    return tuple(int(hexvalue[i:i + lv // 3], 16) for i in range(0, lv, lv // 3))

#---------- begin function rgb2HEX
# @describe converts a rgb tuple to a hex string
# @param rgbvalue tuple
# @return string
# @usage host=common.rgb2HEX(rgbvalue)
def rgb2HEX(rgb):
    return '#%02x%02x%02x' % rgb

#---------- begin function hostname
# @describe gets document root (HTTP_HOST)
# @return string
# @usage host=common.hostname()
def hostname():
    return os.environ['HTTP_HOST']

#---------- begin function isAdmin ----------
# @describe returns true if the current user is a WaSQL Admin
# @return boolean
# @usage if(common.isAdmin()):
def isAdmin():
    if int(wasql.user('utype')) == 0:
        return True
    else:
        return False

#---------- begin function isAjax ----------
# @describe returns true if page was called using AJAX
# @return boolean
# @usage if(common.isAjax()):
def isAjax():
    if len(wasql.request('AjaxRequestUniqueId')) > 0:
        return True
    else:
        return False

#---------- begin function isCLI ----------
# @describe returns true if script is running from a Command Line
# @return boolean
# @usage if(common.isCLI()):
def isCLI():
    if sys.stdin.isatty():
        return True
    else:
        return False

#---------- begin function isDate ----------
# @describe returns true if string is a date
# @param format string - format to check
# @return boolean
# @usage if(common.isDate('2024-10-11')):
def isDate(string,date_format="%Y-%m-%d"):
    try: 
        datetime.strptime(string, date_format)
        return True

    except ValueError:
        return False

#---------- begin function isDateTime ----------
# @describe returns true if string is a datetime
# @return boolean
# @usage if(common.isDateTime('2024-10-11')):
def isDateTime(string):
    try: 
        dateutil.parser.parse(string, False)
        return True

    except ValueError:
        return False

#---------- begin function isEmail ----------
# @describe returns true if specified string is a valid email address
# @param str string - string to check
# @return boolean - returns true if specified string is a valid email address
# @usage if(common.isEmail(str)):
def isEmail(str):
    if(type(obj) is str):
        return bool(re.search(r"^[\w\.\+\-]+\@[\w]+\.[a-z]{2,10}$", str))
    else:
        return False

#---------- begin function isEven ----------
# @describe returns true if specified number is an even number
# @param num number - number to check
# @return boolean - returns true if specified number is an even number
# @usage if(common.isEven(num)):
def isEven(num):
  return num % 2 == 0

#---------- begin function isFactor ----------
# @describe returns true if num is divisable by divisor without a remainder
# @param num number - number to check
# @param divisor number
# @return boolean - returns true if num is divisable by divisor without a remainder
# @usage if(common.isFactor(num,2)):
def isFactor(num,div):
    return num % div == 0

#---------- begin function isFunction ----------
# @describe returns true if str is a function
# @param str string - name of function to check
# @return boolean
# @usage if(common.isFunction('isFactor')):
def isFunction(str):
    return inspect.isfunction(str)

#---------- begin function isJson ----------
# @describe returns true if specified object is JSON
# @param obj - object to check
# @return boolean - returns true if specified object is JSON
# @usage if(common.isJson(obj)):
def isJson(obj):
    if(type(obj) is list):
        try:
            obj = json.dumps(obj)
        except ValueError as e:
            return False

    if(type(obj) is str):
        try:
            json_object = json.loads(obj)
        except ValueError as e:
            return False
        return True
    else:
        return False
    return False

#---------- begin function isWindows ----------
# @describe returns true if script is running on a Windows platform
# @return boolean
# @usage if(common.isWindows()):
def isWindows():
    if sys.platform == 'win32':
        return True
    elif sys.platform == 'win32':
        return True
    elif sys.platform == 'win64':
        return True
    elif os.name == 'nt':
        return True
    else:
        return False

#---------- begin function loadExtras ----------
# @describe loads extra modules found in the python/extras folder
# @param str string
# @return str string
# @usage common.loadExtras('mymodule')
def loadExtras(file):
    extra_path = common.scriptPath('extras')
    afile = "{}/databases/{}".format(extra_path,file)
    if os.path.exists(afile):
        try:
            sys.path.append(extra_path)
            #__import__(name, globals=None, locals=None, fromlist=(), level=0)
            __import__(file, globals(), locals(), [], 0)
            return True
        except Exception as err:
            common.abort(sys.exc_info(),err)

    else:
        afile = "{}/{}".format(extra_path,file)
        if os.path.exists(afile):
            try:
                sys.path.append(extra_path)
                #__import__(name, globals=None, locals=None, fromlist=(), level=0)
                __import__(file, globals(), locals(), [], 0)
                return True
            except Exception as err:
                common.abort(sys.exc_info(),err)

        else:
            return False

#---------- begin function nl2br ----------
# @describe converts new lines to <br /> tags in string
# @param str string
# @return str string
# @usage print(common.nl2br(str))
def nl2br(string):
    return string.replace('\n','<br />\n')

#---------- begin function  ----------
# @exclude internal use and excluded from docs
def parseViews(str):
    global VIEWS
    VIEWS = {}
    matches = re.findall(r'<view:(.*?)>(.+?)</view:\1>', str,re.MULTILINE|re.IGNORECASE|re.DOTALL)
    for (viewname,viewbody) in matches:
        VIEWS[viewname]=viewbody
    return True

#---------- begin function  ----------
# @exclude internal use and excluded from docs
def parseViewsOnly(str):
    views = {}
    matches = re.findall(r'<view:(.*?)>(.+?)</view:\1>', str,re.MULTILINE|re.IGNORECASE|re.DOTALL)
    for (viewname,viewbody) in matches:
        views[viewname]=viewbody
    return views

#---------- begin function  ----------
# @exclude internal use and excluded from docs
def parseCodeBlocks(str):
    matches = re.findall(r'<\?=(.*?)\?>', str,re.MULTILINE|re.IGNORECASE|re.DOTALL)
    for match in matches:
        #add our imports: common, db, re,
        evalstr = 'import common'+os.linesep
        evalstr += 'import config'+os.linesep
        evalstr += 'import db'+os.linesep
        evalstr += 'import re'+os.linesep
        evalstr += os.linesep+"print({})".format(match)
        rtn = evalPython(evalstr).strip()
        repstr = "<?={}?>".format(match)
        if rtn == 'None':
            rtn = ''
        str = str_replace(repstr,rtn,str)
    matches = re.findall(r'<\?py(.*?)\?>', str,re.MULTILINE|re.IGNORECASE|re.DOTALL)
    for match in matches:
        #add our imports: common, db, re,
        evalstr = 'import common'+os.linesep
        evalstr += 'import config'+os.linesep
        evalstr += 'import db'+os.linesep
        evalstr += 'import re'+os.linesep
        lines = match.splitlines()
        for line in lines:
            evalstr += os.linesep+"print({})".format(line.strip())
        rtn = evalPython(evalstr).strip()
        if rtn == 'None':
            rtn = ''
        repstr = "<?py{}?>".format(match)
        str = str_replace(repstr,rtn,str)
    return str

#---------- begin function sendMail ----------
# @describe sends email
# @param dictionary or parameters
#   smtp - string - SMTP server address
#   smtpuser - string - SMTP username - will default to Config smtpuser
#   smtppass - string - SMTP password - will default to Config smtppass
#   [port] - integer - optional port
#   to - string - email addresses to send to
#   [cc] - string - email addresses to cc
#   [bcc] - string - email addresses to bcc
#   from - string - email address to send from
#   subject - string - email subject
#   message - string - email message
#   [attach] - string -  full file path to file to attach
# @return mixed true or error message
# @usage ok=common.sendmail(**params)
# @reference https://www.tutorialspoint.com/python/python_sending_email.htm
def sendMail(params):
    if('smtppass' not in params):
        if('smtppass' in config.CONFIG):
            params['smtppass']=config.CONFIG['smtppass']
        else:
            print('missing smtppass')
            sys.exit(123)

    if('smtpuser' not in params):
        if('smtpuser' in config.CONFIG):
            params['smtpuser']=config.CONFIG['smtpuser']
        else:
            print('missing smtpuser')
            sys.exit(123)

    if('smtp' not in params):
        if('smtp' in config.CONFIG):
            params['smtp']=config.CONFIG['smtp']
        else:
            print('missing smtp')
            sys.exit(123)

    if('smtpport' not in params):
        if('smtpport' in config.CONFIG):
            params['smtpport']=config.CONFIG['smtpport']
        else:
            print('missing smtpport')
            sys.exit(123)

    if('from' not in params):
        if('email_from' in config.CONFIG):
            params['from']=config.CONFIG['email_from']
        else:
            print('missing from')
            sys.exit(123)

    #confirm required info

    #create a unique marker
    marker = "WASQLPYTHONSENDMAILMARKER"

    # Define the main headers.
    part1 =  "From: <{}>".format(params['from'])+os.linesep
    part1 += "To: <{}>".format(params['to'])+os.linesep
    part1 += "Subject: {}".format(params['subject'])+os.linesep
    part1 += "MIME-Version: 1.0"+os.linesep
    part1 += "Content-Type: multipart/mixed; boundary={}".format(marker)+os.linesep
    part1 += "--{}".format(marker)+os.linesep

    # Define the message action
    part2 = "Content-Type: text/html"+os.linesep
    part2 += "Content-Transfer-Encoding:8bit"+os.linesep+os.linesep
    part2 += params['message']+os.linesep
    part2 += "--{}".format(marker)

    message = part1 + part2

    if('attach' in params):
        message += os.linesep
        # Read a file and encode it into base64 format
        fo = open(params['attach'], "rb")
        filecontent = fo.read()
        fo.close()
        encodedcontent = base64.b64encode(filecontent)  # base64
        # Define the attachment section
        part3 =  "Content-Type: multipart/mixed; name=\"{}\"".format(params['attach'])+os.linesep
        part3 += "Content-Transfer-Encoding:base64"+os.linesep
        part3 += "Content-Disposition: attachment; filename={}".format(params['attach'])+os.linesep
        part3 += encodedcontent+os.linesep
        part3 += "--{}".format(marker)
        message += part3

    message += "--"+os.linesep

    try:
        if('smtpport' in params):
            smtpObj = smtplib.SMTP(params['smtp'],params['smtpport'])
        else:
            smtpObj = smtplib.SMTP(params['smtp'])

        smtpObj.starttls()
        smtpObj.login(params['smtpuser'],params['smtppass'])
        smtpObj.sendmail(params['from'], params['to'], message)
        return 1

    except Exception as err:
        abort(sys.exc_info(),err)
        return err

#---------- begin function setTagAttributes ----------
# @describe builds and attribute string out of a list of attributes
# @param atts list
# @return str string
# @usage tag+=common.setTagAttributes(atts)
def setTagAttributes(atts={},skipatts=[]):
    attstring=''
    #pass through common html attributes and ones used by submitForm and ajaxSubmitForm Validation js
    htmlatts=[
        'id','name','class','style','title','alt','accesskey','tabindex',
        'onclick','onchange','onmouseover','onmouseout','onmousedown','onmouseup','onkeypress','onkeyup','onkeydown','onblur','onfocus','oninvalid','oninput',
        '_behavior','display','capture',
        'required','requiredmsg','mask','maskmsg','displayname','size','minlength','maxlength','wrap','readonly','disabled',
        'placeholder','pattern','data-pattern-msg','spellcheck','max','min','readonly','step',
        'lang','autocorrect','list','data-requiredif','autofocus','accept','acceptmsg','autocomplete',
        'action','onsubmit'
        ]
    #oninvalid
    if 'pattern' in atts and 'oninvalid' not in atts and 'data-pattern_message' in atts:
        atts['oninvalid']="setCustomValidity(this.getAttribute('data-pattern_message'));"
    #autofocus
    if 'autofocus' in atts:
        atts['autofocus']="autofocus"
    #required
    if '_required' in atts and atts['_required']==1:
        atts['required']='required'
        del atts['_required']
    if 'required' in atts and atts['required']==1:
        atts['required']='required'
    if 'required' in atts and atts['required']==0:
        del atts['required']
    #inputmax maps to maxlength
    if 'inputmax' in atts and atts['inputmax'].isnumeric() and atts['inputmax'] > 0:
        atts['maxlength']=atts['inputmax']
        del atts['inputmax']
    #mask maps to data-mask
    if 'mask' in atts:
        if 'data-mask' not in atts:
            atts['data-mask']=atts['mask']
        del atts['mask']
    #displayname maps to data-displayname
    if 'displayname' in atts:
        if 'data-displayname' not in atts:
            atts['data-displayname']=atts['displayname']
        del atts['displayname']
    #behavior and _behavior map to data-behavior
    if '_behavior' in atts:
        if 'data-behavior' not in atts:
            atts['data-behavior']=atts['_behavior']
        del atts['_behavior']
    if 'behavior' in atts:
        if 'data-behavior' not in atts:
            atts['data-behavior']=atts['behavior']
        del atts['behavior']
    #readonly
    if 'readonly' in atts:
        if atts['readonly'].isnumeric() and atts['readonly']==0:
            del atts['readonly']
        else:
            atts['readonly']='readonly'
    #disabled
    if 'disabled' in atts:
        if atts['disabled'].isnumeric() and atts['disabled']==0:
            del atts['disabled']
        else:
            atts['disabled']='disabled'
    #look through htmlatts
    for att in htmlatts:
        if att in skipatts:
            continue
        if att in atts and len(atts[att]):
            val=removeHtml(atts[att])
            val=val.replace('"',"'")
            attstring += ' {}="{}"'.format(att,val)
    #add any data- atts
    for att in atts:
        if att.startswith('data-'):
            val=removeHtml(atts[att])
            val=val.replace('"',"'")
            attstring += ' {}="{}"'.format(att,val)
    
    return attstring

#---------- begin function  ----------
# @exclude internal use and excluded from docs
def setView(name,clear=0):
    global VIEW
    if name in VIEWS:
        if clear == 1:
            VIEW = {}
        VIEW[name]=VIEWS[name]

#---------- begin function scriptPath ----------
# @describe returns script path
# @param [dirs] str - subdirs
# @return str string
# @usage path=common.scriptPath()
# @usage path=common.scriptPath('/temp')
def scriptPath(d=''):
        spath = os.path.dirname(os.path.realpath(__file__))
        return os.path.realpath("{}/{}".format(spath,d))

#---------- begin function  ----------
# @exclude internal use and excluded from docs
def createView(name,val):
    global VIEW
    VIEW[name] = val

#---------- begin function  ----------
# @exclude internal use and excluded from docs
def removeHtml(str):
    return re.sub(r'<[^>]*?>', '', str)

#---------- begin function  ----------
# @exclude internal use and excluded from docs
def removeView(name):
    global VIEW
    if name in VIEW:
        del VIEW[name]

#---------- begin function debugValue ----------
# @describe shows errors in developer console
# @param obj mixed
# @usage common.debugValue(recs)
def debugValue(obj):
    global DEBUG
    DEBUG.append(obj)

#---------- begin function  ----------
# @exclude internal use and excluded from docs
def debugValues():
    global DEBUG
    debugstr=''
    for idx,obj in enumerate(DEBUG):
        debugstr+='<div id="debug_{}" style="display:none;">'.format(idx)+os.linesep
        debugstr+=pprint.pformat(obj).strip("'")+os.linesep
        debugstr+='</div>'+os.linesep
        onload="if(typeof(console) != 'undefined' && typeof(console.log) != 'undefined'){console.log(document.getElementById('debug_"+"{}".format(idx)+"').innerHTML);}"
        debugstr+=buildOnLoad(onload)+os.linesep
    return debugstr

#---------- begin function printValue ----------
# @describe returns an html block showing the contents of the object,array,or variable specified
# @param obj mixed
# @return str string
# @usage common.printValue(recs)
def printValue(obj):
    if(isJson(obj)):
        print('<pre class="printvalue" type="JSON">')
        print(json.dumps(obj, indent=4, sort_keys=True))
        print('</pre>')
    else:
        typename=type(obj).__name__
        print('<pre class="printvalue" type="'+typename+'">')
        print(pprint.pformat(obj).strip("'"))
        print('</pre>')

#---------- begin function sleep ----------
# @describe sleeps x seconds
# @param x number  seconds
# @return boolean
# @usage common.sleep(3):
def sleep(x):
    return ttime.sleep(x)

#---------- begin function stringContains ----------
# @describe returns true if string contains substr
# @param str string
# @param substr string
# @return boolean
# @usage if(common.stringContains(str,val)):
def stringContains(str,substr):
    if substr in str:
        return True
    else:
        return False

#---------- begin function stringEndsWith ----------
# @describe returns true if string ends with substr
# @param str string
# @param substr string
# @return boolean
# @usage if(common.stringEndsWith(str,val)):
def stringEndsWith(str,substr):
    return str.endswith(substr)

#---------- begin function stringBeginsWith ----------
# @describe returns true if string begins with substr
# @param str string
# @param substr string
# @return boolean
# @usage if(common.stringBeginsWith(str,val)):
def stringBeginsWith(str,substr):
    return str.startswith(substr)

#---------- begin function str_replace ----------
# @describe replaces str with str2 in str3
# @param str string
# @param str2 string
# @param str3 string
# @return string
# @usage newstr=common.str_replace('a','b','abb')
def str_replace(str, str2, str3):
    result = str3.replace(str,str2)
    return result

#---------- begin function time ----------
# @describe returns unix timestamp
# @return int
# @usage t=common.time()
def time():
    return ttime.time()

#---------- begin function verboseNumber ----------
# @describe converts a number(seconds) to a string
# @param int integer
# @return str string
# @usage print(common.verboseNumber(524))
def verboseNumber(num):
    d = { 0 : 'zero', 1 : 'one', 2 : 'two', 3 : 'three', 4 : 'four', 5 : 'five',
          6 : 'six', 7 : 'seven', 8 : 'eight', 9 : 'nine', 10 : 'ten',
          11 : 'eleven', 12 : 'twelve', 13 : 'thirteen', 14 : 'fourteen',
          15 : 'fifteen', 16 : 'sixteen', 17 : 'seventeen', 18 : 'eighteen',
          19 : 'nineteen', 20 : 'twenty',
          30 : 'thirty', 40 : 'forty', 50 : 'fifty', 60 : 'sixty',
          70 : 'seventy', 80 : 'eighty', 90 : 'ninety' }
    k = 1000
    m = k * 1000
    b = m * 1000
    t = b * 1000

    assert(0 <= num)

    if (num < 20):
        return d[num]

    if (num < 100):
        if num % 10 == 0: return d[num]
        else: return d[num // 10 * 10] + '-' + d[num % 10]

    if (num < k):
        if num % 100 == 0: return d[num // 100] + ' hundred'
        else: return d[num // 100] + ' hundred and ' + verboseNumber(num % 100)

    if (num < m):
        if num % k == 0: return verboseNumber(num // k) + ' thousand'
        else: return verboseNumber(num // k) + ' thousand, ' + verboseNumber(num % k)

    if (num < b):
        if (num % m) == 0: return verboseNumber(num // m) + ' million'
        else: return verboseNumber(num // m) + ' million, ' + verboseNumber(num % m)

    if (num < t):
        if (num % b) == 0: return verboseNumber(num // b) + ' billion'
        else: return verboseNumber(num // b) + ' billion, ' + verboseNumber(num % b)

    if (num % t == 0): return verboseNumber(num // t) + ' trillion'
    else: return verboseNumber(num // t) + ' trillion, ' + verboseNumber(num % t)

    raise AssertionError('num is too large: %s' % str(num))

#---------- begin function list2CSV--------------------
# @describe Converts a list to a CSV string. Example use: converting getDBRecords results into CSV file.
# @param arr array - The list to convert
# @param params array - Optional CSV formatting parameters
# [-fields] - Comma separated list of fields to include in the CSV
# [-fieldmap] - field=>mapname Array of fieldmaps to change the name on the first line
# [-noheader] - Do not include a header row
# [-delim] - Field delimiter string defaults to comma
# [-enclose] - Field enclosure string defaults to quote
# [-linedelim] - Line delimiter string defaults to newline
# @usage
#   csv=list2CSV(recs,{
#       '-fields':'name,age,color'
#   })
# @return string - CSV-formatted output
def list2CSV(recs = [], params = {}):
    # Defaults
    if '-delim' not in params:
        params['-delim'] = ','
    if '-enclose' not in params:
        params['-enclose'] = '"'
    if '-linedelim' not in params:
        params['-linedelim'] = "\n"
    if not isinstance(recs, list) or len(recs) == 0:
        return "No records found"
    # Get fields for header row
    fields = []
    if '-fields' in params:
        if isinstance(params['fields'], list):
            fields = params['-fields']
        else:
            fields = re.split(r'[\,\:\;]+', strip(params['-fields']))
    else:
        for rec in recs:
            if not isinstance(rec, str):
                for k in rec:
                    if k not in fields:
                        fields.append(k)
            else:
                fields.append(rec)
    fieldmap = {}
    for field in fields:
        key = field
        if '-fieldmap' in params and field in params['-fieldmap']:
            field = params['-fieldmap'][field]
        elif field+'_dname' in params:
            field = params[field+'_dname']
        elif field+'_displayname' in params:
            field = params[field+'_displayname']
        else:
            field = field.lower().replace(' ', '_')
        fieldmap[key] = field
    csvlines = []
    if '-noheader' not in params or params['-noheader'] == 0:
        if '-force' in params and params['-force']:
            csvlines.append(csvImplode(list(fieldmap.values()), params['-delim'], params['-enclose'], 1))
        else:
            csvlines.append(csvImplode(list(fieldmap.values()), params['-delim'], params['-enclose']))
    for rec in recs:
        vals = []
        for field in fieldmap:
            if isinstance(rec[field], (dict, list)):
                rec[field] = json.dumps(rec[field], ensure_ascii=False).encode('utf-8', errors='replace').decode('utf-8') # Convert to JSON don't force ASCII and then replace non-UTF-8 characters in the JSON string with \0xfffd Unicode replacement character
            vals.append(rec[field])
        if '-force' in params and params['-force']:
            csvlines.append(csvImplode(vals, params['-delim'], params['-enclose'], 1))
        else:
            csvlines.append(csvImplode(vals, params['-delim'], params['-enclose']))
    return "\r\n".join(csvlines)

#---------- begin function listFiles--------------------------------------
# @describe returns a list of files in a directory
# @param string adir - directory path
# @return string - return a list of files
# @usage files=common.listFiles(mypath)
def listFiles(adir):
    files = os.listdir(adir)
    #Remove anything that is not a file
    #files = [f for f in files if os.path.isfile(adir+'/'+f)]
    return files 

#---------- begin function listFilesEx--------------------------------------
# @describe returns a list of files in a directory
# @param string adir - directory path
# @return string - return a list of files
# @usage files=common.listFiles(mypath)
def listFilesEx(adir):
    files = os.listdir(adir)
    #Remove anything that is not a file
    #files = [f for f in files if os.path.isfile(adir+'/'+f)]
    recs=[]
    for f in files:
        rec={}
        rec['name']=f
        rec['path']=adir
        rec['afile']=adir+'/'+f
        rec['ext']=os.path.splitext(rec['afile'])[1][1:]
        info = os.lstat(rec['afile'])
        rec['size']=info.st_size
        rec['atime']=info.st_atime
        rec['adate']=datetime.fromtimestamp(rec['atime']).strftime("%Y-%m-%d")
        rec['mtime']=info.st_mtime
        rec['mdate']=datetime.fromtimestamp(rec['mtime']).strftime("%Y-%m-%d")
        rec['ctime']=info.st_ctime
        rec['cdate']=datetime.fromtimestamp(rec['ctime']).strftime("%Y-%m-%d")
        recs.append(rec)
    #
    return recs 

#---------- begin function csvImplode--------------------------------------
# @describe Creates a csv string from an array
# @param arr array - The array to convert to a csv string
# @param delim char[optional] - The delimiter character - defaults to a comma
# @param enclose char[optional] - The enclose character - defaults to a double-quote
# @return string - Returns a csv string
# @usage line=csvImplode(parts_array)
def csvImplode(parts = {}, delim = ',', enclose = '"'): # UNUSED , force = 0):
    io_stream = io.StringIO()
    csv_writer = csv.writer(io_stream, delimiter=delim, quotechar=enclose, quoting=csv.QUOTE_MINIMAL)
    csv_writer.writerow(parts)
    line = io_stream.getvalue()
    io_stream.close()
    line = line.rstrip()
    line.replace(r'[\r\n]+$', '') # Remove carriage return and/or newline at end of string that may have been added by writerow
    line = line.rstrip()
    return line





