package pages;

import org.openqa.selenium.By;
import org.openqa.selenium.NoSuchElementException;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import platform.BasePage;

public class FacebookLoginPage extends BasePage{

    public static String FACEBOOK_LOGIN_TITLE = "Log into Facebook";

    public FacebookLoginPage(WebDriver driver) {
        super(driver);
    }

    public boolean isValidPage(){
        return this.driver.getTitle().contains(FACEBOOK_LOGIN_TITLE);
    }

    public void submitCredentials(String email, String password){
        WebElement element = this.driver.findElement(By.id("email"));
        element.sendKeys(email);
        element = this.driver.findElement(By.id("pass"));
        element.sendKeys(password);
        element = this.driver.findElement(By.id("loginbutton"));
        element.submit();
    }

    public boolean isLoggedIn(){
        try {
            this.driver.findElement(By.id("logoutMenu"));
            return true;
        } catch (NoSuchElementException e){
            return false;
        }
    }


}
