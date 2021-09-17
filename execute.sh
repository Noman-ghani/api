sudo su
cd /var/www/html/
git checkout staging
git checkout .
git pull
php artisan cache:clear
exit