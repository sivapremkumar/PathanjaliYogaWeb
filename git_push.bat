@echo off
git add .
git commit -m "Implement authentication, donate with razorpay, backend API changes"
git push > git_push_log.txt 2>&1
echo Done > git_done.txt
