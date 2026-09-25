#!/usr/bin/env python3
import json
import sys

try:
    import argostranslate.translate
except Exception as exc:
    sys.stderr.write("Argos Translate is not installed: %s\n" % exc)
    sys.exit(2)

target = sys.argv[1] if len(sys.argv) > 1 else "en"

try:
    installed = argostranslate.translate.get_installed_languages()
    source_language = next((lang for lang in installed if getattr(lang, "code", None) == "en"), None)
    target_language = next((lang for lang in installed if getattr(lang, "code", None) == target), None)

    if source_language is None or target_language is None:
        sys.stderr.write("Required Argos language package is not installed for en -> %s\n" % target)
        sys.exit(3)

    translation = source_language.get_translation(target_language)
    if translation is None:
        sys.stderr.write("No installed Argos translation path for en -> %s\n" % target)
        sys.exit(4)
except Exception as exc:
    sys.stderr.write("Unable to initialize Argos translation for en -> %s: %s\n" % (target, exc))
    sys.exit(5)

try:
    payload = json.load(sys.stdin)
except Exception:
    payload = []

if not isinstance(payload, list):
    payload = []

out = []
for item in payload:
    text = str(item)
    try:
        out.append(translation.translate(text))
    except Exception:
        out.append(text)

json.dump(out, sys.stdout, ensure_ascii=False)
