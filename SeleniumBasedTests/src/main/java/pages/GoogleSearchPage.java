package pages;

import org.openqa.selenium.By;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import platform.constants.PagesHtmlConstants;

import java.util.List;


/**
 * Functionality of the google search page
 */
public class GoogleSearchPage {

    WebDriver driver;

    public static final String GOOGLE_ADDRESS = "http://www.google.com";

    public GoogleSearchPage(WebDriver driver) {
        this.driver = driver;
    }

    /**
     * Searches google for the given text
     * @param keywordToSearch
     */
    public void searchGoogle(String keywordToSearch){
        WebElement element = this.driver.findElement(By.name("q"));
        element.sendKeys(keywordToSearch);
        element.submit();
    }

    /**
     * @return the result after using the search google function
     */
    public List<WebElement> getResultList(){
        WebElement element = this.driver.findElement(By.id("search"));
        element = element.findElement(By.id("ires"));
        List<WebElement> results = element.findElements(By.className("g"));
        return results;
    }

    /**
     * @return the first result from the result list functionality
     */
    public String getFirstResult(){
        List<WebElement> results  = getResultList();
        if ( results == null || results.isEmpty()){
            System.err.println("Failed to get results");
            return null;
        }
        WebElement element = results.get(0); // we want the first result
        element = element.findElement(By.tagName(PagesHtmlConstants.TAG_A));
        return element.getAttribute(PagesHtmlConstants.ATTRIBUTE_HREF);
    }

}
