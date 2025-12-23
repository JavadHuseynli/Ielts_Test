#!/usr/bin/env python3
import re

# Read the JSON file
with open('json/users.json', 'r', encoding='utf-8') as f:
    content = f.read()

# Fix double commas - any pattern like ", ," or ",\n,"
content = re.sub(r',\s*,', ',', content)

# Write the fixed content
with open('json/users.json', 'w', encoding='utf-8') as f:
    f.write(content)

print("Fixed double commas in JSON file")

# Try to parse it
import json
try:
    with open('json/users.json', 'r', encoding='utf-8') as f:
        data = json.load(f)
    print(f"✓ Valid JSON! Total students: {len(data)}")
except json.JSONDecodeError as e:
    print(f"✗ Still invalid: {e}")
