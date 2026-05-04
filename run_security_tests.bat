@echo off
echo ============================================
echo    CDTA Security Tests Runner
echo ============================================
echo.
echo Running all automated security tests...
echo.

php artisan test --filter Security --ansi

echo.
echo ============================================
echo    Security Tests Complete
echo ============================================
pause
