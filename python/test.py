#! python
"""
References
    https://www.php2python.com/

"""
import os
import sys
#import common.py
try:
    import common
except ImportError as err:
    print("Content-type: text/plain; charset=UTF-8;\n\n")
    sys.exit(err)
#header
if not common.isCLI():
    print("Content-type: text/html; charset=UTF-8;\n\n")
#show message
common.echo("test successful")