package pages;

import org.openqa.selenium.By;
import org.openqa.selenium.NoSuchElementException;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import platform.BasePage;

/**
 * Created by elad.cohen on 12/2/2015.
 */
public class AccessTokenPage extends BasePage{

    public AccessTokenPage(WebDriver driver){
        super(driver);
    }

    public boolean isValidPage(){
        try {
            WebElement element = this.driver.findElement(By.id("wrap"));
            return element.getText().contains("Access token generated successfully");
        } catch (NoSuchElementException e) {
            return false;
        }

    }


}
