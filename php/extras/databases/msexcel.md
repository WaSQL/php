# msexcel.php — Microsoft Excel driver

Treats an Excel workbook as a database — **each worksheet is a table** — through the Microsoft Excel ODBC driver. Also reads sheet names directly out of `.xlsx` files with `ZipArchive`, because the ODBC driver's sheet listing is unreliable.

## At a glance
| | |
|---|---|
| `dbtype` | `msexcel` |
| Connects via | `odbc_connect()` with a DSN-less connect string built by the driver |
| Requires | Windows + the ACE Excel ODBC driver (matching PHP's bitness), PHP `odbc` **and** `zip` extensions |
| File | `php/extras/databases/msexcel.php` (~1293 lines, 25 functions) |

The driver checks its own dependencies on load and emits warnings if `odbc` or `zip` is missing, or a notice if `ZipArchive` is unavailable (it then falls back to the deprecated `zip_*` functions).

## config.xml
```xml
<database group="MS Excel" dbicon="icon-application-excel"
    name="msexcel_delegate" displayname="MS Excel Delegate"
    dbname="d:/temp/delegate.xlsx" dbtype="msexcel" />
```

| attribute | used for |
|---|---|
| `dbname` | **path to the workbook** — becomes `Dbq=`, with its directory as `DefaultDir=` |
| `connect` | a full ODBC connect string, used instead of the built one |

The generated connect string is roughly:
```
Driver={Microsoft Excel Driver (*.xls, *.xlsx, *.xlsm, *.xlsb)};Dbq=<file>;DefaultDir=<dir>;
Extended Properties="Mode=ReadWrite;ReadOnly=false;MaxScanRows=16;HDR=YES"
```

## Calling it
A worksheet is referenced with a trailing `$` (ODBC convention), usually bracketed:
```php
$recs = dbQueryResults('msexcel_delegate', "SELECT TOP 50 * FROM [Sheet1$]");
$n    = dbGetCount('msexcel_delegate', ['-table'=>'[Sheet1$]']);
```

## Function reference

**Query / execute**
| function |
|---|
| `msexcelQueryResults($query,$params)`, `msexcelEnumQueryResults()`, `msexcelExecuteSQL($query,$params)` |
| `msexcelNamedQueryList()` / `msexcelNamedQuery($name)` |

**Records**
| function |
|---|
| `msexcelGetDBRecord()`, `msexcelGetDBRecords()`, `msexcelGetDBCount()`, `msexcelGetDBFields()` |
| `msexcelListRecords($params)` → `databaseListRecords` HTML grid |

**Schema / metadata**
| function | notes |
|---|---|
| `msexcelGetDBTables()` | the worksheets |
| `msexcelGetSheetNamesFromXlsx($file)` | reads sheet names straight from the xlsx zip — more reliable than the ODBC catalog |
| `msexcelIsDBTable()`, `msexcelGetDBSchema()`, `msexcelGetDBFieldInfo()`, `msexcelGetAllTableFields()` | — |
| `msexcelGetDBIndexes()`, `msexcelGetDBTableIndexes()`, `msexcelGetAllTableIndexes()`, `msexcelGetDBTablePrimaryKeys()`, `msexcelGetConfigValue()` | — |

**Connection / internal**
| function |
|---|
| `msexcelParseConnectParams($params)`, `msexcelDBConnect($params)`, `msexcelCloseDBConnection()`, `msexcelValidateIdentifier()` |

## Notes & gotchas
- **Sheet names need the `$` suffix and usually brackets**: `[Sheet1$]`. A named range works too; a sheet name with spaces *must* be bracketed.
- **`MaxScanRows=16` means column types are guessed from the first 16 rows.** A mixed column silently returns `NULL` for the values that don't match the guessed type — the classic "my numbers are blank" symptom. There is no in-band fix; restructure the sheet or import it first.
- **`HDR=YES` assumes row 1 is headers.**
- **`msexcelCloseDBConnection()` exists for a reason** — Excel/ODBC holds the file open, and an unclosed handle blocks anything else (including the user) from writing the workbook. Call it when you're done with a write.
- **Bitness must match** between PHP and the ACE driver.
- Deleting rows is not meaningfully supported by the Excel driver; treat workbooks as append/read.
- **No cert/TLS attributes** — meaningless for a local file.

## See also
`mscsv.md`, `msaccess.md`, `odbc.md`. For read-only spreadsheet ingestion on Linux, `duckdb.md` handles `xlsx` without any ODBC layer.
