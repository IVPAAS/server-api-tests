package tests.pages;

import org.openqa.selenium.WebDriver;
import org.openqa.selenium.htmlunit.HtmlUnitDriver;
import org.testng.annotations.BeforeTest;
import org.testng.annotations.Test;
import pages.GoogleSearchPage;

import java.util.concurrent.TimeUnit;

public class TestGoogleSearch {

    WebDriver driver;

    @BeforeTest
    public void setup(){

        // TODO change the driver to be a one selected from a configuration file
        driver = new HtmlUnitDriver();
        driver.manage().timeouts().implicitlyWait(10, TimeUnit.SECONDS);
        driver.get(GoogleSearchPage.GOOGLE_ADDRESS);

    }

    /**
     * Test
     */
    @Test(priority=0)
    public void test_Search_Key_Word(){
        GoogleSearchPage gsp = new GoogleSearchPage(this.driver);
        gsp.searchGoogle("facebook logout");
        // Check the title of the page
        System.out.println("Page title is: " + driver.getTitle());
        String result = gsp.getFirstResult();
        System.out.println("Result was : " + result);
        driver.quit();
    }

}
