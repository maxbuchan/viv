/*                                                                -*- C -*-
   +----------------------------------------------------------------------+
   | PHP Version 7                                                        |
   +----------------------------------------------------------------------+
   | Copyright (c) 1997-2017 The PHP Group                                |
   +----------------------------------------------------------------------+
   | This source file is subject to version 3.01 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available through the world-wide-web at the following url:           |
   | http://www.php.net/license/3_01.txt                                  |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Author: Stig Sæther Bakken <ssb@php.net>                             |
   +----------------------------------------------------------------------+
*/

/* $Id$ */

#define CONFIGURE_COMMAND " './configure'  '--prefix=/opt/bitnami/php' '--enable-fpm' '--with-fpm-user=daemon' '--with-fpm-group=daemon' '--with-apxs2=/opt/bitnami/apache2/bin/apxs' '--with-expat-dir=/opt/bitnami/common' '--with-zlib-dir=/opt/bitnami/common' '--enable-mbstring=all' '--enable-soap' '--enable-bcmath' '--enable-ftp' '--with-xmlrpc' '--enable-fastcgi' '--enable-force-cgi-redirect' '--enable-cgi' '--with-imap=/bitnami/lampstack-linux-x64/src/imap-2007f' '--with-imap-ssl=/opt/bitnami/common' '--with-png-dir=/opt/bitnami/common' '--with-gd' '--with-zlib' '--with-curl=/opt/bitnami/common' '--with-jpeg-dir=/opt/bitnami/common' '--enable-exif' '--with-openssl=/opt/bitnami/common' '--with-ldap=/opt/bitnami/common' '--enable-calendar' '--enable-ctype' '--enable-pcntl' '--enable-session' '--with-regex=php' '--enable-spl' '--enable-zip' '--with-bz2=/opt/bitnami/common' '--enable-sockets' '--with-mcrypt=/opt/bitnami/common' '--with-icu-dir=/opt/bitnami/common' '--with-tidy=/opt/bitnami/common' '--with-mysql=mysqlnd' '--with-mysqli=mysqlnd' '--with-pdo-mysql=mysqlnd' '--with-pdo_sqlite=/bitnami/lampstack-linux-x64/output/sqlite' '--with-sqlite3=/bitnami/lampstack-linux-x64/output/sqlite' '--with-iconv=/opt/bitnami/common' '--with-libxml-dir=/opt/bitnami/common' '--with-xsl=/opt/bitnami/common' '--with-dom=/opt/bitnami/common' '--with-freetype-dir=/opt/bitnami/common' '--with-gmp=/opt/bitnami/common' '--with-gettext' '--enable-intl' '--with-readline=/opt/bitnami/common' '--disable-huge-code-pages'"
#define PHP_ADA_INCLUDE		""
#define PHP_ADA_LFLAGS		""
#define PHP_ADA_LIBS		""
#define PHP_APACHE_INCLUDE	""
#define PHP_APACHE_TARGET	""
#define PHP_FHTTPD_INCLUDE      ""
#define PHP_FHTTPD_LIB          ""
#define PHP_FHTTPD_TARGET       ""
#define PHP_CFLAGS		"$(CFLAGS_CLEAN) "
#define PHP_DBASE_LIB		""
#define PHP_BUILD_DEBUG		""
#define PHP_GDBM_INCLUDE	""
#define PHP_IBASE_INCLUDE	""
#define PHP_IBASE_LFLAGS	""
#define PHP_IBASE_LIBS		""
#define PHP_IFX_INCLUDE		""
#define PHP_IFX_LFLAGS		""
#define PHP_IFX_LIBS		""
#define PHP_INSTALL_IT		"$(mkinstalldirs) '$(INSTALL_ROOT)/opt/bitnami/apache2/modules' &&                 $(mkinstalldirs) '$(INSTALL_ROOT)/opt/bitnami/apache2/conf' &&                  /opt/bitnami/apache2/bin/apxs -S LIBEXECDIR='$(INSTALL_ROOT)/opt/bitnami/apache2/modules'                        -S SYSCONFDIR='$(INSTALL_ROOT)/opt/bitnami/apache2/conf'                        -i -a -n php7 libphp7.la"
#define PHP_IODBC_INCLUDE	""
#define PHP_IODBC_LFLAGS	""
#define PHP_IODBC_LIBS		""
#define PHP_MSQL_INCLUDE	""
#define PHP_MSQL_LFLAGS		""
#define PHP_MSQL_LIBS		""
#define PHP_MYSQL_INCLUDE	"@MYSQL_INCLUDE@"
#define PHP_MYSQL_LIBS		"@MYSQL_LIBS@"
#define PHP_MYSQL_TYPE		"@MYSQL_MODULE_TYPE@"
#define PHP_ODBC_INCLUDE	""
#define PHP_ODBC_LFLAGS		""
#define PHP_ODBC_LIBS		""
#define PHP_ODBC_TYPE		""
#define PHP_OCI8_SHARED_LIBADD 	""
#define PHP_OCI8_DIR			""
#define PHP_OCI8_ORACLE_VERSION		""
#define PHP_ORACLE_SHARED_LIBADD 	"@ORACLE_SHARED_LIBADD@"
#define PHP_ORACLE_DIR				"@ORACLE_DIR@"
#define PHP_ORACLE_VERSION			"@ORACLE_VERSION@"
#define PHP_PGSQL_INCLUDE	""
#define PHP_PGSQL_LFLAGS	""
#define PHP_PGSQL_LIBS		""
#define PHP_PROG_SENDMAIL	"/usr/bin/sendmail"
#define PHP_SOLID_INCLUDE	""
#define PHP_SOLID_LIBS		""
#define PHP_EMPRESS_INCLUDE	""
#define PHP_EMPRESS_LIBS	""
#define PHP_SYBASE_INCLUDE	""
#define PHP_SYBASE_LFLAGS	""
#define PHP_SYBASE_LIBS		""
#define PHP_DBM_TYPE		""
#define PHP_DBM_LIB		""
#define PHP_LDAP_LFLAGS		""
#define PHP_LDAP_INCLUDE	""
#define PHP_LDAP_LIBS		""
#define PHP_BIRDSTEP_INCLUDE     ""
#define PHP_BIRDSTEP_LIBS        ""
#define PEAR_INSTALLDIR         "/opt/bitnami/php/lib/php"
#define PHP_INCLUDE_PATH	".:/opt/bitnami/php/lib/php"
#define PHP_EXTENSION_DIR       "/opt/bitnami/php/lib/php/extensions"
#define PHP_PREFIX              "/opt/bitnami/php"
#define PHP_BINDIR              "/opt/bitnami/php/bin"
#define PHP_SBINDIR             "/opt/bitnami/php/sbin"
#define PHP_MANDIR              "/opt/bitnami/php/php/man"
#define PHP_LIBDIR              "/opt/bitnami/php/lib/php"
#define PHP_DATADIR             "/opt/bitnami/php/share/php"
#define PHP_SYSCONFDIR          "/opt/bitnami/php/etc"
#define PHP_LOCALSTATEDIR       "/opt/bitnami/php/var"
#define PHP_CONFIG_FILE_PATH    "/opt/bitnami/php/lib"
#define PHP_CONFIG_FILE_SCAN_DIR    ""
#define PHP_SHLIB_SUFFIX        "so"
