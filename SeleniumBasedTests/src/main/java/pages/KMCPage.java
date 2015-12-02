package pages;

import org.openqa.selenium.By;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import platform.BasePage;

public class KMCPage extends BasePage{

    public KMCPage(WebDriver driver) {
        super(driver);
    }

    public void logOut(){
        WebElement element = driver.findElement(By.id("Logout"));
        element.submit();
    }
}
