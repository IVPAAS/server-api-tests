package tests.flows;

import org.openqa.selenium.WebDriver;
import org.testng.annotations.AfterTest;
import org.testng.annotations.BeforeTest;
import org.testng.annotations.Test;
import pages.*;
import platform.DriverFactory;

import java.io.IOException;

import static junit.framework.Assert.assertTrue;

public class TestFullFacebookDistributorCreds {

    private static final String LOGIN_EXAMPLE_URL = "http://centos.kaltura/index.php/extservices/facebookoauth2/provider_id/NDc=/page_id/OTI1MTc3NDQ3NTE3OTM1/permissions/bWFuYWdlX3BhZ2VzLHB1Ymxpc2hfYWN0aW9ucyx1c2VyX3ZpZGVvcw==/re_request_permissions/MQ==";
    private static final String EXAMPLE_FACEBOOK_EMAIL = "opognua_qinson_1448207843@tfbnw.net";
    private static final String EXAMPLE_FACEBOOK_PASSWORD = "kaltura";
    private static final String EXAMPLE_KALTURA_EMAIL = "noam.arad@kaltura.com";
    private static final String EXAMPLE_KALTURA_PASSWORD = "zubur1";

    WebDriver driver;

    @BeforeTest
    public void setup() throws IOException {

        // TODO change the driver to be a one selected from a configuration file
        driver = DriverFactory.getChromeDriver();
        driver.get(LOGIN_EXAMPLE_URL);

    }

    /**
     * Test
     */
    @Test(priority=0)
    public void test_Full_Flow(){

        // Page 1 : login into the kaltura login box
        KalturaFacebookLoginPage klp = new KalturaFacebookLoginPage(this.driver);
        if (! klp.validatePage() ) {
            System.err.println("Not a valid kaltura facebook login page");
            return;
        }
        if (klp.isLoggedIn()){
            KMCPage kPage = new KMCPage(this.driver);
            kPage.logOut();
        }

        klp.submitCredentials(EXAMPLE_KALTURA_EMAIL, EXAMPLE_KALTURA_PASSWORD);

        // Page 2 : proceed to facebook
        ProceedToFacebookForAuthorizationPage proceedPage = new ProceedToFacebookForAuthorizationPage(this.driver);
        if (!proceedPage.isValidPage()) {
            System.err.println("Not a valid proceed page");
            return;
        }
        proceedPage.proceedToFacebook();
        // Page 3 : facebook login
        FacebookLoginPage facebookLoginPage = new FacebookLoginPage(this.driver);
        if (!facebookLoginPage.isValidPage()) {
            System.err.println("Not a valid facebook login page");
            return;
        }
        facebookLoginPage.submitCredentials(EXAMPLE_FACEBOOK_EMAIL, EXAMPLE_FACEBOOK_PASSWORD);
        // Page 4 :
        AccessTokenPage accessTokenPage = new AccessTokenPage(this.driver);
        assertTrue(accessTokenPage.isValidPage());

    }

    @AfterTest
    public void closeDriver(){
        this.driver.quit();
    }

}
