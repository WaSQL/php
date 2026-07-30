# mscsv.php — Microsoft Text (CSV) driver

Treats a **folder of `.csv` / `.txt` files as a database** — each file is a table — using the Microsoft Text ODBC driver.

## At a glance
| | |
|---|---|
| `dbtype` | `mscsv` |
| Connects via | `odbc_connect()` with a DSN-less connect string built by the driver |
| Requires | Windows + the *Microsoft Access Database Engine* (ACE) text driver, matching PHP's bitness |
| File | `php/extras/databases/mscsv.php` (~968 lines, 23 functions) |

## config.xml
```xml
<database group="MS CSV" dbicon="icon-file-txt"
    name="mscsv_temp" displayname="MS CSV Temp"
    dbname="d:/temp" dbtype="mscsv" />
```

| attribute | used for |
|---|---|
| `dbname` | **the directory** containing the CSV files — becomes `Dbq=` |
| `connect` | a full ODBC connect string, used instead of the built one |

The generated connect string is roughly:
```
Driver={Microsoft Access Text Driver (*.txt, *.csv)};Dbq=<dir>;
Extended Properties="Mode=ReadWrite;ReadOnly=false;MaxScanRows=2;HDR=YES"
```
It falls back to the older `Driver={Microsoft Text Driver (*.txt; *.csv)}` when the ACE text driver isn't present.

## Calling it
The **filename is the table name**:
```php
$recs = dbQueryResults('mscsv_temp', "select top 5 * from all_colors.csv where code like '%alice%'");
$ok   = dbExecuteSQL('mscsv_temp', "insert into all_colors.csv (code,name,hex,red,green,blue) values('alice_test','test','#111222',1,2,3)");
```

## Function reference

**Query / execute**
| function |
|---|
| `mscsvQueryResults($query,$params)`, `mscsvEnumQueryResults()`, `mscsvExecuteSQL($query,$params)` |
| `mscsvNamedQueryList()` / `mscsvNamedQuery($name)` |

**Records**
| function |
|---|
| `mscsvGetDBRecord()`, `mscsvGetDBRecords()`, `mscsvGetDBCount()`, `mscsvGetDBFields()` |
| `mscsvListRecords($params)` → `databaseListRecords` HTML grid |

**Schema / metadata**
| function |
|---|
| `mscsvGetDBTables()` (lists the files), `mscsvIsDBTable()`, `mscsvGetDBSchema()`, `mscsvGetDBFieldInfo()`, `mscsvGetAllTableFields()` |
| `mscsvGetDBIndexes()`, `mscsvGetDBTableIndexes()`, `mscsvGetAllTableIndexes()`, `mscsvGetDBTablePrimaryKeys()`, `mscsvGetConfigValue()` |

**Connection / internal**
| function |
|---|
| `mscsvParseConnectParams($params)`, `mscsvDBConnect($params)`, `mscsvQuoteIdentifier()` |

## Notes & gotchas
- **⚠️ `SELECT` and `INSERT` only.** `UPDATE` and `DELETE` are **not supported** by the text driver — stated explicitly at the top of the driver file. Plan around it (rewrite the file, or use `duckdb`).
- **`MaxScanRows=2` means types are guessed from the first two rows.** A column that looks numeric in rows 1–2 and holds text later returns `NULL` for those rows rather than erroring. Add a `schema.ini` in the folder to pin column types when this bites.
- **`HDR=YES` assumes a header row.** A headerless file gets its first data row eaten as column names.
- **Bitness must match** between PHP and the ACE driver, or you get a misleading "Data source name not found".
- The apache user needs write access to the directory for inserts.
- **No cert/TLS attributes** — meaningless for local files.

## See also
`msexcel.md` (same pattern for spreadsheets), `msaccess.md`, `odbc.md`. `duckdb.md` is usually the better choice for read-heavy CSV work — it handles typing properly and runs on Linux.
