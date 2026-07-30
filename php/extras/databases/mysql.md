# mysql.php — MySQL / MariaDB driver

Queries MySQL and MariaDB through **mysqli**. This is the engine behind `dbtype="mysql"` *secondary* connections. Note that the **main site database** does not go through this file — that connection is made by `databaseConnect()` in `php/database.php`.

## At a glance
| | |
|---|---|
| `dbtype` | `mysql` |
| Connects via | `mysqli_init()` + `mysqli_options()` + `mysqli_real_connect()` |
| Requires | PHP `mysqli` extension |
| Handle globals | `$dbh_mysql`, `$dbh` |
| Auth | user/password, optionally over TLS with a client cert |
| File | `php/extras/databases/mysql.php` (~2380 lines, 41 functions) |

## config.xml
```xml
<database name="reporting" dbtype="mysql"
    dbhost="db.x.com" dbport="3306" dbname="reporting"
    dbuser="svc" dbpass="…" />
```

With TLS / a client certificate:
```xml
<database name="reporting" dbtype="mysql"
    dbhost="db.x.com" dbname="reporting" dbuser="svc" dbpass="…"
    dbcert="/etc/wasql/certs/client.crt"
    dbkey="/etc/wasql/certs/client.key"
    dbca="/etc/wasql/certs/ca.crt" />
```

| attribute | used for |
|---|---|
| `dbhost` | server. `localhost` is rewritten to `127.0.0.1`, then resolved with `gethostbyname()` |
| `dbport` | defaults to `3306` |
| `dbname`, `dbuser`, `dbpass` | required (`dbname` missing ⇒ connect aborts) |
| `dbcert` | client certificate (X.509) |
| `dbkey` | client private key |
| `dbca` | CA bundle used to verify the server |
| `dbcapath` | directory of CA certs |
| `dbcipher` | cipher list |
| `dbssl` | `1` forces TLS with no client cert |
| `dbsslverify` | `0` accepts a self-signed server cert |

Per-user overrides (`dbuser_slloyd="…"`) and `CONFIG` fallbacks (`dbuser_mysql` / `mysql_dbuser`) both work, same as the other drivers.

## Calling it
```php
$recs = dbQueryResults('reporting', "SELECT * FROM orders LIMIT 50");
$rec  = dbGetRecord('reporting', "SELECT VERSION() AS v");
$n    = dbGetCount('reporting', ['-table'=>'orders','status'=>1]);
$grid = dbListRecords('reporting', ['-query'=>"SELECT …"]);
```

## Function reference

**Query / execute**
| function | notes |
|---|---|
| `mysqlQueryResults($query,$params)` | main entry. Sets `mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT)` so mysqli throws, and records timing/errors in `$DATABASE['_lastquery']` |
| `mysqlEnumQueryResults()` | row-at-a-time enumeration |
| `mysqlExecuteSQL($query,$params)` | non-SELECT |
| `mysqlNamedQueryList()` / `mysqlNamedQuery($name)` | canned queries for the admin DB console |
| `mysqlOptimizations()` | server tuning observations |

**Records**
| function |
|---|
| `mysqlGetDBRecords($params)`, `mysqlGetDBRecordById($table,$id)` |
| `mysqlGetDBCount($params)` |
| `mysqlAddDBRecords($params)` (bulk), `mysqlAddDBRecordsProcess()` |
| `mysqlEditDBRecordById()`, `mysqlDelDBRecordById()` |
| `mysqlListRecords($params)` → `databaseListRecords` HTML grid |
| `mysqlGetDBQuery()`, `mysqlGetDBWhere()`, `mysqlGetDBExpression()` | query-building internals |

**Schema / metadata / DDL**
| function |
|---|
| `mysqlGetDBTables()`, `mysqlGetDBViews()`, `mysqlIsDBTable($table)`, `mysqlGetDBName()` |
| `mysqlGetDBSchema()`, `mysqlGetDBFieldInfo($table)`, `mysqlGetAllTableFields()`, `mysqlGetAllTableIndexes()` |
| `mysqlGetDDL()`, `mysqlGetTableDDL()`, `mysqlGetFunctionDDL()`, `mysqlGetProcedureDDL()`, `mysqlGetPackageDDL()`, `mysqlGetTriggerDDL()`, `mysqlGetAllProcedures()` |
| `mysqlAddDBFields()`, `mysqlDropDBFields()` |

**Connection / internal**
| function | notes |
|---|---|
| `mysqlParseConnectParams($params)` | `<database>` attributes → `-key` params, per-user overrides, `CONFIG` fallbacks |
| `mysqlDBConnect($params)` | `-single` supported. 5s connect timeout via `MYSQLI_OPT_CONNECT_TIMEOUT`. Returns `null` on failure with the reason in `$CONFIG['mysql_error']` |
| `mysqlSetConnectSSL($dbh,$params)` | sets TLS options, returns the flags for `mysqli_real_connect`. Returns `0` when no TLS attribute is set |
| `mysqlCertFileCheck($path,$label)` | returns why a cert/key/CA path is unusable, or `''` |
| `mysqlEscapeString()`, `mysqlEscapeIdentifier()` | — |
| `mysqlParseConnectParamsOLD()` | dead legacy copy, not called |

## TLS — why it needs code and not just a connect string
mysqli has **no connection-string form**: `mysqli_real_connect` takes discrete host/user/pass/db/port arguments. TLS therefore has to be configured on the handle *before* connecting, which is what `mysqlSetConnectSSL()` does between `mysqli_init()` and `mysqli_real_connect()`. When no TLS attribute is set it returns `0` and `mysqli_ssl_set()` is never called, so the connect is byte-identical to the pre-TLS behaviour.

Client-certificate *authentication* (as opposed to just an encrypted transport) also needs the grant side:
```sql
ALTER USER 'svc'@'%' REQUIRE X509;
```

## Notes & gotchas
- **`connect` is dead for this driver.** `mysqlParseConnectParams` builds a `-connect` string (and even appends `application_name`, a PostgreSQL-only option — it is copy-pasted from `postgresql.php`), but `mysqlDBConnect` never reads it. Use the discrete attributes.
- **mysqlnd does not read `[client]` from `my.cnf`**, so the `db*` TLS attributes are the only way to configure certs here.
- `dbhost` goes through `gethostbyname()`, so a hostname that doesn't resolve fails before mysqli is involved.
- The `set_charset("utf8mb4")` call is deliberately commented out in `mysqlDBConnect` — it broke Vietnamese text on at least one site. Don't re-enable it globally.
- **`dbkey` is overloaded across drivers**: here it's a TLS private key; in `gigya.php` the same attribute name is the API key. Dispatch is by `dbtype`, so they never collide in practice.
- The main site DB (`databaseConnect()` in `php/database.php`) takes only host/user/pass/dbname and has **no TLS support**.

## See also
`wasql_reference.md` → *TLS / certificate authentication on a connection*. `mysql-tuner.md` / `mysql-tuner.py` and `mysqltuner.pl` in this directory are separate server-tuning tools, not part of the driver.
