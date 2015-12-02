package platform;

import org.openqa.selenium.WebDriver;

abstract public class BasePage {

    protected WebDriver driver;

    public BasePage(WebDriver driver) {
        this.driver = driver;
    }
}

