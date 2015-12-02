package tests.pages;

import org.junit.After;
import org.openqa.selenium.WebDriver;
import org.testng.annotations.BeforeTest;
import org.testng.annotations.Test;
import pages.FacebookLoggedInPage;
import pages.FacebookLoginPage;
import platform.DriverFactory;

import java.io.IOException;
import java.util.concurrent.TimeUnit;

import static junit.framework.Assert.assertTrue;

public class TestFacebookLogin {

    public static final String LOGIN_EXAMPLE_URL = "http://www.facebook.com";

    WebDriver driver;

    @BeforeTest
    public void setup() throws IOException {

        // TODO change the driver to be a one selected from a configuration file
        driver = DriverFactory.getChromeDriver();
        driver.manage().timeouts().implicitlyWait(10, TimeUnit.SECONDS);
        driver.get(LOGIN_EXAMPLE_URL);

    }

    /**
     * Test
     */
    @Test(priority=0)
    public void test_Submit_Credentials(){

        String myEail = "opognua_qinson_1448207843@tfbnw.net";
        String myPass = "kaltura";
        FacebookLoginPage flp = new FacebookLoginPage(this.driver);
        if (! flp.isValidPage() ) {
            System.err.println("Failed to validate page");
            return;
        }
        if (flp.isLoggedIn()){
            FacebookLoggedInPage loggedInPage = new FacebookLoggedInPage(this.driver);
            loggedInPage.logout();
        }

        flp.submitCredentials(myEail, myPass);
        assertTrue(flp.isLoggedIn());
    }

    @After
    public void closeDriver(){
        driver.quit();
    }
}
