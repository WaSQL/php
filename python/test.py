#! python
"""
References
    https://www.php2python.com/

    sys library
    https://docs.python.org/3/library/sys.html
        sys.argv
            The list of command line arguments passed to a Python script. argv[0] is the script name
        sys.exc_info()
            This function returns a tuple of three values 
            type, value, traceback
        sys.executable
            A string giving the absolute path of the executable binary for the Python interpreter
        sys.exit([int])
            Exit from Python. Most systems require it to be in the range 0–127. 0=success
        sys.getsizeof(object)
            Return the size of an object in bytes (memory it is using).
        sys.path
            A list of strings that specifies the search path for modules
        sys.platform
            This string contains a platform identifier. 
                aix, freebsd, linux ,win32, cygwin, darwin (macOS)
                if sys.platform.startswith('linux'):
        sys.version
            A string containing the version number of the Python interpreter plus additional information on the build number and compiler used.
    
    os library
    https://docs.python.org/3/library/os.html
        os.chdir(path)
        os.getcwd()
        os.getenv(key, default=None)
            Return the value of the environment variable key if it exists, or default if it doesn’t
        os.getpid()
            Return the current process id.
        os.putenv(key, value)
            Set the environment variable named key to the string value
        os.unsetenv(key)
            Unset (delete) the environment variable named key
        os.linesep
            line seperator
        os.listdir(path='.')
            Return a list containing the names of the entries in the directory given by path.
        os.mkdir(path, mode=511, *, dir_fd=None)
            Create a directory named path with numeric mode mode.
        os.makedirs(name, mode=511, exist_ok=False)¶
            Recursive directory creation
        os.rename(src, dst, *, src_dir_fd=None, dst_dir_fd=None)
            Rename the file or directory src to dst
        os.rmdir(path, *, dir_fd=None)
            Remove (delete) the directory path
        os.unlink(path, *, dir_fd=None)
            Remove (delete) the file path. This function is semantically identical to remove();
        os.abort()
            Generate a SIGABRT signal to the current process. On Unix, the default behavior is to produce a core dump; on Windows, the process immediately returns an exit code of 3.
        os.system(command)
            Execute the command (a string) in a subshell
        os.cpu_count()
            Return the number of CPUs in the system
        os.getloadavg()
            Return the number of processes in the system run queue averaged over the last 1, 5, and 15 minutes or raises OSError if the load average was unobtainable.
    
    json library
    https://docs.python.org/3/library/json.html

    pprint library
    https://docs.python.org/3/library/pprint.html
        pprint.pformat(object, indent=1, width=80, depth=None, *, compact=False, sort_dicts=True, underscore_numbers=False)
            Return the formatted representation of object as a string
    
    re library - regular expressions
    https://docs.python.org/3/library/re.html
        re.sub(pattern, repl, string, count=0, flags=0)  - substring
            Return the string obtained by replacing the leftmost non-overlapping occurrences of pattern in string by the replacement repl.
        re.search(pattern, string, flags=0)
            Scan through string looking for the first location where the regular expression pattern produces a match, and return a corresponding match object.
        re.findall 
            matches all occurrences of a pattern, not just the first one as search() does.
            re.findall(r"\w+ly\b", text)


        
"""
#imports
import os
import sys
try:
    import common
    import config
except Exception as err:
    exc_type, exc_obj, exc_tb = sys.exc_info()
    fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
    print("Import Error: {}. ExeptionType: {}, Filename: {}, Linenumber: {}".format(err,exc_type,fname,exc_tb.tb_lineno))
    sys.exit(3)
#header
if not common.isCLI():
    print("Content-type: text/html; charset=UTF-8;\n\n")

# print(config.value('name'))
# print(config.value())

#show message
common.echo("test successful")
#test listFiles
files=common.listFiles(os.getcwd())
print(files)
files=common.listFilesEx(os.getcwd())
print(files)