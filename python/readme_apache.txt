##### changes to httpd.conf to support python
    Alias /python/ "d:/wasql/python/"
</IfModule>

<Directory "d:/wasql/python/">
    Options Indexes FollowSymLinks ExecCGI
    AllowOverride ALL
    Require all granted
</Directory>

<IfModule dir_module>
    DirectoryIndex index.html index.htm index.php index.py
</IfModule>

##### restart apache
##### add a page called index.py to the _pages table as the index
##### browse to /python/?_view=index.py


