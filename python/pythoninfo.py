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
import common
import subprocess

for p in pkg_resources.working_set:
	print(p.project_name)
	if p.project_name=='fdb':
		continue
	try:     
		for line in subprocess.check_output(['python3','-m','pip','show',p.project_name]).decode('utf-8').split('\n'):
			parts=line.split(':',1)
			partscount=len(parts)
			if partscount != 2:
				continue;
			key=parts[0]
			val=parts[1]
			print("{} = {}".format(key,val))
	except Exception as err:
		print("")

	print("\n")





