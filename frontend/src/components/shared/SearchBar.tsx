import { useState, useEffect } from 'react';
import { Search } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface SearchBarProps {
  /**
   * Current search value
   */
  value: string;

  /**
   * Callback when search value changes
   */
  onChange: (value: string) => void;

  /**
   * Placeholder text
   * @default "Cari..."
   */
  placeholder?: string;

  /**
   * Debounce delay in milliseconds
   * Set to 0 to disable debouncing
   * @default 0
   */
  debounce?: number;

  /**
   * Optional callback when user presses Enter
   */
  onSearch?: () => void;

  /**
   * Optional className for the wrapper
   */
  className?: string;

  /**
   * Optional className for the input
   */
  inputClassName?: string;
}

/**
 * SearchBar - Reusable search input with icon
 *
 * Used across 15+ pages (desktop + mobile) for consistent search UX.
 * Supports optional debouncing for API calls.
 *
 * @example
 * ```tsx
 * const [search, setSearch] = useState('');
 *
 * <SearchBar
 *   value={search}
 *   onChange={setSearch}
 *   placeholder="Cari karyawan..."
 *   debounce={300}
 * />
 * ```
 */
export function SearchBar({
  value,
  onChange,
  placeholder = 'Cari...',
  debounce = 0,
  onSearch,
  className,
  inputClassName,
}: SearchBarProps) {
  const [internalValue, setInternalValue] = useState(value);

  // Sync external value changes
  useEffect(() => {
    setInternalValue(value);
  }, [value]);

  // Debounce logic
  useEffect(() => {
    if (debounce === 0) return;

    const timer = setTimeout(() => {
      onChange(internalValue);
    }, debounce);

    return () => clearTimeout(timer);
  }, [internalValue, debounce, onChange]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newValue = e.target.value;
    setInternalValue(newValue);

    // If no debounce, call onChange immediately
    if (debounce === 0) {
      onChange(newValue);
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter' && onSearch) {
      onSearch();
    }
  };

  return (
    <div className={cn('relative', className)}>
      <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
      <Input
        type="search"
        placeholder={placeholder}
        value={internalValue}
        onChange={handleChange}
        onKeyDown={handleKeyDown}
        className={cn('pl-9', inputClassName)}
        aria-label={placeholder}
      />
    </div>
  );
}
