import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  Server,
  Activity,
  RefreshCw,
  Play,
  Square,
  FileText,
  AlertCircle,
  CheckCircle2,
  Clock,
  Cpu,
  HardDrive,
  Loader2,
  RotateCcw,
  Pause,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Progress } from '@/components/ui/progress';
import { useNotificationStore } from '@/stores';
import {
  getServicesStatus,
  restartService,
  startService,
  stopService,
  restartAllServices,
  type ServiceStatus,
} from '@/lib/api/system';
import { LogViewerDialog } from './components/log-viewer';
import { cn } from '@/lib/utils';

const iconMap: Record<string, React.ElementType> = {
  server: Server,
  layout: Activity,
  'scan-face': Activity,
  database: HardDrive,
  layers: Activity,
  globe: Activity,
};

export default function ServicesPage() {
  const queryClient = useQueryClient();
  const { success, error: showError } = useNotificationStore();

  const [autoRefresh, setAutoRefresh] = useState(true);
  const [selectedService, setSelectedService] = useState<string | null>(null);
  const [actionDialog, setActionDialog] = useState<{
    open: boolean;
    service: string;
    action: 'restart' | 'start' | 'stop';
    title: string;
    description: string;
  } | null>(null);
  const [logViewerOpen, setLogViewerOpen] = useState(false);

  // Fetch services status
  const { data, isLoading, refetch } = useQuery({
    queryKey: ['services-status'],
    queryFn: getServicesStatus,
    refetchInterval: autoRefresh ? 5000 : false, // Auto-refresh every 5 seconds
    refetchIntervalInBackground: true,
  });

  const services = data?.services || {};
  const systemInfo = data?.system;

  // Mutations
  const restartMutation = useMutation({
    mutationFn: restartService,
    onSuccess: (data) => {
      success('Service Restarted', data.message);
      queryClient.invalidateQueries({ queryKey: ['services-status'] });
    },
    onError: (error: any) => {
      showError('Error', error.response?.data?.message || 'Failed to restart service');
    },
  });

  const startMutation = useMutation({
    mutationFn: startService,
    onSuccess: (data) => {
      success('Service Started', data.message);
      queryClient.invalidateQueries({ queryKey: ['services-status'] });
    },
    onError: (error: any) => {
      showError('Error', error.response?.data?.message || 'Failed to start service');
    },
  });

  const stopMutation = useMutation({
    mutationFn: stopService,
    onSuccess: (data) => {
      success('Service Stopped', data.message);
      queryClient.invalidateQueries({ queryKey: ['services-status'] });
    },
    onError: (error: any) => {
      showError('Error', error.response?.data?.message || 'Failed to stop service');
    },
  });

  const restartAllMutation = useMutation({
    mutationFn: restartAllServices,
    onSuccess: (data) => {
      success('All Services Restarted', data.message);
      setTimeout(() => {
        queryClient.invalidateQueries({ queryKey: ['services-status'] });
      }, 3000);
    },
    onError: (error: any) => {
      showError('Error', error.response?.data?.message || 'Failed to restart all services');
    },
  });

  const handleAction = (service: string, action: 'restart' | 'start' | 'stop') => {
    const serviceData = services[service];
    let title = '';
    let description = '';

    switch (action) {
      case 'restart':
        title = `Restart ${serviceData.name}?`;
        description = 'Service akan di-restart. Proses ini memakan waktu beberapa detik.';
        break;
      case 'start':
        title = `Start ${serviceData.name}?`;
        description = 'Service akan dijalankan kembali.';
        break;
      case 'stop':
        title = `Stop ${serviceData.name}?`;
        description = 'Service akan dihentikan. Aplikasi mungkin tidak berfungsi dengan baik.';
        break;
    }

    setActionDialog({
      open: true,
      service,
      action,
      title,
      description,
    });
  };

  const confirmAction = () => {
    if (!actionDialog) return;

    const { service, action } = actionDialog;

    switch (action) {
      case 'restart':
        restartMutation.mutate(service);
        break;
      case 'start':
        startMutation.mutate(service);
        break;
      case 'stop':
        stopMutation.mutate(service);
        break;
    }

    setActionDialog(null);
  };

  const handleRestartAll = () => {
    setActionDialog({
      open: true,
      service: 'all',
      action: 'restart',
      title: 'Restart Semua Services?',
      description:
        'PERINGATAN: Semua services akan di-restart. Aplikasi akan tidak dapat diakses selama beberapa detik. Yakin ingin melanjutkan?',
    });
  };

  const confirmRestartAll = () => {
    restartAllMutation.mutate();
    setActionDialog(null);
  };

  const handleViewLogs = (service: string) => {
    setSelectedService(service);
    setLogViewerOpen(true);
  };

  // Calculate overall status
  const runningCount = Object.values(services).filter((s) => s.status === 'running').length;
  const stoppedCount = Object.values(services).filter((s) => s.status === 'stopped').length;
  const totalCount = Object.keys(services).length;
  const healthyCount = Object.values(services).filter((s) => s.health === 'healthy').length;

  const allHealthy = healthyCount === totalCount && totalCount > 0;

  const formatUptime = (seconds: number | null) => {
    if (!seconds) return 'N/A';

    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    const parts = [];
    if (days > 0) parts.push(`${days}d`);
    if (hours > 0) parts.push(`${hours}h`);
    if (minutes > 0) parts.push(`${minutes}m`);

    return parts.join(' ') || '< 1m';
  };

  return (
    <div className="p-4 sm:p-6 max-w-[1600px] mx-auto space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-foreground flex items-center gap-2">
            <Server className="h-7 w-7 text-primary" />
            System Services Monitor
          </h1>
          <p className="text-sm text-muted-foreground mt-1">
            Real-time monitoring • Last updated: {data?.timestamp ? new Date(data.timestamp).toLocaleTimeString() : 'N/A'}
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => setAutoRefresh(!autoRefresh)}
            className={cn(autoRefresh && 'border-primary/50 text-primary')}
          >
            {autoRefresh ? (
              <>
                <Activity className="h-4 w-4 mr-2 animate-pulse" />
                Auto-refresh ON
              </>
            ) : (
              <>
                <Pause className="h-4 w-4 mr-2" />
                Auto-refresh OFF
              </>
            )}
          </Button>
          <Button variant="outline" size="sm" onClick={() => refetch()}>
            <RefreshCw className="h-4 w-4 mr-2" />
            Refresh
          </Button>
        </div>
      </div>

      {/* Overall Status */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <Card className={cn('border-2', allHealthy ? 'border-green-500/30 bg-green-50/50 dark:bg-green-950/20' : 'border-yellow-500/30')}>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">System Status</p>
                <p className="text-2xl font-bold mt-1">
                  {allHealthy ? (
                    <span className="text-green-600 dark:text-green-400 flex items-center gap-2">
                      <CheckCircle2 className="h-6 w-6" />
                      All Healthy
                    </span>
                  ) : (
                    <span className="text-yellow-600 dark:text-yellow-400 flex items-center gap-2">
                      <AlertCircle className="h-6 w-6" />
                      Issues Detected
                    </span>
                  )}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Running Services</p>
                <p className="text-2xl font-bold mt-1 flex items-center gap-2">
                  <Activity className="h-6 w-6 text-primary" />
                  {runningCount} / {totalCount}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Stopped Services</p>
                <p className="text-2xl font-bold mt-1 flex items-center gap-2">
                  <Square className="h-6 w-6 text-destructive" />
                  {stoppedCount}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Services List */}
      <div className="space-y-4">
        {isLoading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="h-8 w-8 animate-spin text-primary" />
          </div>
        ) : (
          Object.entries(services).map(([key, service]) => (
            <ServiceCard
              key={key}
              service={service}
              onRestart={() => handleAction(key, 'restart')}
              onStart={() => handleAction(key, 'start')}
              onStop={() => handleAction(key, 'stop')}
              onViewLogs={() => handleViewLogs(key)}
              formatUptime={formatUptime}
            />
          ))
        )}
      </div>

      {/* System Info */}
      {systemInfo && (
        <Card>
          <CardHeader>
            <CardTitle className="text-lg flex items-center gap-2">
              <Activity className="h-5 w-5 text-primary" />
              System Information
            </CardTitle>
          </CardHeader>
          <CardContent className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div>
              <p className="text-muted-foreground">Docker Version</p>
              <p className="font-mono mt-1">{systemInfo.docker_version}</p>
            </div>
            <div>
              <p className="text-muted-foreground">Compose Version</p>
              <p className="font-mono mt-1">{systemInfo.compose_version}</p>
            </div>
            <div>
              <p className="text-muted-foreground">Disk Usage</p>
              <p className="font-mono mt-1">{systemInfo.disk_usage}</p>
            </div>
            <div>
              <p className="text-muted-foreground">Containers</p>
              <p className="font-mono mt-1">
                {systemInfo.running_containers} / {systemInfo.total_containers} running
              </p>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Quick Actions */}
      <Card className="border-destructive/20 bg-destructive/5">
        <CardHeader>
          <CardTitle className="text-lg text-destructive flex items-center gap-2">
            <AlertCircle className="h-5 w-5" />
            Danger Zone
          </CardTitle>
          <CardDescription>Actions that affect all services</CardDescription>
        </CardHeader>
        <CardContent>
          <Button
            variant="destructive"
            onClick={handleRestartAll}
            disabled={restartAllMutation.isPending}
          >
            {restartAllMutation.isPending ? (
              <>
                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                Restarting All...
              </>
            ) : (
              <>
                <RotateCcw className="h-4 w-4 mr-2" />
                Restart All Services
              </>
            )}
          </Button>
        </CardContent>
      </Card>

      {/* Action Confirmation Dialog */}
      <AlertDialog open={actionDialog?.open || false} onOpenChange={() => setActionDialog(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{actionDialog?.title}</AlertDialogTitle>
            <AlertDialogDescription>{actionDialog?.description}</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={actionDialog?.service === 'all' ? confirmRestartAll : confirmAction}
              className={
                actionDialog?.action === 'stop' || actionDialog?.service === 'all'
                  ? 'bg-destructive text-destructive-foreground hover:bg-destructive/90'
                  : ''
              }
            >
              Confirm
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Log Viewer */}
      {selectedService && (
        <LogViewerDialog
          open={logViewerOpen}
          onOpenChange={setLogViewerOpen}
          service={selectedService}
          serviceName={services[selectedService]?.name || ''}
        />
      )}
    </div>
  );
}

