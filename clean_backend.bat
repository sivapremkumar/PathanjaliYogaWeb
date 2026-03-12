@echo off
cd d:\Indinova\Websites\YogaTrust\yoga-backend
git rm -r --cached node_modules/
git rm --cached .env
git add .gitignore
git commit -m "Remove node_modules and .env"
git push > push_log.txt 2>&1
echo Output saved to push_log.txt
