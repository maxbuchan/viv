GENCCODE_ASSEMBLY_TYPE=-a gcc
SO=so
SOBJ=so
A=a
LIBPREFIX=lib
LIB_EXT_ORDER=.52.1
COMPILE=gcc -DAI_ADDRCONFIG=0 -I/bitnami/lampstack-linux-x64/output/sqlite/include -I/opt/bitnami/common/include -I/opt/bitnami/common/include/sasl -I/opt/bitnami/common/include -D_REENTRANT  -DU_HAVE_ELF_H=1 -DU_HAVE_ATOMIC=0  -DU_ATTRIBUTE_DEPRECATED= -I/opt/bitnami/common/include -fPIC -m64 -std=c99 -Wall -pedantic -Wshadow -Wpointer-arith -Wmissing-prototypes -Wwrite-strings   -c
LIBFLAGS=-I/opt/bitnami/common/include -DPIC -fPIC
GENLIB=gcc -I/opt/bitnami/common/include -fPIC -m64 -std=c99 -Wall -pedantic -Wshadow -Wpointer-arith -Wmissing-prototypes -Wwrite-strings   -L/bitnami/lampstack-linux-x64/output/sqlite/lib -L/opt/bitnami/common/lib  -shared -Wl,-Bsymbolic
LDICUDTFLAGS=-nodefaultlibs -nostdlib
LD_SONAME=-Wl,-soname -Wl,
RPATH_FLAGS=
BIR_LDFLAGS=-Wl,-Bsymbolic
AR=ar
ARFLAGS=r
RANLIB=ranlib
INSTALL_CMD=/usr/bin/install -c
