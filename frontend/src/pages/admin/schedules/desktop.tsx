import { useState } from 'react';
import {
  Calendar,
  CalendarRange,
  Download,
  FileSpreadsheet,
} from 'lucide-react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { PageHeader } from '@/components/shared';

import { MonthlyScheduleList } from './monthly/index';
import GradeScheduleBuilder from './grade-builder';

// Desktop version (original implementation)
export function DesktopSchedulesPage() {
  const [activeTab, setActiveTab] = useState('monthly');

  return (
    <div className="space-y-4 p-4 sm:space-y-6 sm:p-6">
      {/* Page Header */}
      <PageHeader
        title="Manajemen Jadwal"
        description="Kelola jadwal kerja dan jadwal bulanan"
        icon={Calendar}
        actions={
          <Button variant="outline" size="sm">
            <Download className="mr-2 h-4 w-4" />
            Export
          </Button>
        }
      />

      {/* Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
        <TabsList className="grid w-full grid-cols-2 lg:w-auto lg:inline-flex">
          <TabsTrigger value="monthly" className="gap-2">
            <CalendarRange className="h-4 w-4" />
            <span className="hidden sm:inline">Jadwal Bulanan</span>
            <span className="sm:hidden">Bulanan</span>
          </TabsTrigger>
          <TabsTrigger value="grade" className="gap-2">
            <FileSpreadsheet className="h-4 w-4" />
            <span className="hidden sm:inline">Susun Jadwal Mengajar</span>
            <span className="sm:hidden">Mengajar</span>
          </TabsTrigger>
        </TabsList>

        <TabsContent value="monthly" className="mt-6">
          <MonthlyScheduleList showHeader={false} />
        </TabsContent>

        <TabsContent value="grade" className="mt-6">
          <GradeScheduleBuilder />
        </TabsContent>
      </Tabs>
    </div>
  );
}
