package pages;


import org.openqa.selenium.By;
import org.openqa.selenium.NoSuchElementException;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import platform.BasePage;


public class ProceedToFacebookForAuthorizationPage extends BasePage{


    public ProceedToFacebookForAuthorizationPage(WebDriver driver) {
        super(driver);
    }

    public boolean isValidPage(){
        try {
            getProceedToFacebookElement();
            return true;
        } catch (NoSuchElementException e){
            return false;
        }
    }

    public WebElement getProceedToFacebookElement(){
        return this.driver.findElement(By.linkText("Proceed to Facebook for authorization"));
    }

    public void proceedToFacebook(){
        WebElement link = getProceedToFacebookElement();
        link.click();
    }

}
