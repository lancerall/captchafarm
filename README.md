CaptchaFarm
===========

A database-less PHP captcha farm


Usage:
This app can be used to provide a central location for multiple human beings to simultaneously solve captcha challenges via a simple interface, on behalf of a computer. One example use case for this type of application is as part of an automated functional testing solution. If multiple distributed automated functional tests require the user to solve a captcha challenge, then this CaptchaFarm can be used to provide a portal for one or multiple users to solve these captchas and provide responses for the tests to use. This would allow the tests to run without directly prompting a user for input, and would allow for the tests to be run in a distributed environment, e.g. across multiple VMs. Other uses are up to your imagination. 
Please don't use CaptchaFarm for less savory purposes. :)

Instructions:
This captcha farm is relatively simple and comes in 2 parts-- the interface for humans to solve captcha images (captcha-form.php) and a basic API for your application to poll for a result (pollresponses.php).

1. Dump your captcha challenge png images into the /images directory. 

Notes: This can be performed via scp or ftp or any other acceptable method. Keep in mind that images will only be recognized if they are in the prescribed directory, and in the configured format (.png by default). You can change either of these configuration settings in captcha-form.php (by changing $images_dir and $fileSuffix respectively.) Ensure that the web user on your server has the ability to read/write/delete files from this /images directory.

2. Users should visit /captcha-form.php and solve captcha images until there are none left.

Notes: Adding ?purge to the URL will clean up any old files (or unnecessary files) in the images directory. i.e. /captcha-form.php?purge

3. Your application can make requests to pollresponses.php?challenge=abc in order to check for a solution to a specific captcha. 

Notes: In this case, "abc" would be the filename that was uploaded to the images folder, e.g. "images/abc.png". This API either returns a blank response (no solution provided yet) or returns the response text provided by the human captcha solver. It is advisable to have your application call back to pollresponses.php (with a brief wait period) until it receives a text response.


Requirements:
Web server with PHP installed
SCP access or some other method by which to transfer files into a specified /images directory