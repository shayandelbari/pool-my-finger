import os
import base64
from datetime import datetime, timezone

from mysql.connector import MySQLConnection
from mysql.connector.abstracts import MySQLConnectionAbstract, MySQLCursorAbstract
from requests import Request, get

class PoolMyFingerDB:
    db : MySQLConnectionAbstract
    cursor : MySQLCursorAbstract

    def __init__(self):
        DB_HOST = os.environ.get("DB_HOST", "127.0.0.1")
        DB_PORT = os.environ.get("DB_HOST", "3306")
        DB_NAME = os.environ.get("DB_NAME", "test_pools_app")
        DB_USER = os.environ.get("DB_USER", "root")
        DB_PASS = os.environ.get("DB_PASS", "")

        db = MySQLConnection(
            host=DB_HOST,
            port=DB_PORT,
            database=DB_NAME,
            user=DB_USER,
            password=DB_PASS
        )

        self.db = db
        self.cursor = db.cursor()

        # Create cache table
        self.cursor.execute(
        "CREATE TABLE IF NOT EXISTS site_cache (" \
            "url VARCHAR(2048) PRIMARY KEY," \
            "content MEDIUMTEXT," \
            "last_scrape DATETIME" \
        ")")

    def check_cache(self, url : str):
        sql = "SELECT url, content FROM site_cache WHERE url = %s"
        val = (url)
        
        self.cursor.execute(sql, val)
        res = self.cursor.fetchone()

        print(res)

    def store_site(self, url : str, content : bytes):
        sql = "INSERT INTO site_cache (url, content, last_scrape) VALUES (%s, %s, %s)"
        val = (url, base64.b64encode(content), datetime.now(timezone.utc))
        self.cursor.execute(sql, val)

