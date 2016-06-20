package tests.pages;

import org.junit.After;
import org.openqa.selenium.WebDriver;
import org.testng.annotations.BeforeTest;
import org.testng.annotations.Test;
import pages.KMCPage;
import pages.KalturaFacebookLoginPage;
import platform.DriverFactory;

import java.io.IOException;
import java.util.concurrent.TimeUnit;

import static junit.framework.Assert.assertTrue;

public class TestKalturaLogin {


    public static final String LOGIN_EXAMPLE_URL = "http://centos.kaltura/index.php/extservices/facebookoauth2/provider_id/NDc=/page_id/OTI1MTc3NDQ3NTE3OTM1/permissions/bWFuYWdlX3BhZ2VzLHB1Ymxpc2hfYWN0aW9ucyx1c2VyX3ZpZGVvcw==/re_request_permissions/MQ==";

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

        String myEail = "noam.arad@kaltura.com";
        String myPass = "zubur1";
        KalturaFacebookLoginPage klp = new KalturaFacebookLoginPage(this.driver);
        if (! klp.validatePage() ) {
            System.err.println("Failed to validate page");
            return;
        }
        if (klp.isLoggedIn()){
            KMCPage kPage = new KMCPage(driver);
            kPage.logOut();
        }

        klp.submitCredentials(myEail, myPass);
        assertTrue(klp.isLoggedIn());
    }

    @After
    public void closeDriver(){
        driver.quit();
    }

}
