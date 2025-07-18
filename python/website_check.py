# website_check.py - crawls a site and check for broken links and misspellings
#   python3 -m pip install requests beautifulsoup4 pyspellchecker
import requests
from bs4 import BeautifulSoup
from urllib.parse import urljoin, urlparse
from collections import deque
import csv
import re
import sys
from spellchecker import SpellChecker

# ---------------------
# Config & Setup
# ---------------------
if len(sys.argv) < 2:
    print("Usage: python crawler.py <base_url>")
    sys.exit(1)

BASE_URL = sys.argv[1]
if not BASE_URL.startswith("http"):
    BASE_URL = "https://" + BASE_URL

MAX_PAGES = 200  # Adjust depth limit
visited = set()
broken_links = []
misspellings = []

# Initialize spell checker
spell = SpellChecker()

# Custom whitelist for brand/technical words
WHITELIST = {"precisionspan", "login", "register", "css", "html", "javascript", "utm", "api"}

# Queue for crawling
queue = deque([BASE_URL])
page_count = 0

def is_internal_link(link):
    """Check if link belongs to the same domain"""
    return urlparse(link).netloc == urlparse(BASE_URL).netloc or urlparse(link).netloc == ""

def get_clean_text(html):
    """Extract visible text from HTML, ignoring scripts/styles"""
    soup = BeautifulSoup(html, 'html.parser')
    for script in soup(["script", "style"]):
        script.extract()
    return soup.get_text(separator=' ')

while queue and len(visited) < MAX_PAGES:
    url = queue.popleft()
    if url in visited:
        continue
    visited.add(url)
    page_count += 1

    print(f"[{page_count}] Crawling: {url}")

    try:
        resp = requests.get(url, timeout=10)
        if resp.status_code != 200:
            print(f"   -> Broken link (Status {resp.status_code})")
            broken_links.append([url, resp.status_code])
            continue

        # Extract and clean text
        text = get_clean_text(resp.text)
        text = text.replace("’", "'")  # normalize apostrophes

        # Extract words (including contractions like don't)
        words = re.findall(r"[a-zA-Z]+(?:'[a-zA-Z]+)?", text.lower())

        # Filter out short words and whitelist
        words = [w for w in words if len(w) > 2 and w not in WHITELIST]

        # Spell check
        misspelled = spell.unknown(words)
        if misspelled:
            print(f"   -> Found {len(misspelled)} possible misspellings")
        for word in misspelled:
            misspellings.append([url, word])

        # Extract internal links for crawling
        soup = BeautifulSoup(resp.text, 'html.parser')
        for link in soup.find_all('a', href=True):
            href = urljoin(BASE_URL, link['href'])
            if is_internal_link(href) and href not in visited:
                queue.append(href)

    except Exception as e:
        print(f"   -> Error: {e}")
        broken_links.append([url, str(e)])

# ---------------------
# Save Reports
# ---------------------
with open('website_check_broken_links.csv', 'w', newline='', encoding='utf-8') as f:
    writer = csv.writer(f)
    writer.writerow(['URL', 'Error'])
    writer.writerows(broken_links)

with open('website_check_misspellings.csv', 'w', newline='', encoding='utf-8') as f:
    writer = csv.writer(f)
    writer.writerow(['URL', 'Misspelled Word'])
    writer.writerows(misspellings)

print("\n✅ Crawl complete.")
print(f"Total pages crawled: {page_count}")
print(f"Broken links found: {len(broken_links)} (saved to website_check_broken_links.csv)")
print(f"Misspellings found: {len(misspellings)} (saved to website_check_misspellings.csv)")
