package tests.platform;

import junit.framework.TestCase;
import org.junit.*;
import org.junit.runner.RunWith;
import org.junit.runners.JUnit4;
import org.openqa.selenium.By;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import org.openqa.selenium.chrome.ChromeDriverService;
import org.openqa.selenium.remote.DesiredCapabilities;
import org.openqa.selenium.remote.RemoteWebDriver;
import org.openqa.selenium.support.ui.ExpectedConditions;
import org.openqa.selenium.support.ui.WebDriverWait;
import platform.constants.PathConstants;

import java.io.File;
import java.io.IOException;
import java.util.concurrent.TimeUnit;


@RunWith(JUnit4.class)
public class TestChromeDriver extends TestCase {


    private static ChromeDriverService service;
    private WebDriver driver;

    @BeforeClass
    public static void createAndStartService() throws IOException {
        String chromeDriverExePath = TestChromeDriver.class.getResource(PathConstants.CHROME_WINDOWS_DRIVER_EXE).getFile();
        service = new ChromeDriverService.Builder()
                .usingDriverExecutable(new File(chromeDriverExePath))
                .usingAnyFreePort()
                .build();
        service.start();
    }

    @AfterClass
    public static void createAndStopService() {
        service.stop();
    }

    @Before
    public void createDriver() {
        driver = new RemoteWebDriver(service.getUrl(),
                DesiredCapabilities.chrome());
        driver.manage().timeouts().implicitlyWait(10, TimeUnit.SECONDS);
    }

    @After
    public void quitDriver() {
        driver.quit();
    }

    @Test
    public void test_Google_Search() {
        driver.get("http://www.google.com");
        WebElement searchBox = driver.findElement(By.name("q"));
        searchBox.sendKeys("webdriver");
        searchBox.submit();
        // here we wait for google to finish loading the results
        new WebDriverWait(driver, 3).until(ExpectedConditions.titleContains("webdriver"));
    }
}