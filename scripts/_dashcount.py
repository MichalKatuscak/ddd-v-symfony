import re,sys
for p in sys.argv[1:]:
    txt=open(p).read()
    parts=txt.split('---',2); body=parts[2] if len(parts)>2 else txt
    body=re.sub(r':::code\{[^}]*\}.*?\n:::','',body,flags=re.S)
    lead=prose=table=0
    for l in body.split('\n'):
        s=l.strip()
        if not s or s.startswith('#'): continue
        if s.startswith('|'):
            table+=len(re.findall(r'\S – ',s)); continue
        n=len(re.findall(r'\S – ',s))
        if not n: continue
        if re.match(r'^[-*\d.]+\s+(\*\*[^*]+\*\*|`[^`]+`)\s+– ',s):
            lead+=1; n-=1
        prose+=n
    print(f"{p}  bulletLead={lead} prose={prose} table={table}")
