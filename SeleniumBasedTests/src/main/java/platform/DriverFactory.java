package platform;

import org.openqa.selenium.WebDriver;
import org.openqa.selenium.chrome.ChromeDriverService;
import org.openqa.selenium.htmlunit.HtmlUnitDriver;
import org.openqa.selenium.remote.DesiredCapabilities;
import org.openqa.selenium.remote.RemoteWebDriver;
import platform.constants.PathConstants;
import platform.constants.TimeoutConstants;

import java.io.File;
import java.io.IOException;
import java.util.concurrent.TimeUnit;

/**
 * Created by elad.cohen on 12/1/2015.
 */
public class DriverFactory {

    private static ChromeDriverService chromeService;

    public static WebDriver getChromeDriver() throws IOException {
        if (DriverFactory.chromeService == null ){
            String chromeDriverExePath = DriverFactory.class.getResource(PathConstants.CHROME_WINDOWS_DRIVER_EXE).getFile();
            DriverFactory.chromeService = new ChromeDriverService.Builder()
                    .usingDriverExecutable(new File(chromeDriverExePath))
                    .usingAnyFreePort()
                    .build();
            DriverFactory.chromeService.start();
        }
        WebDriver driver = new RemoteWebDriver(DriverFactory.chromeService.getUrl(),DesiredCapabilities.chrome());
        driver.manage().timeouts().implicitlyWait(TimeoutConstants.TIMEOUT_IMPLICIT_DRIVER_WAIT, TimeUnit.SECONDS);
        return driver;
    }

    /**
     * @return valid html unit driver
     */
    public static WebDriver getHtmlUnitDriver(){
        WebDriver driver = new HtmlUnitDriver();
        driver.manage().timeouts().implicitlyWait(10, TimeUnit.SECONDS);
        return driver;
    }



}
