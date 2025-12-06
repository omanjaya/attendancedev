import { useState } from 'react';
import {
  Calendar,
  Layers,
  UserPlus,
  CalendarRange,
  Download,
  Users,
  Grid3X3,
} from 'lucide-react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { PageHeader } from '@/components/shared';

import { ScheduleListContent } from './tabs/ScheduleListContent';
import { ScheduleBuilderContent } from './tabs/ScheduleBuilderContent';
import { ScheduleAssignContent } from './tabs/ScheduleAssignContent';
import { TeacherScheduleContent } from './tabs/TeacherScheduleContent';
import { TeacherScheduleGridContent } from './tabs/TeacherScheduleGridContent';
import { MonthlyScheduleList } from './monthly/index';

// Desktop version (original implementation)
export function DesktopSchedulesPage() {
  const [activeTab, setActiveTab] = useState('monthly');

  return (
    <div className="space-y-4 p-4 sm:space-y-6 sm:p-6">
      {/* Page Header */}
      <PageHeader
        title="Manajemen Jadwal"
        description="Kelola jadwal kerja, penugasan, dan jadwal bulanan"
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
        <TabsList className="grid w-full grid-cols-6 lg:w-auto lg:inline-flex">
          <TabsTrigger value="monthly" className="gap-2">
            <CalendarRange className="h-4 w-4" />
            <span className="hidden sm:inline">Bulanan</span>
            <span className="sm:hidden">Bulan</span>
          </TabsTrigger>
          <TabsTrigger value="list" className="gap-2">
            <Calendar className="h-4 w-4" />
            <span className="hidden sm:inline">Daftar Jadwal</span>
            <span className="sm:hidden">Jadwal</span>
          </TabsTrigger>
          <TabsTrigger value="builder" className="gap-2">
            <Layers className="h-4 w-4" />
            <span className="hidden sm:inline">Builder</span>
            <span className="sm:hidden">Builder</span>
          </TabsTrigger>
          <TabsTrigger value="grid" className="gap-2">
            <Grid3X3 className="h-4 w-4" />
            <span className="hidden sm:inline">Susun Guru</span>
            <span className="sm:hidden">Grid</span>
          </TabsTrigger>
          <TabsTrigger value="teacher" className="gap-2">
            <Users className="h-4 w-4" />
            <span className="hidden sm:inline">Per Guru</span>
            <span className="sm:hidden">Guru</span>
          </TabsTrigger>
          <TabsTrigger value="assign" className="gap-2">
            <UserPlus className="h-4 w-4" />
            <span className="hidden sm:inline">Penugasan</span>
            <span className="sm:hidden">Tugas</span>
          </TabsTrigger>
        </TabsList>

        <TabsContent value="monthly" className="mt-6">
          <MonthlyScheduleList showHeader={false} />
        </TabsContent>

        <TabsContent value="list" className="mt-6">
          <ScheduleListContent />
        </TabsContent>

        <TabsContent value="builder" className="mt-6">
          <ScheduleBuilderContent />
        </TabsContent>

        <TabsContent value="grid" className="mt-6">
          <TeacherScheduleGridContent />
        </TabsContent>

        <TabsContent value="teacher" className="mt-6">
          <TeacherScheduleContent />
        </TabsContent>

        <TabsContent value="assign" className="mt-6">
          <ScheduleAssignContent />
        </TabsContent>
      </Tabs>
    </div>
  );
}

