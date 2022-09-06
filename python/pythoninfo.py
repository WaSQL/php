#! python
"""
Loop through the installed packages and get info on each. pip show {module} returns the folloiwng
	Name: asn1crypto
	Version: 1.5.1
	Summary: Fast ASN.1 parser and serializer with definitions for private keys, public keys, certificates, CRL, OCSP, CMS,
	PKCS#3, PKCS#7, PKCS#8, PKCS#12, PKCS#5, X.509 and TSP
	Home-page: https://github.com/wbond/asn1crypto
	Author: wbond
	Author-email: will@wbond.net
	License: MIT
	Location: c:\program files\python310\lib\site-packages
	Requires:
	Required-by: oscrypto, snowflake-connector-python
"""
import pkg_resources
import subprocess
import sys

prows=''
for p in pkg_resources.working_set:
	if p.project_name=='fdb':
		continue
	
	prow='<div class="align-center w_bold" style="margin-top:10px;font-size:clamp(11px,2vw,24px);color:#1d415e"><a name="module_{}">{}</a></div><table class="table striped condensed" style="border:1px solid #000;margin-top:5px;">'.format(p.project_name,p.project_name)
	info=''
	try:
		info = pkg_resources.get_distribution(p.project_name).get_metadata('PKG-INFO')
	except Exception as err:
		info = pkg_resources.get_distribution(p.project_name).get_metadata('METADATA')

	for line in info.splitlines():
		parts=line.split(':',1)
		partscount=len(parts)
		if partscount != 2:
			continue;
		if parts[0]=='Classifier':
			break;
		key=parts[0]
		val=parts[1]
		trow='<tr><td style="background:#1d415e;color:#FFF;white-space:nowrap;padding-right:0.4rem;">{}</td><td style="width:100%;">{}</td></tr>'.format(key,val)
		prow=prow+trow

	prow=prow+"</table>"
	prows=prows+prow


output="""
<section>
	<div style="background:#1d415e;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
		<div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="brand-python"></span> Python</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Version {}</div>
	</div>
	{}
</section>
"""
print(output.format(sys.version,prows))





