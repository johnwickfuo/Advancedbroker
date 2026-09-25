#!/usr/bin/env python3
import json,sys
try:
    import argostranslate.translate
except Exception as exc:
    sys.stderr.write("Argos Translate is not installed: %s\n" % exc)
    sys.exit(2)

target=sys.argv[1] if len(sys.argv)>1 else "en"
payload=json.load(sys.stdin)
if not isinstance(payload,list):
    payload=[]
out=[]
for item in payload:
    text=str(item)
    try:
        out.append(argostranslate.translate.translate(text,"en",target))
    except Exception:
        out.append(text)
json.dump(out,sys.stdout,ensure_ascii=False)
