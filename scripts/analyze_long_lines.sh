#\!/bin/bash

# Find all relevant Blade template files (excluding icons, basic UI components)
files=$(find . -path "*/resources/views/*" -name "*.blade.php"  < /dev/null |  grep -v -E "(cache|storage/framework|vendor|node_modules|temp|debug|test|components/icons|components/auth)" | sort)

echo "Analyzing Blade files for lines longer than 120 characters..."
echo "================================================================"

for file in $files; do
    # Count lines longer than 120 characters
    long_lines=$(awk 'length($0) > 120 { count++ } END { print count+0 }' "$file")
    
    if [ "$long_lines" -gt 0 ]; then
        # Get file size for context
        total_lines=$(wc -l < "$file")
        
        echo "File: $file"
        echo "Long lines: $long_lines / $total_lines total"
        
        # Show some examples of long lines
        echo "Sample long lines:"
        awk 'length($0) > 120 { print "  Line " NR ": " substr($0, 1, 150) "..." }' "$file" | head -3
        echo "---"
    fi
done
