select tablename, fieldname, count(*) entries from _fielddata
group by tablename, fieldname
having count(*) > 1
order by tablename,fieldname
