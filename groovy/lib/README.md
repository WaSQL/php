# WaSQL Groovy Library Folder

This folder contains JDBC drivers and dependencies required for database connectivity in WaSQL Groovy.

## How It Works

The WaSQL Groovy framework automatically loads all JAR files from this `lib/` directory at runtime. When you use any database driver (MySQL, PostgreSQL, Oracle, etc.), the system automatically includes these JARs in the classpath.

**You simply need to:**
1. Download the required JAR file(s) for your database
2. Place them in this `lib/` folder
3. The system will automatically find and use them

No additional configuration or classpath setup is needed.

## Current JAR Files

| File | Purpose | Used By |
|------|---------|---------|
| `ctreeJDBC.jar` | FairCom c-treeACE JDBC Driver | ctreedb.groovy |
| `ngdbc.jar` | SAP HANA JDBC Driver | hanadb.groovy |
| `mysql-connector-j-*.jar` | MySQL Connector/J | mysqldb.groovy |
| `postgresql-*.jar` | PostgreSQL JDBC Driver | postgresdb.groovy |
| `mssql-jdbc-*.jar` | Microsoft SQL Server JDBC Driver | mssqldb.groovy |
| `ojdbc11-*.jar` | Oracle JDBC Driver (Thin) — only one version needed, see note below | oracledb.groovy |
| `snowflake-jdbc-*.jar` | Snowflake JDBC Driver | snowflakedb.groovy |
| `sqlite-jdbc-*.jar` | SQLite JDBC Driver | sqlitedb.groovy |
| `duckdb_jdbc-*.jar` | DuckDB JDBC Driver | duckdb.groovy |
| `ucanaccess-*.jar` | MS Access / CSV / Excel JDBC Driver | msaccessdb.groovy, mscsvdb.groovy, msexceldb.groovy |
| `jackcess-*.jar` | Dependency for UCanAccess | msaccessdb.groovy, mscsvdb.groovy, msexceldb.groovy |
| `hsqldb-*.jar` | Dependency for UCanAccess | msaccessdb.groovy, mscsvdb.groovy, msexceldb.groovy |
| `commons-lang3-*.jar` | Apache Commons (dependency) | msaccessdb.groovy, mscsvdb.groovy, msexceldb.groovy |
| `commons-logging-*.jar` | Apache Commons Logging (dependency) | msaccessdb.groovy, mscsvdb.groovy, msexceldb.groovy |
| `slf4j-nop-*.jar` | SLF4J No-Op Logger — suppresses noisy logging from drivers | Various |

## Obtaining Missing JAR Files

### c-treeACE (ctreedb.groovy)

**Required:** `ctreeJDBC.jar`

**Download:**
- Official site: https://www.faircom.com/products/faircom-db
- The JDBC driver comes with the c-treeACE installation
- Contact FairCom for driver download or check your installation directory

**Documentation:**
- https://docs.faircom.com/doc/ctreeACE/JDBC_Developer_Guide.pdf

**Additional files needed:**
- `libctsqlshm64.so` (Linux) or equivalent native library for your platform

---

### SAP HANA (hanadb.groovy)

**Required:** `ngdbc.jar`

**Download:**
- Official SAP Tools: https://tools.hana.ondemand.com/#hanatools
- Look for "HANA Database Client" or "JDBC Driver"

**Maven/Gradle:**
```gradle
implementation 'com.sap.cloud.db.jdbc:ngdbc:2.16.14'
```

**Documentation:**
- https://help.sap.com/docs/SAP_HANA_PLATFORM/0eec0d68141541d1b07893a39944924e/ff15928cf5594d78b841fbbe649f04b4.html

---

### MySQL (mysqldb.groovy)

**Required:** `mysql-connector-j-*.jar`

**Download:**
- Official site: https://dev.mysql.com/downloads/connector/j/
- Select "Platform Independent" and download the ZIP file
- Extract and copy the JAR file to this folder

**Maven/Gradle:**
```gradle
implementation 'com.mysql:mysql-connector-j:9.5.0'
```

---

### PostgreSQL (postgresdb.groovy)

**Required:** `postgresql-*.jar`

**Download:**
- Official site: https://jdbc.postgresql.org/download/
- Direct download: https://jdbc.postgresql.org/download/postgresql-42.7.8.jar

**Maven/Gradle:**
```gradle
implementation 'org.postgresql:postgresql:42.7.8'
```

---

### Microsoft SQL Server (mssqldb.groovy)

**Required:** `mssql-jdbc-*.jar`

**Download:**
- Official site: https://learn.microsoft.com/en-us/sql/connect/jdbc/download-microsoft-jdbc-driver-for-sql-server
- Download the `.tar.gz` or `.zip` file
- Extract and copy the appropriate JAR (e.g., `mssql-jdbc-13.3.0.jre11.jar`)

**Maven/Gradle:**
```gradle
implementation 'com.microsoft.sqlserver:mssql-jdbc:13.3.0.jre11'
```

---

### Oracle (oracledb.groovy)

**Required:** one `ojdbc11-*.jar` (pick the version that matches your Oracle database)

> **Note:** Two versions are included in this folder (`ojdbc11-9.5.0.jar` and `ojdbc11-23.26.0.0.0.jar`). You only need one — use the version that matches your Oracle server. If unsure, use the newer `23.x` jar for Oracle 19c+.

