package pages;

import org.openqa.selenium.By;
import org.openqa.selenium.NoSuchElementException;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import platform.BasePage;


public class KalturaFacebookLoginPage extends BasePage{

    public static final String KALTURA_LOGIN_TITLE = "Kaltura - Open Source Video Platform";
    public static final String KALTURA_ALTERNATIVE_TITLE = "Kaltura Admin Console";

    public KalturaFacebookLoginPage(WebDriver driver){
        super(driver);
    }

    public boolean validatePage(){
        return KALTURA_LOGIN_TITLE.equals(this.driver.getTitle()) ||
                KALTURA_ALTERNATIVE_TITLE.equals(this.driver.getTitle()) ;
    }

    public void submitCredentials(String email, String password){
        WebElement element = this.driver.findElement(By.name("email"));
        element.sendKeys(email);
        element = this.driver.findElement(By.name("password"));
        element.sendKeys(password);
        element = this.driver.findElement(By.id("login"));
        element.submit();
    }

    public boolean isLoggedIn(){
        try{
            driver.findElement(By.id("Logout"));
            return true;
        } catch (NoSuchElementException nsee){
            return false;
        }
    }

}
