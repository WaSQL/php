# msaccess.php — Microsoft Access driver

Queries an Access `.mdb` / `.accdb` file through the **Microsoft Access ODBC driver**. Windows-only in practice.

## At a glance
| | |
|---|---|
| `dbtype` | `msaccess` |
| Connects via | `odbc_connect()` with a DSN-less connect string built by the driver |
| Requires | Windows + the *Microsoft Access Database Engine* (ACE) ODBC driver, matching PHP's bitness (32- vs 64-bit is the usual failure) |
| File | `php/extras/databases/msaccess.php` (~1040 lines, 24 functions) |

## config.xml
```xml
<database group="MS Access" dbicon="icon-database"
    name="msaccess_legacy" displayname="Legacy Access DB"
    dbname="d:/data/legacy.accdb" dbtype="msaccess" />
```

| attribute | used for |
|---|---|
| `dbname` | **path to the .mdb/.accdb file** — becomes `Dbq=` in the connect string |
| `dbuser` / `dbpass` | optional `Uid=` / `Pwd=` for a password-protected database |
| `connect` | a full ODBC connect string, used instead of the built one |

The generated connect string is:
```
Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=<dbname>;ExtendedAnsiSQL=1
```
`ExtendedAnsiSQL=1` is what makes the newer SQL syntax and larger text types work — don't drop it if you hand-write `connect`.

## Calling it
```php
$recs = dbQueryResults('msaccess_legacy', "SELECT TOP 50 * FROM Customers");
$n    = dbGetCount('msaccess_legacy', ['-table'=>'Customers']);
```

## Function reference

**Query / execute**
| function |
|---|
| `msaccessQueryResults($query,$params)`, `msaccessEnumQueryResults()`, `msaccessExecuteSQL($query,$params)` |
| `msaccessNamedQueryList()` / `msaccessNamedQuery($name)` |

**Records**
| function |
|---|
| `msaccessGetDBRecord()`, `msaccessGetDBRecords()`, `msaccessGetDBCount()`, `msaccessGetDBFields()` |
| `msaccessListRecords($params)` → `databaseListRecords` HTML grid |

**Schema / metadata**
| function |
|---|
| `msaccessGetDBTables()`, `msaccessIsDBTable()`, `msaccessGetDBSchema()`, `msaccessGetDBFieldInfo()`, `msaccessGetAllTableFields()` |
| `msaccessGetDBIndexes()`, `msaccessGetDBTableIndexes()`, `msaccessGetAllTableIndexes()`, `msaccessGetDBTablePrimaryKeys()` |
| `msaccessAddDBFields()`, `msaccessDropDBFields()`, `msaccessGetConfigValue()` |

**Connection / internal**
| function |
|---|
| `msaccessParseConnectParams($params)`, `msaccessDBConnect($params)` |

## Notes & gotchas
- **Bitness must match.** A 64-bit PHP needs the 64-bit ACE driver. Mismatch gives "Data source name not found and no default driver specified", which reads like a missing DSN but isn't.
- **`odbc_connect`, not `odbc_pconnect`** — connections are not persistent here, which is deliberate: Access holds a lock file (`.laccdb`) and pooled connections keep it alive.
- **The apache user needs write access to the file's directory**, not just the file, so Access can create its lock file. Read-only queries fail without it.
- **Paging is `SELECT TOP n`**, and Access SQL is its own dialect (`IIF`, `Nz`, `*` wildcards in `LIKE` under some settings, `#date#` literals).
- **Primary keys need a special lookup** — Access does not expose them through the usual ODBC catalog calls; that's what `msaccessGetDBTablePrimaryKeys()` is for (see the reference links at the top of the driver).
- **No cert/TLS attributes** — meaningless for a local file.

## See also
`mscsv.md` and `msexcel.md` — the same ODBC-file pattern for text and spreadsheet sources. `odbc.md` for generic ODBC behaviour.
