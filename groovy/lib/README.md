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
| `ojdbc11-*.jar` | Oracle JDBC Driver (Thin) | oracledb.groovy |
| `snowflake-jdbc-*.jar` | Snowflake JDBC Driver | snowflakedb.groovy |
| `sqlite-jdbc-*.jar` | SQLite JDBC Driver | sqlitedb.groovy |
| `ucanaccess-*.jar` | MS Access JDBC Driver | msaccessdb.groovy |
| `jackcess-*.jar` | Dependency for UCanAccess | msaccessdb.groovy |
| `hsqldb-*.jar` | Dependency for UCanAccess | msaccessdb.groovy |
| `commons-lang3-*.jar` | Apache Commons (dependency) | msaccessdb.groovy |
| `commons-logging-*.jar` | Apache Commons Logging (dependency) | msaccessdb.groovy |
| `slf4j-nop-*.jar` | SLF4J No-Op Logger | Various |

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

### SAP HANA (hanadb.groovy)

**Required:** `ngdbc.jar` (or `hanaJDBC.jar`)

**Download:**
- Official SAP Tools: https://tools.hana.ondemand.com/#hanatools
- Look for "HANA Database Client" or "JDBC Driver"

**Maven/Gradle:**
```gradle
implementation 'com.sap.cloud.db.jdbc:ngdbc:2.16.14'
```

**Documentation:**
- https://help.sap.com/docs/SAP_HANA_PLATFORM/0eec0d68141541d1b07893a39944924e/ff15928cf5594d78b841fbbe649f04b4.html

### MySQL (mysqldb.groovy)

**Required:** `mysql-connector-j-*.jar` (formerly mysql-connector-java)

**Download:**
- Official site: https://dev.mysql.com/downloads/connector/j/
- Select "Platform Independent" and download the ZIP file
- Extract and copy the JAR file to this folder

**Maven/Gradle:**
```gradle
implementation 'mysql:mysql-connector-java:8.0.33'
// or newer:
implementation 'com.mysql:mysql-connector-j:9.5.0'
```

### PostgreSQL (postgresdb.groovy)

**Required:** `postgresql-*.jar`

**Download:**
- Official site: https://jdbc.postgresql.org/download/
- Direct download: https://jdbc.postgresql.org/download/postgresql-42.7.8.jar

**Maven/Gradle:**
```gradle
implementation 'org.postgresql:postgresql:42.7.8'
```

### Microsoft SQL Server (mssqldb.groovy)

**Required:** `mssql-jdbc-*.jar`

**Download:**
- Official site: https://docs.microsoft.com/en-us/sql/connect/jdbc/download-microsoft-jdbc-driver-for-sql-server
- Download the `.tar.gz` or `.zip` file
- Extract and copy the appropriate JAR (e.g., `mssql-jdbc-13.3.0.jre11-preview.jar`)

**Maven/Gradle:**
```gradle
implementation 'com.microsoft.sqlserver:mssql-jdbc:13.3.0.jre11-preview'
```

### Oracle (oracledb.groovy)

**Required:** `ojdbc11-*.jar` (or ojdbc8/ojdbc10 depending on Java version)

**Download:**
- Official site: https://www.oracle.com/database/technologies/appdev/jdbc-downloads.html
- Download the appropriate JDBC driver for your Oracle version
- For Java 11+: use `ojdbc11.jar`
- For Java 8: use `ojdbc8.jar`

**Maven/Gradle:**
```gradle
implementation 'com.oracle.database.jdbc:ojdbc11:23.26.0.0.0'
```

**Note:** You may need to accept Oracle's license agreement to download.

### Snowflake (snowflakedb.groovy)

**Required:** `snowflake-jdbc-*.jar`

**Download:**
- Official docs: https://docs.snowflake.com/en/user-guide/jdbc-download.html
- Maven Central: https://repo1.maven.org/maven2/net/snowflake/snowflake-jdbc/

**Maven/Gradle:**
```gradle
implementation 'net.snowflake:snowflake-jdbc:3.27.1'
```

**Note:** The Snowflake JDBC driver is quite large (~84MB) as it includes many dependencies.

### SQLite (sqlitedb.groovy)

**Required:** `sqlite-jdbc-*.jar`

**Download:**
- GitHub: https://github.com/xerial/sqlite-jdbc
- Maven Central: https://repo1.maven.org/maven2/org/xerial/sqlite-jdbc/

**Maven/Gradle:**
```gradle
implementation 'org.xerial:sqlite-jdbc:3.51.0.0'
```

### MS Access (msaccessdb.groovy)

**Required (multiple files):**
- `ucanaccess-*.jar`
- `jackcess-*.jar`
- `hsqldb-*.jar`
- `commons-lang3-*.jar`
- `commons-logging-*.jar`

**Download:**
- UCanAccess GitHub: https://github.com/spannm/ucanaccess
- Download the release ZIP which includes all dependencies
- Extract all JAR files to this folder

**Maven/Gradle:**
```gradle
implementation 'io.github.spannm:ucanaccess:5.0.1'
```

**Note:** UCanAccess requires multiple dependencies. Make sure to include all of them.

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

- Each JDBC driver has its own license
- SAP HANA NGDBC: SAP proprietary license
- Oracle JDBC: Oracle Technology Network License Agreement
- MySQL Connector: GPL v2 with FOSS exception
- PostgreSQL JDBC: BSD License
- MS SQL Server JDBC: Microsoft Software License
- Snowflake JDBC: Apache License 2.0
- SQLite JDBC: Apache License 2.0
- UCanAccess: Apache License 2.0
- c-treeACE: FairCom proprietary license

Ensure you comply with the license terms for any drivers you use.
