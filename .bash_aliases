alias test-all='./run_full_test_suite.sh &'

# Routes inspection
alias routes='php artisan route:list --except-vendor --columns=method,uri,name'
alias routes-api='php artisan route:list --path=api/v1 --except-vendor'
alias routes-finance='php artisan route:list --path=api/v1 | grep -E "invoices|payments|bank-accounts"'
alias routes-accounting='php artisan route:list --path=api/v1 | grep -E "accounts|journals|fiscal"'

# Quick route search
alias route-search='php artisan route:list --except-vendor | grep -i'

# Module specific routes
alias routes-module='php artisan route:list --except-vendor | grep'
