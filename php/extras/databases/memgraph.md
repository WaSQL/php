# memgraph.php — Memgraph (graph) driver

Queries **Memgraph** over the Bolt protocol using **Cypher**, via the `laudis/neo4j-php-client` Composer package. The only graph database in this directory, so the usual table/record vocabulary is mapped onto nodes, labels and relationships.

## At a glance
| | |
|---|---|
| `dbtype` | `memgraph` |
| Connects via | `ClientBuilder::create()->withDriver('memgraph', 'bolt://host:port', …)` |
| Requires | `composer require laudis/neo4j-php-client` — the driver autoloads `vendor/autoload.php` from the repo root if present |
| Handle global | `$dbh_memgraph` |
| Query language | **Cypher**, not SQL |
| File | `php/extras/databases/memgraph.php` (~1160 lines, 23 functions) |

## Configuration
The header documents `CONFIG`-style entries:
```xml
<memgraph_dbhost>localhost</memgraph_dbhost>
<memgraph_dbport>7687</memgraph_dbport>
<memgraph_dbname>memgraph</memgraph_dbname>
<memgraph_dbuser>memgraph</memgraph_dbuser>
<memgraph_dbpass>memgraph</memgraph_dbpass>
```
`memgraphParseConnectParams` also reads a normal `<database>` tag, so a named connection works too:
```xml
<database name="graph" dbtype="memgraph"
    dbhost="localhost" dbport="7687" dbname="memgraph"
    dbuser="memgraph" dbpass="…" />
```

| attribute | used for |
|---|---|
| `dbhost` / `dbport` | build the `bolt://host:port` URL. Bolt's default port is **7687** |
| `dbuser` / `dbpass` | basic auth. **If either is empty the client connects with no authentication at all** |
| `dbname` | database/alias name |
| `dbmode` | optional mode override (`CONFIG` fallbacks `dbmode_memgraph` / `memgraph_dbmode`) |

## Calling it
```php
$recs = dbQueryResults('graph', "MATCH (n:Person) RETURN n LIMIT 50");
$n    = dbGetCount('graph', ['-table'=>'Person']);
```

## Function reference

**Query / execute**
| function |
|---|
| `memgraphQueryResults($cypher,$params)`, `memgraphExecuteSQL($cypher,$params)` |
| `memgraphNamedQueryList()` / `memgraphNamedQuery($name)` |

**Records / nodes**
| function | notes |
|---|---|
| `memgraphGetDBRecord()`, `memgraphGetDBRecords()`, `memgraphGetDBCount()` | node queries behind the usual record API |
| `memgraphCreateNode()`, `memgraphUpdateNode()`, `memgraphDeleteNode()` | node CRUD |
| `memgraphCreateRelationship()` | creates an edge between two nodes |
| `memgraphListRecords($params)` | → `databaseListRecords` HTML grid |

**Graph metadata** (the table/field API mapped onto graph concepts)
| function | returns |
|---|---|
| `memgraphGetDBLabels()` | node labels |
| `memgraphGetDBTables()` | labels presented as "tables" |
| `memgraphGetDBNodeProperties()`, `memgraphGetDBFields()`, `memgraphGetDBFieldInfo()`, `memgraphGetDBTableFields()` | properties presented as "fields" |
| `memgraphGetRelationshipTypes()` | edge types |
| `memgraphGetDBTableIndexes()` | indexes |
| `memgraphGetStats()` | server statistics |

**Connection / internal**
| function |
|---|
| `memgraphParseConnectParams($params)`, `memgraphDBConnect($params)` |

## Notes & gotchas
- **A missing Composer package fails at *use* time, not load time.** The `require_once` is guarded by `file_exists`, but the `use Laudis\Neo4j\…` statements are not — if `vendor/autoload.php` is absent you get a class-not-found error on the first connect rather than a clear "driver unavailable" message.
- **Empty credentials silently mean unauthenticated.** The builder only attaches `Authenticate::basic()` when *both* `dbuser` and `dbpass` are non-empty, so a typo'd password key downgrades to an anonymous connection instead of failing loudly.
- **Labels are not tables.** `memgraphGetDBTables()` returns labels so the admin console has something to show, but there is no schema — two nodes with the same label can have completely different properties, so `GetDBFieldInfo` reports what it *sampled*, not a contract.
- **Cypher, not SQL** — none of the SQL-oriented helpers elsewhere in WaSQL (`-where` string building, DDL functions) apply. Write Cypher explicitly.
- **No cert/TLS attributes.** Bolt supports TLS (`bolt+s://` / `neo4j+s://`), but this driver hardcodes the `bolt://` scheme.
- There is **no DDL / bulk-insert support** here, unlike the relational drivers.

## See also
[Memgraph docs](https://memgraph.com/docs) and [laudis/neo4j-php-client](https://github.com/laudis-technologies/neo4j-php-client), both linked at the top of the driver.
