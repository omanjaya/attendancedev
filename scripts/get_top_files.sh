#\!/bin/bash

# Find main page files (not components) with most long lines
find . -path "*/resources/views/pages/*" -name "*.blade.php"  < /dev/null |  while read file; do
    long_lines=$(awk 'length($0) > 120 { count++ } END { print count+0 }' "$file")
    total_lines=$(wc -l < "$file")
    
    if [ "$long_lines" -gt 10 ]; then
        echo "$long_lines:$total_lines:$file"
    fi
done | sort -nr | head -10
