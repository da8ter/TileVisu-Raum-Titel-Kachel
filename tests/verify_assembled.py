#!/usr/bin/env python3
"""Verifiziert, dass das zusammengesetzte Kachel-HTML semantisch dem
Referenzstand entspricht:

1. CSS-Block: byte-identisch (Shared-Suffix wird an Originalposition reinjiziert;
   nur der erlaubte Header-Kommentar des Shared-Files darf hinzukommen).
2. JS: gleiche Menge an Top-Level-Funktionsdeklarationen, jede Funktion
   token-identisch (Kommentare/Whitespace normalisiert). Funktions-
   REIHENFOLGE darf abweichen (Deklarationen werden gehoistet).
3. JS: Nicht-Funktions-Zeilen (Top-Level-Statements) in identischer Reihenfolge.
4. HTML-Skelett (außerhalb von <style>/<script>) identisch.

Aufruf: verify_assembled.py <referenz.html> <assembled.html>
"""
import re
import sys


def split(path):
    lines = open(path).readlines()
    ss = next(i for i, l in enumerate(lines) if l.strip() == '<style>')
    se = next(i for i, l in enumerate(lines) if l.strip() == '</style>')
    cs = next(i for i, l in enumerate(lines) if l.strip() == '<script>')
    ce = max(i for i, l in enumerate(lines) if l.strip() == '</script>')
    css = lines[ss + 1:se]
    js = lines[cs + 1:ce]
    skeleton = lines[:ss + 1] + lines[se:cs + 1] + lines[ce:]
    return css, js, skeleton


def functions_and_rest(js_lines):
    # Kommentare vorab entfernen, damit reine Kommentar-Diffs
    # (z.B. Header des Shared-Files) nicht als Statements zählen
    src = ''.join(js_lines)
    src = re.sub(r'/\*.*?\*/', '', src, flags=re.S)
    src = re.sub(r'//[^\n]*', '', src)
    js_lines = [l + '\n' for l in src.splitlines()]
    funcs, rest, i = {}, [], 0
    while i < len(js_lines):
        m = re.match(r'^\s*function (\w+)\s*\(', js_lines[i])
        if m:
            name, body = m.group(1), [js_lines[i]]
            depth = js_lines[i].count('{') - js_lines[i].count('}')
            j = i + 1
            while j < len(js_lines) and depth > 0:
                body.append(js_lines[j])
                depth += js_lines[j].count('{') - js_lines[j].count('}')
                j += 1
            funcs[name] = ''.join(body)
            i = j
        else:
            rest.append(js_lines[i].strip())
            i += 1
    return funcs, [l for l in rest if l != '']


def tokens(src):
    src = re.sub(r'//[^\n]*', '', src)
    src = re.sub(r'/\*.*?\*/', '', src, flags=re.S)
    return re.sub(r'\s+', ' ', src).strip()


def strip_comments_css(lines):
    src = ''.join(lines)
    src = re.sub(r'/\*.*?\*/', '', src, flags=re.S)
    return [l for l in (x.strip() for x in src.splitlines()) if l]


ref_css, ref_js, ref_skel = split(sys.argv[1])
asm_css, asm_js, asm_skel = split(sys.argv[2])

ok = True

if strip_comments_css(ref_css) != strip_comments_css(asm_css):
    print('FEHLER: CSS weicht ab (jenseits von Kommentaren)')
    ok = False

rf, rr = functions_and_rest(ref_js)
af, ar = functions_and_rest(asm_js)

if set(rf) != set(af):
    print('FEHLER: Funktionsmengen differieren:', set(rf) ^ set(af))
    ok = False
else:
    for name in rf:
        if tokens(rf[name]) != tokens(af[name]):
            print(f'FEHLER: Funktion {name} token-divergent')
            ok = False

if rr != ar:
    import difflib
    print('FEHLER: Top-Level-Statements differieren:')
    for line in list(difflib.unified_diff(rr, ar, n=0))[:20]:
        print('  ', line)
    ok = False

if ref_skel != asm_skel:
    print('FEHLER: HTML-Skelett differiert')
    ok = False

print('VERIFIKATION OK' if ok else 'VERIFIKATION FEHLGESCHLAGEN')
sys.exit(0 if ok else 1)
