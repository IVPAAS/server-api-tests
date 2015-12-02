package tests.pages;

import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import org.testng.annotations.BeforeTest;
import org.testng.annotations.Test;
import pages.KalturaFacebookLogoutHelperPage;
import platform.DriverFactory;

import java.io.IOException;

/**
 * Created by elad.cohen on 12/1/2015.
 */
public class TestFacebookLogoutHelper {

    WebDriver driver;

    @BeforeTest
    public void setup() throws IOException {

        // TODO change the driver to be a one selected from a configuration file
        driver = DriverFactory.getChromeDriver();
        driver.get(KalturaFacebookLogoutHelperPage.FACEBOOK_LOGOUT_URL);

    }

    /**
     * Test
     */
    @Test(priority=0)
    public void test_Facebook_Logout(){
        KalturaFacebookLogoutHelperPage flhp = new KalturaFacebookLogoutHelperPage(this.driver);
        if ( flhp.isValidLogoutPageStructure() ) {
            System.out.println("Found valid facebook logout page");
            WebElement logoutButton =  flhp.getLogoutButton();
            logoutButton.submit();
            System.out.println("Successfully logged out of facebook");
        } else {
            System.err.println("Facebook logout page seems invalid ");
        }
        driver.quit();
    }
}
