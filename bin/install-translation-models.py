#!/usr/bin/env python3
import sys
import argostranslate.package

targets=["de","fr","it","es","nl","sv","no","da","ja","ko","zh","hi","ar","pl","tl","pt"]
argostranslate.package.update_package_index()
packages=argostranslate.package.get_available_packages()
installed=[]
missing=[]
for target in targets:
    matches=[p for p in packages if p.from_code=="en" and p.to_code==target]
    if not matches:
        missing.append(target)
        continue
    pkg=matches[0]
    path=pkg.download()
    argostranslate.package.install_from_path(path)
    installed.append(target)
print("Installed:",", ".join(installed) or "none")
if missing:
    print("No direct English model found for:",", ".join(missing))
