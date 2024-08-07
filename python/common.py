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
    import csv
    from contextlib import redirect_stdout
    from math import sin, cos, sqrt, atan2, radians
    import subprocess
    from datetime import datetime
    import time as ttime
    import base64
    import urllib.parse
    import json
    import smtplib
    import config
except Exception as err:
    exc_type, exc_obj, exc_tb = sys.exc_info()
    fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
    print("Import Error: {}. ExeptionType: {}, Filename: {}, Linenumber: {}".format(err,exc_type,fname,exc_tb.tb_lineno))
    sys.exit(3)

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

#---------- begin function getParentPath
# @describe gets the parent path
# @param string phone number
# @return string - formatted phone number (xxx) xxx-xxxx
# @usage ph=common.FormatPhone('8014584741')
def getParentPath(path):
    return os.path.abspath(os.path.join(path, os.pardir))

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

#---------- begin function hostname
# @describe gets document root (HTTP_HOST)
# @return string
# @usage host=common.hostname()
def hostname():
    return os.environ['HTTP_HOST']

#---------- begin function isCLI ----------
# @describe returns true if script is running from a Command Line
# @return boolean
# @usage if(common.isCLI()):
def isCLI():
    if sys.stdin.isatty():
        return True
    else:
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
# 	csv=list2CSV(recs,{
# 		'-fields':'name,age,color'
# 	})
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





