#!/bin/sh
LDFLAGS="-L/opt/bitnami/common/lib $LDFLAGS"
export LDFLAGS
CFLAGS="-I/opt/bitnami/common/include/ImageMagick -I/opt/bitnami/common/include $CFLAGS"
export CFLAGS
CXXFLAGS="-I/opt/bitnami/common/include $CXXFLAGS"
export CXXFLAGS
		    
PKG_CONFIG_PATH="/opt/bitnami/common/lib/pkgconfig"
export PKG_CONFIG_PATH
