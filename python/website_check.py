# website_check.py - crawls a site and check for broken links and misspellings
#   python3 -m pip install requests beautifulsoup4 pyspellchecker
import requests
from bs4 import BeautifulSoup
from urllib.parse import urljoin, urlparse
from collections import deque
import csv
import re
import sys
import time
import signal
from spellchecker import SpellChecker

# ---------------------
# Config & Setup
# ---------------------
if len(sys.argv) < 2:
    print("Usage: python website_check.py <base_url>")
    sys.exit(1)

BASE_URL = sys.argv[1]
if not BASE_URL.startswith("http"):
    BASE_URL = "https://" + BASE_URL

MAX_PAGES = 200  # Adjust depth limit
SLOW_THRESHOLD = 3.0  # Pages slower than 3 seconds
visited = set()
broken_links = []
misspellings = []
slow_pages = []

# Initialize spell checker with better accuracy
spell = SpellChecker()

# Load custom dictionary words
custom_words = set()
try:
    with open('website_check_whitelist.txt', 'r', encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith('#') and not line.startswith('/'):
                custom_words.add(line.lower())
    spell.word_frequency.load_words(custom_words)
    print(f"Loaded {len(custom_words)} custom words into dictionary")
except FileNotFoundError:
    print("Note: website_check_whitelist.txt not found")

def is_misspelled(word):
    """Check if a word is misspelled using pyspellchecker"""
    try:
        # Skip very short words, numbers, or mixed alphanumeric
        if len(word) < 3 or word.isdigit() or not word.isalpha():
            return False
        
        # Skip words with capital letters in middle (likely proper nouns/brands)
        if any(c.isupper() for c in word[1:]):
            return False
            
        # Skip if it's a known word
        if word in spell:
            return False
            
        # Additional heuristics to reduce false positives
        technical_patterns = [
            r'.*ing$', r'.*ed$', r'.*er$', r'.*ly$', r'.*tion$', r'.*sion$',
            r'^pre.*', r'^post.*', r'^anti.*', r'^pro.*', r'^sub.*'
        ]
        
        if any(re.match(pattern, word) for pattern in technical_patterns):
            # Only flag if confidence is very low
            candidates = spell.candidates(word)
            if candidates:
                # If the closest suggestion requires many changes, it's likely a real word
                closest = min(candidates, key=lambda w: spell.edit_distance_2(word, w))
                if spell.edit_distance_2(word, closest) > 2:
                    return False
        
        return word not in spell
        
    except (KeyboardInterrupt, SystemExit):
        raise
    except:
        return False

# Signal handler for graceful shutdown
def signal_handler(signum, frame):
    print("\n\n🛑 Interrupted by user. Saving partial results...")
    save_results()
    print(f"Partial crawl saved. Pages crawled: {page_count}")
    sys.exit(0)

signal.signal(signal.SIGINT, signal_handler)

def save_results():
    """Save all results to CSV files"""
    with open('website_check_broken_links.csv', 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        writer.writerow(['URL', 'Error'])
        writer.writerows(broken_links)

    with open('website_check_misspellings.csv', 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        writer.writerow(['URL', 'Misspelled Word'])
        writer.writerows(misspellings)

    with open('website_check_slow_pages.csv', 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        writer.writerow(['URL', 'Load Time (seconds)'])
        writer.writerows(slow_pages)

# Extract domain words and add to spell checker
domain = urlparse(BASE_URL).netloc.lower()
domain_words = set(re.findall(r'[a-zA-Z]+', domain))
spell.word_frequency.load_words(domain_words)

# Base whitelist for technical/common words
BASE_WHITELIST = {
    "login", "register", "css", "html", "javascript", "utm", "api", "etc", 
    "https", "ssl", "tech", "txt", "sms", "www", "http", "url", "cdn",
    "app", "dev", "com", "org", "net", "edu", "gov", "io", "co", "uk",
    # Common prefixes that appear in compound words
    "multi", "pre", "post", "sub", "super", "inter", "intra", "auto", "semi",
    "anti", "pro", "non", "un", "re", "de", "over", "under", "out", "up"
}

# Load additional whitelist terms and URLs to ignore from file
ignore_urls = set()
try:
    with open('website_check_whitelist.txt', 'r', encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            if line.startswith('/'):
                ignore_urls.add(line)
            else:
                BASE_WHITELIST.add(line.lower())
except FileNotFoundError:
    pass

# Combine base whitelist with domain words
WHITELIST = BASE_WHITELIST | domain_words

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
    text = soup.get_text(separator=' ')
    
    # Remove URLs from text to avoid spell-checking domain names
    text = re.sub(r'https?://[^\s]+', ' ', text)
    
    return text

while queue and len(visited) < MAX_PAGES:
    url = queue.popleft()
    if url in visited:
        continue
    visited.add(url)
    page_count += 1

    print(f"[{page_count}] Crawling: {url}")

    try:
        start_time = time.time()
        resp = requests.get(url, timeout=10)
        load_time = time.time() - start_time
        
        if resp.status_code != 200:
            print(f"   -> Broken link (Status {resp.status_code})")
            broken_links.append([url, resp.status_code])
            continue
        
        # Check for slow pages
        if load_time > SLOW_THRESHOLD:
            print(f"   -> Slow page ({load_time:.2f}s)")
            slow_pages.append([url, f"{load_time:.2f}"])
        elif load_time > 1.0:
            print(f"   -> Load time: {load_time:.2f}s")

        # Extract and clean text
        text = get_clean_text(resp.text)
        text = text.replace("'", "'")  # normalize apostrophes

        # Extract words (including contractions and hyphenated words)
        words = re.findall(r"[a-zA-Z]+(?:[-'][a-zA-Z]+)*", text.lower())

        # Filter out short words and whitelist
        words = [w for w in words if len(w) > 2 and w not in WHITELIST]

        # Spell check with hyphenated word handling
        misspelled = set()
        for word in words:
            if '-' in word:
                # For hyphenated words, check each part separately
                parts = word.split('-')
                for part in parts:
                    if len(part) > 2 and part not in WHITELIST and is_misspelled(part):
                        misspelled.add(word)  # Flag the whole hyphenated word
                        break
            else:
                # Regular word check
                if is_misspelled(word):
                    misspelled.add(word)
        
        if misspelled:
            print(f"   -> Found {len(misspelled)} possible misspellings")
        for word in misspelled:
            misspellings.append([url, word])

        # Extract internal links for crawling
        soup = BeautifulSoup(resp.text, 'html.parser')
        for link in soup.find_all('a', href=True):
            href = link['href']
            
            # Skip non-HTTP URI schemes
            if href.startswith(('mailto:', 'javascript:', 'tel:', 'sms:', 'ftp:', 'file:', 'data:', 'blob:')):
                continue
                
            # Skip URLs in ignore list (check both relative and full URL)
            if href in ignore_urls:
                continue
                
            full_href = urljoin(BASE_URL, href)
            if urlparse(full_href).path in ignore_urls:
                continue
            
            # Skip named anchors (fragments) - they're just references to same page
            if '#' in full_href:
                full_href = full_href.split('#')[0]
            
            # Only add internal links to crawl queue
            if is_internal_link(full_href) and full_href not in visited:
                queue.append(full_href)

    except Exception as e:
        print(f"   -> Error: {e}")
        broken_links.append([url, str(e)])

# ---------------------
# Save Reports
# ---------------------
save_results()

print("\n✅ Crawl complete.")
print(f"Total pages crawled: {page_count}")
print(f"Broken links found: {len(broken_links)} (saved to website_check_broken_links.csv)")
print(f"Misspellings found: {len(misspellings)} (saved to website_check_misspellings.csv)")
print(f"Slow pages found: {len(slow_pages)} (saved to website_check_slow_pages.csv)")
print(f"Domain words ignored: {sorted(domain_words)}")
