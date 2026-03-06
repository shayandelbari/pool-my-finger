from bs4 import BeautifulSoup
from .utils import *
import requests


class PoolMyFingerScraper:
    # Selectors
    RESULTS_SELECTOR = "div#searchResultList div#spinLoader div.row div h2"

    POOLS_URL = "https://montreal.ca/en/places?mtl_content.lieux.installation.code="

    def __init__(self):
        self.db_handler = PoolMyFingerDB()

    def get_link(self, type : str, page : int):
        return f"{self.POOLS_URL}{type}&page={page}"

    def get_pools(self):
        for key, val in TYPES:
            res = requests.get(self.get_link(key, 1))


if __name__ == "__main__":
    import os

    scraper = PoolMyFingerScraper()
    print(scraper.get_pools())