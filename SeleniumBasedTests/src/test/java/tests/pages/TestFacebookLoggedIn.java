package tests.pages;

import org.openqa.selenium.WebDriver;
import org.testng.annotations.BeforeTest;
import org.testng.annotations.Test;
import pages.FacebookLoggedInPage;
import platform.DriverFactory;

import java.io.IOException;

/**
 * Created by elad.cohen on 12/2/2015.
 */
public class TestFacebookLoggedIn{

    WebDriver driver;

    @BeforeTest
    public void setup(){

        // TODO change the driver to be a one selected from a configuration file
        try {
            this.driver  = DriverFactory.getChromeDriver();
        } catch (IOException e) {
            e.printStackTrace();
        }

        driver.get("http://www.facebook.com");

    }

    /**
     * Test
     */
    @Test(priority=0)
    public void test_Logout(){
        FacebookLoggedInPage loggedInPage = new FacebookLoggedInPage(this.driver);
        loggedInPage.logout();
    }

}
