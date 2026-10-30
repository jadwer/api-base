#!/bin/sh
# Base contra la que verify y pre-push eligen que probar: la rama remota si
# existe; si no, el merge-base con la rama por omision del remoto.
remote="${1:-origin}"
branch="$(git rev-parse --abbrev-ref HEAD)"
base="$remote/$branch"
git rev-parse --verify -q "$base" >/dev/null || base="$(git merge-base HEAD "$remote/HEAD" 2>/dev/null || git rev-list --max-parents=0 HEAD | tail -1)"
echo "$base"
