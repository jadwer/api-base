#!/usr/bin/env bash
# Constancia de verify (2026-10-30, copiada de AtomoPlatform): verify corre sin
# conexion abierta y, al salir en verde, deja la huella de lo que probo; el
# pre-push la compara y, si es lo mismo que se empuja, no repite las pruebas.
# Asi el push dura segundos y GitHub no cierra la conexion SSH a media prueba.
#
#   scripts/verify-stamp.sh write <base>   # lo llama verify.sh al terminar en verde
#   scripts/verify-stamp.sh check <base>   # 0 si la constancia vale para lo que se empuja
#
# La huella junta el commit, la base contra la que se eligio que probar y el
# estado del arbol: solo vale con el arbol limpio y el mismo HEAD.
set -euo pipefail

ROOT="$(git rev-parse --show-toplevel)"
STAMP="$(git rev-parse --git-dir)/lwm-verify-ok"
cd "$ROOT"

fingerprint() {
    local base
    base="$(git rev-parse "$1" 2>/dev/null || echo 0)"
    {
        echo "head $(git rev-parse HEAD)"
        echo "base $base"
        git status --porcelain=v1 --untracked-files=all
        git diff HEAD
    } | sha256sum | cut -d' ' -f1
}

case "${1:-}" in
    write)
        [ -n "$(git status --porcelain --untracked-files=all)" ] && { echo "  (sin constancia: el arbol tiene cambios sin commit)"; exit 0; }
        fingerprint "${2:-origin/HEAD}" >"$STAMP"
        echo "  constancia: $(git rev-parse --short HEAD) verificado; el push no repite las pruebas"
        ;;
    check)
        [ -f "$STAMP" ] || exit 1
        [ -z "$(git status --porcelain --untracked-files=all)" ] || exit 1
        [ "$(cat "$STAMP")" = "$(fingerprint "${2:-origin/HEAD}")" ]
        ;;
    *)
        echo "uso: $0 write|check <base>" >&2
        exit 2
        ;;
esac