**Download:**
- Official site: https://www.oracle.com/database/technologies/appdev/jdbc-downloads.html
- For Java 11+: use `ojdbc11.jar`
- For Java 8: use `ojdbc8.jar`

**Maven/Gradle:**
```gradle
implementation 'com.oracle.database.jdbc:ojdbc11:23.26.0.0.0'
```

**Note:** You may need to accept Oracle's license agreement to download.

---

### Snowflake (snowflakedb.groovy)

**Required:** `snowflake-jdbc-*.jar`

**Download:**
- Official docs: https://docs.snowflake.com/en/developer-guide/jdbc/jdbc-download
- Maven Central: https://repo1.maven.org/maven2/net/snowflake/snowflake-jdbc/

**Maven/Gradle:**
```gradle
implementation 'net.snowflake:snowflake-jdbc:3.27.1'
```

**Note:** The Snowflake JDBC driver is large (~84MB) as it bundles many dependencies.

---

### SQLite (sqlitedb.groovy)

**Required:** `sqlite-jdbc-*.jar`

**Download:**
- GitHub releases: https://github.com/xerial/sqlite-jdbc/releases
- Maven Central: https://repo1.maven.org/maven2/org/xerial/sqlite-jdbc/

**Maven/Gradle:**
```gradle
implementation 'org.xerial:sqlite-jdbc:3.51.1.0'
```

---

### DuckDB (duckdb.groovy)

**Required:** `duckdb_jdbc-*.jar`

DuckDB is an in-process analytical database — no server required. Ideal for fast local queries over CSV, Parquet, and other file formats.

**Download:**
- GitHub releases: https://github.com/duckdb/duckdb-java/releases
- Maven Central: https://repo1.maven.org/maven2/org/duckdb/duckdb_jdbc/

**Maven/Gradle:**
```gradle
implementation 'org.duckdb:duckdb_jdbc:1.5.2.1'
```

**Documentation:**
- https://duckdb.org/docs/api/java

---

### MS Access / CSV / Excel (msaccessdb.groovy, mscsvdb.groovy, msexceldb.groovy)

All three drivers share the same UCanAccess engine. **All five JARs are required.**

| JAR | Purpose |
|-----|---------|
| `ucanaccess-*.jar` | Core UCanAccess driver |
| `jackcess-*.jar` | Reads/writes Access file format |
| `hsqldb-*.jar` | In-memory SQL engine used by UCanAccess |
| `commons-lang3-*.jar` | Apache Commons utility dependency |
| `commons-logging-*.jar` | Apache Commons logging dependency |

**Download:**
- UCanAccess GitHub: https://github.com/spannm/ucanaccess (release ZIP includes all five JARs)
- SourceForge: https://sourceforge.net/projects/ucanaccess/

**Maven/Gradle:**
```gradle
implementation 'io.github.spannm:ucanaccess:5.0.1'
```

---

### Firebird (firebirddb.groovy)

**Required:** `jaybird-full-*.jar`

> **Note:** No Jaybird JAR is currently present in this folder. Add one if you need Firebird connectivity.

**Download:**
- Official site: https://firebirdsql.org/en/jdbc-driver/
- GitHub releases: https://github.com/FirebirdSQL/jaybird/releases

**Maven/Gradle:**
```gradle
implementation 'org.firebirdsql.jdbc:jaybird:5.0.6.java11'
```

---

## Version Compatibility

- The `*` in filenames indicates version numbers may vary
- Newer versions are generally backward compatible
- If you encounter issues, check the database driver documentation for compatibility with your database version

## Troubleshooting

**"ClassNotFoundException" errors:**
- The required JAR file is missing from this folder
- Check the error message for the driver class name
- Download and place the appropriate JAR file here

**"UnsupportedClassVersionError":**
- The JAR file was compiled for a newer Java version
- Download an older version of the driver compatible with your Java version
- Or upgrade your Java installation

**Connection errors:**
- Verify the JAR is present and not corrupted
- Check that you're using the correct driver class and JDBC URL format
- Refer to the database-specific groovy file (e.g., `mysqldb.groovy`) for configuration details

## Adding New Database Drivers

To add support for a new database type:

1. Download the JDBC driver JAR file
2. Place it in this `lib/` folder
3. Create a new database driver file (e.g., `mydb.groovy`) following the pattern of existing drivers
4. The JAR will be automatically loaded at runtime

## License Notes

| Driver | License |
|--------|---------|
| MySQL Connector/J | GPL v2 with FOSS exception |
| PostgreSQL JDBC | BSD 2-Clause |
| Microsoft SQL Server JDBC | MIT |
| Oracle JDBC (ojdbc) | Oracle Technology Network License |
| Snowflake JDBC | Apache 2.0 |
| SQLite JDBC | Apache 2.0 |
| DuckDB JDBC | MIT |
| UCanAccess | Apache 2.0 |
| SAP HANA NGDBC | SAP proprietary |
| c-treeACE JDBC | FairCom proprietary |
| Jaybird (Firebird) | LGPL 2.1 |
| SLF4J | MIT |

Ensure you comply with the license terms for any drivers you use.
