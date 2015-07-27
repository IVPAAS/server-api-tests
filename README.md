# server-api-tests
This is an infrastructure that should help you create and run black box tests on the kaltura API while using the kaltura php5 client.

Prerequisites
================
* make sure phpunit is installed on the env you're testing.
* copy config.ini.template to config.ini and change all relevant values

Create new test
===============
* In order to create a new test you need to create a new class that inherits from "PHPUnit_Framework_TestCase"
* In the class each method you define will be a new test.
* The method can adhere to all phpunit conventions such as grouping etc'
* There is no need to create a Kaltura client since a default one is created for you (see notes for further explanation).
* In the test function to test an API function create a test object:
    - $test = $this->createTest("<api-service-name>", "<api-action-name>", array("<param-name" => "param-value",...),array($this,"<name-Of-Validation-Method>));
* Now you can just run the test you created.
    - $test->runTest();
* NOTES: 
    - The validation method should be defined in the same class and doesn't need to return anything, you can use all the asserts and functionality that exists in phpunit
    - By default the client used is with the configured partner and admin secret if you want to run a test with a different client that has different privileges you can create it and send it as a parameter in the "runTest" call.
    - Some API calls require some prerequisites (e.g.: entry-id of a valid entry), in order to support this you can just add the annotation "@pre <prerequisite-name" to the test method function and it will be created and sent as a parameter to the call. (e.g.: "@pre entry" will cause a new entry to be created and a field with the entry_id will be sent along).
    
    
     