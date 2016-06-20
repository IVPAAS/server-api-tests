package pages;

import org.openqa.selenium.By;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import platform.constants.PagesHtmlConstants;

/**
 * Created by elad.cohen on 12/1/2015.
 */
public class KalturaFacebookLogoutHelperPage {

    WebDriver driver;
    public static final String FACEBOOK_LOGOUT_URL = "https://www.facebook.com/help/contact/logout?id=260749603972907";

    public KalturaFacebookLogoutHelperPage(WebDriver driver) {
        this.driver = driver;
    }

    public WebElement getLogoutButton(){
        return driver.findElement(By.name("logout"));
    }

    /**
     * validate logout page
     */
    public boolean isValidLogoutPageStructure(){
        if ( ! driver.getTitle().contains("Facebook Help Center") ) {
            System.err.println("Facebook logout page - title does not match condition title was :" + driver.getTitle() );
            return false;
        }
        WebElement logOutButton = getLogoutButton();

        if ( logOutButton == null ||
                !PagesHtmlConstants.TAG_BUTTON.equals(logOutButton.getTagName()) ||
                !"logout".equals(logOutButton.getAttribute(PagesHtmlConstants.ATTRIBUTE_NAME)) ||
                !PagesHtmlConstants.SUBMIT_BUTTON_TYPE.equals(logOutButton.getAttribute(PagesHtmlConstants.ATTRIBUTE_TYPE))
                ) {
            System.err.println("Facebook logout page - submit button did not match condition ");
            return false;
        }
        return true;
    }




}
