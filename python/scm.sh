#!/bin/bash
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if command -v python3 >/dev/null 2>&1; then
    PY=python3
else
    PY=python
fi
exec "$PY" "$DIR/scm.py" "$@"
