import re, hashlib, time, feedparser, requests, mysql.connector
from bs4 import BeautifulSoup
from datetime import datetime

PHONE_RE = re.compile(r'(?:\+?84|0)(?:\d[\s\.-]?){8,10}')
ACC_RE   = re.compile(r'\b\d{9,16}\b')

RSS_FEEDS = [
  # TODO: điền các RSS công khai hợp lệ, VD: "https://example.com/rss"
]

def norm_phone(s):
  d = re.sub(r'\D','', s)
  if d.startswith('0'): d = '84' + d[1:]
  if not d.startswith('84'): d = '84'+d
  return '+'+d

def norm_acc(s):
  return re.sub(r'\D','', s).lstrip('0')

def fetch_text(url):
  try:
    html = requests.get(url, timeout=10, headers={'User-Agent':'Mozilla/5.0'}).text
    soup = BeautifulSoup(html, 'html.parser')
    texts = ' '.join([t.get_text(' ', strip=True) for t in soup.find_all(['p','li','article'])])[:6000]
    title = soup.title.get_text(strip=True) if soup.title else url
    return title, texts
  except Exception:
    return url, ""

def upsert_entity(cur, type_, value_norm):
  cur.execute("SELECT id FROM entity WHERE type=%s AND value_norm=%s LIMIT 1", (type_, value_norm))
  row = cur.fetchone()
  if row: return row[0]
  cur.execute("INSERT INTO entity (type, value_norm) VALUES (%s,%s)", (type_, value_norm))
  return cur.lastrowid

def add_evidence(cur, entity_id, src_url, src_name, title, excerpt, published_at):
  h = hashlib.sha1((excerpt[:500] + src_url).encode('utf-8')).hexdigest()
  cur.execute("SELECT id FROM evidence WHERE entity_id=%s AND content_hash=%s LIMIT 1", (entity_id, h))
  if cur.fetchone(): return
  cur.execute("""INSERT INTO evidence (entity_id, source_url, source_name, title, excerpt, published_at, content_hash)
                 VALUES (%s,%s,%s,%s,%s,%s,%s)""",
              (entity_id, src_url, src_name, title, excerpt[:1000], published_at, h))

def main():
  conn = mysql.connector.connect(
    host="127.0.0.1", user="root", password="", database="checkscam", charset='utf8mb4'
  )
  cur = conn.cursor()

  for feed_url in RSS_FEEDS:
    d = feedparser.parse(feed_url)
    source_name = d.feed.get('title', feed_url)
    for e in d.entries:
      url = e.link
      pub = None
      if 'published_parsed' in e and e.published_parsed:
        pub = datetime(*e.published_parsed[:6])
      title, text = fetch_text(url)
      phones = { norm_phone(m.group()) for m in PHONE_RE.finditer(text) }
      accs   = { norm_acc(m.group()) for m in ACC_RE.finditer(text) }
      if not phones and not accs: 
        continue

      for p in phones:
        ent_id = upsert_entity(cur, 'phone', p)
        add_evidence(cur, ent_id, url, source_name, title, text[:500], pub)
      for a in accs:
        ent_id = upsert_entity(cur, 'bank', a)
        add_evidence(cur, ent_id, url, source_name, title, text[:500], pub)

      conn.commit()
      time.sleep(0.3)

  cur.close()
  conn.close()

if __name__ == "__main__":
  main()