// Service Card Component
interface ServiceCardProps {
  service: ServiceStatus;
  onRestart: () => void;
  onStart: () => void;
  onStop: () => void;
  onViewLogs: () => void;
  formatUptime: (seconds: number | null) => string;
}

function ServiceCard({ service, onRestart, onStart, onStop, onViewLogs, formatUptime }: ServiceCardProps) {
  const isRunning = service.status === 'running';
  const Icon = iconMap[service.icon] || Server;

  return (
    <Card className={cn('border-2 transition-all', isRunning ? 'border-green-500/30' : 'border-red-500/30')}>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className={cn('p-2 rounded-lg', isRunning ? 'bg-green-100 dark:bg-green-950' : 'bg-red-100 dark:bg-red-950')}>
              <Icon className={cn('h-6 w-6', isRunning ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400')} />
            </div>
            <div>
              <CardTitle className="text-lg flex items-center gap-2">
                {service.name}
                {isRunning ? (
                  <Badge className="bg-green-500">Running</Badge>
                ) : (
                  <Badge variant="destructive">Stopped</Badge>
                )}
              </CardTitle>
              <p className="text-sm text-muted-foreground mt-0.5">{service.description}</p>
            </div>
          </div>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" onClick={onViewLogs}>
              <FileText className="h-4 w-4 mr-1" />
              Logs
            </Button>
            {isRunning ? (
              <>
                <Button variant="outline" size="sm" onClick={onRestart}>
                  <RotateCcw className="h-4 w-4 mr-1" />
                  Restart
                </Button>
                <Button variant="destructive" size="sm" onClick={onStop}>
                  <Square className="h-4 w-4 mr-1" />
                  Stop
                </Button>
              </>
            ) : (
              <Button variant="default" size="sm" onClick={onStart}>
                <Play className="h-4 w-4 mr-1" />
                Start
              </Button>
            )}
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="space-y-1">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <Clock className="h-4 w-4" />
              Uptime
            </div>
            <p className="font-semibold">{formatUptime(service.uptime)}</p>
          </div>

          {isRunning && service.cpu !== null && (
            <div className="space-y-1">
              <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <Cpu className="h-4 w-4" />
                CPU Usage
              </div>
              <div className="space-y-1">
                <p className="font-semibold">{service.cpu.toFixed(1)}%</p>
                <Progress value={service.cpu} className="h-2" />
              </div>
            </div>
          )}

          {isRunning && service.memory && (
            <div className="space-y-1">
              <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <HardDrive className="h-4 w-4" />
                Memory
              </div>
              <div className="space-y-1">
                <p className="font-semibold text-sm">{service.memory}</p>
                {service.memory_percent && <Progress value={service.memory_percent} className="h-2" />}
              </div>
            </div>
          )}

          <div className="space-y-1">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <RefreshCw className="h-4 w-4" />
              Restarts
            </div>
            <p className="font-semibold">{service.restart_count}</p>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
