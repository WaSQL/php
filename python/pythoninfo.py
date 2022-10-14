#! python

import pkg_resources
import sys
try:
	sys.stdin.reconfigure(encoding='utf-8')
	sys.stdout.reconfigure(encoding='utf-8')
except Exception as err:
	nothing=''

prows=''
for p in pkg_resources.working_set:
	prow='''
		<section>
		<div class="align-center w_bold" style="margin-top:10px;font-size:clamp(11px,2vw,24px);color:#1d415e">
			<a name="module_{}">{}</a>
		</div>
		<table class="table striped condensed" style="border:1px solid #000;margin-top:5px;">
		'''.format(p.project_name,p.project_name)
	info=''
	try:
		info = pkg_resources.get_distribution(p.project_name).get_metadata('PKG-INFO')
	except Exception as err:
		info = pkg_resources.get_distribution(p.project_name).get_metadata('METADATA')

	for line in info.splitlines():
		parts=line.split(':',1)
		partscount=len(parts)
		if partscount != 2:
			continue

		if len(parts[0])==0:
			break

		if "<" in parts[0]:
			break

		if "|" in parts[0]:
			break

		if "." in parts[0]:
			break

		if "[" in parts[0]:
			break

		if " " in parts[0]:
			break

		if "(" in parts[0]:
			break

		if parts[0]=='Metadata-Version':
			continue

		if parts[0]=='Description-Content-Type':
			break

		if parts[0]=='Classifier':
			break	

		if parts[0]=='Home-page':
			parts[1]='<a href="{}" class="w_link" target="_blank">{}</a>'.format(parts[1],parts[1])

		if 'email' in parts[0]:
			parts[1]='<a href="mailto:{}" class="w_link">{}</a>'.format(parts[1],parts[1])

		key=parts[0]
		val=parts[1]
		trow='	<tr><td style="background:#1d415e;color:#FFF;white-space:nowrap;padding-right:0.4rem;">{}</td><td style="width:100%;">{}</td></tr>'.format(key,val)
		trow=trow+"\n"
		prow=prow+trow

	prow=prow+"\n</table>\n</section>\n\n"
	prows=prows+prow


output="""
<header>
	<div style="background:#1d415e;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
		<div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="brand-python"></span> Python</div>
		<div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Version {}</div>
	</div>
</header>
{}
"""
print(output.format(sys.version,prows))





