package pages;

import org.openqa.selenium.By;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import platform.BasePage;

/**
 * Created by elad.cohen on 12/2/2015.
 */
public class FacebookLoggedInPage extends BasePage{

    public FacebookLoggedInPage(WebDriver driver) {
        super(driver);
    }

    public void logout(){
        WebElement element = this.driver.findElement(By.id("u_k_c"));
        element.submit();
    }
}
