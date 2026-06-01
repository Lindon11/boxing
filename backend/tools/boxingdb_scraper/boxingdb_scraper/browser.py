from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path
import time


@dataclass
class SeleniumSettings:
    browser: str = "chrome"
    headless: bool = True
    wait_seconds: int = 15
    page_load_timeout: int = 45
    user_agent: str | None = None
    slow_mo: float = 0.0


class SeleniumBrowser:
    def __init__(self, settings: SeleniumSettings):
        self.settings = settings
        self.driver = None

    def __enter__(self) -> "SeleniumBrowser":
        self.driver = self._build_driver()
        self.driver.set_page_load_timeout(self.settings.page_load_timeout)
        return self

    def __exit__(self, exc_type, exc, tb) -> None:
        if self.driver is not None:
            self.driver.quit()

    def fetch(
        self,
        url: str,
        wait_for: str | None = "body",
        scroll: bool = True,
        screenshot_path: Path | None = None,
    ) -> str:
        if self.driver is None:
            raise RuntimeError("SeleniumBrowser must be used as a context manager.")

        from selenium.webdriver.common.by import By
        from selenium.webdriver.support import expected_conditions as EC
        from selenium.webdriver.support.ui import WebDriverWait

        self.driver.get(url)

        if wait_for:
            WebDriverWait(self.driver, self.settings.wait_seconds).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, wait_for))
            )

        if self.settings.slow_mo > 0:
            time.sleep(self.settings.slow_mo)

        if scroll:
            self._scroll_to_bottom()

        if screenshot_path is not None:
            screenshot_path.parent.mkdir(parents=True, exist_ok=True)
            self.driver.save_screenshot(str(screenshot_path))

        return self.driver.page_source

    def _build_driver(self):
        try:
            from selenium import webdriver
        except ImportError as exc:
            raise RuntimeError(
                "Selenium is not installed. Run `python -m pip install -r "
                "backend/tools/boxingdb_scraper/requirements.txt`."
            ) from exc

        if self.settings.browser == "firefox":
            options = webdriver.FirefoxOptions()
            if self.settings.headless:
                options.add_argument("-headless")
            if self.settings.user_agent:
                options.set_preference("general.useragent.override", self.settings.user_agent)
            return webdriver.Firefox(options=options)

        options = webdriver.ChromeOptions()
        if self.settings.headless:
            options.add_argument("--headless=new")
        options.add_argument("--disable-dev-shm-usage")
        options.add_argument("--disable-gpu")
        options.add_argument("--no-sandbox")
        options.add_argument("--window-size=1440,1200")
        options.add_argument("--lang=en-GB")
        if self.settings.user_agent:
            options.add_argument(f"--user-agent={self.settings.user_agent}")

        return webdriver.Chrome(options=options)

    def _scroll_to_bottom(self) -> None:
        if self.driver is None:
            return

        last_height = 0
        for _ in range(12):
            height = self.driver.execute_script("return document.body.scrollHeight")
            if height == last_height:
                break
            self.driver.execute_script("window.scrollTo(0, document.body.scrollHeight)")
            last_height = height
            time.sleep(0.7)
