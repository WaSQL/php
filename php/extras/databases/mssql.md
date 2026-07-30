# mssql.php — Microsoft SQL Server driver

Queries SQL Server through the Microsoft **`sqlsrv`** extension. The connection is described by an *array* of connection-info keys rather than a string, which makes this driver's `connect` attribute behave differently from every other driver here.

## At a glance
| | |
|---|---|
| `dbtype` | `mssql` |
| Connects via | `sqlsrv_connect($dbhost, $connect_array)` |
| Requires | PHP 7.2+ with the `sqlsrv` PECL extension and the Microsoft ODBC Driver for SQL Server |
| Handle global | `$dbh_mssql` (plus a `$mssql` array of state) |
| File | `php/extras/databases/mssql.php` (~2192 lines, 37 functions) |

Install notes from the top of the driver:
```
apt install php-pear unixodbc unixodbc-dev
pecl install sqlsrv
pecl install pdo_sqlsrv
# plus the MS ODBC driver for SQL Server
```

## config.xml
```xml
<database name="erp_sql" dbtype="mssql"
    dbhost="sqlsrv01\\instance" dbname="ERP"
    dbuser="svc" dbpass="…" />
```

| attribute | used for |
|---|---|
| `dbhost` | server, `server\instance` form supported. Passed as `sqlsrv_connect`'s first argument |
| `dbname` | becomes the `Database` connect key |
| `dbuser` / `dbpass` | become `UID` / `PWD`. **Omit both to use Windows Authentication** |
| `encrypt` | `Encrypt` — encrypt the connection (default `0`) |
| `trust_server_certificate` | `TrustServerCertificate` — accept a self-signed server cert (default false) |
| `login_timeout` | `LoginTimeout`. Forced to `5` if not set |
| `charset` | `CharacterSet` |

Each of these also has `CONFIG` fallbacks in both spellings (`mssql_encrypt` / `encrypt_mssql`, etc.).

## Calling it
```php
$recs = dbQueryResults('erp_sql', "SELECT TOP 50 * FROM dbo.orders");
$rec  = dbGetRecord('erp_sql', "SELECT @@VERSION AS v");
$n    = dbGetCount('erp_sql', ['-table'=>'dbo.orders']);
```

## Function reference

**Query / execute**
| function |
|---|
| `mssqlQueryResults($query,$params)`, `mssqlEnumQueryResults()`, `mssqlExecuteSQL($query,$params)` |
| `mssqlNamedQueryList()` / `mssqlNamedQuery($name)` |

**Records**
| function |
|---|
| `mssqlGetDBRecord()`, `mssqlGetDBRecords()`, `mssqlGetDBRecordById()`, `mssqlGetDBCount()` |
| `mssqlAddDBRecord()`, `mssqlAddDBRecords()` (bulk), `mssqlAddDBRecordsProcess()` |
| `mssqlEditDBRecord()`, `mssqlEditDBRecordById()` |
| `mssqlDelDBRecord()`, `mssqlDelDBRecordById()` |
| `mssqlListRecords($params)` → `databaseListRecords` HTML grid |

**Schema / metadata / DDL**
| function |
|---|
| `mssqlGetDBTables()`, `mssqlGetDBDatabases()`, `mssqlIsDBTable()`, `mssqlGetDBTablePrimaryKeys()` |
| `mssqlGetDBSchema()`, `mssqlGetDBFieldInfo()`, `mssqlGetAllTableFields()`, `mssqlGetAllTableIndexes()`, `mssqlGetDBTableIndexes()` |
| `mssqlGetTableDDL()`, `mssqlCreateDBTable()`, `mssqlAlterDBTable()`, `mssqlAddDBFields()`, `mssqlDropDBFields()`, `mssqlListDBDatatypes()` |

**Server / health**
| function | notes |
|---|---|
| `mssqlGetServerInfo()` | version / edition / configuration |
| `mssqlGetSpaceUsed()` | database and per-table space usage |

**Connection / internal**
| function |
|---|
| `mssqlParseConnectParams($params)` — builds the connect **array**; `mssqlDBConnect($params)`; `mssqlEscapeString()` |

## Notes & gotchas
- **`connect` is an array here, not a string.** `mssqlParseConnectParams` assembles `['Database'=>…, 'UID'=>…, 'PWD'=>…, 'CharacterSet'=>…, …]`, and any extra `<database>` attributes are copied into it as-is. Anything you'd write in an ODBC connect string must be expressed as a connect-array key instead.
- **Windows Authentication is the fallback**: if `UID`/`PWD` are absent, `sqlsrv_connect` authenticates as the process account (the apache user).
- **`LoginTimeout` is forced to 5 seconds** if the connect array doesn't already set it, so a dead server fails fast rather than hanging a page render.
- TLS here is about *transport and trust* (`Encrypt`, `TrustServerCertificate`), not client-certificate authentication — SQL Server uses AAD/Kerberos for that, and `sqlsrv` has no client-cert option. This is why the `dbcert`/`dbkey`/`dbca` vocabulary is **not** implemented in this driver.
- The PHP < 7 `mssql_*` code path (with the `mssql.charset` / `mssql.textlimit` ini tweaks at the top of the file and `$CONFIG['mssql_old']`) is legacy; anything current takes the `sqlsrv` branch.

## See also
`wasql_reference.md` → *Named / secondary DB connections*. `mssql-tuner.md` / `mssql-tuner.py` in this directory are a separate server-tuning tool.
