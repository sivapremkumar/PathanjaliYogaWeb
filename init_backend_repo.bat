@echo off
cd d:\Indinova\Websites\YogaTrust\yoga-backend
git init
git checkout -b main
git add .
git commit -m "Initial commit of yoga-backend"
git remote add origin https://github.com/sivapremkumar/yoga-backend.git
git push -u origin main > push_log.txt 2>&1
echo Done.
