#!/usr/bin/env bash

#   +---------------------------------------------------------------------------------+
#   | This file is part of mcol                                                 |
#   +---------------------------------------------------------------------------------+
#   | Copyright (c) 2023 Jesse Greathouse (https://github.com/jesse-greathouse/mcol) |
#   +---------------------------------------------------------------------------------+
#   | mcol is free software: you can redistribute it and/or modify              |
#   | it under the terms of the GNU General Public License as published by            |
#   | the Free Software Foundation, either version 3 of the License, or               |
#   | (at your option) any later version.                                             |
#   |                                                                                 |
#   | mcol is distributed in the hope that it will be useful,                   |
#   | but WITHOUT ANY WARRANTY; without even the implied warranty of                  |
#   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                   |
#   | GNU General Public License for more details.                                    |
#   |                                                                                 |
#   | You should have received a copy of the GNU General Public License               |
#   | along with mcol.  If not, see <http://www.gnu.org/licenses/>.             |
#   +---------------------------------------------------------------------------------+
#   | Author: Jesse Greathouse <jesseg@wheelpros.com>                                 |
#   +---------------------------------------------------------------------------------+

# resolve real path to script including symlinks or other hijinks
SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do # resolve $SOURCE until the file is no longer a symlink
  TARGET="$(readlink "$SOURCE")"
  if [[ ${TARGET} == /* ]]; then
    echo "SOURCE '$SOURCE' is an absolute symlink to '$TARGET'"
    SOURCE="$TARGET"
  else
    BIN="$( dirname "$SOURCE" )"
    echo "SOURCE '$SOURCE' is a relative symlink to '$TARGET' (relative to '$BIN')"
    SOURCE="$BIN/$TARGET" # if $SOURCE was a relative symlink, we need to resolve it relative to the path where the symlink file was located
  fi
done
RBIN="$( dirname "$SOURCE" )"
BIN="$( cd -P "$( dirname "$SOURCE" )" && pwd )"
DIR="$( cd -P "$BIN/../" && pwd )"
VAR="$( cd -P "$DIR/var" && pwd )"

rm -rf ${VAR}/cache/opcache/*
