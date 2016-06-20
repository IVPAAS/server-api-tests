This project is targeted to HTML related tests

Link to selenium http://www.seleniumhq.org/
You can get the needed download links from there and information

Requirements
------------
 * You have java JDK & maven installed
    javac -version && echo %JAVA_HOME%
    mvn -version && %M2_HOME%
 * install the browser that you want to test (chrome/firefox/....)
 * copy from the web the driver for that browser and place it under resources/driver

Run steps
---------
After fulfilling all requirements
Open the project in you maven supported IDE
Open the test you want to run and press play

* Note - if you want to make sure that the driver that you expect to use is working run it's test from platform


TODO: 
- get the drivers exe automatically
- support additional OS
- add an abstract function for the BasePage isValidPage
- build a better class structure
- create BaseLoginPage that will be the parent of KalturaLoginPage and FacebookLoginPage
- create a BaseTest (similar to the BasePage)
- in the flow class separate the different phases to tests functions and set order descending 

