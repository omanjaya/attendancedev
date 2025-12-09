import { useState, useEffect, useRef } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Loader2, Download, RefreshCw } from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { getServiceLogs } from '@/lib/api/system';

interface LogViewerDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  service: string;
  serviceName: string;
}

export function LogViewerDialog({ open, onOpenChange, service, serviceName }: LogViewerDialogProps) {
  const [lines, setLines] = useState(100);
  const [autoScroll, setAutoScroll] = useState(true);
  const logsEndRef = useRef<HTMLDivElement>(null);

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['service-logs', service, lines],
    queryFn: () => getServiceLogs(service, lines),
    enabled: open,
    refetchInterval: 5000, // Auto-refresh every 5 seconds
  });

  // Auto-scroll to bottom when new logs arrive
  useEffect(() => {
    if (autoScroll && logsEndRef.current) {
      logsEndRef.current.scrollIntoView({ behavior: 'smooth' });
    }
  }, [data, autoScroll]);

  const handleDownload = () => {
    if (!data?.logs) return;

    const blob = new Blob([data.logs], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${service}-logs-${new Date().toISOString()}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  };

  const logLines = data?.logs.split('\n').filter(Boolean) || [];

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-4xl h-[80vh] flex flex-col">
        <DialogHeader>
          <DialogTitle className="flex items-center justify-between">
            <span>Service Logs: {serviceName}</span>
            <Badge variant="outline">{service}</Badge>
          </DialogTitle>
          <DialogDescription>
            Real-time logs from Docker container
          </DialogDescription>
        </DialogHeader>

        {/* Controls */}
        <div className="flex items-center gap-2 py-2 border-b">
          <Select value={lines.toString()} onValueChange={(v) => setLines(parseInt(v))}>
            <SelectTrigger className="w-[130px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="50">Last 50 lines</SelectItem>
              <SelectItem value="100">Last 100 lines</SelectItem>
              <SelectItem value="250">Last 250 lines</SelectItem>
              <SelectItem value="500">Last 500 lines</SelectItem>
              <SelectItem value="1000">Last 1000 lines</SelectItem>
            </SelectContent>
          </Select>

          <Button
            variant="outline"
            size="sm"
            onClick={() => setAutoScroll(!autoScroll)}
            className={autoScroll ? 'border-primary' : ''}
          >
            {autoScroll ? 'Auto-scroll ON' : 'Auto-scroll OFF'}
          </Button>

          <Button
            variant="outline"
            size="sm"
            onClick={() => refetch()}
            disabled={isRefetching}
          >
            {isRefetching ? (
              <Loader2 className="h-4 w-4 animate-spin" />
            ) : (
              <RefreshCw className="h-4 w-4" />
            )}
          </Button>

          <Button variant="outline" size="sm" onClick={handleDownload}>
            <Download className="h-4 w-4 mr-2" />
            Download
          </Button>

          <div className="ml-auto text-sm text-muted-foreground">
            {logLines.length} lines
          </div>
        </div>

        {/* Logs Container */}
        <div className="flex-1 overflow-auto bg-black/90 rounded-lg p-4 font-mono text-xs">
          {isLoading ? (
            <div className="flex items-center justify-center h-full">
              <Loader2 className="h-8 w-8 animate-spin text-white" />
            </div>
          ) : logLines.length === 0 ? (
            <div className="flex items-center justify-center h-full text-gray-400">
              No logs available
            </div>
          ) : (
            <div className="space-y-0.5">
              {logLines.map((line, index) => (
                <LogLine key={index} line={line} index={index} />
              ))}
              <div ref={logsEndRef} />
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}

// Log Line Component with syntax highlighting
function LogLine({ line, index }: { line: string; index: number }) {
  // Simple syntax highlighting
  let color = 'text-gray-300';

  if (line.includes('ERROR') || line.includes('error') || line.includes('Error')) {
    color = 'text-red-400';
  } else if (line.includes('WARNING') || line.includes('warning') || line.includes('WARN')) {
    color = 'text-yellow-400';
  } else if (line.includes('INFO') || line.includes('info')) {
    color = 'text-blue-400';
  } else if (line.includes('SUCCESS') || line.includes('success')) {
    color = 'text-green-400';
  } else if (line.includes('DEBUG') || line.includes('debug')) {
    color = 'text-purple-400';
  }

  return (
    <div className="flex gap-4 hover:bg-white/5 px-2 py-0.5 rounded">
      <span className="text-gray-600 select-none w-12 text-right shrink-0">
        {index + 1}
      </span>
      <span className={color}>{line}</span>
    </div>
  );
}
