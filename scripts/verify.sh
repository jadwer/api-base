#!/bin/sh
# Pruebas antes del push: Larastan + pruebas de los modulos tocados (suite
# completa si hay cambios fuera de Modules/). Correr a mano ANTES de `git push`:
#   scripts/verify.sh [remoto]      # por omision origin
# En verde deja la constancia y el pre-push ya no repite las pruebas.
set -e
root="$(git rev-parse --show-toplevel)"
cd "$root"
base="$(sh scripts/push-base.sh "${1:-origin}")"

files="$(git diff --name-only "$base..HEAD")"
if printf '%s\n' "$files" | grep -q '\.php$'; then
    echo "verify: Larastan"
    php -d memory_limit=2G vendor/bin/phpstan analyse --memory-limit=2G --no-progress

    modules="$(printf '%s\n' "$files" | sed -n 's#^Modules/\([^/]*\)/.*#Modules/\1#p' | sort -u | while read -r m; do [ -d "$m/tests" ] && echo "$m"; done)"
    other="$(printf '%s\n' "$files" | grep -E '^(app|config|routes|database|tests)/' || true)"
    if [ -n "$other" ]; then
        echo "verify: cambios fuera de modulos, suite completa en paralelo"
        php artisan test --parallel --processes=6
    elif [ -n "$modules" ]; then
        echo "verify: pruebas de" $modules
        # artisan test --parallel acepta una sola ruta: una corrida por modulo
        for path in $modules tests/Feature/Contracts; do
            php artisan test --parallel --processes=6 "$path" || exit 1
        done
    fi
else
    echo "verify: sin PHP, nada que probar"
fi
bash scripts/verify-stamp.sh write "$base"
